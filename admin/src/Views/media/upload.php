<?php

    // admin/src/Views/media/upload.php - Upload View

    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }

?>

<!-- Header -->
<div class="bg-white rounded-xl shadow-md border-b border-gray-200">
    <div class="max-w-1xl mx-auto px-4 sm:px-6 lg:px-8">
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
                    <li class="text-gray-900 font-medium">Upload</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="max-w-1xl mx-auto py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Upload Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Upload Files</h3>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <!-- File Drop Zone -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Files</label>
                        <div class="relative">
                            <input type="file" class="sr-only" id="files" name="files[]" multiple
                                   accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.zip">
                            <label for="files" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors group" id="dropZone">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-10 h-10 mb-3 text-gray-400 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500">
                                        <span class="font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500" id="fileLabel">Choose files...</p>
                                </div>
                            </label>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Allowed file types: Images, Videos, Audio, PDF, DOC, DOCX, TXT, ZIP<br>
                            Maximum file size: <span class="font-medium"><?= ini_get('upload_max_filesize') ?></span>
                        </p>
                    </div>
                    
                    <!-- File Preview Area -->
                    <div id="filePreview" class="hidden mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Selected Files</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="previewGrid"></div>
                    </div>
                    
                    <!-- File Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Alt Text (for images)
                            </label>
                            <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                   id="alt_text" name="alt_text" placeholder="Describe the image content">
                            <p class="mt-1 text-xs text-gray-500">Important for accessibility and SEO</p>
                        </div>
                        <div>
                            <label for="caption" class="block text-sm font-medium text-gray-700 mb-2">Caption</label>
                            <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                   id="caption" name="caption" placeholder="Optional caption">
                        </div>
                    </div>
                    
                    <!-- Public Access Toggle -->
                    <div class="mb-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" 
                                       id="is_public" name="is_public" checked>
                            </div>
                            <div class="ml-3">
                                <label for="is_public" class="text-sm font-medium text-gray-700">
                                    Make files publicly accessible
                                </label>
                                <p class="text-xs text-gray-500">Public files can be accessed by anyone with the link</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <a href="<?= Router::url('media') ?>" 
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancel
                        </a>
                        <button type="submit" id="uploadBtn"
                                class="inline-flex items-center px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Upload Files
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Guidelines Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Upload Guidelines</h3>
                </div>
                <div class="p-6 space-y-6">
                    <!-- File Types -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Allowed File Types</h4>
                        <ul class="space-y-2">
                            <li class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                </svg>
                                Images: JPG, PNG, GIF, WebP
                            </li>
                            <li class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h6l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM4 8a1 1 0 000 2h1v3a1 1 0 001 1h3a1 1 0 001-1v-3h1a1 1 0 100-2H4z"/>
                                </svg>
                                Videos: MP4, AVI, MOV
                            </li>
                            <li class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-3 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                                </svg>
                                Audio: MP3, WAV, OGG
                            </li>
                            <li class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>
                                </svg>
                                Documents: PDF, DOC, DOCX, TXT
                            </li>
                            <li class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-3 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                Archives: ZIP
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Size Limits -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Size Limits</h4>
                        <ul class="space-y-1 text-sm text-gray-600">
                            <li>Maximum file size: <span class="font-semibold text-gray-900"><?= ini_get('upload_max_filesize') ?></span></li>
                            <li>Maximum total size: <span class="font-semibold text-gray-900"><?= ini_get('post_max_size') ?></span></li>
                        </ul>
                    </div>
                    
                    <!-- Best Practices -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Best Practices</h4>
                        <ul class="space-y-1 text-sm text-gray-600">
                            <li class="flex items-start">
                                <svg class="w-3 h-3 mt-1.5 mr-2 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Use descriptive filenames
                            </li>
                            <li class="flex items-start">
                                <svg class="w-3 h-3 mt-1.5 mr-2 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Optimize images before uploading
                            </li>
                            <li class="flex items-start">
                                <svg class="w-3 h-3 mt-1.5 mr-2 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Always provide alt text for images
                            </li>
                            <li class="flex items-start">
                                <svg class="w-3 h-3 mt-1.5 mr-2 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Consider file sizes for web performance
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Progress Modal -->
<div id="uploadProgressModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center mb-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mr-3"></div>
                    <h3 class="text-lg font-medium text-gray-900">Uploading Files...</h3>
                </div>
                
                <div class="mb-4">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out" 
                             style="width: 0%" id="uploadProgressBar"></div>
                    </div>
                </div>
                
                <div id="uploadStatus" class="text-sm text-gray-600">Preparing upload...</div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const $dropZone = $('#dropZone');
        const $fileInput = $('#files');
        
        // Drag and drop functionality
        $dropZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('border-blue-400 bg-blue-50');
        });
        
        $dropZone.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-blue-400 bg-blue-50');
        });
        
        $dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-blue-400 bg-blue-50');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $fileInput[0].files = files;
                updateFileLabel(files);
                previewFiles(files);
            }
        });
        
        // File input change handler
        $fileInput.on('change', function() {
            const files = this.files;
            if (files.length > 0) {
                updateFileLabel(files);
                previewFiles(files);
            }
        });
        
        // Form submit handler
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            const files = $fileInput[0].files;
            if (files.length === 0) {
                showAlert('Please select files to upload', 'error');
                return;
            }
            
            uploadFiles();
        });
    });

    function updateFileLabel(files) {
        const fileCount = files.length;
        const label = fileCount === 1 ? files[0].name : fileCount + ' files selected';
        $('#fileLabel').text(label);
    }

    function previewFiles(files) {
        const $preview = $('#filePreview');
        const $grid = $('#previewGrid');
        $grid.empty();
        $preview.removeClass('hidden');
        
        for (let i = 0; i < Math.min(files.length, 6); i++) {
            const file = files[i];
            const $item = $('<div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">');
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $item.html(
                        '<div class="aspect-w-1 aspect-h-1">' +
                        '<img src="' + e.target.result + '" class="w-full h-24 object-cover">' +
                        '</div>' +
                        '<div class="p-2">' +
                        '<p class="text-xs text-gray-600 truncate" title="' + file.name + '">' + file.name + '</p>' +
                        '<p class="text-xs text-gray-400">' + formatFileSize(file.size) + '</p>' +
                        '</div>'
                    );
                };
                reader.readAsDataURL(file);
            } else {
                const icon = getFileIcon(file.type);
                $item.html(
                    '<div class="h-24 flex items-center justify-center bg-gray-100">' +
                    '<svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">' + icon + '</svg>' +
                    '</div>' +
                    '<div class="p-2">' +
                    '<p class="text-xs text-gray-600 truncate" title="' + file.name + '">' + file.name + '</p>' +
                    '<p class="text-xs text-gray-400">' + formatFileSize(file.size) + '</p>' +
                    '</div>'
                );
            }
            
            $grid.append($item);
        }
        
        if (files.length > 6) {
            $grid.append(
                '<div class="bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-center">' +
                '<div class="text-center p-4">' +
                '<p class="text-sm text-gray-500">+' + (files.length - 6) + ' more</p>' +
                '<p class="text-xs text-gray-400">files</p>' +
                '</div>' +
                '</div>'
            );
        }
    }

    function getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) {
            return '<path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>';
        }
        if (mimeType.startsWith('video/')) {
            return '<path d="M2 6a2 2 0 012-2h6l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM4 8a1 1 0 000 2h1v3a1 1 0 001 1h3a1 1 0 001-1v-3h1a1 1 0 100-2H4z"/>';
        }
        if (mimeType.startsWith('audio/')) {
            return '<path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>';
        }
        if (mimeType === 'application/pdf') {
            return '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>';
        }
        if (mimeType.includes('zip') || mimeType.includes('archive')) {
            return '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>';
        }
        return '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function uploadFiles() {
        const formData = new FormData($('#uploadForm')[0]);
        
        showModal('uploadProgressModal');
        $('#uploadBtn').prop('disabled', true);
        const endpoint = '<?= Router::url('media/upload') ?>';
        $.ajax({
            url: `${endpoint}` ,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        $('#uploadProgressBar').css('width', percentComplete + '%');
                        $('#uploadStatus').text('Uploading... ' + Math.round(percentComplete) + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                hideModal('uploadProgressModal');
                showAlert('Files uploaded successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '<?= Router::url('media/upload') ?>?uploaded=1';
                }, 1500);
            },
            error: function(xhr) {
                hideModal('uploadProgressModal');
                $('#uploadBtn').prop('disabled', false);
                
                let message = 'Upload failed';
                try {
                    const response = JSON.parse(xhr.responseText);
                    message = response.message || message;
                } catch (e) {}
                
                showAlert(message, 'error');
            }
        });
    }

    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function hideModal(modalId) {
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