// Amhorti Schedule Admin JavaScript

(function($) {
    'use strict';
    
    var AmhortiAdmin = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Form submissions
            $(document).on('submit', '#amhorti-add-sheet-form', function(e) {
                e.preventDefault();
                self.handleSheetForm($(this));
            });
            
            $(document).on('submit', '#amhorti-add-schedule-form', function(e) {
                e.preventDefault();
                self.handleScheduleForm($(this));
            });
            
            // Delete actions
            $(document).on('click', '.delete-sheet', function() {
                var sheetId = $(this).data('id');
                self.deleteSheet(sheetId);
            });
            
            $(document).on('click', '.delete-schedule', function() {
                var scheduleId = $(this).data('id');
                self.deleteSchedule(scheduleId);
            });
            
            // Edit actions (placeholder for future implementation)
            $(document).on('click', '.edit-sheet', function() {
                var sheetId = $(this).data('id');
                self.editSheet(sheetId);
            });
            
            $(document).on('click', '.edit-schedule', function() {
                var scheduleId = $(this).data('id');
                self.editSchedule(scheduleId);
            });
            
            // Save sheet functionality
            $(document).on('click', '.save-sheet', function() {
                var row = $(this).closest('tr');
                var sheetId = $(this).data('id');
                var data = {
                    action: 'amhorti_admin_edit_sheet',
                    sheet_id: sheetId,
                    nonce: $('[name="amhorti_admin_nonce"]').val()
                };
                
                // Collect edited values
                row.find('.edit-input').each(function() {
                    var field = $(this).data('field');
                    data[field] = $(this).val();
                });
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        self.showMessage('Erreur : ' + response.data, 'error');
                    }
                });
            });
            
            // Cancel edit functionality
            $(document).on('click', '.cancel-edit', function() {
                location.reload();
            });
        },
        
        handleSheetForm: function(form) {
            var self = this;
            var submitBtn = form.find('input[type="submit"]');
            var originalText = submitBtn.val();
            
            // Validate form
            var sheetName = form.find('#sheet_name').val().trim();
            if (!sheetName) {
                self.showMessage('Sheet name is required', 'error');
                return;
            }
            
            // Set loading state
            submitBtn.val('Adding...').prop('disabled', true);
            form.addClass('amhorti-admin-loading');
            
            var data = {
                action: 'amhorti_admin_save_sheet',
                sheet_name: sheetName,
                sort_order: form.find('#sort_order').val(),
                nonce: form.find('[name="amhorti_admin_nonce"]').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Sheet added successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('Network error occurred', 'error');
                },
                complete: function() {
                    submitBtn.val(originalText).prop('disabled', false);
                    form.removeClass('amhorti-admin-loading');
                }
            });
        },
        
        handleScheduleForm: function(form) {
            var self = this;
            var submitBtn = form.find('input[type="submit"]');
            var originalText = submitBtn.val();
            
            // Validate form
            var timeStart = form.find('#time_start').val();
            var timeEnd = form.find('#time_end').val();
            var slotCount = form.find('#slot_count').val();
            
            if (!timeStart || !timeEnd || !slotCount) {
                self.showMessage('All fields are required', 'error');
                return;
            }
            
            if (timeStart >= timeEnd) {
                self.showMessage('Start time must be before end time', 'error');
                return;
            }
            
            // Set loading state
            submitBtn.val('Adding...').prop('disabled', true);
            form.addClass('amhorti-admin-loading');
            
            var data = {
                action: 'amhorti_admin_save_schedule',
                day_of_week: form.find('#day_of_week').val(),
                time_start: timeStart,
                time_end: timeEnd,
                slot_count: slotCount,
                nonce: form.find('[name="amhorti_admin_nonce"]').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Time slot added successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('Network error occurred', 'error');
                },
                complete: function() {
                    submitBtn.val(originalText).prop('disabled', false);
                    form.removeClass('amhorti-admin-loading');
                }
            });
        },
        
        deleteSheet: function(sheetId) {
            var self = this;
            
            if (!confirm('Are you sure you want to delete this sheet? This action cannot be undone.')) {
                return;
            }
            
            var data = {
                action: 'amhorti_admin_delete_sheet',
                sheet_id: sheetId,
                nonce: $('[name="amhorti_admin_nonce"]').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Sheet deleted successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('Network error occurred', 'error');
                }
            });
        },
        
        deleteSchedule: function(scheduleId) {
            var self = this;
            
            if (!confirm('Are you sure you want to delete this time slot? This action cannot be undone.')) {
                return;
            }
            
            var data = {
                action: 'amhorti_admin_delete_schedule',
                schedule_id: scheduleId,
                nonce: $('[name="amhorti_admin_nonce"]').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Time slot deleted successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('Network error occurred', 'error');
                }
            });
        },
        
        editSheet: function(sheetId) {
            var row = $('[data-sheet-id="' + sheetId + '"]');
            
            // Make cells editable
            row.find('.editable-cell').each(function() {
                var field = $(this).data('field');
                var currentValue = $(this).text().trim();
                
                if (field === 'is_active') {
                    var select = '<select class="edit-input" data-field="' + field + '">';
                    select += '<option value="1"' + (currentValue === 'Actif' ? ' selected' : '') + '>Actif</option>';
                    select += '<option value="0"' + (currentValue === 'Inactif' ? ' selected' : '') + '>Inactif</option>';
                    select += '</select>';
                    $(this).html(select);
                } else {
                    var inputType = field === 'sort_order' ? 'number' : 'text';
                    $(this).html('<input type="' + inputType + '" class="edit-input" data-field="' + field + '" value="' + currentValue + '" />');
                }
            });
            
            // Show/hide buttons
            row.find('.edit-sheet').hide();
            row.find('.save-sheet, .cancel-edit').show();
        },
        
        editSchedule: function(scheduleId) {
            // Placeholder for edit functionality
            alert('Edit functionality will be implemented in a future version');
        },
        
        showMessage: function(message, type) {
            // Remove existing messages
            $('.amhorti-admin-message').remove();
            
            var messageHtml = '<div class="amhorti-admin-message ' + type + '">' + message + '</div>';
            $('.wrap h1').after(messageHtml);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $('.amhorti-admin-message').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('body').hasClass('toplevel_page_amhorti-schedule') || 
            $('body').hasClass('amhorti-schedule_page_amhorti-sheets') ||
            $('body').hasClass('amhorti-schedule_page_amhorti-schedules')) {
            AmhortiAdmin.init();
        }
    });
    
})(jQuery);