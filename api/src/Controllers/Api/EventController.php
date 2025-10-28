<?php

// api/src/Controllers/Api/EventController.php - Event API Controller

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\User;
use App\Models\Event;
use App\Models\Tag;
use App\Models\Media;
use App\Services\CacheService;
use App\Validators\EventValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;

class EventController extends BaseController
{
    private User $userModel;
    private Event $eventModel;
    private Tag $tagModel;
    private Media $mediaModel;
    private CacheService $cache;
    private EventValidator $validator;
    // private Database $db; // Add explicit database property

    public function __construct(
        Database $database,      // Add Database as first parameter
        Event $eventModel, 
        User $userModel, 
        Tag $tagModel, 
        Media $mediaModel,
        CacheService $cache,
        EventValidator $validator
    ) {

        parent::__construct();
        $this->db = $database;           // Initialize database property
        $this->userModel = $userModel;
        $this->eventModel = $eventModel;
        $this->tagModel = $tagModel;
        $this->mediaModel = $mediaModel;
        $this->cache = $cache;
        $this->validator = $validator;
    }


    public function increaseView(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event || $event['status'] !== 'published') {
            throw new NotFoundException('Event not found'.$eventId);
        }
        
        $result = $this->eventModel->incrementViewCount(
            $event['id'], 
            $request->getUser()['id'] ?? null, 
            $request->getIp()
        );

        return $this->successResponse([], $result ? 'View increased successfully.' : 'View increase failed/already increased.');
    }


    public function index(Request $request): Response
    {
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        $categoryId = $request->getQuery('category');
        $organizerId = $request->getQuery('organizer');
        $featured = $request->getQuery('featured') === 'true';
        $isOnline = $request->getQuery('online');
        $isFree = $request->getQuery('free') === 'true';
        $city = $request->getQuery('city');
        $country = $request->getQuery('country');
        $dateFrom = $request->getQuery('date_from');
        $dateTo = $request->getQuery('date_to');
        
        $filters = [];
        if ($categoryId) $filters['category_id'] = (int)$categoryId;
        if ($organizerId) $filters['organizer_id'] = (int)$organizerId;
        if ($featured) $filters['is_featured'] = true;
        if ($isOnline !== null) $filters['is_online'] = $isOnline === 'true';
        if ($isFree) $filters['is_free'] = true;
        if ($city) $filters['city'] = $city;
        if ($country) $filters['country'] = $country;
        if ($dateFrom) $filters['date_from'] = $dateFrom;
        if ($dateTo) $filters['date_to'] = $dateTo;

        $cacheKey = $this->cache->generateKey('events_list', $page, $perPage, $filters);
        
        $result = $this->cache->remember($cacheKey, function() use ($page, $perPage, $filters) {
            return $this->eventModel->getPublished($page, $perPage, $filters);
        }, 300); // 5 minutes cache

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => $filters
        ]);
    }

    public function show(Request $request, string $identifier): Response
    {
        // Try to find by ID first, then by slug
        if (is_numeric($identifier)) {
            $event = $this->eventModel->find((int)$identifier);
        } else {
            $event = $this->eventModel->findBySlug($identifier);
        }
        
        if (!$event || $event['status'] !== 'published') {
            throw new NotFoundException('Event not found');
        }

        // Increment view count
        $result = $this->eventModel->incrementViewCount(
            $event['id'], 
            $request->getUser()['id'] ?? null, 
            $request->getIp()
        );

        // Get related content
        $tags = $this->tagModel->getTagsByContent('event', $event['id']);
        $media = $this->mediaModel->getContentMedia('event', $event['id']);

        // Check if user liked or is attending this event
        $isLiked = false;
        $isAttending = false;
        $isBooked = false;
        if (isset($request->getUser()['id'])) {
            $isLiked = $this->eventModel->isLikedByUser($event['id'], $request->getUser()['id']);
            $isAttending = $this->eventModel->isAttendingEvent($event['id'], $request->getUser()['id']);
            $isBooked = $this->eventModel->isBookedByUser($event['id'], $request->getUser()['id']);
        }

        $response = array_merge($event, [
            'tags' => $tags,
            'media' => $media,
            'is_liked' => $isLiked,
            'is_bookmarked' => $isBooked,
            'is_attending' => $isAttending
        ]);

        return $this->successResponse($response);
    }

    /**
     * Admin: Get all events with filters (includes all statuses)
     */
    public function adminIndex(Request $request): Response
    {
        // Only admins and contributors can access
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Access denied');
        }
        
        try {
            $page = (int)($request->getQuery('page') ?? 1);
            $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
            $search = $request->getQuery('search');
            $status = $request->getQuery('status');
            $type = $request->getQuery('type');
            $categoryId = $request->getQuery('category');
            $organizerId = $request->getQuery('organizer_id');
            
            // For contributors, only show their own events
            if ($request->getUser()['role'] === 'contributor') {
                $organizerId = $request->getUser()['id'];
            }
            
            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($status) $filters['status'] = $status;
            if ($type) {
                if ($type === 'online') {
                    $filters['is_online'] = true;
                } elseif ($type === 'in-person') {
                    $filters['is_online'] = false;
                }
            }
            if ($categoryId) $filters['category_id'] = (int)$categoryId;
            if ($organizerId) $filters['organizer_id'] = (int)$organizerId;
            
            $result = $this->eventModel->getAdminEvents($page, $perPage, $filters);
            
            // Add tags for each event
            foreach ($result['data'] as &$event) {
                $event['tags'] = $this->tagModel->getTagsByContent('event', $event['id']);
            }
            
            return $this->paginatedResponse($result['data'], $result['pagination'], [
                'filters' => $filters
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load events: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Admin: Get single event with all details
     */
    public function adminShow(Request $request, string $id): Response
    {
        // Only admins and contributors can access
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Access denied');
        }
        
        try {
            $event = $this->eventModel->find((int)$id);
            
            if (!$event) {
                throw new NotFoundException('Event not found');
            }
            
            // Contributors can only view their own events
            if ($request->getUser()['role'] === 'contributor' && $event['organizer_id'] !== $request->getUser()['id']) {
                throw new AuthorizationException('Access denied');
            }
            
            // Get all related data regardless of status
            $tags = $this->tagModel->getTagsByContent('event', $event['id']);
            $media = $this->mediaModel->getContentMedia('event', $event['id']);
            
            // Get organizer details
            $organizerSql = "SELECT id, username, first_name, last_name, email, profile_image 
                            FROM users WHERE id = ?";
            $organizer = $this->db->fetch($organizerSql, [$event['organizer_id']]);
            
            // Get approval details if approved
            $approver = null;
            if ($event['approved_by']) {
                $approverSql = "SELECT id, username, first_name, last_name, email 
                               FROM users WHERE id = ?";
                $approver = $this->db->fetch($approverSql, [$event['approved_by']]);
            }
            
            // Get category details
            $category = null;
            if ($event['category_id']) {
                $categorySql = "SELECT * FROM categories WHERE id = ?";
                $category = $this->db->fetch($categorySql, [$event['category_id']]);
            }
            
            // Get attendee statistics
            $attendeeStatsSql = "SELECT 
                                    COUNT(*) as total_registered,
                                    COUNT(CASE WHEN status = 'attending' THEN 1 END) as confirmed_attending,
                                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
                                 FROM event_attendees 
                                 WHERE event_id = ?";
            $attendeeStats = $this->db->fetch($attendeeStatsSql, [$event['id']]);
            
            // Get moderation history if exists
            $moderationHistory = [];
            if ($request->getUser()['role'] === 'super_admin') {
                $moderationSql = "SELECT mq.*, u.first_name, u.last_name 
                                 FROM moderation_queue mq
                                 LEFT JOIN users u ON mq.reviewed_by = u.id
                                 WHERE mq.content_type = 'event' AND mq.content_id = ?
                                 ORDER BY mq.created_at DESC";
                $moderationHistory = $this->db->fetchAll($moderationSql, [$event['id']]);
            }
            
            $response = array_merge($event, [
                'tags' => $tags,
                'media' => $media,
                'organizer' => $organizer,
                'approver' => $approver,
                'category' => $category,
                'attendee_stats' => $attendeeStats,
                'moderation_history' => $moderationHistory
            ]);
            
            return $this->successResponse($response);
            
        } catch (\Exception $e) {
            if ($e instanceof NotFoundException || $e instanceof AuthorizationException) {
                throw $e;
            }
            return $this->errorResponse('Failed to load event: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Admin: Get events for moderation queue
     */
    public function moderation(Request $request): Response
    {
        // Only super_admin can moderate
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }
        
        try {
            $page = (int)($request->getQuery('page') ?? 1);
            $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
            
            $result = $this->eventModel->getModerationQueue($page, $perPage);
            
            // Add additional data for each event
            foreach ($result['data'] as &$event) {
                $event['tags'] = $this->tagModel->getTagsByContent('event', $event['id']);
                
                // Get flags if any
                $flagsSql = "SELECT cf.*, u.first_name, u.last_name 
                            FROM content_flags cf
                            LEFT JOIN users u ON cf.reporter_id = u.id
                            WHERE cf.content_type = 'event' AND cf.content_id = ?
                            ORDER BY cf.created_at DESC";
                $event['flags'] = $this->db->fetchAll($flagsSql, [$event['id']]);
            }
            
            return $this->paginatedResponse($result['data'], $result['pagination']);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load moderation queue: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Admin: Get event statistics
     */
    public function statistics(Request $request): Response
    {
        // Only admins and contributors can access
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Access denied');
        }
        
        try {
            $organizerId = $request->getQuery('organizer_id');
            
            // For contributors, only show their own statistics
            if ($request->getUser()['role'] === 'contributor') {
                $organizerId = $request->getUser()['id'];
            }
            
            if ($organizerId) {
                // Get statistics for specific organizer
                $sql = "SELECT 
                            COUNT(*) as total_events,
                            COUNT(CASE WHEN status = 'published' THEN 1 END) as published_events,
                            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_events,
                            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_events,
                            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_events,
                            COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_events,
                            COUNT(CASE WHEN is_online = 1 AND status = 'published' THEN 1 END) as online_events,
                            COUNT(CASE WHEN is_free = 1 AND status = 'published' THEN 1 END) as free_events,
                            COUNT(CASE WHEN start_date >= CURDATE() AND status = 'published' THEN 1 END) as upcoming_count,
                            SUM(view_count) as total_views,
                            SUM(like_count) as total_likes,
                            SUM(current_attendees) as total_attendees
                        FROM events 
                        WHERE organizer_id = ? AND deleted_at IS NULL";
                
                $stats = $this->db->fetch($sql, [$organizerId]);
            } else {
                // Get overall statistics (admin only)
                $stats = $this->eventModel->getEventStatistics();
            }
            
            // Get popular locations
            $locations = $this->eventModel->getPopularLocations(5);
            
            // Get recent activity
            $recentActivitySql = "SELECT e.id, e.title, e.status, e.created_at, e.updated_at,
                                         u.first_name, u.last_name
                                  FROM events e
                                  LEFT JOIN users u ON e.organizer_id = u.id
                                  WHERE e.deleted_at IS NULL ";
            
            if ($organizerId) {
                $recentActivitySql .= " AND e.organizer_id = ? ";
            }
            
            $recentActivitySql .= " ORDER BY e.updated_at DESC LIMIT 10";
            
            $recentActivity = $organizerId 
                ? $this->db->fetchAll($recentActivitySql, [$organizerId])
                : $this->db->fetchAll($recentActivitySql);
            
            return $this->successResponse([
                'stats' => $stats,
                'popular_locations' => $locations,
                'recent_activity' => $recentActivity
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load statistics: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Admin: Get analytics data
     */
    public function getAnalytics(Request $request): Response
    {
        // Only admins and contributors can access
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Access denied');
        }
        
        try {
            $startDate = $request->getQuery('start_date') ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $request->getQuery('end_date') ?? date('Y-m-d');
            $organizerId = $request->getQuery('organizer_id');
            
            // For contributors, only show their own analytics
            if ($request->getUser()['role'] === 'contributor') {
                $organizerId = $request->getUser()['id'];
            }
            
            // Base conditions
            $conditions = ['e.deleted_at IS NULL'];
            $params = [];
            
            if ($organizerId) {
                $conditions[] = 'e.organizer_id = ?';
                $params[] = $organizerId;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            
            // Get events created in date range
            $eventsCreatedSql = "SELECT DATE(created_at) as date, COUNT(*) as count
                                FROM events e
                                {$whereClause}
                                AND DATE(created_at) BETWEEN ? AND ?
                                GROUP BY DATE(created_at)
                                ORDER BY date";
            
            $eventsCreated = $this->db->fetchAll(
                $eventsCreatedSql, 
                array_merge($params, [$startDate, $endDate])
            );
            
            // Get views over time
            $viewsSql = "SELECT DATE(cv.view_time) as date, COUNT(*) as views
                         FROM content_views cv
                         INNER JOIN events e ON cv.content_id = e.id
                         WHERE cv.content_type = 'event'
                         AND cv.view_date BETWEEN ? AND ?
                         " . ($organizerId ? "AND e.organizer_id = ?" : "") . "
                         GROUP BY DATE(cv.view_time)
                         ORDER BY date";
            
            $viewsParams = [$startDate, $endDate];
            if ($organizerId) $viewsParams[] = $organizerId;
            
            $viewsOverTime = $this->db->fetchAll($viewsSql, $viewsParams);
            
            // Get attendee registrations over time
            $registrationsSql = "SELECT DATE(ea.created_at) as date, COUNT(*) as registrations
                                FROM event_attendees ea
                                INNER JOIN events e ON ea.event_id = e.id
                                WHERE ea.status = 'attending'
                                AND DATE(ea.created_at) BETWEEN ? AND ?
                                " . ($organizerId ? "AND e.organizer_id = ?" : "") . "
                                GROUP BY DATE(ea.created_at)
                                ORDER BY date";
            
            $registrations = $this->db->fetchAll($registrationsSql, $viewsParams);
            
            // Get top performing events
            $topEventsSql = "SELECT e.id, e.title, e.view_count, e.like_count, 
                                    e.current_attendees, e.max_capacity,
                                    c.name as category_name
                             FROM events e
                             LEFT JOIN categories c ON e.category_id = c.id
                             {$whereClause}
                             AND e.status = 'published'
                             AND e.created_at BETWEEN ? AND ?
                             ORDER BY e.view_count DESC
                             LIMIT 10";
            
            $topEvents = $this->db->fetchAll(
                $topEventsSql,
                array_merge($params, [$startDate, $endDate])
            );
            
            // Get category performance
            $categoryPerfSql = "SELECT c.name as category, 
                                       COUNT(e.id) as event_count,
                                       AVG(e.view_count) as avg_views,
                                       AVG(e.current_attendees) as avg_attendees
                                FROM events e
                                LEFT JOIN categories c ON e.category_id = c.id
                                {$whereClause}
                                AND e.created_at BETWEEN ? AND ?
                                GROUP BY e.category_id, c.name
                                ORDER BY event_count DESC";
            
            $categoryPerformance = $this->db->fetchAll(
                $categoryPerfSql,
                array_merge($params, [$startDate, $endDate])
            );
            
            // Get location analytics
            $locationSql = "SELECT venue_city, venue_country, 
                                   COUNT(*) as event_count,
                                   SUM(current_attendees) as total_attendees
                            FROM events e
                            {$whereClause}
                            AND e.created_at BETWEEN ? AND ?
                            AND venue_city IS NOT NULL
                            GROUP BY venue_city, venue_country
                            ORDER BY event_count DESC
                            LIMIT 10";
            
            $locationAnalytics = $this->db->fetchAll(
                $locationSql,
                array_merge($params, [$startDate, $endDate])
            );
            
            // Calculate summary metrics
            $summaryMetrics = [
                'total_events' => count($topEvents),
                'total_views' => array_sum(array_column($topEvents, 'view_count')),
                'total_attendees' => array_sum(array_column($topEvents, 'current_attendees')),
                'avg_capacity_filled' => $this->calculateAvgCapacityFilled($topEvents)
            ];
            
            return $this->successResponse([
                'summary' => $summaryMetrics,
                'events_created' => $eventsCreated,
                'views_timeline' => $viewsOverTime,
                'registrations_timeline' => $registrations,
                'top_events' => $topEvents,
                'category_performance' => $categoryPerformance,
                'location_analytics' => $locationAnalytics
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load analytics: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Admin: Get calendar events
     */
    public function calendar(Request $request): Response
    {
        // Only admins and contributors can access
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Access denied');
        }
        
        try {
            $start = $request->getQuery('start') ?? date('Y-m-01');
            $end = $request->getQuery('end') ?? date('Y-m-t');
            $organizerId = $request->getQuery('organizer_id');
            
            // For contributors, only show their own events
            if ($request->getUser()['role'] === 'contributor') {
                $organizerId = $request->getUser()['id'];
            }
            
            $sql = "SELECT e.id, e.title, e.slug, e.start_date, e.end_date, 
                           e.start_time, e.end_time, e.status,
                           e.is_online, e.venue_name, e.venue_city, 
                           e.current_attendees, e.max_capacity,
                           c.name as category_name, c.color as category_color
                    FROM events e
                    LEFT JOIN categories c ON e.category_id = c.id
                    WHERE e.deleted_at IS NULL
                    AND ((e.start_date >= ? AND e.start_date <= ?)
                         OR (e.end_date >= ? AND e.end_date <= ?)
                         OR (e.start_date <= ? AND e.end_date >= ?))";
            
            $params = [$start, $end, $start, $end, $start, $end];
            
            if ($organizerId) {
                $sql .= " AND e.organizer_id = ?";
                $params[] = $organizerId;
            }
            
            $sql .= " ORDER BY e.start_date ASC, e.start_time ASC";
            
            $events = $this->db->fetchAll($sql, $params);
            
            return $this->successResponse($events);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load calendar events: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Helper: Calculate average capacity filled
     */
    private function calculateAvgCapacityFilled(array $events): float
    {
        $validEvents = array_filter($events, function($event) {
            return $event['max_capacity'] > 0;
        });
        
        if (empty($validEvents)) {
            return 0.0;
        }
        
        $totalPercentage = 0;
        foreach ($validEvents as $event) {
            $percentage = ($event['current_attendees'] / $event['max_capacity']) * 100;
            $totalPercentage += min($percentage, 100); // Cap at 100%
        }
        
        return round($totalPercentage / count($validEvents), 1);
    }

    public function featured(Request $request): Response
    {
        $limit = min((int)($request->getQuery('limit') ?? 5), 20);
        
        $cacheKey = $this->cache->generateKey('events_featured', $limit);
        
        $featuredEvents = $this->cache->remember($cacheKey, function() use ($limit) {
            return $this->eventModel->getFeatured($limit);
        }, 600); // 10 minutes cache

        return $this->successResponse($featuredEvents);
    }

    public function upcoming(Request $request): Response
    {
        $limit = min((int)($request->getQuery('limit') ?? 10), 50);
        $categoryId = $request->getQuery('category');
        $isOnline = $request->getQuery('online');
        $isFree = $request->getQuery('free') === 'true';
        $city = $request->getQuery('city');
        
        $filters = [];
        if ($categoryId) $filters['category_id'] = (int)$categoryId;
        if ($isOnline !== null) $filters['is_online'] = $isOnline === 'true';
        if ($isFree) $filters['is_free'] = true;
        if ($city) $filters['city'] = $city;

        $cacheKey = $this->cache->generateKey('events_upcoming', $limit, $filters);
        
        $upcomingEvents = $this->cache->remember($cacheKey, function() use ($limit, $filters) {
            return $this->eventModel->getUpcoming($limit, $filters);
        }, 300); // 5 minutes cache

        return $this->successResponse($upcomingEvents);
    }

    public function byCategory(Request $request, string $categorySlug): Response
    {
        // Get category by slug
        $categorySql = "SELECT id, name FROM categories WHERE slug = ? AND is_active = 1 AND deleted_at IS NULL";
        $category = $this->db->fetch($categorySql, [$categorySlug]);
        
        if (!$category) {
            throw new NotFoundException('Category not found');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);

        $result = $this->eventModel->getByCategory($category['id'], $page, $perPage);

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'category' => $category
        ]);
    }

    public function like(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event || $event['status'] !== 'published') {
            throw new NotFoundException('Event not found');
        }

        $isLiked = $this->eventModel->toggleLike($eventId, $request->getUser()['id'], $request->getIp());

        // Log activity
        $action = $isLiked ? 'event_liked' : 'event_unliked';
        $this->logActivity($request->getUser()['id'], $action, 'event', $eventId);

        return $this->successResponse([
            'is_liked' => $isLiked,
            'like_count' => $this->eventModel->find($eventId)['like_count']
        ], $isLiked ? 'Event liked' : 'Event unliked');
    }

    /**
     * Check if user has liked this event
     */
    public function likeStatus(Request $request, string $id): Response
    {
        $user = $request->getUser() ?? null;
        
        if (!$user) {
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_liked' => false,
                    'like_count' => 0
                ]
            ]);
        }
        
        try {
            $isLiked = $this->userModel->isContentLiked($user['id'], 'event', (int)$id);
            $likeCount = $this->userModel->getContentLikeCount('event', (int)$id);
            
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_liked' => $isLiked,
                    'like_count' => $likeCount
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Error checking event like status: " . $e->getMessage());
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to check like status'
            ], 500);
        }
    }

    public function bookmark(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        
        $event = $this->eventModel->find($eventId);
        if (!$event || $event['status'] !== 'published') {
            throw new NotFoundException('Event not found');
        }

        // Check if already bookmarked
        $isBookmarked = $this->eventModel->toggleBookmark($eventId, $request->getUser()['id'], $request->getIp());

        // Log activity
        $action = $isBookmarked ? 'event_bookmarked' : 'event_unbookmarked';
        $this->logActivity($request->getUser()['id'], $action, 'events', $eventId);

        return $this->successResponse([
            'is_bookmarked' => $isBookmarked,
            'bookmark_count' => $this->eventModel->find($eventId)['bookmark_count']
        ], $isBookmarked ? 'Event bookmarked' : 'Event removed from bookmark');
    }

    /**
     * Check if user has bookmarked this event
     */
    public function bookmarkStatus(Request $request, string $id): Response
    {
        $user = $request->getUser() ?? null;
        
        if (!$user) {
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_bookmarked' => false,
                    'bookmark_count' => 0
                ]
            ]);
        }
        
        try {
            
            $isBookmarked = $this->userModel->isContentBookmarked($user['id'], 'event', (int)$id);
            $bookmarkCount = $this->userModel->getContentBookmarkCount('event', (int)$id);
            
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_bookmarked' => $isBookmarked,
                    'bookmark_count' => $bookmarkCount
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Error checking event bookmark status: " . $e->getMessage());
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to check bookmark status'
            ], 500);
        }
    }


    public function attendanceStatus(Request $request, string $id): Response
    {
        $user = $request->getUser() ?? null;
        $eventId = (int)$id;
        
        if (!$user) {
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_attending' => false,
                    'attendance_count' => 0
                ]
            ]);
        }
        
        try {
            
            $isAttending = $this->eventModel->isAttendingEvent($eventId, $user['id']);
            // $attendanceCount = $this->userModel->getEventAttendanceCount($eventId, $user['id']);
            $attendanceCount = $this->eventModel->getAttendanceCount($eventId);
            
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_attending' => $isAttending,
                    'attendance_count' => $attendanceCount
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Error checking event attendance status: " . $e->getMessage());
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to check attendance status'
            ], 500);
        }
    }

    public function attend(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event || $event['status'] !== 'published') {
            throw new NotFoundException('Event not found');
        }

        // Check if event has capacity limits
        if ($event['max_capacity'] && $event['current_attendees'] >= $event['max_capacity']) {
            $isAttending = $this->eventModel->isAttendingEvent($eventId, $request->getUser()['id']);
            if (!$isAttending) {
                return $this->errorResponse('Event is at full capacity');
            }
        }

        // Check registration deadline
        if ($event['registration_deadline'] && 
            new \DateTime($event['registration_deadline']) < new \DateTime()) {
            return $this->errorResponse('Registration deadline has passed');
        }

        $isAttending = $this->eventModel->toggleAttendance($eventId, $request->getUser()['id']);

        // Log activity
        $action = $isAttending ? 'event_attending' : 'event_cancelled_attendance';
        $this->logActivity($request->getUser()['id'], $action, 'event', $eventId);

        // Get updated event data
        $updatedEvent = $this->eventModel->find($eventId);

        return $this->successResponse([
            'is_attending' => $isAttending,
            'current_attendees' => $updatedEvent['current_attendees']
        ], $isAttending ? 'You are now attending this event' : 'Attendance cancelled');
    }

    public function attendees(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event || $event['status'] !== 'published') {
            throw new NotFoundException('Event not found');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);

        $result = $this->eventModel->getEventAttendees($eventId, $page, $perPage);

        return $this->paginatedResponse($result['data'], $result['pagination']);
    }

    public function create(Request $request): Response
    {
        // Only contributors and admins can create events
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Contributor access required');
        }

        $data = $request->getData();
        $data['organizer_id'] = $request->getUser()['id'];

        // Format datetime fields
        if (isset($data['registration_deadline']) && !empty($data['registration_deadline'])) {
            $data['registration_deadline'] = $this->formatDatetime($data['registration_deadline']);
        }
        if (isset($data['max_capacity']) && !empty($data['max_capacity'])) {
            $data['max_capacity'] = (int)$data['max_capacity'];
        }

        $validation = $this->validator->validateEvent($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $event = $this->eventModel->createEvent($data);
        
        if (!$event) {
            return $this->errorResponse('Failed to create event');
        }

        // Handle tags if provided
        if (isset($data['tags'])) {
            $tags = [];
            
            if (is_string($data['tags'])) {
                // Try to decode as JSON first
                $decoded = json_decode($data['tags'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Successfully decoded JSON array
                    foreach ($decoded as $tag) {
                        if (is_array($tag) && isset($tag['name'])) {
                            $tags[] = trim($tag['name']);
                        } elseif (is_string($tag)) {
                            $tags[] = trim($tag);
                        }
                    }
                } else {
                    // Not JSON, treat as comma-separated string
                    $tags = explode(',', $data['tags']);
                    $tags = array_map('trim', $tags);
                }
            } elseif (is_array($data['tags'])) {
                // Already an array
                foreach ($data['tags'] as $tag) {
                    if (is_array($tag) && isset($tag['name'])) {
                        $tags[] = trim($tag['name']);
                    } elseif (is_string($tag)) {
                        $tags[] = trim($tag);
                    }
                }
            }
            
            // Remove empty values
            $tags = array_filter($tags, function($tag) {
                return !empty(trim($tag));
            });
            
            // Remove duplicates
            $tags = array_unique($tags); // This should now show: array(2) { [0]=> string(4) "good" [1]=> string(4) "love" }
            
            if (!empty($tags)) {
                $tags = $this->tagModel->findOrCreateTags($tags);
                $tagIds = array_column($tags, 'id');
                $this->tagModel->syncContentTags('event', $event['id'], $tagIds);
            } else {
                // Clear all tags if empty array/string was provided
                $this->tagModel->syncContentTags('event', $event['id'], []);
            }
        }

        // Handle media attachments if provided
        if (!empty($data['media_ids'])) {
            foreach ($data['media_ids'] as $mediaId) {
                $this->mediaModel->attachToContent($mediaId, 'event', $event['id']);
            }
        }

        // Clear cache
        $this->cache->deletePattern('events_%');

        // Log activity
        $this->logActivity($request->getUser()['id'], 'event_created', 'event', $event['id']);

        return $this->successResponse($event, 'Event created successfully', 201);
    }

    public function update(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $event['organizer_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        $data = $request->getAllData();

        // Format datetime fields
        if (isset($data['registration_deadline']) && !empty($data['registration_deadline'])) {
            $data['registration_deadline'] = $this->formatDatetime($data['registration_deadline']);
        }
        if (isset($data['max_capacity']) && !empty($data['max_capacity'])) {
            $data['max_capacity'] = (int)$data['max_capacity'];
        }
        
        $validation = $this->validator->validateEvent($data, $eventId);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $updatedEvent = $this->eventModel->updateEvent($eventId, $data);
        
        if (!$updatedEvent) {
            return $this->errorResponse('Failed to update event');
        }

        // Handle tags if provided
        if (isset($data['tags'])) {
            $tags = [];
            
            if (is_string($data['tags'])) {
                // Try to decode as JSON first
                $decoded = json_decode($data['tags'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Successfully decoded JSON array
                    foreach ($decoded as $tag) {
                        if (is_array($tag) && isset($tag['name'])) {
                            $tags[] = trim($tag['name']);
                        } elseif (is_string($tag)) {
                            $tags[] = trim($tag);
                        }
                    }
                } else {
                    // Not JSON, treat as comma-separated string
                    $tags = explode(',', $data['tags']);
                    $tags = array_map('trim', $tags);
                }
            } elseif (is_array($data['tags'])) {
                // Already an array
                foreach ($data['tags'] as $tag) {
                    if (is_array($tag) && isset($tag['name'])) {
                        $tags[] = trim($tag['name']);
                    } elseif (is_string($tag)) {
                        $tags[] = trim($tag);
                    }
                }
            }
            
            // Remove empty values
            $tags = array_filter($tags, function($tag) {
                return !empty(trim($tag));
            });
            
            // Remove duplicates
            $tags = array_unique($tags); // This should now show: array(2) { [0]=> string(4) "good" [1]=> string(4) "love" }
            
            if (!empty($tags)) {
                $tags = $this->tagModel->findOrCreateTags($tags);
                $tagIds = array_column($tags, 'id');
                $this->tagModel->syncContentTags('event', $eventId, $tagIds);
            } else {
                // Clear all tags if empty array/string was provided
                $this->tagModel->syncContentTags('event', $eventId, []);
            }
        }

        // Handle media attachments if provided
        if (isset($data['media_ids'])) {
            // Remove existing attachments
            $this->db->execute("DELETE FROM content_media WHERE content_type = 'event' AND content_id = ?", [$eventId]);
            
            // Add new attachments
            foreach ($data['media_ids'] as $mediaId) {
                $this->mediaModel->attachToContent($mediaId, 'event', $eventId);
            }
        }

        // Clear cache
        $this->cache->deletePattern('events_%');

        // Log activity
        $this->logActivity($request->getUser()['id'], 'event_updated', 'event', $eventId);

        return $this->successResponse($updatedEvent, 'Event updated successfully');
    }

    /**
     * Admin: Update event status (for moderation)
     */
    public function updateStatus(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        // Only super admins can moderate
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Super admin access required');
        }

        $data = $request->getData();
        $status = $data['status'] ?? '';
        $moderationNotes = $data['moderation_notes'] ?? '';

        // Validate status
        $validStatuses = ['published', 'rejected', 'pending', 'draft', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return $this->errorResponse('Invalid status provided', [], 400);
        }

        try {
            $this->db->beginTransaction();

            // Update event status
            $updateData = [
                'status' => $status,
                'approved_by' => $request->getUser()['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ];

            $updated = $this->eventModel->update($eventId, $updateData);

            if (!$updated) {
                $this->db->rollback();
                return $this->errorResponse('Failed to update event status');
            }

            // Add moderation entry if notes provided
            if (!empty($moderationNotes)) {
                $moderationSql = "INSERT INTO moderation_queue 
                                (content_type, content_id, author_id, status, reviewer_notes, reviewed_by, reviewed_at, created_at) 
                                VALUES ('event', ?, ?, ?, ?, ?, NOW(), NOW())
                                ON DUPLICATE KEY UPDATE 
                                status = VALUES(status), 
                                reviewer_notes = VALUES(reviewer_notes), 
                                reviewed_by = VALUES(reviewed_by), 
                                reviewed_at = VALUES(reviewed_at)";
                
                $this->db->execute($moderationSql, [
                    $eventId, 
                    $event['organizer_id'], // Add the author_id (organizer_id for events)
                    $status, 
                    $moderationNotes, 
                    $request->getUser()['id']
                ]);
            }

            $this->db->commit();

            // Clear cache
            $this->cache->deletePattern('events_%');

            // Log activity
            $this->logActivity(
                $request->getUser()['id'], 
                'event_status_updated', 
                'event', 
                $eventId,
                ['new_status' => $status, 'notes' => $moderationNotes]
            );

            $message = $status === 'published' ? 'Event approved successfully' : 
                      ($status === 'rejected' ? 'Event rejected successfully' : 'Event status updated successfully');

            return $this->successResponse(['status' => $status], $message);

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error updating event status: " . $e->getMessage());
            return $this->errorResponse('Failed to update event status: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Admin: Bulk update event statuses
     */
    public function bulkUpdateStatus(Request $request): Response
    {
        // Only super admins can moderate
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Super admin access required');
        }

        $data = $request->getData();
        $eventIds = $data['event_ids'] ?? [];
        $status = $data['status'] ?? '';
        $moderationNotes = $data['moderation_notes'] ?? '';

        if (empty($eventIds) || !is_array($eventIds)) {
            return $this->errorResponse('Event IDs are required', [], 400);
        }

        // Validate status
        $validStatuses = ['published', 'rejected', 'pending', 'draft', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return $this->errorResponse('Invalid status provided', [], 400);
        }

        try {
            $this->db->beginTransaction();

            $updatedCount = 0;
            $userId = $request->getUser()['id'];
            $timestamp = date('Y-m-d H:i:s');

            foreach ($eventIds as $eventId) {
                $eventId = (int)$eventId;
                
                // Verify event exists
                $event = $this->eventModel->find($eventId);
                if (!$event) {
                    continue;
                }

                // Update event status
                $updateData = [
                    'status' => $status,
                    'approved_by' => $userId,
                    'approved_at' => $timestamp
                ];

                $updated = $this->eventModel->update($eventId, $updateData);

                if ($updated) {
                    $updatedCount++;

                    // Add moderation entry if notes provided
                    if (!empty($moderationNotes)) {
                        $moderationSql = "INSERT INTO moderation_queue 
                                        (content_type, content_id, author_id, status, reviewer_notes, reviewed_by, reviewed_at, created_at) 
                                        VALUES ('event', ?, ?, ?, ?, ?, ?, NOW())
                                        ON DUPLICATE KEY UPDATE 
                                        status = VALUES(status), 
                                        reviewer_notes = VALUES(reviewer_notes), 
                                        reviewed_by = VALUES(reviewed_by), 
                                        reviewed_at = VALUES(reviewed_at)";
                        
                        $this->db->execute($moderationSql, [
                            $eventId, 
                            $event['organizer_id'], // Add the author_id 
                            $status, 
                            $moderationNotes, 
                            $userId, 
                            $timestamp
                        ]);
                    }

                    // Log activity
                    $this->logActivity($userId, 'event_bulk_status_updated', 'event', $eventId, [
                        'new_status' => $status, 
                        'notes' => $moderationNotes
                    ]);
                }
            }

            $this->db->commit();

            // Clear cache
            $this->cache->deletePattern('events_%');

            $message = $updatedCount > 0 ? 
                "Successfully updated {$updatedCount} event(s)" : 
                "No events were updated";

            return $this->successResponse([
                'updated_count' => $updatedCount,
                'total_requested' => count($eventIds)
            ], $message);

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error bulk updating event statuses: " . $e->getMessage());
            return $this->errorResponse('Failed to bulk update event statuses: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Admin: Flag event for review
     */
    public function flagForReview(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        // Only admins can flag for review
        if (!in_array($request->getUser()['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Access denied');
        }

        $data = $request->getData();
        $reason = $data['reason'] ?? '';
        $notes = $data['notes'] ?? '';

        if (empty($reason)) {
            return $this->errorResponse('Reason for flagging is required', [], 400);
        }

        try {
            // Add to content flags
            $flagSql = "INSERT INTO content_flags 
                       (content_type, content_id, reporter_id, reason, reviewer_notes, status, created_at) 
                       VALUES ('event', ?, ?, ?, ?, 'pending', NOW())";
            
            $flagged = $this->db->execute($flagSql, [
                $eventId, 
                $request->getUser()['id'], 
                $reason, 
                $notes
            ]);

            if ($flagged) {
                // Update event flagged status
                $this->eventModel->update($eventId, ['is_flagged' => 1]);

                // Log activity
                $this->logActivity(
                    $request->getUser()['id'], 
                    'event_flagged', 
                    'event', 
                    $eventId,
                    ['reason' => $reason, 'notes' => $notes]
                );

                return $this->successResponse([], 'Event flagged for review successfully');
            } else {
                return $this->errorResponse('Failed to flag event');
            }

        } catch (\Exception $e) {
            error_log("Error flagging event: " . $e->getMessage());
            return $this->errorResponse('Failed to flag event: ' . $e->getMessage(), [], 500);
        }
    }


    public function delete(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $event['organizer_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        $deleted = $this->eventModel->delete($eventId);
        
        if ($deleted) {
            // Clear cache
            $this->cache->deletePattern('events_%');

            // Log activity
            $this->logActivity($request->getUser()['id'], 'event_deleted', 'event', $eventId);

            return $this->successResponse([], 'Event deleted successfully');
        } else {
            return $this->errorResponse('Failed to delete event');
        }
    }

    public function forceDelete(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        // Only super admins can force delete
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Super admin access required for permanent deletion');
        }

        try {
            // Begin transaction for safe deletion
            $this->db->beginTransaction();
            
            // Delete related records first to maintain referential integrity
            $this->deleteEventRelatedData($eventId);
            
            // Permanently delete the event
            $deleted = $this->eventModel->forceDelete($eventId);
            
            if ($deleted) {
                $this->db->commit();
                
                // Clear cache
                $this->cache->deletePattern('events_%');
                
                // Log activity
                $this->logActivity(
                    $request->getUser()['id'], 
                    'event_force_deleted', 
                    'event', 
                    $eventId,
                    ['title' => $event['title']]
                );
                
                return $this->successResponse([], 'Event permanently deleted');
            } else {
                $this->db->rollback();
                return $this->errorResponse('Failed to delete event');
            }
            
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error force deleting event {$eventId}: " . $e->getMessage());
            return $this->errorResponse('Failed to delete event: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Helper method to delete all related data for an event
     */
    private function deleteEventRelatedData(int $eventId): void
    {
        // Delete event attendees
        $this->db->execute("DELETE FROM event_attendees WHERE event_id = ?", [$eventId]);
        
        // Delete content tags
        $this->db->execute("DELETE FROM content_tags WHERE content_type = 'event' AND content_id = ?", [$eventId]);
        
        // Delete content views
        $this->db->execute("DELETE FROM content_views WHERE content_type = 'event' AND content_id = ?", [$eventId]);
        
        // Delete content likes
        $this->db->execute("DELETE FROM content_likes WHERE content_type = 'event' AND content_id = ?", [$eventId]);
        
        // Delete bookmarks
        $this->db->execute("DELETE FROM bookmarks WHERE content_type = 'event' AND content_id = ?", [$eventId]);
        
        // Delete content media attachments
        $this->db->execute("DELETE FROM content_media WHERE content_type = 'event' AND content_id = ?", [$eventId]);
        
        // Delete content flags
        $this->db->execute("DELETE FROM content_flags WHERE content_type = 'event' AND content_id = ?", [$eventId]);
        
        // Delete from moderation queue
        $this->db->execute("DELETE FROM moderation_queue WHERE content_type = 'event' AND content_id = ?", [$eventId]);
        
        // Delete activity logs related to this event
        $this->db->execute("DELETE FROM activity_logs WHERE target_type = 'event' AND target_id = ?", [$eventId]);
    }

    public function myEvents(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        $status = $request->getQuery('status') ?? 'all';

        $result = $this->eventModel->getByOrganizer($userId, $page, $perPage, $status);

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => ['status' => $status]
        ]);
    }

    public function requestPublish(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        // Check permissions
        if ($event['organizer_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        if ($event['status'] !== 'draft') {
            return $this->errorResponse('Only draft events can be submitted for review');
        }

        $updated = $this->eventModel->update($eventId, ['status' => 'pending']);
        
        if ($updated) {
            // Clear cache
            $this->cache->deletePattern('events_%');

            // Log activity
            $this->logActivity($request->getUser()['id'], 'event_submitted_for_review', 'event', $eventId);

            return $this->successResponse([], 'Event submitted for review');
        } else {
            return $this->errorResponse('Failed to submit event');
        }
    }

    public function analytics(Request $request, string $id): Response
    {
        $eventId = (int)$id;
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $event['organizer_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        $analytics = $this->getEventAnalytics($eventId);

        return $this->successResponse($analytics);
    }

    private function getEventAnalytics(int $eventId): array
    {
        // Views over time (last 30 days)
        $viewsSql = "SELECT DATE(view_time) as date, COUNT(*) as views
                     FROM content_views 
                     WHERE content_type = 'event' AND content_id = ? 
                     AND view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                     GROUP BY DATE(view_time)
                     ORDER BY date DESC";
        
        $viewsOverTime = $this->db->fetchAll($viewsSql, [$eventId]);

        // Registration over time
        $registrationsSql = "SELECT DATE(created_at) as date, COUNT(*) as registrations
                             FROM event_attendees 
                             WHERE event_id = ? AND status = 'attending'
                             GROUP BY DATE(created_at)
                             ORDER BY date DESC
                             LIMIT 30";
        
        $registrationsOverTime = $this->db->fetchAll($registrationsSql, [$eventId]);

        // Attendee statistics
        $attendeeStatsSql = "SELECT 
                                COUNT(*) as total_registered,
                                COUNT(CASE WHEN status = 'attending' THEN 1 END) as confirmed_attending,
                                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
                             FROM event_attendees 
                             WHERE event_id = ?";
        
        $attendeeStats = $this->db->fetch($attendeeStatsSql, [$eventId]);

        return [
            'views_over_time' => $viewsOverTime,
            'registrations_over_time' => $registrationsOverTime,
            'attendee_statistics' => $attendeeStats
        ];
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
    {
        $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $this->db->execute($sql, [
            $userId,
            $action,
            $targetType,
            $targetId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($metadata)
        ]);
    }

    private function formatDatetime($datetime): ?string
    {
        if (empty($datetime)) {
            return null;
        }

        // Handle different datetime formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
            'm/d/Y H:i:s',
            'm-d-Y H:i:s'
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $datetime);
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Try to parse with strtotime as fallback
        $timestamp = strtotime($datetime);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        error_log('Failed to parse datetime: ' . $datetime);
        return null;
    }
}