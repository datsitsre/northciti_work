<?php

    // admin/src/Views/media/edit.php - Edit Media View

    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }

    require_once __DIR__ . '/../../Helpers/ContentViewHelper.php';

?>

<!-- Header -->
<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($title) ?></h1>
            </div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="<?= Router::url('media') ?>" class="text-gray-500 hover:text-gray-700 transition-colors">
                            Media Library
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li>
                        <a href="<?= Router::url('media/show?id=' . $media['id']) ?>" class="text-gray-500 hover:text-gray-700 transition-colors">
                            Details
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="text-gray-900 font-medium">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Edit Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Media Details</h3>
                </div>
                
                <form method="POST" id="editForm" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <!-- Form Fields -->
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                                    Alt Text
                                </label>
                                <input type="text" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                       id="alt_text" 
                                       name="alt_text"
                                       value="<?= htmlspecialchars($media['alt_text'] ?? '') ?>"
                                       placeholder="Describe the content">
                                <p class="mt-1 text-xs text-gray-500">
                                    Important for accessibility and SEO
                                </p>
                            </div>
                            
                            <div>
                                <label for="caption" class="block text-sm font-medium text-gray-700 mb-2">
                                    Caption
                                </label>
                                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-vertical" 
                                          id="caption" 
                                          name="caption" 
                                          rows="3"
                                          placeholder="Optional caption"><?= htmlspecialchars($media['caption'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Public Access Toggle -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" 
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" 
                                           id="is_public" 
                                           name="is_public"
                                           <?= ($media['is_public'] ?? false) ? 'checked' : '' ?>>
                                </div>
                                <div class="ml-3">
                                    <label for="is_public" class="text-sm font-medium text-gray-700">
                                        Make file publicly accessible
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Public files can be accessed by anyone with the link
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200">
                        <a href="<?= Router::url('media/show?id=' . $media['id']) ?>" 
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancel
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- File Preview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">File Preview</h3>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <?php if ($media['file_type'] === 'image'): ?>
                            <div class="relative group">
                                <img src="<?= _getMediaUrl($media['file_path']) ?>" 
                                     alt="<?= htmlspecialchars($media['alt_text'] ?? $media['original_filename']) ?>"
                                     class="w-full rounded-lg shadow-sm border border-gray-200">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-200 rounded-lg"></div>
                            </div>
                        <?php else: ?>
                            <div class="py-12 px-6 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <div class="text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="currentColor" viewBox="0 0 20 20">
                                        <?= _getFileTypeIcon($media['mime_type']) ?>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-900 mb-1"><?= htmlspecialchars($media['original_filename']) ?></p>
                                    <p class="text-xs text-gray-500"><?= strtoupper(pathinfo($media['original_filename'], PATHINFO_EXTENSION)) ?> File</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- File Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">File Information</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Filename</dt>
                            <dd class="text-sm text-gray-900 text-right max-w-[60%] break-words"><?= htmlspecialchars($media['original_filename']) ?></dd>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">File Type</dt>
                            <dd class="text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= htmlspecialchars($media['mime_type']) ?>
                                </span>
                            </dd>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">File Size</dt>
                            <dd class="text-sm font-semibold text-gray-900"><?= _formatBytes($media['file_size']) ?></dd>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Uploaded</dt>
                            <dd class="text-sm text-gray-900"><?= _formatDate($media['created_at']) ?></dd>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Downloads</dt>
                            <dd class="text-sm font-semibold text-gray-900">
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <?= number_format($media['download_count'] ?? 0) ?>
                                </span>
                            </dd>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm">
                                <?php
                                $status = $media['status'] ?? 'approved';
                                $statusClasses = [
                                    'approved' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'flagged' => 'bg-red-100 text-red-800'
                                ];
                                $statusClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                    <?= strtoupper($status) ?>
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    <button type="button" 
                            onclick="downloadMedia(<?= $media['id'] ?>)"
                            class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download File
                    </button>
                    
                    <button type="button" 
                            onclick="copyToClipboard('<?= _getMediaUrl($media['file_path']) ?>')"
                            class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Copy URL
                    </button>
                    
                    <?php if (($media['status'] ?? 'approved') === 'pending'): ?>
                        <div class="flex space-x-2">
                            <button type="button" 
                                    onclick="moderateMedia(<?= $media['id'] ?>, 'approve')"
                                    class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Approve
                            </button>
                            <button type="button" 
                                    onclick="moderateMedia(<?= $media['id'] ?>, 'reject')"
                                    class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Reject
                            </button>
                        </div>
                    <?php else: ?>
                        <button type="button" 
                                onclick="deleteMedia(<?= $media['id'] ?>)"
                                class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete File
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Form submit handler
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const $submitBtn = $(this).find('button[type="submit"]');
            const originalText = $submitBtn.html();
            
            // Show loading state
            $submitBtn.prop('disabled', true).html(
                '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                '<path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                '</svg>Saving...'
            );
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('Media details updated successfully!', 'success');
                        setTimeout(() => {
                            window.location.href = '<?= Router::url('media/show?id=' . $media['id']) ?>';
                        }, 1500);
                    } else {
                        showAlert(response.message || 'Failed to update media details', 'error');
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr) {
                    let message = 'Failed to update media details';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        message = response.message || message;
                    } catch (e) {}
                    
                    showAlert(message, 'error');
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });
    });

    function downloadMedia(id) {
        window.open('<?= Router::url('media/download') ?>?id=' + id, '_blank');
    }

    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showAlert('URL copied to clipboard!', 'success');
            }).catch(() => {
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showAlert('URL copied to clipboard!', 'success');
        } catch (err) {
            showAlert('Failed to copy URL', 'error');
        }
        
        document.body.removeChild(textArea);
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
                setTimeout(() => {
                    location.reload();
                }, 1500);
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
                setTimeout(() => {
                    window.location.href = '<?= Router::url('media') ?>';
                }, 1500);
            } else {
                showAlert(response.message, 'error');
            }
        }).fail(function() {
            showAlert('Delete failed', 'error');
        });
    }

    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200';
        const iconClass = type === 'success' ? 'text-green-400' : 'text-red-400';
        const iconPath = type === 'success' 
            ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' 
            : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z';
        
        const alert = $(`
            <div class="fixed top-4 right-4 max-w-sm w-full bg-white border ${alertClass} rounded-lg shadow-lg z-50 transform transition-all duration-300 ease-in-out">
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
        
        // Trigger entry animation
        setTimeout(() => {
            alert.addClass('translate-x-0').removeClass('translate-x-full');
        }, 100);
        
        setTimeout(function() {
            alert.addClass('translate-x-full').removeClass('translate-x-0');
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    }
</script>