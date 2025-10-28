<?php
    // admin/src/Views/media/index.php - Media Library Main View

    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }
?>

<style>
    .table-container { max-height: calc(100vh - 200px); overflow-y: auto; }
    .moderation-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .moderation-table th { position: sticky; top: 0; z-index: 10; }
    .priority-urgent { border-left: 4px solid #dc2626; }
    .priority-high { border-left: 4px solid #ea580c; }
    .priority-medium { border-left: 4px solid #eab308; }
    .priority-low { border-left: 4px solid #22c55e; }
    .content-preview { max-width: 300px; word-wrap: break-word; }
    .score-bar { width: 60px; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
    .score-fill { height: 100%; transition: width 0.3s ease; }
    .score-low { background: #22c55e; }
    .score-medium { background: #eab308; }
    .score-high { background: #ea580c; }
    .score-critical { background: #dc2626; }
    .action-btn { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
    .btn-approve { background: #dcfce7; color: #166534; }
    .btn-approve:hover { background: #bbf7d0; }
    .btn-reject { background: #fef2f2; color: #991b1b; }
    .btn-reject:hover { background: #fecaca; }
    .btn-view { background: #dbeafe; color: #1e40af; }
    .btn-view:hover { background: #bfdbfe; }
    /* .loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; } */
    .toast { position: fixed; bottom: 20px; right: 20px; z-index: 1000; padding: 12px 24px; border-radius: 8px; color: white; font-weight: 500; }
    .toast.success { background: #10b981; }
    .toast.error { background: #ef4444; }
    .toast.warning { background: #f59e0b; }
</style>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($title) ?></h1>
            <p class="text-gray-600">Review and moderate pending comments</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Queue Size:</span>
                <span id="queueSize" class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">0</span>
            </div>
            <button id="refreshBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
            <button id="autoProcessBtn" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-magic mr-2"></i>Auto Process
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 hover:shadow-xl duration-300 p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Priority:</label>
                <select id="priorityFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Priorities</option>
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Content Type:</label>
                <select id="contentTypeFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    <option value="news">News</option>
                    <option value="event">Events</option>
                    <option value="comment">Comments</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Status:</label>
                <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="all">All Status</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="flex items-center">
                    <input type="checkbox" id="flaggedOnlyFilter" class="mr-2">
                    <span class="text-sm text-gray-700">Flagged Only</span>
                </label>
            </div>
            
            <button id="clearFiltersBtn" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Clear Filters
            </button>
        </div>
    </div>

    <!-- Table Container -->
    <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl relative">
        <div id="loadingOverlay" class="loading-overlay hidden">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-2"></i>
                <p class="text-gray-600">Loading moderation queue...</p>
            </div>
        </div>

        <div class="table-container overflow-x-auto">
            <table class="moderation-table">
                <thead class="bg-gray-50">
                    <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3">Author</th>
                        <th class="px-6 py-3">Content</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Priority</th>
                        <th class="px-6 py-3">Risk Score</th>
                        <th class="px-6 py-3">Flags</th>
                        <th class="px-6 py-3">Created</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="queueTableBody" class="bg-white divide-y divide-gray-200">
                    <!-- Table rows will be inserted here -->
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden text-center py-12">
            <i class="fas fa-check-circle text-6xl text-green-400 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-900 mb-2">Queue is Empty!</h3>
            <p class="text-gray-600">All content has been reviewed. Great job!</p>
        </div>
    </div>

    <!-- Pagination -->
    <div id="paginationContainer" class="flex items-center justify-between mt-6 hidden">
        <div class="text-sm text-gray-700">
            Showing <span id="showingFrom">1</span> to <span id="showingTo">10</span> of <span id="totalItems">0</span> results
        </div>
        <div class="flex space-x-2">
            <button id="prevPageBtn" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50" disabled>
                Previous
            </button>
            <span id="currentPageSpan" class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md">1</span>
            <button id="nextPageBtn" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50" disabled>
                Next
            </button>
        </div>
    </div>
</div>

<!-- Quick Action Modal -->
<div id="quickActionModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Content Details</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div id="modalContent" class="p-6">
                <!-- Modal content will be loaded here -->
            </div>
            
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <div class="flex justify-end space-x-3">
                    <button id="modalApproveBtn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check mr-2"></i>Approve
                    </button>
                    <button id="modalRejectBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                    <button id="modalDeleteBtn" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer"></div>

<script>
    // Complete JavaScript for admin/templates/comments/index.php - Comment Moderation Interface

    // Global variables
    let currentQueue = [];
    let currentFilters = {};
    let currentPage = 1;
    let totalPages = 1;
    let selectedItemId = null;

    // Initialize when document is ready
    $(document).ready(function() {
        setupEventListeners();
        loadModerationQueue();
    });

    function getAuthToken() {
        return '<?php echo $_SESSION['auth_token'] ?? ''; ?>' || localStorage.getItem('auth_token');
    }

    function setupEventListeners() {
        // Filter changes
        $('#priorityFilter, #contentTypeFilter, #statusFilter').on('change', function() {
            currentPage = 1;
            updateFilters();
            loadModerationQueue();
        });

        $('#flaggedOnlyFilter').on('change', function() {
            currentPage = 1;
            updateFilters();
            loadModerationQueue();
        });

        // Clear filters button
        $('#clearFiltersBtn').on('click', function() {
            $('#priorityFilter, #contentTypeFilter, #statusFilter').val('');
            $('#statusFilter').val('pending'); // Keep pending as default
            $('#flaggedOnlyFilter').prop('checked', false);
            currentFilters = {};
            currentPage = 1;
            loadModerationQueue();
        });

        // Refresh button
        $('#refreshBtn').on('click', function() {
            loadModerationQueue();
        });

        // Auto process button
        $('#autoProcessBtn').on('click', function() {
            if (confirm('This will automatically process comments based on AI analysis. Continue?')) {
                autoProcessQueue();
            }
        });

        // Table action buttons (using event delegation for dynamic content)
        $(document).on('click', '.view-detail', function() {
            const itemId = $(this).data('id');
            showDetailModal(itemId);
        });

        $(document).on('click', '.quick-approve', function() {
            const itemId = $(this).data('id');
            quickModerate(itemId, 'approve');
        });

        $(document).on('click', '.quick-reject', function() {
            const itemId = $(this).data('id');
            quickModerate(itemId, 'reject');
        });

        // Modal action buttons
        $('#modalApproveBtn').on('click', function() {
            if (selectedItemId) {
                quickModerate(selectedItemId, 'approve');
            }
        });

        $('#modalRejectBtn').on('click', function() {
            if (selectedItemId) {
                quickModerate(selectedItemId, 'reject');
            }
        });

        $('#modalDeleteBtn').on('click', function() {
            if (selectedItemId && confirm('Are you sure you want to delete this comment?')) {
                quickModerate(selectedItemId, 'delete');
            }
        });

        // Close modal
        $('#closeModal').on('click', function() {
            $('#quickActionModal').addClass('hidden');
            selectedItemId = null;
        });

        // Close modal when clicking outside
        $('#quickActionModal').on('click', function(e) {
            if (e.target === this) {
                $(this).addClass('hidden');
                selectedItemId = null;
            }
        });

        // Pagination buttons
        $('#prevPageBtn').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadModerationQueue();
            }
        });

        $('#nextPageBtn').on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadModerationQueue();
            }
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // ESC to close modal
            if (e.key === 'Escape') {
                $('#quickActionModal').addClass('hidden');
                selectedItemId = null;
            }
            // R to refresh
            if (e.key === 'r' && e.ctrlKey) {
                e.preventDefault();
                loadModerationQueue();
            }
        });
    }

    function updateFilters() {
        currentFilters = {
            priority: $('#priorityFilter').val(),
            flagged_only: $('#flaggedOnlyFilter').is(':checked') ? '1' : ''
        };

        // Remove empty filters
        Object.keys(currentFilters).forEach(key => {
            if (!currentFilters[key]) {
                delete currentFilters[key];
            }
        });
    }

    function loadModerationQueue() {
        $('#loadingOverlay').removeClass('hidden');
        $('#emptyState').addClass('hidden');

        const params = new URLSearchParams({
            page: currentPage,
            per_page: 20,
            ...currentFilters
        });

        $.ajax({
            url: `${API_URL}admin/comments/moderation/queue?${params}`,
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json'
            },
            timeout: 30000,
            success: function(response) {
                $('#loadingOverlay').addClass('hidden');
                
                if (response && response.success && Array.isArray(response.data)) {
                    currentQueue = response.data;
                    renderTable(currentQueue);
                    updatePagination(response.meta?.pagination || response.pagination || {});
                    
                    const totalItems = response.meta?.pagination?.total || response.pagination?.total || response.data.length;
                    $('#queueSize').text(totalItems);
                    
                    if (currentQueue.length === 0) {
                        $('#emptyState').removeClass('hidden');
                    }
                } else {
                    $('#emptyState').removeClass('hidden');
                    $('#queueSize').text('0');
                    console.warn('Unexpected response format:', response);
                }
            },
            error: function(xhr, status, error) {
                $('#loadingOverlay').addClass('hidden');
                console.error('Error loading moderation queue:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                let errorMessage = 'Failed to load moderation queue';
                if (xhr.status === 401) {
                    errorMessage = 'Authentication required. Please log in again.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Access denied. Admin privileges required.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Moderation endpoint not found. Please check your API configuration.';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Server error. Please check server logs and try again.';
                }
                
                showToast(errorMessage, 'error');
                $('#emptyState').removeClass('hidden');
            }
        });
    }

    function renderTable(items) {
        const tbody = $('#queueTableBody');
        tbody.empty();

        if (!Array.isArray(items) || items.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Comments to Moderate</h3>
                            <p class="text-gray-500">All comments have been reviewed or there are no pending comments.</p>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        items.forEach(item => {
            tbody.append(createTableRow(item));
        });
    }

    function createTableRow(item) {
        const priorityClass = item.priority ? `priority-${item.priority}` : '';
        const moderationScore = parseFloat(item.moderation_score || item.queue_score || 0);
        const scoreClass = getModerationScoreClass(moderationScore);
        const riskPercentage = (moderationScore * 100).toFixed(1);

        // Handle content preview - for comments, use the content field
        let contentPreview = '';
        if (item.content) {
            contentPreview = item.content.length > 100 ? item.content.substring(0, 100) + '...' : item.content;
        } else {
            contentPreview = `Comment (ID: ${item.content_id})`;
        }

        // Handle author info
        const authorName = item.author_name || 
                          (item.first_name && item.last_name ? `${item.first_name} ${item.last_name}` : '') ||
                          'Anonymous';
        const authorIdentifier = item.username || item.email || item.author_email || '';

        // Handle flags
        let flagsHtml = '';
        try {
            let flags = [];
            if (item.flags) {
                flags = typeof item.flags === 'string' ? JSON.parse(item.flags) : item.flags;
            }
            
            if (Array.isArray(flags) && flags.length > 0) {
                flagsHtml = flags.map(flag => 
                    `<span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">${escapeHtml(flag.replace(/_/g, ' '))}</span>`
                ).join('');
            } else if (item.is_flagged || item.flag_count > 0) {
                flagsHtml = '<span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Flagged</span>';
            }
        } catch (e) {
            console.warn('Error parsing flags for item', item.id, e);
            if (item.is_flagged || item.flag_count > 0) {
                flagsHtml = '<span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Flagged</span>';
            }
        }

        // Use content_id for actions (the actual comment ID)
        const commentId = item.content_id || item.id;
        const createdDate = item.comment_created_at || item.created_at;

        return `
            <tr class="hover:bg-gray-50 ${priorityClass}" data-id="${commentId}">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            ${item.profile_image ? 
                                `<img class="h-8 w-8 rounded-full object-cover" src="${escapeHtml(item.profile_image)}" alt="${escapeHtml(authorName)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                 <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center" style="display:none;">
                                    <i class="fas fa-user text-gray-500 text-xs"></i>
                                 </div>` :
                                `<div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-500 text-xs"></i>
                                 </div>`
                            }
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">${escapeHtml(authorName)}</div>
                            <div class="text-sm text-gray-500">${escapeHtml(authorIdentifier)}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="content-preview">
                        <div class="text-sm text-gray-900">${escapeHtml(contentPreview)}</div>
                        ${item.content_title ? `<div class="text-xs text-gray-500 mt-1">On: ${escapeHtml(item.content_title)}</div>` : ''}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-comment mr-1"></i>Comment
                    </span>
                </td>
                <td class="px-6 py-4">
                    ${item.priority ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${getPriorityColor(item.priority)}-100 text-${getPriorityColor(item.priority)}-800 capitalize">${escapeHtml(item.priority)}</span>` : '<span class="text-gray-400">-</span>'}
                </td>
                <td class="px-6 py-4">
                    ${moderationScore > 0 ? `
                        <div class="flex items-center space-x-2">
                            <div class="score-bar">
                                <div class="score-fill ${scoreClass}" style="width: ${moderationScore * 100}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700">${riskPercentage}%</span>
                        </div>
                    ` : '<span class="text-gray-400">-</span>'}
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-wrap">
                        ${flagsHtml || '<span class="text-gray-400">-</span>'}
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    ${formatDate(createdDate)}
                </td>
                <td class="px-6 py-4">
                    <div class="flex space-x-2">
                        <button class="action-btn btn-view view-detail" data-id="${commentId}" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn btn-approve quick-approve" data-id="${commentId}" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="action-btn btn-reject quick-reject" data-id="${commentId}" title="Reject">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    function quickModerate(commentId, action) {
        // Show loading state
        const button = $(`.quick-${action}[data-id="${commentId}"]`);
        const originalHtml = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            url: `${API_URL}admin/comments/${commentId}/moderate`,
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            data: JSON.stringify({
                action: action,
                reason: `Quick ${action} from moderation queue`
            }),
            success: function(response) {
                if (response && response.success) {
                    showToast(`Comment ${action}d successfully`, 'success');
                    
                    // Close modal if open
                    $('#quickActionModal').addClass('hidden');
                    selectedItemId = null;
                    
                    // Refresh the queue to get updated data
                    loadModerationQueue();
                } else {
                    showToast(response?.message || `Failed to ${action} comment`, 'error');
                    // Restore button state
                    button.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                console.error(`Error ${action}ing comment:`, xhr.responseJSON || xhr.responseText);
                
                let errorMessage = `Failed to ${action} comment`;
                if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = 'Comment not found or endpoint not available';
                } else if (xhr.status === 401) {
                    errorMessage = 'Authentication required';
                } else if (xhr.status === 403) {
                    errorMessage = 'Access denied';
                }
                
                showToast(errorMessage, 'error');
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
            }
        });
    }

    function showDetailModal(commentId) {
        selectedItemId = commentId;
        
        $.ajax({
            url: `${API_URL}admin/comments/${commentId}`,
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response && response.success) {
                    const comment = response.data;
                    displayCommentDetails(comment);
                    $('#quickActionModal').removeClass('hidden');
                } else {
                    showToast('Failed to load comment details', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error fetching comment details:', xhr.responseJSON || xhr.responseText);
                showToast('Failed to load comment details', 'error');
            }
        });
    }

    function displayCommentDetails(comment) {
        const moderationScore = parseFloat(comment.moderation_score || comment.queue_score || 0);
        const scoreClass = getModerationScoreClass(moderationScore);
        
        // Handle flags and reasons
        let flags = [];
        let reasons = [];
        
        try {
            if (comment.flags) {
                flags = typeof comment.flags === 'string' ? JSON.parse(comment.flags) : comment.flags;
            }
            if (comment.reasons) {
                reasons = typeof comment.reasons === 'string' ? JSON.parse(comment.reasons) : comment.reasons;
            }
        } catch (e) {
            console.warn('Error parsing flags/reasons for comment', comment.id, e);
        }

        const modalContent = `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Comment Content</h4>
                        <div class="bg-white p-3 rounded border">
                            <p class="text-gray-800 whitespace-pre-wrap">${escapeHtml(comment.content)}</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Author Information</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Name:</strong> ${escapeHtml(comment.author_name || 'Anonymous')}</div>
                            <div><strong>Username:</strong> ${escapeHtml(comment.username || 'N/A')}</div>
                            <div><strong>Email:</strong> ${escapeHtml(comment.email || comment.author_email || 'N/A')}</div>
                            <div><strong>Role:</strong> <span class="capitalize">${escapeHtml(comment.role || 'public')}</span></div>
                        </div>
                    </div>

                    ${moderationScore > 0 || (Array.isArray(flags) && flags.length > 0) ? `
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                AI Moderation Analysis
                            </h4>
                            
                            ${moderationScore > 0 ? `
                                <div class="mb-3">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm text-gray-600">Risk Score</span>
                                        <span class="text-sm font-medium">${(moderationScore * 100).toFixed(1)}%</span>
                                    </div>
                                    <div class="score-bar">
                                        <div class="score-fill ${scoreClass}" style="width: ${moderationScore * 100}%"></div>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${Array.isArray(flags) && flags.length > 0 ? `
                                <div class="mb-2">
                                    <span class="text-sm text-gray-600">Detected Issues:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        ${flags.map(flag => 
                                            `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">${escapeHtml(flag.replace(/_/g, ' '))}</span>`
                                        ).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${Array.isArray(reasons) && reasons.length > 0 ? `
                                <div>
                                    <span class="text-sm text-gray-600">Reasons:</span>
                                    <ul class="text-sm text-gray-700 mt-1 space-y-1">
                                        ${reasons.map(reason => `<li>• ${escapeHtml(reason)}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Comment Details</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Status:</strong> <span class="capitalize">${escapeHtml(comment.status)}</span></div>
                            <div><strong>Content Type:</strong> <span class="capitalize">${escapeHtml(comment.content_type)}</span></div>
                            <div><strong>Content ID:</strong> ${comment.content_id}</div>
                            ${comment.content_title ? `<div><strong>On:</strong> ${escapeHtml(comment.content_title)}</div>` : ''}
                            ${comment.parent_id ? `<div><strong>Reply to:</strong> Comment #${comment.parent_id}</div>` : ''}
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Engagement</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span><i class="fas fa-thumbs-up text-green-500 mr-1"></i>Upvotes:</span>
                                <span class="text-green-600 font-medium">${comment.upvotes || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span><i class="fas fa-thumbs-down text-red-500 mr-1"></i>Downvotes:</span>
                                <span class="text-red-600 font-medium">${comment.downvotes || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span><i class="fas fa-flag text-orange-500 mr-1"></i>Flags:</span>
                                <span class="text-orange-600 font-medium">${comment.flag_count || 0}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Timeline</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Created:</strong> ${formatDate(comment.created_at)}</div>
                            ${comment.updated_at && comment.updated_at !== comment.created_at ? `<div><strong>Updated:</strong> ${formatDate(comment.updated_at)}</div>` : ''}
                            ${comment.reviewed_at ? `<div><strong>Reviewed:</strong> ${formatDate(comment.reviewed_at)}</div>` : ''}
                            ${comment.last_edited_at ? `<div><strong>Last Edited:</strong> ${formatDate(comment.last_edited_at)}</div>` : ''}
                        </div>
                    </div>

                    ${comment.edit_count > 0 ? `
                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">
                                <i class="fas fa-edit text-blue-600 mr-2"></i>
                                Edit History
                            </h4>
                            <div class="text-sm text-gray-700">
                                This comment has been edited ${comment.edit_count} time${comment.edit_count > 1 ? 's' : ''}.
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        $('#modalContent').html(modalContent);
    }

    function autoProcessQueue() {
        const button = $('#autoProcessBtn');
        const originalHtml = button.html();
        button.html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...').prop('disabled', true);

        $.ajax({
            url: `${API_URL}admin/comments/moderation/auto-moderate`,
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json'
            },
            success: function(response) {
                button.html(originalHtml).prop('disabled', false);

                if (response && response.success) {
                    const result = response.data;
                    const message = `Auto-processing completed: ${result.processed} processed, ${result.approved} approved, ${result.rejected} rejected, ${result.held} held for review`;
                    showToast(message, 'success');
                    loadModerationQueue();
                } else {
                    showToast('Auto-processing failed', 'error');
                }
            },
            error: function(xhr) {
                button.html(originalHtml).prop('disabled', false);
                console.error('Error auto-processing:', xhr.responseJSON || xhr.responseText);
                showToast('Auto-processing failed', 'error');
            }
        });
    }

    function updatePagination(pagination) {
        if (!pagination || !pagination.total_pages || pagination.total_pages <= 1) {
            $('#paginationContainer').addClass('hidden');
            return;
        }

        $('#paginationContainer').removeClass('hidden');
        
        const from = ((pagination.current_page - 1) * pagination.per_page) + 1;
        const to = Math.min(pagination.current_page * pagination.per_page, pagination.total);
        
        $('#showingFrom').text(from);
        $('#showingTo').text(to);
        $('#totalItems').text(pagination.total);
        $('#currentPageSpan').text(pagination.current_page);
        
        $('#prevPageBtn').prop('disabled', !pagination.has_previous);
        $('#nextPageBtn').prop('disabled', !pagination.has_next);
        
        totalPages = pagination.total_pages;
        currentPage = pagination.current_page;
    }

    // Utility functions
    function getModerationScoreClass(score) {
        if (score >= 0.8) return 'score-critical';
        if (score >= 0.6) return 'score-high';
        if (score >= 0.3) return 'score-medium';
        return 'score-low';
    }

    function getPriorityColor(priority) {
        switch(priority?.toLowerCase()) {
            case 'urgent': return 'red';
            case 'high': return 'orange';
            case 'medium': return 'yellow';
            case 'low': return 'green';
            default: return 'gray';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(s) {
            return map[s];
        });
    }

    function formatDate(dateString) {
        if (!dateString) return 'Unknown';
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } catch (error) {
            console.warn('Invalid date format:', dateString);
            return 'Invalid date';
        }
    }

    function showToast(message, type = 'success') {
        const toastClass = type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success';
        const icon = type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle';
        
        const toast = $(`
            <div class="toast ${toastClass} opacity-0 transform translate-y-2 transition-all duration-300">
                <i class="fas ${icon} mr-2"></i>
                ${escapeHtml(message)}
            </div>
        `);
        
        $('#toastContainer').append(toast);
        
        // Animate in
        setTimeout(() => {
            toast.removeClass('opacity-0 translate-y-2');
        }, 10);
        
        // Animate out and remove
        setTimeout(() => {
            toast.addClass('opacity-0 translate-y-2');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }
</script>
