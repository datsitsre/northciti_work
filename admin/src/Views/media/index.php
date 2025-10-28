<?php
    // admin/src/Views/media/index.php - Media Library Main View

    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }

    require_once __DIR__ . '/../../Helpers/ContentViewHelper.php';
?>

<!-- Responsive Header -->
<div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 mb-3">
    <div class="max-w-1xl mx-auto px-3 sm:px-4 lg:px-8">
        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between sm:py-6">
            <div class="order-1 sm:order-none">
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl"><?= htmlspecialchars($title) ?></h1>
            </div>
            
            <div class="order-2 sm:order-none flex flex-wrap gap-2 sm:flex-nowrap sm:space-x-3">
                <!-- Upload Button -->
                <a href="<?= Router::url('media/upload') ?>" class="w-full md:w-40 inline-flex items-center px-3 py-1.5 text-xs bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors sm:px-4 sm:py-2 sm:text-sm">
                    <svg class="w-3 h-3 mr-1 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span class="whitespace-nowrap">Upload</span>
                </a>
                
                <!-- Moderation Queue Button (Conditional) -->
                <?php if ($has_api_access): ?>
                <a href="<?= Router::url('moderation') ?>" class="w-full md:w-40 inline-flex items-center px-3 py-1.5 text-xs bg-yellow-500 text-white font-medium rounded-lg hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors sm:px-4 sm:py-2 sm:text-sm">
                    <i class="fas fa-flag text-xs sm:text-sm sm:px-1"></i>
                    <span class="whitespace-nowrap ml-1 sm:ml-0">Moderation</span>
                    <?php if (isset($moderation_stats['flagged']) && $moderation_stats['flagged'] > 0): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-xs font-medium rounded-full bg-white text-yellow-800 sm:ml-2"><?= $moderation_stats['flagged'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                
                <!-- Bulk Actions Button -->
                <button type="button" class="w-full md:w-40 inline-flex items-center px-3 py-1.5 text-xs bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed sm:px-4 sm:py-2 sm:text-sm" id="bulkActionsBtn" disabled>
                    <svg class="w-3 h-3 mr-1 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="whitespace-nowrap">Bulk</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Moderation Alert Section - Place this after the header, before statistics cards -->
<?php if (isset($stats['statistics'])): ?>
    <?= _formatModerationAlert($stats['statistics']) ?>
    
    <!-- Additional detailed moderation overview -->
    <?php 
    $modSummary = _getModerationSummary($stats['statistics']);
    if ($modSummary['needs_attention'] > 0): 
    ?>
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 mt-4 mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Moderation Overview</h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= $modSummary['needs_attention'] ?> items need attention
                </span>
            </div>
        </div>
        <div class="p-6">
            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="flex items-center justify-between text-sm font-medium text-gray-700 mb-2">
                    <span>Content Approval Progress</span>
                    <span><?= $modSummary['approval_rate'] ?>% approved</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-gradient-to-r from-green-400 to-green-500 h-3 rounded-full transition-all duration-500" 
                         style="width: <?= $modSummary['approval_rate'] ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span><?= $modSummary['approved'] ?> approved</span>
                    <span><?= $modSummary['total'] ?> total files</span>
                </div>
            </div>
            
            <!-- Action Items -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Pending Review -->
                <?php if ($modSummary['pending'] > 0): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-yellow-800">Pending Review</p>
                                <p class="text-xl font-bold text-yellow-900"><?= $modSummary['pending'] ?></p>
                            </div>
                        </div>
                        <a href="<?= Router::url('media') ?>?status=pending" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-yellow-700 bg-yellow-100 rounded-lg hover:bg-yellow-200 transition-colors">
                            Review
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Flagged Content -->
                <?php if ($modSummary['flagged'] > 0): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 2H21l-3 6 3 6h-8.5l-1-2H5a2 2 0 00-2 2zm9-13.5V9"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-red-800">Flagged Content</p>
                                <p class="text-xl font-bold text-red-900"><?= $modSummary['flagged'] ?></p>
                            </div>
                        </div>
                        <a href="<?= Router::url('media') ?>?status=flagged" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 transition-colors">
                            Review
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Approved Content -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-green-800">Approved Content</p>
                                <p class="text-xl font-bold text-green-900"><?= $modSummary['approved'] ?></p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-green-100 rounded-lg">
                            <?= $modSummary['approval_rate'] ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<div class="max-w-1xl mx-auto py-8">
    <!-- Statistics Cards -->
    <!-- Updated Statistics/Summary Cards with Tailwind CSS -->
    <?php if (isset($stats) && count($stats) > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
        <!-- Total Files Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-blue-100 text-xs font-medium uppercase tracking-wide">Total Files</p>
                        <p class="text-xl font-bold text-white mt-2">
                            <?= number_format($stats['statistics']['total_files'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-400/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Images Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-green-100 text-xs font-medium uppercase tracking-wide">Images</p>
                        <p class="text-xl font-bold text-white mt-2">
                            <?= number_format($stats['statistics']['image_count'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 w-12 h-12 bg-green-400/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Documents Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-purple-100 text-xs font-medium uppercase tracking-wide">Documents</p>
                        <p class="text-xl font-bold text-white mt-2">
                            <?= number_format($stats['statistics']['document_count'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 w-12 h-12 bg-purple-400/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Storage Used Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-yellow-100 text-xs font-medium uppercase tracking-wide">Storage Used</p>
                        <p class="text-xl font-bold text-white mt-2 leading-tight">
                            <?= formatBytes($stats['statistics']['total_size'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 w-12 h-12 bg-yellow-400/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Review Card (Moderation) -->
        <div class="relative overflow-hidden bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-red-100 text-xs font-medium uppercase tracking-wide">Pending Review</p>
                        <p class="text-xl font-bold text-white mt-2">
                            <?= number_format($stats['statistics']['pending_count'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 w-12 h-12 bg-red-400/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Downloads Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-indigo-100 text-xs font-medium uppercase tracking-wide">Total Downloads</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?= number_format($stats['statistics']['total_downloads'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 w-12 h-12 bg-indigo-400/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Moderation Status Overview (Additional Cards Row) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Approved Files -->
        <div class="stat-card  group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-600">Approved</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        <?= number_format($stats['statistics']['approved_count'] ?? 0) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Rejected Files -->
        <div class="stat-card  group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-600">Rejected</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        <?= number_format($stats['statistics']['rejected_count'] ?? 0) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Flagged Files -->
        <div class="stat-card  group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 2H21l-3 6 3 6h-8.5l-1-2H5a2 2 0 00-2 2zm9-13.5V9"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-600">Flagged</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        <?= number_format($stats['statistics']['flagged_count'] ?? 0) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-300">
            <div class="space-y-3">
                <h4 class="text-sm font-bold text-gray-900">Quick Actions</h4>
                <div class="space-y-2">
                    <a href="<?= Router::url('media') ?>?status=pending" class="block hover:underline text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Review Pending (<?= $stats['statistics']['pending_count'] ?? 0 ?>)
                    </a>
                    <a href="<?= Router::url('media') ?>?status=flagged" class="block hover:underline text-sm text-yellow-600 hover:text-yellow-800 font-medium">
                        Check Flagged (<?= $stats['statistics']['flagged_count'] ?? 0 ?>)
                    </a>
                    <a href="<?= Router::url('media/upload') ?>" class="block hover:underline text-sm text-green-600 hover:text-green-800 font-medium">
                        Upload New Files
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif ?>

    <!-- Filters -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 mb-8">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
            <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none" onclick="clearFilters()">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Clear Filters
            </button>
        </div>
        <div class="p-6">
            <form method="GET" action="<?= Router::url('media') ?>" id="filterForm">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">File Type</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" onchange="submitFilters()">
                            <option value="">All Types</option>
                            <option value="image" <?= $filters['type'] === 'image' ? 'selected' : '' ?>>Images</option>
                            <option value="video" <?= $filters['type'] === 'video' ? 'selected' : '' ?>>Videos</option>
                            <option value="audio" <?= $filters['type'] === 'audio' ? 'selected' : '' ?>>Audio</option>
                            <option value="document" <?= $filters['type'] === 'document' ? 'selected' : '' ?>>Documents</option>
                            <option value="other" <?= $filters['type'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" onchange="submitFilters()">
                            <option value="">All Status</option>
                            <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="flagged" <?= $filters['status'] === 'flagged' ? 'selected' : '' ?>>Flagged</option>
                        </select>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Uploader</label>
                        <select name="uploader" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" onchange="submitFilters()">
                            <option value="">All Users</option>
                            <option value="contributors" <?= $filters['uploader'] === 'contributors' ? 'selected' : '' ?>>Contributors</option>
                            <option value="admins" <?= $filters['uploader'] === 'admins' ? 'selected' : '' ?>>Admins</option>
                        </select>
                    </div>
                    
                    <div class="space-y-2 lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Search</label>
                        <div class="relative">
                            <input type="text" name="search" class="w-full pl-3 pr-12 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Search by filename, description..." 
                                   value="<?= htmlspecialchars($filters['search']) ?>">
                            <button class="absolute right-2 top-1/2 transform -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600" type="submit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">View</label>
                        <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                            <button type="button" class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 border-r border-gray-300 hover:bg-blue-100 focus:outline-none focus:bg-blue-100 active" onclick="switchView('grid')">
                                <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button type="button" class="flex-1 px-3 py-2 bg-white text-gray-600 hover:bg-gray-50 focus:outline-none focus:bg-gray-50" onclick="switchView('list')">
                                <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Media Grid -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50">
        <div class="p-6">
            <div id="mediaGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (empty($media_items)): ?>
                    <div class="col-span-full">
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4zM9 6v11a1 1 0 102 0V6a1 1 0 10-2 0zm4 0v11a1 1 0 102 0V6a1 1 0 10-2 0z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No media files found</h3>
                            <p class="text-gray-500 mb-6">Try adjusting your filters or upload some files to get started.</p>
                            <a href="<?= Router::url('media/upload') ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Upload Files
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($media_items as $media): ?>
                        <div class="media-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1" data-id="<?= $media['id'] ?>" data-type="<?= $media['file_type'] ?>">
                            <!-- Card Header -->
                            <div class="p-4 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="checkbox" class="media-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" 
                                               id="media_<?= $media['id'] ?>" data-id="<?= $media['id'] ?>">
                                        <label class="ml-2 block text-sm text-gray-900" for="media_<?= $media['id'] ?>"></label>
                                    </div>
                                    <!-- Updated status badge using helper function -->
                                    <?= _renderStatusBadge($media['moderation_status'] ?? 'pending') ?>
                                </div>
                            </div>
                            
                            <!-- Media Preview -->
                            <div class="relative group">
                                <?php if ($media['file_type'] === 'image'): ?>
                                    <img src="<?= _getMediaUrl($media['file_path']) ?>" 
                                         alt="<?= htmlspecialchars($media['alt_text'] ?? $media['original_filename']) ?>"
                                         class="w-full h-32 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-32 bg-gray-50 flex items-center justify-center">
                                        <div class="text-center">
                                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                                <?= _getFileTypeIcon($media['mime_type']) ?>
                                            </svg>
                                            <p class="text-sm text-gray-500 font-medium">
                                                <?= strtoupper(pathinfo($media['original_filename'], PATHINFO_EXTENSION)) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div class="absolute bottom-4 right-4 text-white text-xs font-medium bg-black/20 px-2 py-1 rounded">
                                        <p class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 text-gray-400">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z" />
                                            </svg>
                                            <?= strtoupper(pathinfo($media['original_filename'], PATHINFO_EXTENSION)) ?> • 
                                            <?= formatBytes($media['file_size']) ?>
                                        </p>

                                        <p class="flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span class="font-medium">
                                                <?php if ($media): ?>
                                                    <?= htmlspecialchars($media['first_name'] . ' ' . $media['last_name']) ?>
                                                    <small class="text-muted">(<?= htmlspecialchars($media['username']) ?>)</small>
                                                <?php else: ?>
                                                    <em>Unknown</em>
                                                <?php endif; ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card Body -->
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 truncate mb-2" title="<?= htmlspecialchars($media['original_filename']) ?>">
                                    <?= _truncateText($media['original_filename'], 30) ?>
                                </h3>
                                <div class="text-sm text-gray-500 space-y-1">
                                    
                                    <p class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <?= _timeAgo($media['created_at']) ?>
                                    </p>
                                    <p class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <?= number_format($media['download_count'] ?? 0) ?> downloads
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Card Footer with Dynamic Moderation Actions -->
                            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                                <div class="flex items-center justify-between space-x-2 overflow-x-auto pb-2">
                                    <!-- Standard Actions -->
                                    <div class="flex space-x-1">
                                        <a type="button" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" 
                                                href="<?= Router::url('media/show?id=' . $media['id']) ?>" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <button type="button" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" 
                                                onclick="editMedia(<?= $media['id'] ?>)" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" 
                                                onclick="downloadMedia(<?= $media['id'] ?>)" title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Dynamic Moderation Actions -->
                                    <div class="flex space-x-1">
                                        <?php 
                                        $status = $media['moderation_status'] ?? 'pending';
                                        $actions = _getModerationActions($status);
                                        foreach ($actions as $action): 
                                        ?>
                                            <?= _renderModerationButton($action, $media['id']) ?>
                                        <?php endforeach; ?>
                                        
                                        <!-- Delete button (always available for admins) -->
                                        <button type="button" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors" 
                                                onclick="deleteMedia(<?= $media['id'] ?>)" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
                <div class="flex flex-col sm:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-700 mb-4 sm:mb-0">
                        Showing <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?> 
                        to <?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total'])) ?> 
                        of <?= number_format($pagination['total']) ?> entries
                    </div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php if ($pagination['has_previous']): ?>
                            <a href="<?= _buildPaginationUrl($pagination['current_page'] - 1) ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a href="<?= _buildPaginationUrl($i) ?>" 
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium focus:z-10 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 <?= $i === $pagination['current_page'] ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="<?= _buildPaginationUrl($pagination['current_page'] + 1) ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Media Detail Modal -->
<div id="mediaModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Media Details</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none" onclick="closeModal('mediaModal')">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div id="mediaModalBody">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div id="bulkModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Bulk Actions</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none" onclick="closeModal('bulkModal')">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p class="text-gray-600 mb-4">Select an action to perform on <span id="selectedCount" class="font-semibold">0</span> selected items:</p>
                <div class="space-y-2">
                    <button type="button" class="w-full flex items-center px-4 py-3 text-left text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="executeBulkAction('approve')">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Approve Selected
                    </button>
                    <button type="button" class="w-full flex items-center px-4 py-3 text-left text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="executeBulkAction('reject')">
                        <svg class="w-5 h-5 mr-3 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                        </svg>
                        Reject Selected
                    </button>
                    <button type="button" class="w-full flex items-center px-4 py-3 text-left text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="executeBulkAction('delete')">
                        <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    $(document).ready(function() {
        initializeMediaLibrary();
    });

    function initializeMediaLibrary() {
        // Initialize checkbox handling
        $('.media-checkbox').on('change', function() {
            updateBulkActions();
            updateSelectedCards();
        });
        
        // Update bulk actions button state
        updateBulkActions();
    }

    function updateBulkActions() {
        const selected = $('.media-checkbox:checked').length;
        const $bulkBtn = $('#bulkActionsBtn');
        
        if (selected > 0) {
            $bulkBtn.prop('disabled', false)
                    .html('<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Selected (' + selected + ')');
        } else {
            $bulkBtn.prop('disabled', true)
                    .html('<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Bulk Actions');
        }
    }

    function updateSelectedCards() {
        $('.media-card').removeClass('ring-2 ring-blue-500 ring-opacity-50');
        $('.media-checkbox:checked').each(function() {
            $(this).closest('.media-card').addClass('ring-2 ring-blue-500 ring-opacity-50');
        });
    }

    function submitFilters() {
        $('#filterForm').submit();
    }

    function clearFilters() {
        window.location.href = '<?= Router::url('media') ?>';
    }

    function switchView(view) {
        const $gridBtn = $('.flex.rounded-lg button:first');
        const $listBtn = $('.flex.rounded-lg button:last');
        
        if (view === 'grid') {
            $gridBtn.removeClass('bg-white text-gray-600').addClass('bg-blue-50 text-blue-600');
            $listBtn.removeClass('bg-blue-50 text-blue-600').addClass('bg-white text-gray-600');
            $('#mediaGrid').removeClass('space-y-4').addClass('grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6');
        } else {
            $listBtn.removeClass('bg-white text-gray-600').addClass('bg-blue-50 text-blue-600');
            $gridBtn.removeClass('bg-blue-50 text-blue-600').addClass('bg-white text-gray-600');
            $('#mediaGrid').removeClass('grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6').addClass('space-y-4');
        }
    }

    function editMedia(id) {
        window.location.href = '<?= Router::url('media/edit') ?>?id=' + id;
    }

    function moderateMedia(id, action) {
        const actionText = action === 'approve' ? 'approve' : 'reject';
        
        if (!confirm('Are you sure you want to ' + actionText + ' this media file?')) {
            return;
        }
        
        $.post('<?= Router::url('media/moderate') ?>', {
            id: id,
            action: action,
            csrf_token: '<?= generateCSRFToken() ?>'
        }).done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                location.reload();
            } else {
                showAlert(response.message, 'error');
            }
        }).fail(function() {
            showAlert('Moderation action failed', 'error');
        });
    }

    function deleteMedia(id) {
        if (!confirm('Are you sure you want to delete this media file? This action cannot be undone.')) {
            return;
        }
        
        $.post('<?= Router::url('media/delete') ?>', {
            id: id,
            csrf_token: '<?= generateCSRFToken() ?>'
        }).done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('[data-id="' + id + '"]').fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                showAlert(response.message, 'error');
            }
        }).fail(function() {
            showAlert('Delete failed', 'error');
        });
    }

    $('#bulkActionsBtn').on('click', function() {
        const selected = $('.media-checkbox:checked').length;
        if (selected > 0) {
            $('#selectedCount').text(selected);
            showModal('bulkModal');
        }
    });

    function executeBulkAction(action) {
        const ids = [];
        $('.media-checkbox:checked').each(function() {
            ids.push($(this).data('id'));
        });
        
        if (ids.length === 0) {
            return;
        }
        
        const actionText = action === 'delete' ? 'delete' : action;
        const confirmText = 'Are you sure you want to ' + actionText + ' ' + ids.length + ' selected items?';
        
        if (!confirm(confirmText)) {
            return;
        }
        
        $.post('<?= Router::url('media/bulk-action') ?>', {
            ids: ids,
            action: action,
            csrf_token: '<?= generateCSRFToken() ?>'
        }).done(function(response) {
            closeModal('bulkModal');
            
            if (response.success) {
                showAlert(response.message, 'success');
                location.reload();
            } else {
                showAlert(response.message, 'error');
            }
        }).fail(function() {
            showAlert('Bulk action failed', 'error');
        });
    }

    function submitModeration() {
        const formData = new FormData(document.getElementById('moderationForm'));
        const mediaId = formData.get('media_id');
        const action = formData.get('action');
        const reason = formData.get('reason');

        fetch(`/api/admin/media/moderation/${mediaId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: action,
                reason: reason,
                csrf_token: '<?= generateCSRFToken() ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#moderationModal').modal('hide');
                location.reload(); // Refresh to see changes
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        document.body.style.overflow = '';
    }

    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200';
        const iconClass = type === 'success' ? 'text-green-400' : 'text-red-400';
        const iconPath = type === 'success' 
            ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' 
            : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z';
        
        const alert = $(`
            <div class="fixed top-4 right-4 max-w-sm w-full bg-white border ${alertClass} rounded-lg shadow-lg z-50">
                <div class="p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 ${iconClass}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="${iconPath}" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">${message}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button type="button" class="inline-flex bg-white rounded-md p-1.5 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" onclick="$(this).closest('.fixed').remove()">
                                    <span class="sr-only">Dismiss</span>
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(alert);
        
        setTimeout(function() {
            alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

</script>

<script>
    // Enhanced download function with better error handling
    function downloadMedia(id) {
        // Show loading state
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
        button.disabled = true;
        
        // Method 1: Direct download via window.location (simplest)
        try {
            const downloadUrl = '<?= Router::url('media/download') ?>?id=' + id;
            window.location.href = downloadUrl;
            
            // Show success message after a brief delay
            setTimeout(() => {
                showAlert('Download started', 'success');
            }, 500);
            
        } catch (error) {
            console.error('Download error:', error);
            showAlert('Download failed. Please try again.', 'error');
        } finally {
            // Restore button state after delay
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.disabled = false;
            }, 1000);
        }
    }

    // Alternative method using hidden iframe (prevents page navigation)
    function downloadMediaIframe(id) {
        const downloadUrl = '<?= Router::url('media/download') ?>?id=' + id;
        
        // Create hidden iframe
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = downloadUrl;
        
        // Handle load/error events
        iframe.onload = function() {
            showAlert('Download started', 'success');
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 2000);
        };
        
        iframe.onerror = function() {
            showAlert('Download failed. Please try again.', 'error');
            document.body.removeChild(iframe);
        };
        
        document.body.appendChild(iframe);
    }

    // Method using fetch with blob download (for API endpoints)
    function downloadMediaFetch(id) {
        const downloadUrl = '<?= Router::url('media/download') ?>?id=' + id;
        
        // Show loading indicator
        showAlert('Preparing download...', 'info');
        
        fetch(downloadUrl, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Get filename from Content-Disposition header
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = 'download';
            
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
                if (filenameMatch) {
                    filename = filenameMatch[1];
                }
            }
            
            return response.blob().then(blob => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.style.display = 'none';
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Cleanup
            window.URL.revokeObjectURL(url);
            
            showAlert('Download completed', 'success');
        })
        .catch(error => {
            console.error('Download error:', error);
            showAlert('Download failed: ' . error.message, 'error');
        });
    }

    // Bulk download function
    function downloadSelectedFiles() {
        const selectedIds = [];
        $('.media-checkbox:checked').each(function() {
            selectedIds.push($(this).data('id'));
        });
        
        if (selectedIds.length === 0) {
            showAlert('No files selected for download', 'error');
            return;
        }
        
        if (selectedIds.length > 10) {
            if (!confirm(`You are about to download ${selectedIds.length} files. This may take a while. Continue?`)) {
                return;
            }
        }
        
        // Download files one by one with small delays
        let downloaded = 0;
        let failed = 0;
        
        showAlert(`Starting download of ${selectedIds.length} files...`, 'info');
        
        selectedIds.forEach((id, index) => {
            setTimeout(() => {
                try {
                    downloadMediaIframe(id);
                    downloaded++;
                } catch (error) {
                    console.error(`Failed to download file ${id}:`, error);
                    failed++;
                }
                
                // Show final result after last file
                if (index === selectedIds.length - 1) {
                    setTimeout(() => {
                        const message = `Download complete: ${downloaded} successful${failed > 0 ? `, ${failed} failed` : ''}`;
                        showAlert(message, failed === 0 ? 'success' : 'warning');
                    }, 1000);
                }
            }, index * 500); // 500ms delay between downloads
        });
    }

    // Add download selected button to bulk actions
    function addDownloadToBulkActions() {
        const bulkModal = document.getElementById('bulkModal');
        const actionsContainer = bulkModal.querySelector('.space-y-2');
        
        const downloadButton = document.createElement('button');
        downloadButton.type = 'button';
        downloadButton.className = 'w-full flex items-center px-4 py-3 text-left text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
        downloadButton.onclick = function() {
            closeModal('bulkModal');
            downloadSelectedFiles();
        };
        
        downloadButton.innerHTML = `
            <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download Selected
        `;
        
        // Insert before the delete button
        const deleteButton = actionsContainer.querySelector('button:last-child');
        actionsContainer.insertBefore(downloadButton, deleteButton);
    }

    // Initialize download functionality when page loads
    $(document).ready(function() {
        addDownloadToBulkActions();
        
        // Add keyboard shortcut for download (Ctrl+D on selected items)
        $(document).keydown(function(e) {
            if (e.ctrlKey && e.which === 68) { // Ctrl+D
                e.preventDefault();
                const selected = $('.media-checkbox:checked');
                if (selected.length > 0) {
                    downloadSelectedFiles();
                }
            }
        });
    });
</script>