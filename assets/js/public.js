// Amhorti Schedule Public JavaScript

(function($) {
    'use strict';
    
    var AmhortiSchedule = {
        currentSheet: 1,
        currentDate: new Date(),
        
        init: function() {
            this.bindEvents();
            this.loadTable();
            this.bindStorageListener();
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
        },
        
        bindStorageListener: function(){
            var self = this;
            try {
                window.addEventListener('storage', function(ev){
                    if(ev.key === 'amhorti_days_updated') {
                        // Recharger seulement si feuille courante correspond ou recharger toujours (simpler)
                        self.loadTable();
                    }
                });
            } catch(e) {}
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
                        cell.addClass('saved').removeClass('saving');
                        setTimeout(function() {
                            cell.removeClass('saved');
                        }, 1000);
                    } else {
                        self.showMessage('Error saving: ' + response.data, 'error');
                        cell.removeClass('saving');
                    }
                },
                error: function() {
                    self.showMessage('Network error occurred while saving', 'error');
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
        `)
        .appendTo('head');
        
})(jQuery);