# API Documentation

## Base URL
```
http://10.30.252.49/api
```

## Authentication
All authenticated endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer <your-jwt-token>
```

## Response Format
All API responses follow this format:
```json
{
    "success": true|false,
    "message": "Human readable message",
    "data": {},
    "meta": {},
    "errors": []
}
```

## Rate Limiting
- 60 requests per minute per IP
- 3600 requests per hour per IP
- Higher limits available with API keys

## Error Codes
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 429: Rate Limit Exceeded
- 500: Internal Server Error

## Endpoints

### Authentication
#### POST /auth/login
Login with email and password.

**Request:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "access_token": "jwt-token",
        "refresh_token": "refresh-token",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {}
    }
}
```

### News
#### GET /news
Get paginated list of news articles.

**Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 10)
- `category` (string): Filter by category slug
- `search` (string): Search in title/content

**Response:**
```json
{
    "success": true,
    "data": [],
    "meta": {
        "pagination": {}
    }
}
```

More endpoints will be documented as they are implemented...














-- =============================================
-- MODERATION FUNCTIONS AND PROCEDURES
-- =============================================

-- Function to calculate user reputation score
DELIMITER //

CREATE FUNCTION calculate_user_reputation(user_id INT) RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_comments INT DEFAULT 0;
    DECLARE approved_comments INT DEFAULT 0;
    DECLARE rejected_comments INT DEFAULT 0;
    DECLARE flagged_comments INT DEFAULT 0;
    DECLARE base_score DECIMAL(5,2) DEFAULT 100.00;
    DECLARE final_score DECIMAL(5,2);
    
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
        COUNT(CASE WHEN is_flagged = 1 THEN 1 END) as flagged
    INTO total_comments, approved_comments, rejected_comments, flagged_comments
    FROM comments 
    WHERE user_id = user_id AND deleted_at IS NULL;
    
    IF total_comments = 0 THEN
        RETURN base_score;
    END IF;
    
    -- Calculate score based on comment history
    SET final_score = base_score + 
        (approved_comments * 2) - 
        (rejected_comments * 10) - 
        (flagged_comments * 5);
    
    -- Ensure score is within bounds
    IF final_score < 0 THEN
        SET final_score = 0;
    ELSEIF final_score > 200 THEN
        SET final_score = 200;
    END IF;
    
    RETURN final_score;
END//

-- Procedure to update user moderation scores
CREATE PROCEDURE update_user_moderation_scores()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE user_id INT;
    DECLARE user_cursor CURSOR FOR 
        SELECT DISTINCT u.id 
        FROM users u 
        INNER JOIN comments c ON u.id = c.user_id 
        WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN user_cursor;
    
    read_loop: LOOP
        FETCH user_cursor INTO user_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        INSERT INTO user_moderation_scores (
            user_id, 
            total_comments, 
            approved_comments, 
            rejected_comments, 
            flagged_comments,
            reputation_score,
            trust_level,
            last_calculated
        )
        SELECT 
            user_id,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
            COUNT(CASE WHEN is_flagged = 1 THEN 1 END) as flagged,
            calculate_user_reputation(user_id) as reputation,
            CASE 
                WHEN calculate_user_reputation(user_id) >= 150 THEN 'verified'
                WHEN calculate_user_reputation(user_id) >= 120 THEN 'trusted'
                WHEN calculate_user_reputation(user_id) >= 80 THEN 'new'
                ELSE 'untrusted'
            END as trust_level,
            NOW() as last_calculated
        FROM comments 
        WHERE user_id = user_id AND deleted_at IS NULL
        ON DUPLICATE KEY UPDATE
            total_comments = VALUES(total_comments),
            approved_comments = VALUES(approved_comments),
            rejected_comments = VALUES(rejected_comments),
            flagged_comments = VALUES(flagged_comments),
            reputation_score = VALUES(reputation_score),
            trust_level = VALUES(trust_level),
            last_calculated = VALUES(last_calculated);
            
    END LOOP;
    
    CLOSE user_cursor;
END//

DELIMITER ;

-- =============================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =============================================

-- Trigger to update comment counts when status changes
DELIMITER //

CREATE TRIGGER update_comment_counts_after_status_change
AFTER UPDATE ON comments
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        -- Update content comment count
        IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
            IF NEW.content_type = 'news' THEN
                UPDATE news SET comment_count = comment_count + 1 WHERE id = NEW.content_id;
            ELSEIF NEW.content_type = 'event' THEN
                UPDATE events SET comment_count = comment_count + 1 WHERE id = NEW.content_id;
            END IF;
        ELSEIF OLD.status = 'approved' AND NEW.status != 'approved' THEN
            IF NEW.content_type = 'news' THEN
                UPDATE news SET comment_count = GREATEST(comment_count - 1, 0) WHERE id = NEW.content_id;
            ELSEIF NEW.content_type = 'event' THEN
                UPDATE events SET comment_count = GREATEST(comment_count - 1, 0) WHERE id = NEW.content_id;
            END IF;
        END IF;
    END IF;
END//

-- Trigger to add comments to moderation queue if needed
CREATE TRIGGER add_to_moderation_queue_after_insert
AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    IF NEW.status = 'pending' AND NEW.requires_review = TRUE THEN
        INSERT INTO comment_moderation_queue (
            comment_id, 
            priority, 
            complexity_score,
            created_at
        ) VALUES (
            NEW.id,
            CASE 
                WHEN NEW.is_flagged = 1 THEN 'high'
                WHEN NEW.moderation_score >= 0.7 THEN 'high'
                WHEN NEW.moderation_score >= 0.4 THEN 'medium'
                ELSE 'low'
            END,
            NEW.moderation_score,
            NOW()
        );
    END IF;
END//

DELIMITER ;

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- Additional indexes for comment moderation queries
CREATE INDEX idx_comments_moderation_status ON comments(status, created_at);
CREATE INDEX idx_comments_moderation_score ON comments(moderation_score DESC, created_at);
CREATE INDEX idx_comments_flagged_pending ON comments(is_flagged, status, created_at);
CREATE INDEX idx_comments_user_status ON comments(user_id, status, created_at);
CREATE INDEX idx_comments_content_status ON comments(content_type, content_id, status);

-- Indexes for moderation logs
CREATE INDEX idx_moderation_logs_date ON moderation_logs(created_at DESC);
CREATE INDEX idx_moderation_logs_moderator_date ON moderation_logs(moderator_id, created_at DESC);

-- Indexes for comment flags
CREATE INDEX idx_comment_flags_status_date ON comment_flags(status, created_at DESC);
CREATE INDEX idx_comment_flags_type_status ON comment_flags(flag_type, status);

-- =============================================
-- VIEWS FOR COMMON QUERIES
-- =============================================

-- View for moderation dashboard
CREATE VIEW moderation_dashboard_stats AS
SELECT 
    COUNT(*) as total_pending,
    COUNT(CASE WHEN is_flagged = 1 THEN 1 END) as flagged_pending,
    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_pending,
    COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as new_today,
    AVG(moderation_score) as avg_moderation_score
FROM comments 
WHERE status = 'pending' AND deleted_at IS NULL;

-- View for user comment statistics
CREATE VIEW user_comment_stats AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    u.role,
    u.status as user_status,
    COUNT(c.id) as total_comments,
    COUNT(CASE WHEN c.status = 'approved' THEN 1 END) as approved_comments,
    COUNT(CASE WHEN c.status = 'pending' THEN 1 END) as pending_comments,
    COUNT(CASE WHEN c.status = 'rejected' THEN 1 END) as rejected_comments,
    COUNT(CASE WHEN c.is_flagged = 1 THEN 1 END) as flagged_comments,
    AVG(c.moderation_score) as avg_moderation_score,
    MAX(c.created_at) as last_comment_date
FROM users u
LEFT JOIN comments c ON u.id = c.user_id AND c.deleted_at IS NULL
GROUP BY u.id, u.username, u.email, u.role, u.status;