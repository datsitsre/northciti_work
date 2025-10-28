// admin/assets/js/events.js

// Global variables
let currentPage = 1;
let currentView = 'list';
let events = [];
let categories = [];
let tags = [];
let selectedTags = [];
let currentEventId = null;
let uploadedImagePath = null;
let currentCalendarDate = new Date();
let moderationEventId = null;

// API endpoints
const ADMIN_API = `${API_URL}admin`;

// Initialize when DOM is loaded
$(document).ready(function() {
    loadCategories();
    loadEvents();
    loadStatistics();
    setupEventListeners();
    initializeImageUpload();
    
    // Initialize date pickers
    flatpickr("#startDate", {
        minDate: "today"
    });
    
    flatpickr("#endDate", {
        minDate: "today"
    });
    
    flatpickr("#registrationDeadline", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today"
    });
});

// Setup event listeners
function setupEventListeners() {
    // Search input
    $('#searchInput').on('input', debounce(function() {
        currentPage = 1;
        loadEvents();
    }, 300));
    
    // Filters
    $('#statusFilter, #typeFilter, #categoryFilter').on('change', function() {
        currentPage = 1;
        loadEvents();
    });
    
    // Form submission
    $('#eventForm').on('submit', function(e) {
        e.preventDefault();
        saveEvent();
    });
    
    // Tag input
    $('#tagInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            addTag();
        }
    });
    
    // Event type change
    $('#eventType').on('change', toggleEventTypeFields);
    
    // Free event checkbox
    $('#isFree').on('change', togglePricing);
}

// Load events
async function loadEvents() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            per_page: 10,
            search: $('#searchInput').val(),
            status: $('#statusFilter').val(),
            type: $('#typeFilter').val(),
            category: $('#categoryFilter').val()
        });
        
        // Remove empty params
        for (const [key, value] of [...params]) {
            if (!value) params.delete(key);
        }
        
        const response = await fetch(`${ADMIN_API}/events?${params}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });
        
        if (!response.ok) throw new Error('Failed to load events');
        
        const data = await response.json();
        
        if (data.success) {
            events = data.data;
            
            if (currentView === 'list') {
                renderEventsList(events);
            } else if (currentView === 'calendar') {
                renderCalendar();
            } else if (currentView === 'moderation') {
                loadModerationQueue();
            }
            
            renderPagination(data.meta.pagination);
        }
    } catch (error) {
        console.error('Error loading events:', error);
        showNotification('Failed to load events', 'error');
    }
}

// Render events list
function renderEventsList(events) {
    const container = $('#listView');
    container.empty();
    
    if (events.length === 0) {
        container.html(`
            <div class="text-center py-12">
                <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No events found</p>
            </div>
        `);
        return;
    }
    
    events.forEach(event => {
        const eventCard = `
            <div class="event-card bg-white rounded-lg shadow p-6 ${event.is_featured ? 'featured' : ''} ${event.is_online ? 'online' : 'in-person'}">
                <div class="flex items-start justify-between flex-body">
                    <div class="flex-1">
                        <div class="flex flex-body items-center space-x-3 mb-2">
                            <h3 class="text-lg font-semibold">${escapeHtml(event.title)}</h3>
                            <div class="flex-1">
                                ${event.is_featured ? '<span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">Featured</span>' : ''}
                                ${event.is_online ? '<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"><i class="fas fa-globe mr-1"></i>Online</span>' : ''}
                                ${event.is_free ? '<span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Free</span>' : ''}
                            </div>
                        </div>
                        <p class="text-gray-600 mb-3">${escapeHtml(event.description)}</p>
                        <div class="flex flex-body items-center space-x-4 text-sm text-gray-500">
                            <span><i class="fas fa-calendar mr-1"></i>${formatDate(event.start_date)}</span>
                            ${event.start_time ? `<span><i class="fas fa-clock mr-1"></i>${formatTime(event.start_time)}</span>` : ''}
                            <span><i class="fas fa-tag mr-1"></i>${event.category_name || 'Uncategorized'}</span>
                            ${event.venue_city ? `<span><i class="fas fa-map-marker-alt mr-1"></i>${escapeHtml(event.venue_city)}</span>` : ''}
                            <span><i class="fas fa-users mr-1"></i>${event.max_capacity || 0} attendees capacity</span>
                        </div>
                        <div class="mt-3 flex flex-body items-center space-x-4 text-sm">
                            <span><i class="fas fa-eye mr-1"></i>${event.view_count || 0} views</span>
                            <span><i class="fas fa-heart mr-1"></i>${event.like_count || 0} likes</span>
                            <span><i class="fas fa-comment mr-1"></i>${event.comment_count || 0} comments</span>
                        </div>
                    </div>
                    ${event.featured_image ? `
                        <div class="ml-4">
                            <img src="${UPLOADS_URL + event.featured_image}" alt="${escapeHtml(event.title)}" 
                                 class="w-32 h-20 object-cover rounded-lg">
                        </div>
                    ` : ''}
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2 mr-3">
                        ${renderStatusBadge(event.status)}
                        <span class="text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">Created by ${escapeHtml(event.organizer_name || 'Unknown')}</span>
                        <span class="text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">Organized by ${escapeHtml(event.actual_organizer_name || 'Unknown')}</span>
                        <span class="text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">Organizer address: ${escapeHtml(event.actual_organizer_address || 'Unknown')}</span>
                        <span class="text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">Organizer email: ${escapeHtml(event.actual_organizer_email || 'Unknown')}</span>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="viewEvent(${event.id})" class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editEvent(${event.id})" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${event.status === 'pending' ? `
                            <button onclick="openModerationModal(${event.id})" class="text-orange-600 hover:text-orange-800">
                                <i class="fas fa-shield-alt"></i>
                            </button>
                        ` : ''}
                        <button onclick="deleteEvent(${event.id})" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.append(eventCard);
    });
}

// Load categories
async function loadCategories() {
    try {
        const response = await fetch(`${API_URL}categories`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });
        
        if (!response.ok) throw new Error('Failed to load categories');
        
        const data = await response.json();
        
        if (data.success) {
            categories = data.data;
            
            // Populate category filters
            const categoryFilter = $('#categoryFilter');
            const eventCategory = $('#eventCategory');
            
            categoryFilter.empty().append('<option value="">All Categories</option>');
            eventCategory.empty().append('<option value="">Select a category</option>');
            
            categories.forEach(category => {
                const option = `<option value="${category.id}">${escapeHtml(category.name)}</option>`;
                categoryFilter.append(option);
                eventCategory.append(option);
            });
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Load statistics
async function loadStatistics() {
    try {
        const response = await fetch(`${ADMIN_API}/events/statistics`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });
        
        if (!response.ok) throw new Error('Failed to load statistics');
        
        const data = await response.json();

        if (data.success) {
            const stat = data.data.stats;
            $('#totalEvents').text(stat.total_events || 0);
            $('#upcomingEvents').text(stat.upcoming_count || 0);
            $('#pendingEvents').text(stat.pending_count || 0);
            $('#onlineEvents').text(stat.online_count || 0);
            $('#totalAttendees').text(stat.total_attendees || 0);
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Create/Edit event modal
function openCreateEventModal() {
    currentEventId = null;
    $('#modalTitle').text('Create New Event');
    $('#submitBtnText').text('Create Event');
    $('#eventForm')[0].reset();
    selectedTags = [];
    renderTags();
    uploadedImagePath = null;
    $('#imagePreview').hide();
    $('#imageUploadZone').show();
    $('#eventModal').removeClass('hidden');
}

async function editEvent(id) {
    try {
        const response = await fetch(`${ADMIN_API}/events/${id}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });
        
        if (!response.ok) throw new Error('Failed to load event');
        
        const data = await response.json();
        
        if (data.success) {
            const event = data.data;
            currentEventId = event.id;
            
            $('#modalTitle').text('Edit Event');
            $('#submitBtnText').text('Update Event');
            
            // Populate form fields
            $('#eventTitle').val(event.title);
            $('#eventDescription').val(event.description);
            $('#eventContent').val(event.content);
            $('#eventCategory').val(event.category_id);

            $('#actualOrganizerName').val(event.actual_organizer_name);
            $('#actualOrganizerAddress').val(event.actual_organizer_address);
            $('#actualOrganizerEmail').val(event.actual_organizer_email);

            $('#eventType').val(event.is_online ? 'online' : 'in-person');
            $('#startDate').val(event.start_date);
            
            // Format time properly for HTML time input (HH:MM format)
            if (event.start_time) {
                const startTime = event.start_time.substring(0, 5); // Get HH:MM from HH:MM:SS
                $('#startTime').val(startTime);
            }
            
            $('#endDate').val(event.end_date);
            
            if (event.end_time) {
                const endTime = event.end_time.substring(0, 5); // Get HH:MM from HH:MM:SS
                $('#endTime').val(endTime);
            }
            
            $('#timezone').val(event.timezone);
            
            // Location fields
            $('#venueName').val(event.venue_name);
            $('#venueAddress').val(event.venue_address);
            $('#venueCity').val(event.venue_city);
            $('#venueState').val(event.venue_state);
            $('#venueCountry').val(event.venue_country);
            $('#venuePostalCode').val(event.venue_postal_code);
            
            // Online fields
            $('#onlinePlatform').val(event.online_platform);
            $('#onlineLink').val(event.online_link);
            $('#onlinePassword').val(event.online_password);
            
            // Registration & pricing
            $('#isFree').prop('checked', event.is_free);
            $('#registrationRequired').prop('checked', event.registration_required);
            $('#isFeatured').prop('checked', event.is_featured);
            $('#price').val(event.price);
            $('#currency').val(event.currency);
            $('#maxCapacity').val(event.max_capacity);
            // Format registration deadline for datetime-local input
            if (event.registration_deadline) {
                // Convert from "YYYY-MM-DD HH:MM:SS" to "YYYY-MM-DDTHH:MM"
                const deadline = event.registration_deadline.replace(' ', 'T').substring(0, 16);
                $('#registrationDeadline').val(deadline);
            }
            $('#registrationLink').val(event.registration_link);
            
            // SEO
            $('#metaTitle').val(event.meta_title);
            $('#metaDescription').val(event.meta_description);
            
            // Tags
            selectedTags = event.tags || [];
            renderTags();
            
            // Featured image
            if (event.featured_image) {
                uploadedImagePath = event.featured_image;
                $('#previewImage').attr('src', `${UPLOADS_URL + event.featured_image}`);
                $('#imagePreview').show();
                $('#imageUploadZone').hide();
            } else {
                uploadedImagePath = null;
                $('#imagePreview').hide();
                $('#imageUploadZone').show();
            }
            
            toggleEventTypeFields();
            togglePricing();
            
            $('#eventModal').removeClass('hidden');
        }
    } catch (error) {
        console.error('Error loading event:', error);
        showNotification('Failed to load event details', 'error');
    }
}
function closeEventDetailsModal() {
    $('#eventDetailsModal').addClass('hidden');
}

// Calendar view functions
function switchTab(view) {
    currentView = view;
    
    // Update tab styles
    $('.tab-active').removeClass('tab-active text-indigo-600').addClass('text-gray-600');
    $(`button[onclick="switchTab('${view}')"]`).addClass('tab-active text-indigo-600').removeClass('text-gray-600');
    
    // Hide all views
    $('#listView, #calendarView, #moderationView').addClass('hidden');
    
    // Show selected view
    if (view === 'list') {
        $('#listView').removeClass('hidden');
        loadEvents();
    } else if (view === 'calendar') {
        $('#calendarView').removeClass('hidden');
        renderCalendar();
    } else if (view === 'moderation') {
        $('#moderationView').removeClass('hidden');
        loadModerationQueue();
    }
}

function renderCalendar() {
    const year = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();
    
    // Update month display
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    $('#calendarMonth').text(`${monthNames[month]} ${year}`);
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    
    // Build calendar grid
    let html = '';
    
    // Day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        html += `<div class="bg-gray-100 p-2 text-center font-semibold text-sm">${day}</div>`;
    });
    
    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        html += `<div class="calendar-day other-month">${day}</div>`;
    }
    
    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayEvents = events.filter(e => e.start_date === date);
        
        html += `
            <div class="calendar-day ${isToday(year, month, day) ? 'bg-indigo-50' : ''}">
                <div class="font-semibold mb-1">${day}</div>
                ${dayEvents.slice(0, 3).map(event => `
                    <div class="calendar-event ${event.is_online ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}" 
                         onclick="viewEvent(${event.id})" title="${escapeHtml(event.title)}">
                        ${event.start_time ? formatTime(event.start_time) + ' - ' : ''}${escapeHtml(event.title)}
                    </div>
                `).join('')}
                ${dayEvents.length > 3 ? `
                    <div class="text-xs text-gray-500 mt-1">+${dayEvents.length - 3} more</div>
                ` : ''}
            </div>
        `;
    }
    
    // Next month days
    const remainingDays = 42 - (firstDay + daysInMonth); // 6 weeks * 7 days
    for (let day = 1; day <= remainingDays; day++) {
        html += `<div class="calendar-day other-month">${day}</div>`;
    }
    
    $('#calendarGrid').html(html);
}

function previousMonth() {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
    renderCalendar();
}

function nextMonth() {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
    renderCalendar();
}

function currentMonth() {
    currentCalendarDate = new Date();
    renderCalendar();
}

function isToday(year, month, day) {
    const today = new Date();
    return year === today.getFullYear() && 
           month === today.getMonth() && 
           day === today.getDate();
}

// Moderation functions
async function loadModerationQueue() {
    try {
        const response = await fetch(`${ADMIN_API}/events?status=pending`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });
        
        if (!response.ok) throw new Error('Failed to load moderation queue');
        
        const data = await response.json();
        
        if (data.success) {
            renderModerationQueue(data.data);
        }
    } catch (error) {
        console.error('Error loading moderation queue:', error);
        showNotification('Failed to load moderation queue', 'error');
    }
}

function renderModerationQueue(events) {
    const container = $('#moderationQueue');
    container.empty();
    
    if (events.length === 0) {
        container.html(`
            <div class="text-center py-12">
                <i class="fas fa-check-circle text-6xl text-green-400 mb-4"></i>
                <p class="text-gray-500">No events pending moderation</p>
            </div>
        `);
        return;
    }
    
    events.forEach(event => {
        const item = `
            <div class="p-6 hover:bg-gray-50">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="font-semibold text-lg mb-2">${escapeHtml(event.title)}</h4>
                        <p class="text-gray-600 mb-3">${escapeHtml(event.description)}</p>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span><i class="fas fa-user mr-1"></i>${escapeHtml(event.organizer_name || 'Unknown')}</span>
                            <span><i class="fas fa-calendar mr-1"></i>${formatDate(event.start_date)}</span>
                            <span><i class="fas fa-clock mr-1"></i>Submitted ${formatRelativeTime(event.created_at)}</span>
                        </div>
                    </div>
                    <div class="ml-4 flex space-x-2">
                        <button onclick="viewEvent(${event.id})" 
                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded">
                            View Details
                        </button>
                        <button onclick="openModerationModal(${event.id})" 
                                class="px-3 py-1 text-sm bg-orange-600 text-white hover:bg-orange-700 rounded">
                            Moderate
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.append(item);
    });
}

function openModerationModal(eventId) {
    moderationEventId = eventId;
    
    // Load event details for moderation
    const event = events.find(e => e.id === eventId);
    if (!event) return;
    
    const content = `
        <div class="space-y-4">
            <div>
                <h4 class="font-semibold">${escapeHtml(event.title)}</h4>
                <p class="text-sm text-gray-600 mt-1">${escapeHtml(event.description)}</p>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-gray-700">Organizer:</span>
                    <span class="ml-2">${escapeHtml(event.organizer_name || 'Unknown')}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Category:</span>
                    <span class="ml-2">${event.category_name || 'Uncategorized'}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Date:</span>
                    <span class="ml-2">${formatDate(event.start_date)}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Type:</span>
                    <span class="ml-2">${event.is_online ? 'Online' : 'In-Person'}</span>
                </div>
            </div>
            ${event.featured_image ? `
                <div>
                    <span class="font-medium text-gray-700 text-sm">Featured Image:</span>
                    <img src="${UPLOADS_URL + event.featured_image}" alt="Featured image" 
                         class="mt-2 w-full max-w-md rounded-lg">
                </div>
            ` : ''}
        </div>
    `;
    
    $('#moderationContent').html(content);
    $('#moderationModal').removeClass('hidden');
}

function closeModerationModal() {
    $('#moderationModal').addClass('hidden');
    moderationEventId = null;
}

async function approveEvent() {
    if (!moderationEventId) return;
    
    try {
        const notes = $('#moderationNotes').val();
        
        const response = await fetch(`${ADMIN_API}/events/${moderationEventId}/status`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: 'published',
                moderation_notes: notes
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Event approved successfully', 'success');
            closeModerationModal();
            loadEvents();
            loadStatistics();
        } else {
            showNotification(data.message || 'Failed to approve event', 'error');
        }
    } catch (error) {
        console.error('Error approving event:', error);
        showNotification('Failed to approve event', 'error');
    }
}

async function rejectEvent() {
    if (!moderationEventId) return;
    
    const notes = $('#moderationNotes').val();
    if (!notes.trim()) {
        showNotification('Please provide a reason for rejection', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${ADMIN_API}/events/${moderationEventId}/status`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: 'rejected',
                moderation_notes: notes
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Event rejected', 'success');
            closeModerationModal();
            loadEvents();
            loadStatistics();
        } else {
            showNotification(data.message || 'Failed to reject event', 'error');
        }
    } catch (error) {
        console.error('Error rejecting event:', error);
        showNotification('Failed to reject event', 'error');
    }
}

// Event type field toggling
function toggleEventTypeFields() {
    const eventType = $('#eventType').val();
    
    $('#locationSection, #onlineSection').addClass('hidden');
    
    if (eventType === 'in-person' || eventType === 'hybrid') {
        $('#locationSection').removeClass('hidden');
    }
    
    if (eventType === 'online' || eventType === 'hybrid') {
        $('#onlineSection').removeClass('hidden');
    }
}

// Pricing field toggling
function togglePricing() {
    const isFree = $('#isFree').is(':checked');
    
    if (isFree) {
        $('#pricingSection').hide();
        $('#price').val(0);
        // Remove validation constraints when hidden
        $('#maxCapacity').removeAttr('required').removeAttr('min');
    } else {
        $('#pricingSection').show();
        // Restore validation constraints when visible
        $('#maxCapacity').attr('min', '1');
    }
}

// Tag management
function addTag() {
    const tagInput = $('#tagInput');
    const tagName = tagInput.val().trim();
    
    if (!tagName) return;
    
    // Check if tag already exists
    if (selectedTags.some(tag => tag.name === tagName)) {
        showNotification('Tag already added', 'warning');
        return;
    }
    
    selectedTags.push({ name: tagName });
    tagInput.val('');
    renderTags();
}

function removeTag(index) {
    selectedTags.splice(index, 1);
    renderTags();
}

function renderTags() {
    const container = $('#tagsList');
    container.empty();
    
    selectedTags.forEach((tag, index) => {
        const tagEl = `
            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                ${escapeHtml(tag.name)}
                <button type="button" onclick="removeTag(${index})" class="ml-2 text-gray-500 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </span>
        `;
        container.append(tagEl);
    });
}

// Image upload
function initializeImageUpload() {
    const uploadZone = $('#imageUploadZone');
    const fileInput = $('#imageInput');
    
    // Click to upload
    uploadZone.on('click', function() {
        fileInput.click();
    });
    
    // File selection
    fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            uploadImage(file);
        }
    });
    
    // Drag and drop
    uploadZone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    
    uploadZone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    
    uploadZone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            uploadImage(files[0]);
        }
    });
}

async function uploadImage(file) {
    // Validate file type
    if (!file.type.startsWith('image/')) {
        showNotification('Please upload an image file', 'error');
        return;
    }
    
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        showNotification('Image size must be less than 10MB', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'events');
    
    try {
        const response = await fetch(`${ADMIN_API}/upload/image`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            uploadedImagePath = data.data.url;
            $('#previewImage').attr('src', `${UPLOADS_URL + data.data.url}`);
            $('#imagePreview').show();
            $('#imageUploadZone').hide();
            showNotification('Image uploaded successfully', 'success');
        } else {
            showNotification(data.message || 'Failed to upload image', 'error');
        }
    } catch (error) {
        console.error('Error uploading image:', error);
        showNotification('Failed to upload image', 'error');
    }
}

function removeImage() {
    uploadedImagePath = null;
    $('#imagePreview').hide();
    $('#imageUploadZone').show();
    $('#imageInput').val('');
}

// Pagination
function renderPagination(pagination) {
    const container = $('#pagination');
    container.empty();
    
    // Update items display
    $('#startItem').text((pagination.current_page - 1) * pagination.per_page + 1);
    $('#endItem').text(Math.min(pagination.current_page * pagination.per_page, pagination.total));
    $('#totalItems').text(pagination.total);
    
    // Previous button
    if (pagination.has_previous) {
        container.append(`
            <button onclick="goToPage(${pagination.current_page - 1})" 
                    class="px-3 py-1 border rounded hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </button>
        `);
    }
    
    // Page numbers
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        container.append(`
            <button onclick="goToPage(1)" class="px-3 py-1 border rounded hover:bg-gray-50">1</button>
        `);
        if (startPage > 2) {
            container.append(`<span class="px-2">...</span>`);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === pagination.current_page;
        container.append(`
            <button onclick="goToPage(${i})" 
                    class="px-3 py-1 border rounded ${isActive ? 'bg-indigo-600 text-white' : 'hover:bg-gray-50'}">
                ${i}
            </button>
        `);
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            container.append(`<span class="px-2">...</span>`);
        }
        container.append(`
            <button onclick="goToPage(${pagination.total_pages})" 
                    class="px-3 py-1 border rounded hover:bg-gray-50">
                ${pagination.total_pages}
            </button>
        `);
    }
    
    // Next button
    if (pagination.has_next) {
        container.append(`
            <button onclick="goToPage(${pagination.current_page + 1})" 
                    class="px-3 py-1 border rounded hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        `);
    }
}

function goToPage(page) {
    currentPage = page;
    loadEvents();
}

// Utility functions
function renderStatusBadge(status) {
    const statusStyles = {
        published: 'bg-green-100 text-green-800',
        draft: 'bg-gray-100 text-gray-800',
        pending: 'bg-orange-100 text-orange-800',
        cancelled: 'bg-red-100 text-red-800',
        completed: 'bg-blue-100 text-blue-800',
        rejected: 'bg-red-100 text-red-800'
    };
    
    return `<span class="status-badge ${statusStyles[status] || 'bg-gray-100 text-gray-800'}">${status}</span>`;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) return 'today';
    if (diffDays === 1) return 'yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
    return `${Math.floor(diffDays / 365)} years ago`;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type = 'success') {
    const notification = $(`
        <div class="fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            type === 'warning' ? 'bg-orange-500' : 'bg-blue-500'
        } text-white">
            <div class="flex items-center">
                <i class="fas ${
                    type === 'success' ? 'fa-check-circle' : 
                    type === 'error' ? 'fa-exclamation-circle' : 
                    type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'
                } mr-2"></i>
                <span>${message}</span>
            </div>
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.fadeOut(() => notification.remove());
    }, 5000);
}

function closeEventModal() {
    $('#eventModal').addClass('hidden');
}

// Save event
async function saveEvent() {
    try {
        // Clear validation errors first
        $('.border-red-500').removeClass('border-red-500');
        
        const formData = new FormData($('#eventForm')[0]);
        
        // Add tags
        formData.append('tags', JSON.stringify(selectedTags));
        
        // Add featured image path
        if (uploadedImagePath) {
            formData.append('featured_image', uploadedImagePath);
        }
        
        // Set is_online based on event type
        const eventType = $('#eventType').val();
        formData.append('is_online', eventType === 'online' || eventType === 'hybrid');
        
        // Convert checkbox values
        formData.set('is_free', $('#isFree').is(':checked') ? '1' : '0');
        formData.set('registration_required', $('#registrationRequired').is(':checked') ? '1' : '0');
        formData.set('is_featured', $('#isFeatured').is(':checked') ? '1' : '0');
        
        // Handle max_capacity for free events
        if ($('#isFree').is(':checked')) {
            formData.set('max_capacity', $('#maxCapacity').val() || '0');
        }
        
        const tagNames = selectedTags.map(tag => tag.name);
        formData.append('tags', JSON.stringify(tagNames));

        // Set status
        formData.append('status', $('input[name="save_as_draft"]').is(':checked') ? 'draft' : 'pending');
        
        const url = currentEventId 
            ? `${ADMIN_API}/events/${currentEventId}` 
            : `${ADMIN_API}/events`;
        
        const method = currentEventId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(currentEventId ? 'Event updated successfully' : 'Event created successfully', 'success');
            closeEventModal();
            loadEvents();
            loadStatistics();
        } else {
            if (data.errors) {
                // Create a formatted message with line breaks
                const errorMessages = Object.values(data.errors);
                const formattedMessage = `<div class="text-left">
                    <p class="font-semibold">${data.message || 'Validation errors'}:</p>
                    <ul class="list-disc pl-5 mt-1">
                        ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                    </ul>
                </div>`;
                
                // Show as HTML notification
                showNotification(formattedMessage, 'error', 6000, true); // true for HTML content
                
                // Highlight problematic fields
                Object.keys(data.errors).forEach(field => {
                    const $field = $(`#${field}, [name="${field}"]`);
                    $field.addClass('border-red-500');
                    
                    // Remove highlight when user fixes the field
                    $field.one('input change', () => {
                        $field.removeClass('border-red-500');
                    });
                });
            } else {
                showNotification(data.message || 'Failed to save event', 'error');
            }
        }
    } catch (error) {
        console.error('Error saving event:', error);
        showNotification('Failed to save event', 'error');
    }
}

// Delete event
async function deleteEvent(id) {
    if (!confirm('Are you sure you want to delete this event?')) return;
    
    try {
        const response = await fetch(`${ADMIN_API}/events/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Event deleted successfully', 'success');
            loadEvents();
            loadStatistics();
        } else {
            showNotification(data.message || 'Failed to delete event', 'error');
        }
    } catch (error) {
        console.error('Error deleting event:', error);
        showNotification('Failed to delete event', 'error');
    }
}

// View event details
async function viewEvent(id) {
    try {
        const response = await fetch(`${ADMIN_API}/events/${id}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });
        
        if (!response.ok) throw new Error('Failed to load event');
        
        const data = await response.json();
        
        if (data.success) {
            const event = data.data;
            
            const content = `
                <div class="space-y-6">
                    ${event.featured_image ? `
                        <img src="${UPLOADS_URL + event.featured_image}" alt="${escapeHtml(event.title)}" 
                             class="w-full h-64 object-cover rounded-lg">
                    ` : ''}
                    
                    <div>
                        <h2 class="text-2xl font-bold mb-2">${escapeHtml(event.title)}</h2>
                        <div class="flex items-center space-x-4 text-sm text-gray-600 mb-4">
                            <span>${renderStatusBadge(event.status)}</span>
                            ${event.is_featured ? '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Featured</span>' : ''}
                            ${event.is_online ? '<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">Online Event</span>' : ''}
                            ${event.is_free ? '<span class="bg-green-100 text-green-800 px-2 py-1 rounded">Free</span>' : ''}
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Event Details</h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Date:</dt>
                                    <dd>${formatDate(event.start_date)} ${event.end_date ? `- ${formatDate(event.end_date)}` : ''}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Time:</dt>
                                    <dd>${event.start_time ? formatTime(event.start_time) : 'All day'} ${event.end_time ? `- ${formatTime(event.end_time)}` : ''}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Category:</dt>
                                    <dd>${event.category_name || 'Uncategorized'}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Created By:</dt>
                                    <dd>${escapeHtml(event.organizer_name || 'Unknown')}</dd>
                                </div>

                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Event Organizer:</dt>
                                    <dd>${escapeHtml(event.actual_organizer_name || 'Unknown')}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Event Organizer Address:</dt>
                                    <dd>${escapeHtml(event.actual_organizer_address || '')}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Event Organizer Email:</dt>
                                    <dd>${escapeHtml(event.actual_organizer_email || '')}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Statistics</h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Views:</dt>
                                    <dd>${event.view_count || 0}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Likes:</dt>
                                    <dd>${event.like_count || 0}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Comments:</dt>
                                    <dd>${event.comment_count || 0}</dd>
                                </div>
                                <div class="flex">
                                    <dt class="font-medium text-gray-600 w-32">Attendees:</dt>
                                    <dd>${event.current_attendees || 0} ${event.max_capacity ? `/ ${event.max_capacity}` : ''}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    ${event.venue_name || event.venue_address ? `
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Location</h4>
                            <p class="text-sm">
                                ${event.venue_name ? `<strong>${escapeHtml(event.venue_name)}</strong><br>` : ''}
                                ${event.venue_address ? `${escapeHtml(event.venue_address)}<br>` : ''}
                                ${event.venue_city ? `${escapeHtml(event.venue_city)}, ` : ''}
                                ${event.venue_state ? `${escapeHtml(event.venue_state)} ` : ''}
                                ${event.venue_postal_code ? escapeHtml(event.venue_postal_code) : ''}
                                ${event.venue_country ? `<br>${escapeHtml(event.venue_country)}` : ''}
                            </p>
                        </div>
                    ` : ''}
                    
                    ${event.online_platform || event.online_link ? `
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Online Access</h4>
                            <p class="text-sm">
                                ${event.online_platform ? `Platform: ${escapeHtml(event.online_platform)}<br>` : ''}
                                ${event.online_link ? `Link: <a href="${escapeHtml(event.online_link)}" target="_blank" class="text-indigo-600 hover:underline">${escapeHtml(event.online_link)}</a>` : ''}
                            </p>
                        </div>
                    ` : ''}
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Description</h4>
                        <p class="text-sm text-gray-600">${escapeHtml(event.description)}</p>
                    </div>
                    
                    ${event.content ? `
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Full Details</h4>
                            <div class="prose max-w-none text-sm">${event.content}</div>
                        </div>
                    ` : ''}
                    
                    ${event.tags && event.tags.length > 0 ? `
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Tags</h4>
                            <div class="flex flex-wrap gap-2">
                                ${event.tags.map(tag => `
                                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm">
                                        ${escapeHtml(tag.name)}
                                    </span>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="pt-4 border-t flex justify-end space-x-3">
                        <button onclick="editEvent(${event.id})" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-edit mr-2"></i>Edit Event
                        </button>
                        ${event.status === 'pending' ? `
                            <button onclick="openModerationModal(${event.id})" 
                                    class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                                <i class="fas fa-shield-alt mr-2"></i>Moderate
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;

            $('#eventDetailsContent').html(content);
            $('#eventDetailsModal').removeClass('hidden');
            
        }
    } catch (error) {
        console.error('Error loading event:', error);
        showNotification('Failed to load event', 'error');
    }
}
