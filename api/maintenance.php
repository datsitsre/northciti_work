<?php

// maintenance.php - Simple standalone script
// Place this in your project root directory

// Basic database connection
$host = '127.0.0.1';
$dbname = 'northcity_db_2025';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully.\n";
} catch (PDO\Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get action from command line
$action = $argv[1] ?? 'help';

switch ($action) {
    case 'check':
        checkCounts($pdo);
        break;
    case 'repair':
        repairCounts($pdo);
        break;
    case 'help':
    default:
        showHelp();
        break;
}

function checkCounts($pdo) {
    echo "Checking for count discrepancies...\n\n";
    
    $sql = "SELECT 
                n.id,
                n.title,
                n.view_count as stored_views,
                COALESCE(cv.actual_views, 0) as actual_views,
                n.like_count as stored_likes,
                COALESCE(cl.actual_likes, 0) as actual_likes,
                n.bookmark_count as stored_bookmarks,
                COALESCE(cb.actual_bookmarks, 0) as actual_bookmarks,
                n.comment_count as stored_comments,
                COALESCE(cc.actual_comments, 0) as actual_comments
            FROM news n
            LEFT JOIN (
                SELECT content_id, COUNT(*) as actual_views
                FROM content_views 
                WHERE content_type = 'news'
                GROUP BY content_id
            ) cv ON n.id = cv.content_id
            LEFT JOIN (
                SELECT content_id, COUNT(*) as actual_likes
                FROM content_likes 
                WHERE content_type = 'news'
                GROUP BY content_id
            ) cl ON n.id = cl.content_id
            LEFT JOIN (
                SELECT content_id, COUNT(*) as actual_bookmarks
                FROM bookmarks 
                WHERE content_type = 'news'
                GROUP BY content_id
            ) cb ON n.id = cb.content_id
            LEFT JOIN (
                SELECT content_id, COUNT(*) as actual_comments
                FROM comments 
                WHERE content_type = 'news' AND status = 'approved' AND deleted_at IS NULL
                GROUP BY content_id
            ) cc ON n.id = cc.content_id
            WHERE n.deleted_at IS NULL
            AND (
                n.view_count != COALESCE(cv.actual_views, 0) OR
                n.like_count != COALESCE(cl.actual_likes, 0) OR
                n.bookmark_count != COALESCE(cb.actual_bookmarks, 0) OR
                n.comment_count != COALESCE(cc.actual_comments, 0)
            )
            ORDER BY n.id
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $issues = $stmt->fetchAll();
    
    if (empty($issues)) {
        echo "No count discrepancies found!\n";
        return;
    }
    
    echo "Found " . count($issues) . " articles with incorrect counts:\n\n";
    
    foreach ($issues as $issue) {
        echo "ID: {$issue['id']} - " . substr($issue['title'], 0, 50) . "...\n";
        
        if ($issue['stored_views'] != $issue['actual_views']) {
            echo "  Views: stored={$issue['stored_views']}, actual={$issue['actual_views']}\n";
        }
        if ($issue['stored_likes'] != $issue['actual_likes']) {
            echo "  Likes: stored={$issue['stored_likes']}, actual={$issue['actual_likes']}\n";
        }
        if ($issue['stored_bookmarks'] != $issue['actual_bookmarks']) {
            echo "  Bookmarks: stored={$issue['stored_bookmarks']}, actual={$issue['actual_bookmarks']}\n";
        }
        if ($issue['stored_comments'] != $issue['actual_comments']) {
            echo "  Comments: stored={$issue['stored_comments']}, actual={$issue['actual_comments']}\n";
        }
        echo "\n";
    }
    
    echo "Run 'php maintenance.php repair' to fix these issues.\n";
}

function repairCounts($pdo) {
    echo "Repairing all count discrepancies...\n\n";
    
    // Get all news articles
    $newsStmt = $pdo->prepare("SELECT id FROM news WHERE deleted_at IS NULL");
    $newsStmt->execute();
    $newsArticles = $newsStmt->fetchAll();
    
    $repaired = 0;
    $total = count($newsArticles);
    
    foreach ($newsArticles as $article) {
        $newsId = $article['id'];
        
        // Get actual counts
        $viewCount = getActualCount($pdo, 'content_views', $newsId);
        $likeCount = getActualCount($pdo, 'content_likes', $newsId);
        $bookmarkCount = getActualCount($pdo, 'bookmarks', $newsId);
        $commentCount = getActualCommentCount($pdo, $newsId);
        
        // Update the news record
        $updateSql = "UPDATE news 
                      SET view_count = ?, 
                          like_count = ?, 
                          bookmark_count = ?, 
                          comment_count = ?,
                          updated_at = NOW()
                      WHERE id = ?";
        
        $updateStmt = $pdo->prepare($updateSql);
        $success = $updateStmt->execute([$viewCount, $likeCount, $bookmarkCount, $commentCount, $newsId]);
        
        if ($success) {
            $repaired++;
        }
        
        // Show progress
        if ($repaired % 10 == 0 || $repaired == $total) {
            echo "Progress: $repaired/$total articles processed\n";
        }
    }
    
    echo "\nRepair completed!\n";
    echo "Successfully repaired: $repaired articles\n";
    echo "Total processed: $total articles\n";
}

function getActualCount($pdo, $table, $newsId) {
    $sql = "SELECT COUNT(*) as count FROM $table WHERE content_type = 'news' AND content_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$newsId]);
    $result = $stmt->fetch();
    return (int)$result['count'];
}

function getActualCommentCount($pdo, $newsId) {
    $sql = "SELECT COUNT(*) as count FROM comments 
            WHERE content_type = 'news' AND content_id = ? 
            AND status = 'approved' AND deleted_at IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$newsId]);
    $result = $stmt->fetch();
    return (int)$result['count'];
}

function showHelp() {
    echo "\nSimple Count Repair Tool\n";
    echo "========================\n\n";
    echo "Usage: php maintenance.php [action]\n\n";
    echo "Actions:\n";
    echo "  check  - Check for count discrepancies\n";
    echo "  repair - Fix all count discrepancies\n";
    echo "  help   - Show this help\n\n";
    echo "Examples:\n";
    echo "  php maintenance.php check\n";
    echo "  php maintenance.php repair\n\n";
}

?>