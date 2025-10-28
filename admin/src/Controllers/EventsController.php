<?php

// admin/src/Controllers/EventsController.php - Admin Events Controller

declare(strict_types=1);

class EventsController
{
    private $db;
    private $user;
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
        
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
    }

    public function index()
    {
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        // Load the events management view
        LayoutHelper::setPageData(
            'Events Management',
            'Manage event listings',
            [
                ['label' => 'Events Management', 'url' => '/events']
            ]
        );

        LayoutHelper::render('events/index', [
            'title' => 'Events Management',
            'user' => $this->user,
            'has_api_access' => ApiClient::isAuthenticated()
        ]);
    }

    public function create()
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->getCurrentUser();
        
        // Fetch categories via API
        $categoriesResponse = ApiClient::get("/categories");
        $categories = $categoriesResponse && $categoriesResponse['success'] ? $categoriesResponse['data'] : [];
        
        $this->render('events/create', [
            'user' => $user,
            'categories' => $categories,
            'pageTitle' => 'Create New Event',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Events Management', 'url' => '/admin/events'],
                ['title' => 'Create Event', 'url' => '/admin/events/create']
            ]
        ]);
    }

    public function edit(int $id): void
    {
        // Check authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->user;

        try {
            // Fetch event via API
            $response = ApiClient::get("/admin/events/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception('Event not found');
            }
            
            $event = $response['data'];
            
            // Check permissions
            // Contributors can only edit their own events
            if ($user['role'] !== 'super_admin' && $event['organizer_id'] !== $user['id']) {
                throw new \Exception('You do not have permission to edit this event');
            }
            
            // Check if event is editable based on status
            if ($user['role'] !== 'super_admin' && in_array($event['status'], ['published', 'rejected', 'completed', 'cancelled'])) {
                throw new \Exception('This event cannot be edited in its current status');
            }
            
            // Fetch categories for the dropdown
            $categoriesResponse = ApiClient::get("/categories");
            $categories = $categoriesResponse && $categoriesResponse['success'] ? $categoriesResponse['data'] : [];
            
            // Get organizer details
            $organizerResponse = ApiClient::get("/admin/users/{$event['organizer_id']}");
            if ($organizerResponse && $organizerResponse['success']) {
                $event['organizer'] = $organizerResponse['data'];
            } else {
                // Fallback organizer data
                $event['organizer'] = [
                    'id' => $event['organizer_id'],
                    'username' => $event['username'] ?? 'Unknown',
                    'first_name' => $event['first_name'] ?? 'Unknown',
                    'last_name' => $event['last_name'] ?? 'User',
                    'profile_image' => $event['organizer_profile_image'] ?? null
                ];
            }

            // Ensure all required fields exist
            $event['meta_title'] = $event['meta_title'] ?? '';
            $event['meta_description'] = $event['meta_description'] ?? '';
            $event['featured_image'] = $event['featured_image'] ?? '';
            $event['is_featured'] = $event['is_featured'] ?? false;
            $event['is_online'] = $event['is_online'] ?? false;
            $event['is_free'] = $event['is_free'] ?? true;
            $event['registration_required'] = $event['registration_required'] ?? false;
            $event['view_count'] = $event['view_count'] ?? 0;
            $event['like_count'] = $event['like_count'] ?? 0;
            $event['comment_count'] = $event['comment_count'] ?? 0;
            $event['current_attendees'] = $event['current_attendees'] ?? 0;
            $event['max_capacity'] = $event['max_capacity'] ?? null;

            $url = 'events/edit';
            
            // Set page data for layout
            LayoutHelper::setPageData(
                'Edit Event - ' . htmlspecialchars($event['title']),
                'Update event information',
                [
                    ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                    ['label' => 'Events Management', 'url' => Router::url('events')],
                    ['label' => 'Edit Event', 'url' => Router::url($url)]
                ]
            );
            
            // Render the edit view
            LayoutHelper::render($url, [
                'event' => $event,
                'categories' => $categories,
                'user' => $user,
                'canPublish' => $user['role'] === 'super_admin',
                'API_BASE_URL' => API_BASE_URL,
                'UPLOADS_URL' => UPLOADS_URL,
                'ADMIN_APP_URL' => ADMIN_APP_URL
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            error_log("Error editing event {$id}: " . $e->getMessage());
            
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            // Redirect to events list
            header('Location: ' . Router::url('events'));
            exit;
        }
    }

    public function show(int $id): void
    {
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->user;
        
        try {
            // Fetch event via API
            $response = ApiClient::get("/admin/events/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception('Event not found');
            }

            $event = $response['data'];
            
            // Contributors can only view their own events
            if ($user['role'] !== 'super_admin' && $event['organizer_id'] !== $user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Get organizer details
            $organizerResponse = ApiClient::get("/admin/users/{$event['organizer_id']}");
            if ($organizerResponse && $organizerResponse['success']) {
                $event['organizer'] = $organizerResponse['data'];
            } else {
                // Fallback organizer data
                $event['organizer'] = [
                    'id' => $event['organizer_id'],
                    'username' => $event['username'] ?? 'Unknown',
                    'first_name' => $event['first_name'] ?? 'Unknown',
                    'last_name' => $event['last_name'] ?? 'User',
                    'profile_image' => $event['organizer_profile_image'] ?? null
                ];
            }
            
            // Get approver details if approved
            if ($event['approved_by']) {
                $approverResponse = ApiClient::get("/admin/users/{$event['approved_by']}");
                if ($approverResponse && $approverResponse['success']) {
                    $event['approver'] = $approverResponse['data'];
                }
            }

            // Get tags for the event
            if (!empty($event['tags'])) {
                // Tags are already included in the event response
            } else {
                $event['tags'] = [];
            }
            
            // Get category details
            if ($event['category_id']) {
                $categoryResponse = ApiClient::get("/categories/{$event['category_id']}");
                if ($categoryResponse && $categoryResponse['success']) {
                    $category = $categoryResponse['data'];
                    $event['category_name'] = $category['name'];
                    $event['category_color'] = $category['color'] ?? '#6B7280';
                }
            } else {
                $event['category_name'] = 'Uncategorized';
                $event['category_color'] = '#6B7280';
            }
            
            // Get attendees count
            if (!isset($event['current_attendees'])) {
                $attendeesResponse = ApiClient::get("/admin/events/{$id}/attendees?per_page=1");
                if ($attendeesResponse && $attendeesResponse['success']) {
                    $event['current_attendees'] = $attendeesResponse['meta']['pagination']['total'] ?? 0;
                }
            }
            
            // Ensure all count fields exist
            $event['view_count'] = $event['view_count'] ?? 0;
            $event['like_count'] = $event['like_count'] ?? 0;
            $event['comment_count'] = $event['comment_count'] ?? 0;
            $event['share_count'] = $event['share_count'] ?? 0;
            $event['current_attendees'] = $event['current_attendees'] ?? 0;
            
            // Format dates for display
            $event['formatted_created_at'] = $this->formatDate($event['created_at']);
            $event['formatted_start_date'] = $this->formatDate($event['start_date']);
            $event['formatted_end_date'] = $event['end_date'] ? $this->formatDate($event['end_date']) : null;
            $event['formatted_updated_at'] = $this->formatDate($event['updated_at']);
            
            // Format event duration
            $event['duration'] = $this->calculateEventDuration($event);
            
            // Set page data for layout
            LayoutHelper::setPageData(
                htmlspecialchars($event['title']),
                'Viewing event details',
                [
                    ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                    ['label' => 'Events Management', 'url' => Router::url('events')],
                    ['label' => 'View Event', 'url' => Router::url("events/show/{$id}")]
                ]
            );
            
            // Render the view
            LayoutHelper::render('events/show', [
                'event' => $event,
                'user' => $user,
                'canModerate' => $user['role'] === 'super_admin',
                'canEdit' => $user['role'] === 'super_admin' || $event['organizer_id'] === $user['id'],
                'API_URL' => API_BASE_URL,
                'UPLOADS_URL' => UPLOADS_URL,
                'ADMIN_BASE_URL' => ADMIN_APP_URL
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            error_log("Error viewing event {$id}: " . $e->getMessage());
            
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            // Redirect to events list
            header('Location: ' . Router::url('events'));
            exit;
        }
    }

    public function moderation(): void
    {
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        $user = $this->getCurrentUser();
        
        $this->render('events/moderation', [
            'user' => $user,
            'pageTitle' => 'Events Moderation',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Events Management', 'url' => '/admin/events'],
                ['title' => 'Moderation Queue', 'url' => '/admin/events/moderation']
            ]
        ]);
    }

    public function analytics(): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->getCurrentUser();
        
        $this->render('events/analytics', [
            'user' => $user,
            'pageTitle' => 'Events Analytics',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Events Management', 'url' => '/admin/events'],
                ['title' => 'Analytics', 'url' => '/admin/events/analytics']
            ]
        ]);
    }

    public function attendees(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        try {
            // Fetch event via API
            $eventResponse = ApiClient::get("/admin/events/{$id}");
            
            if (!$eventResponse || !$eventResponse['success']) {
                throw new \Exception('Event not found');
            }
            
            $event = $eventResponse['data'];
            
            // Check permissions
            if ($this->user['role'] !== 'super_admin' && $event['organizer_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Set page data for layout
            LayoutHelper::setPageData(
                'Event Attendees - ' . htmlspecialchars($event['title']),
                'Manage event attendees',
                [
                    ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                    ['label' => 'Events Management', 'url' => Router::url('events')],
                    ['label' => $event['title'], 'url' => Router::url("events/show/{$id}")],
                    ['label' => 'Attendees', 'url' => Router::url("events/attendees/{$id}")]
                ]
            );
            
            LayoutHelper::render('events/attendees', [
                'event' => $event,
                'user' => $this->user,
                'API_URL' => API_BASE_URL
            ]);
            
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            header('Location: ' . Router::url('events'));
            exit;
        }
    }

    // API endpoint to get attendees data (AJAX)
    public function getAttendees(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        header('Content-Type: application/json');
        
        try {
            // Check permissions first
            $eventResponse = ApiClient::get("/admin/events/{$id}");
            if (!$eventResponse || !$eventResponse['success']) {
                throw new \Exception('Event not found');
            }
            
            $event = $eventResponse['data'];
            
            if ($this->user['role'] !== 'super_admin' && $event['organizer_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Get attendees
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $search = $_GET['search'] ?? '';
            
            $params = [
                'page' => $page,
                'per_page' => $perPage
            ];
            
            if ($search) {
                $params['search'] = $search;
            }
            
            $response = ApiClient::get("/admin/events/{$id}/attendees", $params);
            
            if (!$response || !$response['success']) {
                throw new \Exception('Failed to fetch attendees');
            }
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Update event status (approve/reject)
    public function updateStatus(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['status'])) {
                throw new \Exception('Status is required');
            }
            
            // Forward to API
            $response = ApiClient::put("/admin/events/{$id}/status", $data);
            
            if (!$response || !$response['success']) {
                throw new \Exception($response['message'] ?? 'Failed to update status');
            }
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Delete event
    public function delete(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        header('Content-Type: application/json');
        
        try {
            // Check permissions first
            $eventResponse = ApiClient::get("/admin/events/{$id}");
            if (!$eventResponse || !$eventResponse['success']) {
                throw new \Exception('Event not found');
            }
            
            $event = $eventResponse['data'];
            
            if ($this->user['role'] !== 'super_admin' && $event['organizer_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Forward delete request to API
            $response = ApiClient::delete("/admin/events/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception($response['message'] ?? 'Failed to delete event');
            }
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Force delete event (permanent)
    public function forceDelete(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        header('Content-Type: application/json');
        
        try {
            // Forward to API
            $response = ApiClient::delete("/admin/events/{$id}/force");
            
            if (!$response || !$response['success']) {
                throw new \Exception($response['message'] ?? 'Failed to delete event');
            }
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Export attendees list
    public function exportAttendees(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        try {
            // Check permissions
            $eventResponse = ApiClient::get("/admin/events/{$id}");
            if (!$eventResponse || !$eventResponse['success']) {
                throw new \Exception('Event not found');
            }
            
            $event = $eventResponse['data'];
            
            if ($this->user['role'] !== 'super_admin' && $event['organizer_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            $format = $_GET['format'] ?? 'csv';
            
            // Get all attendees
            $response = ApiClient::get("/admin/events/{$id}/attendees/export", [
                'format' => $format
            ]);
            
            if (!$response || !$response['success']) {
                throw new \Exception('Failed to export attendees');
            }
            
            // Get the file path or data from response
            $filePath = $response['data']['file_path'] ?? null;
            $fileData = $response['data']['file_data'] ?? null;
            
            if ($filePath) {
                $this->downloadFile($filePath, $format);
            } elseif ($fileData) {
                $this->sendFileData($fileData, $format, $event['title']);
            } else {
                throw new \Exception('No file data received');
            }
            
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            header('Location: ' . Router::url("events/attendees/{$id}"));
            exit;
        }
    }

    // Calendar view
    public function calendar(): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        LayoutHelper::setPageData(
            'Events Calendar',
            'View events in calendar format',
            [
                ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                ['label' => 'Events Management', 'url' => Router::url('events')],
                ['label' => 'Calendar', 'url' => Router::url('events/calendar')]
            ]
        );
        
        LayoutHelper::render('events/calendar', [
            'user' => $this->user,
            'API_URL' => API_BASE_URL
        ]);
    }

    // Get calendar events (AJAX)
    public function getCalendarEvents(): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        header('Content-Type: application/json');
        
        try {
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            
            $params = [
                'start' => $start,
                'end' => $end
            ];
            
            // If contributor, only show their events
            if ($this->user['role'] === 'contributor') {
                $params['organizer_id'] = $this->user['id'];
            }
            
            $response = ApiClient::get('/admin/events/calendar', $params);
            
            if (!$response || !$response['success']) {
                throw new \Exception('Failed to fetch calendar events');
            }
            
            // Transform events for calendar display
            $events = [];
            foreach ($response['data'] as $event) {
                $events[] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_date'] . ($event['start_time'] ? ' ' . $event['start_time'] : ''),
                    'end' => $event['end_date'] ? ($event['end_date'] . ($event['end_time'] ? ' ' . $event['end_time'] : '')) : null,
                    'url' => Router::url("events/show/{$event['id']}"),
                    'color' => $event['category_color'] ?? '#6366f1',
                    'extendedProps' => [
                        'status' => $event['status'],
                        'is_online' => $event['is_online'],
                        'venue_city' => $event['venue_city'],
                        'attendees' => $event['current_attendees'] ?? 0
                    ]
                ];
            }
            
            echo json_encode($events);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Duplicate event
    public function duplicate(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        try {
            // Fetch the original event
            $response = ApiClient::get("/admin/events/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception('Event not found');
            }
            
            $event = $response['data'];
            
            // Check permissions
            if ($this->user['role'] !== 'super_admin' && $event['organizer_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Prepare duplicated event data
            $newEvent = [
                'title' => $event['title'] . ' (Copy)',
                'slug' => '', // Will be auto-generated
                'description' => $event['description'],
                'content' => $event['content'],
                'category_id' => $event['category_id'],
                'status' => 'draft', // Always set to draft
                'is_featured' => false, // Reset featured status
                'is_online' => $event['is_online'],
                'is_free' => $event['is_free'],
                'start_date' => date('Y-m-d', strtotime('+1 week')), // Set to next week
                'end_date' => $event['end_date'] ? date('Y-m-d', strtotime('+1 week', strtotime($event['end_date']))) : null,
                'start_time' => $event['start_time'],
                'end_time' => $event['end_time'],
                'timezone' => $event['timezone'],
                'venue_name' => $event['venue_name'],
                'venue_address' => $event['venue_address'],
                'venue_city' => $event['venue_city'],
                'venue_state' => $event['venue_state'],
                'venue_country' => $event['venue_country'],
                'venue_postal_code' => $event['venue_postal_code'],
                'online_platform' => $event['online_platform'],
                'online_link' => $event['online_link'],
                'price' => $event['price'],
                'currency' => $event['currency'],
                'max_capacity' => $event['max_capacity'],
                'registration_required' => $event['registration_required'],
                'meta_title' => $event['meta_title'],
                'meta_description' => $event['meta_description']
            ];
            
            // Create the duplicate via API
            $createResponse = ApiClient::post('/admin/events', $newEvent);

            if ($createResponse && $createResponse['success']) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Event duplicated successfully!'
                ];
                
                // Redirect to edit the new event
                header('Location: ' . Router::url('events/edit/' . $createResponse['data']['id']));
            } else {
                throw new \Exception('Failed to duplicate event');
            }
            
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            header('Location: ' . Router::url('events'));
        }
    }

    // Get event statistics (AJAX)
    public function getStatistics(): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        header('Content-Type: application/json');
        
        try {
            $params = [];
            
            // If contributor, only show their stats
            if ($this->user['role'] === 'contributor') {
                $params['organizer_id'] = $this->user['id'];
            }
            
            $response = ApiClient::get('/admin/events/statistics', $params);
            
            if (!$response || !$response['success']) {
                throw new \Exception('Failed to fetch statistics');
            }
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Analytics page
    public function analyticsPage(): void
    {
        // Check authentication and permissions
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->user;
        
        try {
            // Set page data for layout
            LayoutHelper::setPageData(
                'Events Analytics',
                'Comprehensive insights into event performance',
                [
                    ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                    ['label' => 'Events Management', 'url' => Router::url('events')],
                    ['label' => 'Analytics', 'url' => Router::url('events/analytics')]
                ]
            );
            
            // Render the analytics view
            LayoutHelper::render('events/analytics', [
                'user' => $user,
                'API_URL' => API_BASE_URL,
                'UPLOADS_URL' => UPLOADS_URL,
                'Router' => 'Router' // Pass Router class name for use in view
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            error_log("Error loading events analytics: " . $e->getMessage());
            
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to load analytics: ' . $e->getMessage()
            ];
            
            // Redirect to events list
            header('Location: ' . Router::url('events'));
            exit;
        }
    }

    // Get analytics data (AJAX)
    public function getAnalyticsData(): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        header('Content-Type: application/json');
        
        try {
            // Get date range from query parameters
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            // Prepare params
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            // If contributor, only show their analytics
            if ($this->user['role'] === 'contributor') {
                $params['organizer_id'] = $this->user['id'];
            }
            
            // Fetch analytics data from API
            $response = ApiClient::get('/admin/events/analytics', $params);
            
            if (!$response || !$response['success']) {
                throw new \Exception('Failed to fetch analytics data');
            }
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Helper methods
    protected function requireRole(string $role): void
    {
        if (!$this->user || $this->user['role'] !== $role) {
            throw new \Exception('Insufficient permissions');
        }
    }

    protected function requireRoles(array $allowedRoles): void
    {
        if (!$this->user || !in_array($this->user['role'], $allowedRoles)) {
            throw new \Exception('Insufficient permissions');
        }
    }

    protected function getCurrentUser()
    {
        return $this->user;
    }

    protected function render(string $view, array $data = []): void
    {
        LayoutHelper::render($view, $data);
    }

    // Format date helper
    private function formatDate($dateString): array
    {
        if (!$dateString) {
            return [
                'full' => '',
                'date' => '',
                'time' => '',
                'relative' => ''
            ];
        }
        
        $timestamp = strtotime($dateString);
        $now = time();
        $diff = $now - $timestamp;
        
        // Calculate relative time
        $relative = '';
        if ($diff < 0) {
            // Future date
            $diff = abs($diff);
            if ($diff < 86400) {
                $relative = 'today';
            } elseif ($diff < 172800) {
                $relative = 'tomorrow';
            } else {
                $days = floor($diff / 86400);
                $relative = 'in ' . $days . ' day' . ($days > 1 ? 's' : '');
            }
        } else {
            // Past date
            if ($diff < 60) {
                $relative = 'just now';
            } elseif ($diff < 3600) {
                $minutes = floor($diff / 60);
                $relative = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                $relative = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } elseif ($diff < 604800) {
                $days = floor($diff / 86400);
                $relative = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            } else {
                $relative = date('M d, Y', $timestamp);
            }
        }
        
        return [
            'full' => date('F d, Y \a\t g:i A', $timestamp),
            'date' => date('M d, Y', $timestamp),
            'time' => date('g:i A', $timestamp),
            'relative' => $relative
        ];
    }

    // Calculate event duration
    private function calculateEventDuration(array $event): string
    {
        if (!$event['start_date']) {
            return '';
        }
        
        $start = strtotime($event['start_date']);
        $end = $event['end_date'] ? strtotime($event['end_date']) : $start;
        
        if ($start === $end) {
            // Single day event
            if ($event['start_time'] && $event['end_time']) {
                $startTime = strtotime($event['start_date'] . ' ' . $event['start_time']);
                $endTime = strtotime($event['start_date'] . ' ' . $event['end_time']);
                $hours = round(($endTime - $startTime) / 3600, 1);
                return $hours . ' hour' . ($hours != 1 ? 's' : '');
            } else {
                return '1 day';
            }
        } else {
            // Multi-day event
            $days = round(($end - $start) / 86400) + 1;
            return $days . ' day' . ($days != 1 ? 's' : '');
        }
    }

    // Download file helper
    private function downloadFile(string $filePath, string $format): void
    {
        $fullPath = dirname(__DIR__, 4) . '/storage/exports/' . $filePath;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('Export file not found');
        }
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv'
        ];
        
        $extension = [
            'pdf' => 'pdf',
            'excel' => 'xlsx',
            'csv' => 'csv'
        ];
        
        header('Content-Type: ' . ($mimeTypes[$format] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="event_attendees_' . date('Y-m-d') . '.' . ($extension[$format] ?? 'bin') . '"');
        header('Content-Length: ' . filesize($fullPath));
        
        readfile($fullPath);
        
        // Clean up the file after download
        unlink($fullPath);
    }

    // Send file data directly
    private function sendFileData(string $fileData, string $format, string $eventTitle): void
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv'
        ];
        
        $extension = [
            'pdf' => 'pdf',
            'excel' => 'xlsx',
            'csv' => 'csv'
        ];
        
        $filename = sprintf(
            'attendees_%s_%s.%s',
            $this->slugify($eventTitle),
            date('Y-m-d'),
            $extension[$format] ?? 'bin'
        );
        
        header('Content-Type: ' . ($mimeTypes[$format] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($fileData));
        
        echo base64_decode($fileData);
    }

    // Slugify helper
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }
}