/**
 * Flashcard CSV Importer - Admin JavaScript
 */
(function($) {
    'use strict';

    var FCSV = {
        // DOM elements
        $uploadSection: null,
        $confirmSection: null,
        $processSection: null,
        $resultsSection: null,
        $dropZone: null,
        $fileInput: null,
        $flashcardSet: null,
        $uploadStatus: null,
        $logContent: null,

        /**
         * Initialize
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.$uploadSection = $('#fcsv-upload-section');
            this.$confirmSection = $('#fcsv-confirmation-section');
            this.$processSection = $('#fcsv-processing-section');
            this.$resultsSection = $('#fcsv-results-section');
            this.$dropZone = $('#fcsv-drop-zone');
            this.$fileInput = $('#fcsv-file-input');
            this.$flashcardSet = $('#fcsv-flashcard-set');
            this.$uploadStatus = $('#fcsv-upload-status');
            this.$logContent = $('#fcsv-log-content');
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Browse button
            $('#fcsv-browse-btn').on('click', function(e) {
                e.preventDefault();
                self.$fileInput.trigger('click');
            });

            // File input change
            this.$fileInput.on('change', function(e) {
                if (e.target.files.length > 0) {
                    self.handleFile(e.target.files[0]);
                }
            });

            // Drag and drop
            this.$dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            this.$dropZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
            });

            this.$dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    self.handleFile(files[0]);
                }
            });

            // Prevent default drag behavior on document
            $(document).on('dragover drop', function(e) {
                e.preventDefault();
            });

            // Confirm import
            $('#fcsv-confirm-btn').on('click', function(e) {
                e.preventDefault();
                self.processImport();
            });

            // Cancel
            $('#fcsv-cancel-btn').on('click', function(e) {
                e.preventDefault();
                self.reset();
            });

            // New import
            $('#fcsv-new-import-btn').on('click', function(e) {
                e.preventDefault();
                self.reset();
            });

            // Toggle log
            $('#fcsv-toggle-log').on('click', function() {
                var $content = self.$logContent;
                if ($content.is(':visible')) {
                    $content.slideUp();
                    $(this).text(fcsvImporter.strings.show || 'Show');
                } else {
                    $content.slideDown();
                    $(this).text(fcsvImporter.strings.hide || 'Hide');
                }
            });
        },

        /**
         * Handle file selection
         */
        handleFile: function(file) {
            var self = this;

            // Validate flashcard set selection
            if (!this.$flashcardSet.val()) {
                this.showError(fcsvImporter.strings.select_set);
                return;
            }

            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                this.showError(fcsvImporter.strings.invalid_file);
                return;
            }

            // Upload file
            this.uploadFile(file);
        },

        /**
         * Upload file to server
         */
        uploadFile: function(file) {
            var self = this;
            var formData = new FormData();

            formData.append('action', 'fcsv_upload');
            formData.append('nonce', fcsvImporter.nonce);
            formData.append('file', file);

            this.$uploadStatus.html(
                '<div class="fcsv-status uploading">' +
                '<span class="spinner is-active"></span> ' +
                fcsvImporter.strings.uploading +
                '</div>'
            );

            $.ajax({
                url: fcsvImporter.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 300000,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var pct = Math.round((e.loaded / e.total) * 100);
                            self.$uploadStatus.find('.fcsv-status').html(
                                '<span class="spinner is-active"></span> ' +
                                fcsvImporter.strings.uploading + ' (' + pct + '%)'
                            );
                        }
                    });
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        self.showConfirmation(response.data);
                    } else {
                        self.showError(response.data.message || fcsvImporter.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    var msg = fcsvImporter.strings.error;
                    if (status === 'timeout') {
                        msg = 'Upload timed out. Please try a smaller file.';
                    } else if (xhr.status === 0) {
                        msg = 'Network error. Please check your connection.';
                    }
                    self.showError(msg);
                }
            });
        },

        /**
         * Show confirmation screen
         */
        showConfirmation: function(data) {
            var self = this;
            var setName = this.$flashcardSet.find('option:selected').text();

            var html = '<div class="fcsv-preview">';
            html += '<p><strong>Rows detected:</strong> ' + (data.row_count || 0) + '</p>';
            html += '<p><strong>Columns found:</strong> ' + this.escapeHtml(data.headers.join(', ')) + '</p>';
            html += '<p><strong>Target set:</strong> ' + this.escapeHtml(setName) + '</p>';
            html += '<div class="fcsv-mapping">';
            html += '<h4>Column Mapping:</h4>';
            html += '<ul>';
            html += '<li><code>question</code> → Card Front</li>';
            html += '<li><code>answer</code> → Card Back</li>';
            html += '</ul>';
            html += '</div>';
            html += '</div>';

            $('#fcsv-preview-info').html(html);
            
            this.$uploadSection.hide();
            this.$confirmSection.show();
        },

        /**
         * Process import
         */
        processImport: function() {
            var self = this;
            var flashcardSetId = this.$flashcardSet.val();

            this.$confirmSection.hide();
            this.$processSection.show();
            this.$logContent.empty();

            this.addLog('Starting import...', 'info');
            this.addLog('Flashcard Set ID: ' + flashcardSetId, 'info');

            $.ajax({
                url: fcsvImporter.ajax_url,
                type: 'POST',
                data: {
                    action: 'fcsv_process',
                    nonce: fcsvImporter.nonce,
                    flashcard_set_id: flashcardSetId
                },
                timeout: 600000,
                success: function(response) {
                    if (response.success) {
                        self.addLog('Import completed!', 'success');
                        
                        // Display server logs
                        if (response.data.log) {
                            response.data.log.forEach(function(entry) {
                                self.addLog(entry.message, entry.type);
                            });
                        }
                        
                        self.showResults(response.data);
                    } else {
                        self.addLog('Import failed: ' + (response.data.message || 'Unknown error'), 'error');
                        self.showError(response.data.message || fcsvImporter.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    var msg = 'Import failed';
                    if (status === 'timeout') {
                        msg = 'Request timed out. The file may be too large.';
                    }
                    self.addLog(msg, 'error');
                    self.showError(msg);
                }
            });
        },

        /**
         * Show results
         */
        showResults: function(data) {
            var html = '<div class="fcsv-results-summary">';
            html += '<div class="fcsv-stat success"><span class="number">' + (data.created || 0) + '</span><span class="label">Created</span></div>';
            html += '<div class="fcsv-stat warning"><span class="number">' + (data.skipped || 0) + '</span><span class="label">Skipped</span></div>';
            html += '<div class="fcsv-stat error"><span class="number">' + (data.errors || 0) + '</span><span class="label">Errors</span></div>';
            html += '</div>';

            if (data.error_messages && data.error_messages.length > 0) {
                html += '<div class="fcsv-error-list">';
                html += '<h4>Error Details:</h4>';
                html += '<ul>';
                data.error_messages.slice(0, 10).forEach(function(msg) {
                    html += '<li>' + this.escapeHtml(msg) + '</li>';
                }, this);
                if (data.error_messages.length > 10) {
                    html += '<li>... and ' + (data.error_messages.length - 10) + ' more errors</li>';
                }
                html += '</ul>';
                html += '</div>';
            }

            $('#fcsv-results-content').html(html);
            
            this.$processSection.hide();
            this.$resultsSection.show();
        },

        /**
         * Add log entry
         */
        addLog: function(message, type) {
            var icons = {
                'success': '✓',
                'error': '✗',
                'warning': '⚠',
                'info': '•'
            };
            
            var time = new Date().toLocaleTimeString();
            var icon = icons[type] || icons.info;
            
            var $entry = $('<div class="fcsv-log-entry ' + type + '">')
                .html('<span class="time">[' + time + ']</span> <span class="icon">' + icon + '</span> ' + this.escapeHtml(message));
            
            this.$logContent.append($entry);
            
            // Auto-scroll
            var container = $('#fcsv-log-container')[0];
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        /**
         * Show error
         */
        showError: function(message) {
            this.$uploadStatus.html('<div class="fcsv-status error">' + this.escapeHtml(message) + '</div>');
        },

        /**
         * Reset to initial state
         */
        reset: function() {
            this.$uploadSection.show();
            this.$confirmSection.hide();
            this.$processSection.hide();
            this.$resultsSection.hide();
            this.$uploadStatus.empty();
            this.$logContent.empty();
            this.$fileInput.val('');
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        FCSV.init();
    });

})(jQuery);

