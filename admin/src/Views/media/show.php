<?php

    // admin/src/Views/media/show.php - Media Details View (for AJAX)

    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }

    require_once __DIR__ . '/../../Helpers/ContentViewHelper.php';

?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Media Preview -->
    <div class="space-y-4">
        <div class="text-center">
            <?php if ($media['file_type'] === 'image'): ?>
                <div class="relative group">
                    <img src="<?= _getMediaUrl($media['file_path']) ?>" 
                         alt="<?= htmlspecialchars($media['alt_text'] ?? $media['original_filename']) ?>"
                         class="w-full rounded-lg shadow-sm border border-gray-200 max-h-80 object-contain bg-gray-50">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-5 transition-all duration-200 rounded-lg"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Click image to view full size</p>
            <?php else: ?>
                <div class="py-16 px-6 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <div class="text-center">
                        <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <?= _getFileTypeIcon($media['mime_type']) ?>
                        </svg>
                        <p class="text-lg font-medium text-gray-900 mb-1"><?= htmlspecialchars($media['original_filename']) ?></p>
                        <p class="text-sm text-gray-500"><?= strtoupper(pathinfo($media['original_filename'], PATHINFO_EXTENSION)) ?> File</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div class="grid grid-cols-3 gap-2">
            <a href="<?= Router::url('media/download?id=' . $media['id']) ?>" 
               target="_blank"
               class="inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download
            </a>
            <a href="<?= Router::url('media/edit?id=' . $media['id']) ?>" 
               class="inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <button type="button" 
                    onclick="deleteMediaFromModal(<?= $media['id'] ?>)"
                    class="inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
            </button>
        </div>
    </div>
    
    <!-- File Information -->
    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">File Information</h3>
            <dl class="space-y-3">
                <div class="flex justify-between items-start">
                    <dt class="text-sm font-medium text-gray-500">Filename</dt>
                    <dd class="text-sm text-gray-900 text-right max-w-[60%] break-words font-medium"><?= htmlspecialchars($media['original_filename']) ?></dd>
                </div>
                
                <div class="flex justify-between items-start">
                    <dt class="text-sm font-medium text-gray-500">File Type</dt>
                    <dd class="text-sm">
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
                    <dt class="text-sm font-medium text-gray-500">Uploader</dt>
                    <dd class="text-sm text-gray-900">
                        <?php if ($uploader): ?>
                            <span class="font-medium"><?= htmlspecialchars($uploader['first_name'] . ' ' . $uploader['last_name']) ?></span>
                            <span class="text-gray-500 text-xs ml-1">(<?= htmlspecialchars($uploader['username']) ?>)</span>
                        <?php else: ?>
                            <em class="text-gray-500">Unknown</em>
                        <?php endif; ?>
                    </dd>
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
        
        <!-- Description Section -->
        <?php if (!empty($media['alt_text']) || !empty($media['caption'])): ?>
            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Description</h3>
                <div class="space-y-3">
                    <?php if (!empty($media['alt_text'])): ?>
                        <div class="bg-blue-50 rounded-lg p-3">
                            <dt class="text-sm font-medium text-blue-900 mb-1">Alt Text</dt>
                            <dd class="text-sm text-blue-800"><?= htmlspecialchars($media['alt_text']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($media['caption'])): ?>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <dt class="text-sm font-medium text-gray-900 mb-1">Caption</dt>
                            <dd class="text-sm text-gray-700"><?= htmlspecialchars($media['caption']) ?></dd>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Moderation Actions -->
        <?php if (($media['status'] ?? 'approved') === 'pending'): ?>
            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Moderation Actions</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm text-yellow-800 mb-3">This file is pending review. Choose an action:</p>
                            <div class="flex space-x-3">
                                <button type="button" 
                                        onclick="moderateMediaFromModal(<?= $media['id'] ?>, 'approve')"
                                        class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Approve
                                </button>
                                <button type="button" 
                                        onclick="moderateMediaFromModal(<?= $media['id'] ?>, 'reject')"
                                        class="inline-flex items-center px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                                    </svg>
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Quick Actions</h3>
            <div class="space-y-2">
                <button type="button" 
                        onclick="copyToClipboard('<?= _getMediaUrl($media['file_path']) ?>')"
                        class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Copy File URL
                </button>
                
                <?php if ($media['file_type'] === 'image'): ?>
                    <button type="button" 
                            onclick="viewFullSize('<?= _getMediaUrl($media['file_path']) ?>', '<?= htmlspecialchars($media['original_filename']) ?>')"
                            class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                        </svg>
                        View Full Size
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function deleteMediaFromModal(id) {
        if (!confirm('Are you sure you want to delete this media file? This action cannot be undone.')) {
            return;
        }
        
        // Show loading state
        const deleteBtn = event.target;
        const originalText = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Deleting...';
        
        $.post('<?= Router::url('media/delete') ?>', {
            id: id,
            csrf_token: '<?= generateCSRFToken() ?>'
        }).done(function(response) {
            closeModal('mediaModal');
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }).fail(function() {
            showAlert('Delete failed', 'error');
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalText;
        });
    }

    function moderateMediaFromModal(id, action) {
        const actionText = action === 'approve' ? 'approve' : 'reject';
        
        if (!confirm('Are you sure you want to ' + actionText + ' this media file?')) {
            return;
        }
        
        // Show loading state
        const actionBtn = event.target;
        const originalText = actionBtn.innerHTML;
        actionBtn.disabled = true;
        actionBtn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...';
        
        $.post('<?= Router::url('media/moderate') ?>', {
            id: id,
            action: action,
            csrf_token: '<?= generateCSRFToken() ?>'
        }).done(function(response) {
            closeModal('mediaModal');
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert(response.message, 'error');
            }
        }).fail(function() {
            showAlert('Moderation action failed', 'error');
            actionBtn.disabled = false;
            actionBtn.innerHTML = originalText;
        });
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

    function viewFullSize(imageUrl, filename) {
        const newWindow = window.open('', '_blank');
        newWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${filename}</title>
                <style>
                    body { 
                        margin: 0; 
                        padding: 20px; 
                        background: #000; 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        min-height: 100vh; 
                    }
                    img { 
                        max-width: 100%; 
                        max-height: 100vh; 
                        object-fit: contain; 
                        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
                    }
                    .close-btn {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: rgba(255,255,255,0.9);
                        border: none;
                        border-radius: 50%;
                        width: 40px;
                        height: 40px;
                        cursor: pointer;
                        font-size: 18px;
                        font-weight: bold;
                        z-index: 1000;
                    }
                    .close-btn:hover {
                        background: rgba(255,255,255,1);
                    }
                </style>
            </head>
            <body>
                <button class="close-btn" onclick="window.close()">&times;</button>
                <img src="${imageUrl}" alt="${filename}">
            </body>
            </html>
        `);
    }

    // Helper functions that should be defined in the parent page
    function closeModal(modalId) {
        if (typeof parent !== 'undefined' && parent.closeModal) {
            parent.closeModal(modalId);
        } else if (typeof window.closeModal === 'function') {
            window.closeModal(modalId);
        } else {
            // Fallback for Bootstrap modals
            $('#' + modalId).modal('hide');
        }
    }

    function showAlert(message, type) {
        if (typeof parent !== 'undefined' && parent.showAlert) {
            parent.showAlert(message, type);
        } else if (typeof window.showAlert === 'function') {
            window.showAlert(message, type);
        } else {
            // Fallback alert
            alert(message);
        }
    }
</script>