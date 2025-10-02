// Amhorti Schedule Public JavaScript

(function($) {
    'use strict';
    
    var AmhortiSchedule = {
        currentSheet: 1,
        currentDate: new Date(),
        
        init: function() {
            this.bindEvents();
            this.loadTable();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Tab switching
            $(document).on('click', '.amhorti-tab', function() {
                var sheetId = $(this).data('sheet-id');
                self.switchSheet(sheetId);
            });
            
            // Navigation buttons
            $(document).on('click', '.amhorti-nav-btn', function() {
                var direction = $(this).data('direction');
                self.navigate(direction);
            });
            
            // Cell editing
            $(document).on('input blur', '.booking-cell.editable', function() {
                self.debouncedSave($(this));
            });
            
            // Prevent line breaks in contenteditable cells
            $(document).on('keydown', '.booking-cell.editable', function(e) {
                if (e.keyCode === 13) { // Enter key
                    e.preventDefault();
                    $(this).blur();
                }
            });
            
            // Action icon clicks (+ for signup, - for delete)
            $(document).on('click', '.amhorti-icon', function(e) {
                e.stopPropagation();
                e.preventDefault();
                
                var $icon = $(this);
                if ($icon.is(':disabled') || $icon.hasClass('amhorti-processing')) {
                    return;
                }
                
                var action = $icon.data('action');
                var $cell = $icon.closest('.booking-cell');
                
                if (action === 'signup') {
                    self.quickSignup($cell, $icon);
                } else if (action === 'delete') {
                    self.quickDelete($cell, $icon);
                }
            });
        },
        
        switchSheet: function(sheetId) {
            this.currentSheet = sheetId;
            
            // Update active tab
            $('.amhorti-tab').removeClass('active');
            $('[data-sheet-id="' + sheetId + '"]').addClass('active');
            
            // Update container data
            $('.amhorti-schedule-container').data('current-sheet', sheetId);
            
            this.loadTable();
        },
        
        navigate: function(direction) {
            var date = new Date(this.currentDate);
            
            switch(direction) {
                case 'prev':
                    date.setDate(date.getDate() - 7);
                    break;
                case 'next':
                    date.setDate(date.getDate() + 7);
                    break;
                case 'today':
                    date = new Date();
                    break;
            }
            
            this.currentDate = date;
            this.loadTable();
        },
        
        loadTable: function() {
            var self = this;
            var container = $('.amhorti-schedule-container');
            var sheetId = this.currentSheet;
            var startDate = this.formatDate(this.currentDate);
            
            $('.amhorti-loading').show();
            $('.amhorti-table-wrapper').hide();
            
            $.ajax({
                url: amhorti_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'amhorti_get_table_data',
                    sheet_id: sheetId,
                    start_date: startDate,
                    nonce: amhorti_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.amhorti-table-wrapper').html(response.data.html);
                        $('.amhorti-table-wrapper').show();
                    } else {
                        self.showMessage('Error loading table: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('Network error occurred', 'error');
                },
                complete: function() {
                    $('.amhorti-loading').hide();
                }
            });
        },
        
        debouncedSave: function(cell) {
            var self = this;
            
            // Clear existing timeout
            if (cell.data('save-timeout')) {
                clearTimeout(cell.data('save-timeout'));
            }
            
            // Set new timeout
            var timeout = setTimeout(function() {
                self.saveBooking(cell);
            }, 500);
            
            cell.data('save-timeout', timeout);
        },
        
        saveBooking: function(cell) {
            var self = this;
            var data = {
                action: 'amhorti_save_booking',
                sheet_id: this.currentSheet,
                date: cell.data('date'),
                time_start: cell.data('time-start'),
                time_end: cell.data('time-end'),
                slot_number: cell.data('slot'),
                booking_text: cell.text().trim(),
                version: cell.data('version') || 0,
                nonce: amhorti_ajax.nonce
            };
            
            // Add loading state
            cell.addClass('saving');
            
            $.ajax({
                url: amhorti_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // Update version for future saves
                        cell.data('version', response.data.version);
                        cell.data('booking-id', response.data.id);
                        cell.addClass('saved').removeClass('saving');
                        setTimeout(function() {
                            cell.removeClass('saved');
                        }, 1000);
                    } else {
                        // Check if it's a conflict error
                        if (response.data && response.data.conflict) {
                            self.handleConflict(cell, response.data.message);
                        } else {
                            self.showMessage('Error saving: ' + response.data, 'error');
                            cell.removeClass('saving');
                        }
                    }
                },
                error: function() {
                    self.showMessage('Network error occurred while saving', 'error');
                    cell.removeClass('saving');
                }
            });
        },
        
        handleConflict: function(cell, message) {
            var self = this;
            cell.removeClass('saving');
            cell.addClass('conflict');
            
            // Show conflict message with option to reload or override
            var conflictMsg = message + '. Voulez-vous recharger la page pour voir les dernières modifications ?';
            if (confirm(conflictMsg)) {
                // Reload the table to get latest data
                self.loadTable();
            } else {
                // Let user try again (will use updated version from reload)
                cell.removeClass('conflict');
            }
        },
        
        formatDate: function(date) {
            var year = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var day = ('0' + date.getDate()).slice(-2);
            return year + '-' + month + '-' + day;
        },
        
        showMessage: function(message, type) {
            var messageHtml = '<div class="amhorti-message ' + type + '">' + message + '</div>';
            $('.amhorti-schedule-container').prepend(messageHtml);
            
            setTimeout(function() {
                $('.amhorti-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        quickSignup: function($cell, $icon) {
            var self = this;
            
            // Prevent double clicks
            $icon.addClass('amhorti-processing').prop('disabled', true);
            
            // Request auto-label from server and save
            var data = {
                action: 'amhorti_quick_signup',
                sheet_id: this.currentSheet,
                date: $cell.data('date'),
                time_start: $cell.data('time-start'),
                time_end: $cell.data('time-end'),
                slot_number: $cell.data('slot'),
                version: $cell.data('version') || 0,
                nonce: amhorti_ajax.nonce
            };
            
            $cell.addClass('saving');
            
            $.ajax({
                url: amhorti_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // Update cell with new booking data
                        $cell.text(response.data.booking_text);
                        $cell.data('version', response.data.version);
                        $cell.data('booking-id', response.data.id);
                        $cell.data('booking-user-id', response.data.user_id);
                        
                        // Refresh to update action buttons
                        self.loadTable();
                    } else {
                        self.showMessage('Erreur: ' + (response.data.message || response.data), 'error');
                        $icon.removeClass('amhorti-processing').prop('disabled', false);
                        $cell.removeClass('saving');
                    }
                },
                error: function() {
                    self.showMessage('Erreur réseau lors de l\'inscription', 'error');
                    $icon.removeClass('amhorti-processing').prop('disabled', false);
                    $cell.removeClass('saving');
                }
            });
        },
        
        quickDelete: function($cell, $icon) {
            var self = this;
            var bookingId = $cell.data('booking-id');
            
            if (!bookingId) {
                return;
            }
            
            // Prevent double clicks
            $icon.addClass('amhorti-processing').prop('disabled', true);
            
            $cell.addClass('saving');
            
            $.ajax({
                url: amhorti_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'amhorti_delete_booking',
                    booking_id: bookingId,
                    nonce: amhorti_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Clear cell content and reset data
                        $cell.text('');
                        $cell.data('version', 0);
                        $cell.data('booking-id', '');
                        $cell.data('booking-user-id', '');
                        
                        // Refresh to update action buttons
                        self.loadTable();
                    } else {
                        self.showMessage('Erreur: ' + (response.data.message || response.data), 'error');
                        $icon.removeClass('amhorti-processing').prop('disabled', false);
                        $cell.removeClass('saving');
                    }
                },
                error: function() {
                    self.showMessage('Erreur réseau lors de la suppression', 'error');
                    $icon.removeClass('amhorti-processing').prop('disabled', false);
                    $cell.removeClass('saving');
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.amhorti-schedule-container').length) {
            var container = $('.amhorti-schedule-container');
            AmhortiSchedule.currentSheet = container.data('current-sheet') || 1;
            AmhortiSchedule.init();
        }
    });
    
    // Add CSS for saving states
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .booking-cell.saving {
                background: #fff3cd !important;
                border-color: #ffc107 !important;
            }
            .booking-cell.saved {
                background: #d4edda !important;
                border-color: #28a745 !important;
            }
            .booking-cell.conflict {
                background: #f8d7da !important;
                border-color: #dc3545 !important;
            }
        `)
        .appendTo('head');
        
})(jQuery);