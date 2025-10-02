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
            
            // Icon click handlers
            $(document).on('click', '.amhorti-icon-plus', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var cell = $(this).closest('.booking-cell');
                self.handleSignup(cell);
            });
            
            $(document).on('click', '.amhorti-icon-minus', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var cell = $(this).closest('.booking-cell');
                self.handleUnsubscribe(cell);
            });
            
            // Cell editing (skip if just clicked an icon)
            $(document).on('input blur', '.booking-cell.editable', function(e) {
                // Skip save if this was triggered by icon action
                if ($(this).data('skip-save')) {
                    $(this).removeData('skip-save');
                    return;
                }
                self.debouncedSave($(this));
            });
            
            // Prevent line breaks in contenteditable cells
            $(document).on('keydown', '.booking-cell.editable', function(e) {
                if (e.keyCode === 13) { // Enter key
                    e.preventDefault();
                    $(this).blur();
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
        
        handleSignup: function(cell) {
            var self = this;
            
            // Check if user is logged in
            if (!amhorti_ajax.is_logged_in) {
                alert('Vous devez être connecté pour vous inscrire');
                return;
            }
            
            // Build label from localized data
            var label = amhorti_ajax.user_login;
            if (amhorti_ajax.user_last_initial) {
                label += ' ' + amhorti_ajax.user_last_initial + '.';
            }
            
            // Mark to skip automatic save
            cell.data('skip-save', true);
            
            // Update cell text immediately (optimistic update)
            cell.find('.amhorti-cell-text').text(label);
            
            // Add loading state
            cell.addClass('saving');
            
            // Send AJAX request
            $.ajax({
                url: amhorti_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'amhorti_save_booking',
                    mode: 'signup',
                    sheet_id: self.currentSheet,
                    date: cell.data('date'),
                    time_start: cell.data('time-start'),
                    time_end: cell.data('time-end'),
                    slot_number: cell.data('slot'),
                    version: cell.data('version') || 0,
                    nonce: amhorti_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update cell data
                        cell.data('version', response.data.version);
                        cell.data('booking-id', response.data.id);
                        cell.data('owner-id', amhorti_ajax.current_user_id);
                        
                        // Update UI: swap + for -
                        cell.find('.amhorti-icon-plus').remove();
                        if (!cell.find('.amhorti-icon-minus').length) {
                            cell.find('.amhorti-cell-actions').html('<button class="amhorti-icon amhorti-icon-minus" title="Se désinscrire">−</button>');
                        }
                        
                        cell.addClass('saved').removeClass('saving');
                        setTimeout(function() {
                            cell.removeClass('saved');
                        }, 1000);
                    } else {
                        // Revert text on error
                        cell.find('.amhorti-cell-text').text('');
                        self.showMessage('Erreur: ' + response.data, 'error');
                        cell.removeClass('saving');
                    }
                },
                error: function() {
                    // Revert text on error
                    cell.find('.amhorti-cell-text').text('');
                    self.showMessage('Erreur réseau lors de l\'inscription', 'error');
                    cell.removeClass('saving');
                }
            });
        },
        
        handleUnsubscribe: function(cell) {
            var self = this;
            var bookingId = cell.data('booking-id');
            
            if (!bookingId) {
                alert('Aucune réservation à supprimer');
                return;
            }
            
            if (!confirm('Voulez-vous vous désinscrire de ce créneau ?')) {
                return;
            }
            
            // Mark to skip automatic save
            cell.data('skip-save', true);
            
            // Add loading state
            cell.addClass('saving');
            
            // Send AJAX delete request
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
                        // Clear cell text
                        cell.find('.amhorti-cell-text').text('');
                        
                        // Reset version and booking ID
                        cell.data('version', 0);
                        cell.data('booking-id', 0);
                        cell.data('owner-id', 0);
                        
                        // Update UI: swap - for +
                        cell.find('.amhorti-icon-minus').remove();
                        if (!cell.find('.amhorti-icon-plus').length) {
                            cell.find('.amhorti-cell-actions').html('<button class="amhorti-icon amhorti-icon-plus" title="S\'inscrire">+</button>');
                        }
                        
                        cell.addClass('saved').removeClass('saving');
                        setTimeout(function() {
                            cell.removeClass('saved');
                        }, 1000);
                    } else {
                        self.showMessage('Erreur: ' + response.data, 'error');
                        cell.removeClass('saving');
                    }
                },
                error: function() {
                    self.showMessage('Erreur réseau lors de la désinscription', 'error');
                    cell.removeClass('saving');
                }
            });
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