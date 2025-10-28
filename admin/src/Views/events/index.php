<?php
    // admin/src/Views/media/index.php - Media Library Main View

    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
    /* Improved base styling */
    .event-card {
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    .event-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .event-card.featured {
        border-left-color: #fbbf24;
    }
    .event-card.online {
        border-left-color: #3b82f6;
    }
    .event-card.in-person {
        border-left-color: #10b981;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    /* Improved tab navigation */
    .tab-active {
        border-bottom: 3px solid #6366f1;
        color: #6366f1;
        background-color: #f8fafc;
    }
    
    /* Better calendar styling */
    .calendar-view {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background-color: #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    .calendar-day {
        background-color: white;
        min-height: 100px;
        padding: 0.5rem;
    }
    .calendar-day.other-month {
        background-color: #f9fafb;
        color: #9ca3af;
    }
    .calendar-event {
        font-size: 0.75rem;
        padding: 0.125rem 0.25rem;
        margin: 0.125rem 0;
        border-radius: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
    }
    
    /* Enhanced image upload area */
    .image-upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .image-upload-zone:hover {
        border-color: #6366f1;
        background-color: #f9fafb;
    }
    .image-upload-zone.drag-over {
        border-color: #6366f1;
        background-color: #ede9fe;
    }
    
    /* Mobile responsive improvements */
    @media (max-width: 768px) {
        .flex-col-mobile {
            flex-direction: column;
        }
        .space-y-mobile > * + * {
            margin-top: 1rem;
        }
        .w-full-mobile {
            width: 100%;
        }
        .text-sm-mobile {
            font-size: 0.875rem;
        }
        .hidden-mobile {
            display: none;
        }
        .grid-cols-1-mobile {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .grid-cols-2-mobile {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .px-4-mobile {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .py-2-mobile {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .calendar-day {
            min-height: 80px;
            padding: 0.25rem;
        }
        
        /* Enhanced event card mobile styling */
        .event-card {
            margin: 0.5rem 0;
            padding: 1rem;
        }
        .event-card .flex-body {
            flex-direction: column;
            align-items: flex-start !important;
        }
        .event-card .ml-4 {
            margin-left: 0 !important;
            margin-top: 1rem;
            width: 100%;
        }
        .event-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        
        /* Mobile event card layout fixes */
        .event-card .items-start {
            align-items: flex-start !important;
        }
        .event-card .justify-between {
            justify-content: flex-start !important;
            flex-direction: column;
            gap: 1rem;
        }
        .event-card .space-x-3 {
            margin-left: 0 !important;
        }
        .event-card .space-x-3 > * + * {
            margin-left: 0 !important;
            margin-top: 0.5rem;
        }
        .event-card .space-x-4 {
            margin-left: 0 !important;
            flex-wrap: wrap;
        }
        .event-card .space-x-4 > * + * {
            margin-left: 0 !important;
        }
        .event-card .space-x-2 {
            margin-left: 0 !important;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .event-card .space-x-2 > * + * {
            margin-left: 0 !important;
        }
        
        /* Mobile action buttons */
        .event-card .flex.space-x-2 {
            justify-content: center;
            width: 100%;
            margin-top: 1rem;
        }
        .event-card button {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Mobile organizer info layout */
        .event-card .mr-3 {
            margin-right: 0 !important;
            width: 100%;
            flex-direction: column;
            align-items: flex-start;
        }
        .event-card .mr-3 .space-x-2 {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .event-card .mr-3 .space-x-2 > * + * {
            margin-left: 0 !important;
        }
        .event-card .bg-gray-50 {
            margin-bottom: 0.5rem;
            width: 100%;
            text-align: left;
        }
    }
    
    @media (max-width: 480px) {
        .grid-cols-1-sm {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .text-xs-sm {
            font-size: 0.75rem;
        }
        .p-2-sm {
            padding: 0.5rem;
        }
        .calendar-day {
            min-height: 60px;
            font-size: 0.75rem;
        }
    }
    
    /* Better form styling */
    .form-input {
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .form-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    /* Improved buttons */
    .btn {
        transition: all 0.15s ease-in-out;
    }
    .btn:hover {
        transform: translateY(-1px);
    }
    
    /* Better notifications */
    .notification {
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>

<div class="flex h-screen">
    <!-- Main Content -->
    <div class="flex-1">
        <!-- Header -->
        <header class="bg-white rounded-lg shadow-md border-b">
            <div class="flex flex-col-mobile items-center justify-between px-6 py-4 space-y-mobile">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Events Management</h2>
                    <p class="text-gray-600">Manage and moderate event listings</p>
                </div>
                <button onclick="openCreateEventModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors w-full-mobile">
                    <i class="fas fa-plus mr-2"></i>
                    Create Event
                </button>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 grid-cols-2-mobile grid-cols-1-sm">
                <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 p-2-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 text-xs-sm">Total Events</p>
                            <p class="text-2xl font-bold" id="totalEvents">0</p>
                        </div>
                        <i class="fas fa-calendar text-3xl text-indigo-500 opacity-90"></i>
                    </div>
                </div>
                <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 p-2-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 text-xs-sm">Upcoming</p>
                            <p class="text-2xl font-bold text-green-600" id="upcomingEvents">0</p>
                        </div>
                        <i class="fas fa-clock text-3xl text-green-500 opacity-90"></i>
                    </div>
                </div>
                <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 p-2-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 text-xs-sm">Pending</p>
                            <p class="text-2xl font-bold text-orange-600" id="pendingEvents">0</p>
                        </div>
                        <i class="fas fa-hourglass-half text-3xl text-orange-500 opacity-90"></i>
                    </div>
                </div>
                <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 p-2-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 text-xs-sm">Online Events</p>
                            <p class="text-2xl font-bold text-blue-600" id="onlineEvents">0</p>
                        </div>
                        <i class="fas fa-globe text-3xl text-blue-500 opacity-90"></i>
                    </div>
                </div>
                <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 p-2-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 text-xs-sm">Total Attendees</p>
                            <p class="text-2xl font-bold text-purple-600" id="totalAttendees">0</p>
                        </div>
                        <i class="fas fa-user-friends text-3xl text-purple-500 opacity-90"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and View Toggle -->
        <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl ">
            <div class="flex flex-col-mobile items-center justify-between space-y-mobile">
                <!-- Tabs -->
                <div class="flex space-x-6 px-3">
                    <button class="pb-2 font-medium tab-active text-xs px-4-mobile py-2-mobile" onclick="switchTab('list')">
                        <i class="fas fa-list mr-2"></i><span class="hidden-mobile"><br></span>List
                    </button>
                    <button class="pb-2 font-medium text-gray-600 hover:text-gray-900 text-xs px-4-mobile py-2-mobile" onclick="switchTab('calendar')">
                        <i class="fas fa-calendar mr-2"></i><span class="hidden-mobile"><br></span>Calendar
                    </button>
                    <button class="pb-2 font-medium text-gray-600 hover:text-gray-900 text-xs px-4-mobile py-2-mobile" onclick="switchTab('moderation')">
                        <i class="fas fa-shield-alt mr-2"></i><span class="hidden-mobile"><br></span>Moderation
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex flex-col-mobile items-center space-x-4 space-y-mobile w-full-mobile">
                    <div class="flex flex-wrap gap-2 w-full-mobile">
                        <select id="statusFilter" class="border rounded-lg px-3 py-2 form-input flex-1 min-w-0">
                            <option value="">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                        <select id="typeFilter" class="border rounded-lg px-3 py-2 form-input flex-1 min-w-0">
                            <option value="">All Types</option>
                            <option value="online">Online</option>
                            <option value="in-person">In-Person</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                        <select id="categoryFilter" class="border rounded-lg px-3 py-2 form-input flex-1 min-w-0">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div class="relative w-full-mobile">
                        <input type="text" id="searchInput" placeholder="Search events..." 
                               class="border rounded-lg pl-10 pr-4 py-2 w-64 form-input w-full-mobile">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 py-6 ">
            <!-- List View -->
            <div id="listView" class="space-y-4">
                <!-- Events will be loaded here -->
            <!-- Mobile-optimized event card template will be inserted by JavaScript -->
            </div>

            <!-- Calendar View (Hidden by default) -->
            <div id="calendarView" class="hidden">
                <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl p-4">
                    <div class="flex flex-col-mobile items-center justify-between mb-4 space-y-mobile">
                        <h3 class="text-lg font-semibold">
                            <span id="calendarMonth">December 2024</span>
                        </h3>
                        <div class="flex space-x-2">
                            <button onclick="previousMonth()" class="p-2 hover:bg-gray-100 rounded btn">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button onclick="currentMonth()" class="px-3 py-1 text-sm border rounded hover:bg-gray-50 btn">
                                Today
                            </button>
                            <button onclick="nextMonth()" class="p-2 hover:bg-gray-100 rounded btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="calendar-view overflow-x-auto" id="calendarGrid">
                        <!-- Calendar will be rendered here -->
                    </div>
                </div>
            </div>

            <!-- Moderation Queue (Hidden by default) -->
            <div id="moderationView" class="hidden">
                <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl">
                    <div class="p-4 border-b">
                        <h3 class="text-lg font-semibold">Events Pending Moderation</h3>
                    </div>
                    <div id="moderationQueue" class="divide-y">
                        <!-- Pending events will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex flex-col-mobile items-center justify-between space-y-mobile">
                <div class="text-sm text-gray-600 text-sm-mobile">
                    Showing <span id="startItem">1</span>-<span id="endItem">10</span> of <span id="totalItems">0</span> events
                </div>
                <div class="flex flex-wrap justify-center space-x-2 gap-1" id="pagination">
                    <!-- Pagination buttons will be generated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Event Modal -->
<div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold" id="modalTitle">Create New Event</h3>
            <button onclick="closeEventModal()" class="text-gray-400 hover:text-gray-600 btn">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="eventForm" class="p-6 space-y-6">
            <input type="hidden" id="eventId" value="">
            
            <!-- Basic Information -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700">Basic Information</h4>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Event Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="eventTitle" name="title" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea id="eventDescription" name="description" rows="3" required
                              class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Full Content
                    </label>
                    <textarea id="eventContent" name="content" rows="6"
                              class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 grid-cols-1-mobile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="eventCategory" name="category_id" required
                                class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                            <option value="">Select a category</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Event Type <span class="text-red-500">*</span>
                        </label>
                        <select id="eventType" name="event_type" required onchange="toggleEventTypeFields()"
                                class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                            <option value="">Select type</option>
                            <option value="in-person">In-Person</option>
                            <option value="online">Online</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Organizer Information -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700">Organizer Information</h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 grid-cols-1-mobile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Event Organizer Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="actualOrganizerName" name="actual_organizer_name" required
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Event Organizer Address <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="actualOrganizerAddress" name="actual_organizer_address" required
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Event Organizer Email
                        </label>
                        <input type="text" id="actualOrganizerEmail" name="actual_organizer_email" 
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                </div>
            </div>

            <!-- Date & Time -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700">Date & Time</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 grid-cols-1-mobile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="startDate" name="start_date" required
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Start Time
                        </label>
                        <input type="time" id="startTime" name="start_time"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 grid-cols-1-mobile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            End Date
                        </label>
                        <input type="date" id="endDate" name="end_date"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            End Time
                        </label>
                        <input type="time" id="endTime" name="end_time"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Timezone
                    </label>
                    <select id="timezone" name="timezone"
                            class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">Eastern Time</option>
                        <option value="America/Chicago">Central Time</option>
                        <option value="America/Denver">Mountain Time</option>
                        <option value="America/Los_Angeles">Pacific Time</option>
                        <option value="Europe/London">London</option>
                        <option value="Europe/Paris">Paris</option>
                        <option value="Asia/Tokyo">Tokyo</option>
                    </select>
                </div>
            </div>

            <!-- Location (for in-person events) -->
            <div id="locationSection" class="space-y-4 hidden">
                <h4 class="font-semibold text-gray-700">Location Details</h4>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Venue Name
                    </label>
                    <input type="text" id="venueName" name="venue_name"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Address
                    </label>
                    <input type="text" id="venueAddress" name="venue_address"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 grid-cols-1-mobile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            City
                        </label>
                        <input type="text" id="venueCity" name="venue_city"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            State/Province
                        </label>
                        <input type="text" id="venueState" name="venue_state"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 grid-cols-1-mobile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Country
                        </label>
                        <input type="text" id="venueCountry" name="venue_country"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Postal Code
                        </label>
                        <input type="text" id="venuePostalCode" name="venue_postal_code"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                </div>
            </div>

            <!-- Online Event Details -->
            <div id="onlineSection" class="space-y-4 hidden">
                <h4 class="font-semibold text-gray-700">Online Event Details</h4>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Platform
                    </label>
                    <select id="onlinePlatform" name="online_platform"
                            class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                        <option value="">Select platform</option>
                        <option value="zoom">Zoom</option>
                        <option value="teams">Microsoft Teams</option>
                        <option value="meet">Google Meet</option>
                        <option value="webex">Webex</option>
                        <option value="youtube">YouTube Live</option>
                        <option value="facebook">Facebook Live</option>
                        <option value="custom">Custom Platform</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Event Link
                    </label>
                    <input type="url" id="onlineLink" name="online_link"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Access Password (if required)
                    </label>
                    <input type="text" id="onlinePassword" name="online_password"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                </div>
            </div>

            <!-- Registration & Pricing -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700">Registration & Pricing</h4>
                
                <div class="flex flex-col-mobile items-center space-x-6 space-y-mobile">
                    <label class="flex items-center">
                        <input type="checkbox" id="isFree" name="is_free" class="mr-2" onchange="togglePricing()">
                        <span class="text-sm">Free Event</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="registrationRequired" name="registration_required" class="mr-2">
                        <span class="text-sm">Registration Required</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="isFeatured" name="is_featured" class="mr-2">
                        <span class="text-sm">Featured Event</span>
                    </label>
                </div>

                <div id="pricingSection" class="grid grid-cols-1 md:grid-cols-3 gap-4 grid-cols-1-mobile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Price
                        </label>
                        <input type="number" id="price" name="price" min="0" step="0.01"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Currency
                        </label>
                        <select id="currency" name="currency"
                                class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="CAD">CAD</option>
                            <option value="AUD">AUD</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Max Capacity
                        </label>
                        <input type="number" id="maxCapacity" name="max_capacity" min="1"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                </div>

                <div id="registrationSection" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Registration Deadline
                        </label>
                        <input type="datetime-local" id="registrationDeadline" name="registration_deadline"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Registration Link
                        </label>
                        <input type="url" id="registrationLink" name="registration_link"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    </div>
                </div>
            </div>

            <!-- Featured Image -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700">Featured Image</h4>
                
                <div id="imageUploadZone" class="image-upload-zone">
                    <input type="file" id="imageInput" accept="image/*" class="hidden">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-gray-600">Click to upload or drag and drop</p>
                    <p class="text-sm text-gray-500">PNG, JPG, GIF up to 10MB</p>
                </div>

                <div id="imagePreview" class="hidden">
                    <img id="previewImage" src="" alt="Preview" class="w-full rounded-lg">
                    <button type="button" onclick="removeImage()" class="mt-2 text-red-600 hover:text-red-800 btn">
                        <i class="fas fa-trash mr-1"></i> Remove Image
                    </button>
                </div>
            </div>

            <!-- Tags -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700">Tags</h4>
                <div class="flex flex-wrap gap-2" id="tagsList">
                    <!-- Tags will be displayed here -->
                </div>
                <div class="flex flex-col-mobile gap-2">
                    <input type="text" id="tagInput" placeholder="Add a tag and press Enter"
                           class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    <button type="button" onclick="addTag()" class="bg-gray-100 px-4 rounded-lg hover:bg-gray-200 btn w-full-mobile">
                        <i class="fas fa-plus"></i> Add Tag
                    </button>
                </div>
            </div>

            <!-- SEO Settings -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-700">SEO Settings</h4>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Meta Title
                    </label>
                    <input type="text" id="metaTitle" name="meta_title"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to use event title</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Meta Description
                    </label>
                    <textarea id="metaDescription" name="meta_description" rows="2"
                              class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Leave blank to use event description</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col-mobile items-center justify-between pt-4 border-t space-y-mobile">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="save_as_draft" class="mr-2">
                        <span class="text-sm">Save as Draft</span>
                    </label>
                </div>
                <div class="flex flex-col-mobile space-x-3 space-y-mobile w-full-mobile">
                    <button type="button" onclick="closeEventModal()" 
                            class="px-4 py-2 border rounded-lg hover:bg-gray-50 btn w-full-mobile">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 btn w-full-mobile">
                        <i class="fas fa-save mr-2"></i>
                        <span id="submitBtnText">Create Event</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold">Event Details</h3>
            <button onclick="closeEventDetailsModal()" class="text-gray-400 hover:text-gray-600 btn">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="eventDetailsContent" class="p-6">
            <!-- Event details will be loaded here -->
        </div>
    </div>
</div>

<!-- Moderation Modal -->
<div id="moderationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="border-b px-6 py-4">
            <h3 class="text-xl font-semibold">Moderate Event</h3>
        </div>
        <div class="p-6">
            <div id="moderationContent">
                <!-- Moderation content will be loaded here -->
            </div>
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Moderation Notes
                </label>
                <textarea id="moderationNotes" rows="3" 
                          class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 form-input"
                          placeholder="Add notes about your moderation decision..."></textarea>
            </div>
            <div class="mt-6 flex flex-col-mobile justify-end space-x-3 space-y-mobile">
                <button onclick="closeModerationModal()" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50 btn w-full-mobile">
                    Cancel
                </button>
                <button onclick="rejectEvent()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 btn w-full-mobile">
                    <i class="fas fa-times mr-2"></i>Reject
                </button>
                <button onclick="approveEvent()" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 btn w-full-mobile">
                    <i class="fas fa-check mr-2"></i>Approve
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function getAuthToken() {
        return '<?= $_SESSION['auth_token'] ?>' || localStorage.getItem('auth_token');
    }
</script>

<!-- Include events JavaScript -->
<script src="<?= ADMIN_APP_URL ?>/assets/js/events.js"></script>