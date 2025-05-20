// Med-Master Updates System Scripts
jQuery(document).ready(function($) {
    
    // פונקציה עוזרת להצגת הודעות
    function showAlert(selector, message, type = 'info') {
        var $alert = $(selector);
        $alert.removeClass('alert-info alert-success alert-danger')
              .addClass('alert-' + type)
              .html(message)
              .show();
        
        return $alert;
    }
    
    // תיקון לכפתור "קרא עוד" - גרסה משופרת
    // הסרת כל מאזיני אירועים קיימים
    $(document).off('click', '.read-more-toggle');
    $('.read-more-toggle').off('click');
    
    // הוספת מאזין אירועים יחיד ונקי
    $(document).on('click', '.read-more-toggle', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $content = $button.closest('.update-content');
        var $excerpt = $content.find('.excerpt-content');
        var $fullContent = $content.find('.full-content');
        
        if ($excerpt.is(':visible')) {
            $excerpt.hide();
            $fullContent.show();
            $button.html('סגור <i class="bi bi-caret-up-fill"></i>');
        } else {
            $fullContent.hide();
            $excerpt.show();
            $button.html('קרא עוד <i class="bi bi-caret-down-fill"></i>');
        }
    });
    
// תיקון לבעיית מודל ניהול משתמשים
    $('#manageUsersModal').on('shown.bs.modal', function () {
        // וידוא שהטבלה מוצגת
        $('#users-table-container').css('display', 'block');
        
        // רענון תוכן הטבלה במידת הצורך
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_refresh_users_table',
                nonce: medmaster_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#users-table-container').html(response.data);
                    
                    // הוספת מחדש של מאזיני אירועים לכפתורים החדשים
                    bindUserManagementEvents();
                }
            }
        });
    });
    
    // פונקציה לקשירת אירועים לכפתורי ניהול משתמשים
  function bindUserManagementEvents() {
        // כפתור מחיקת משתמש
        $('.delete-user-btn').off('click').on('click', function() {
            const userRow = $(this).closest('tr');
            const userId = userRow.data('user-id');
            const userName = $(this).data('user-name');
            
            console.log('Delete user clicked:', userId, userName);
            
            if (confirm(`האם אתה בטוח שברצונך למחוק את המשתמש "${userName}"? פעולה זו אינה ניתנת לביטול.`)) {
                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'medmaster_delete_user',
                        user_id: userId,
                        nonce: medmaster_ajax.nonce
                    },
                    success: function(response) {
                        console.log('Delete user response:', response);
                        
                        if (response.success) {
                            userRow.fadeOut(400, function() {
                                userRow.remove();
                            });
                            $('#user-management-message').html('<div class="alert alert-success">' + 
                                (response.data.message || 'המשתמש נמחק בהצלחה.') + '</div>');
                        } else {
                            $('#user-management-message').html('<div class="alert alert-danger">' + 
                                (response.data.message || 'שגיאה במחיקת המשתמש.') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete user error:', xhr.responseText);
                        $('#user-management-message').html('<div class="alert alert-danger">שגיאת שרת במחיקת המשתמש.</div>');
                    }
                });
            }
        });
        
        // כפתור שינוי סיסמה
        $('.reset-password-btn').off('click').on('click', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');
            
            console.log('Reset password clicked:', userId, userName);
            
            $('#resetPasswordModalLabel').text(`שינוי סיסמה: ${userName}`);
            $('#reset_user_id').val(userId);
            $('#new_password').val('');
            $('#reset-password-message').html('');
            
            const resetPasswordModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            resetPasswordModal.show();
        });
        
    }
    
    // טיפול במודל ניהול משתמשים
    $('#manageUsersModal').on('shown.bs.modal', function() {
        console.log('User management modal shown');
        
        // טען את הטבלה
        $('#users-table-container').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p>טוען משתמשים...</p></div>');
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_refresh_users_table',
                nonce: medmaster_ajax.nonce
            },
            success: function(response) {
                console.log('User table loaded successfully');
                
                if (response.success) {
                    $('#users-table-container').html(response.data);
                    // קשר את האירועים לכפתורים החדשים
                    bindUserManagementEvents();
                } else {
                    $('#users-table-container').html('<div class="alert alert-danger">שגיאה בטעינת המשתמשים: ' + 
                        (response.data && response.data.message ? response.data.message : 'שגיאה לא ידועה') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading users table:', xhr.responseText);
                $('#users-table-container').html('<div class="alert alert-danger">שגיאת שרת בטעינת המשתמשים.</div>');
            }
        });
    });
    
    // טיפול בטופס שינוי סיסמה
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const userId = $('#reset_user_id').val();
        const newPassword = $('#new_password').val();
        
        if (!userId || !newPassword) {
            $('#reset-password-message').html('<div class="alert alert-danger">נא למלא את כל השדות.</div>');
            return;
        }
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_reset_user_password',
                user_id: userId,
                new_password: newPassword,
                nonce: medmaster_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#reset-password-message').html('<div class="alert alert-success">' + 
                        (response.data.message || 'הסיסמה שונתה בהצלחה.') + '</div>');
                    
                    // סגירת המודל אחרי 2 שניות
                    setTimeout(function() {
                        bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
                    }, 2000);
                } else {
                    $('#reset-password-message').html('<div class="alert alert-danger">' + 
                        (response.data.message || 'שגיאה בשינוי הסיסמה.') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Reset password error:', xhr.responseText);
                $('#reset-password-message').html('<div class="alert alert-danger">שגיאת שרת בשינוי הסיסמה.</div>');
            }
        });
    });
    
    // טיפול בסימון עדכון כנקרא כאשר המשתמש גולל לסוף העדכון
    function setupReadTracking() {
        // יחול רק על משתמשים מחוברים שאינם מנהלים
        if (typeof isAdmin !== 'undefined' && !isAdmin) {
            $('.update-card-wrapper').each(function() {
                var $card = $(this);
                var $checkbox = $card.find('.mark-as-read-checkbox');
                
                // סימון אוטומטי רק אם עדיין לא נקרא
                if (!$checkbox.is(':disabled')) {
                    var checkboxObserver = new IntersectionObserver(function(entries) {
                        if (entries[0].isIntersecting) {
                            // האם גללנו לסוף?
                            var rect = $card[0].getBoundingClientRect();
                            var cardBottom = rect.bottom;
                            var windowHeight = window.innerHeight;
                            
                            // אם תחתית הכרטיס בתוך החלון
                            if (cardBottom <= windowHeight) {
                                // דיליי קצר לפני סימון כנקרא
                                setTimeout(function() {
                                    // סמן כנקרא רק אם לא מסומן כבר
                                    if (!$checkbox.is(':checked')) {
                                        $checkbox.prop('checked', true).trigger('change');
                                    }
                                    
                                    // הפסק להתבונן
                                    checkboxObserver.disconnect();
                                }, 2000); // 2 שניות
                            }
                        }
                    }, { threshold: 0.8 }); // 80% מהאלמנט נראה
                    
                    checkboxObserver.observe($card[0]);
                }
            });
        }
    }
    
    // הפעל את מעקב הקריאה
    setTimeout(setupReadTracking, 1000);
    
    // טיפול באנימציות ואפקטים נוספים
    $('.card').hover(
        function() {
            $(this).find('.admin-update-actions').stop().animate({opacity: 1}, 200);
        },
        function() {
            $(this).find('.admin-update-actions').stop().animate({opacity: 0.6}, 200);
        }
    );
    
    // איתחול tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // ===== חלק למאזיני אירועים נוספים =====
    
    // טיפול בהוספת תגית חדשה
    $('#add-new-tag-btn').on('click', function() {
        $('#new-tag-container').slideDown();
    });
    
    $('#cancel-new-tag-btn').on('click', function() {
        $('#new-tag-container').slideUp();
        $('#new_tag_name, #new_tag_color').val('');
    });
    
    // טיפול בשליחת טופס שינוי סיסמה
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const userId = $('#reset_user_id').val();
        const newPassword = $('#new_password').val();
        
        if (!userId || !newPassword) {
            $('#reset-password-message').html('<div class="alert alert-danger">נא למלא את כל השדות.</div>');
            return;
        }
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_reset_user_password',
                user_id: userId,
                new_password: newPassword,
                nonce: medmaster_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#reset-password-message').html('<div class="alert alert-success">' + 
                        (response.data.message || 'הסיסמה שונתה בהצלחה.') + '</div>');
                    
                    // סגירת המודל אחרי 2 שניות
                    setTimeout(function() {
                        bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
                    }, 2000);
                } else {
                    $('#reset-password-message').html('<div class="alert alert-danger">' + 
                        (response.data.message || 'שגיאה בשינוי הסיסמה.') + '</div>');
                }
            },
            error: function() {
                $('#reset-password-message').html('<div class="alert alert-danger">שגיאת שרת בשינוי הסיסמה.</div>');
            }
        });
    });
    
    // טיפול בסימון עדכונים כנקראים
    $('.mark-as-read-checkbox').on('change', function() {
        var $checkbox = $(this);
        var updateId = $checkbox.data('update-id');
        
        if ($checkbox.is(':checked') && !$checkbox.is(':disabled')) {
            $.ajax({
                url: medmaster_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'medmaster_mark_as_read',
                    update_id: updateId,
                    nonce: medmaster_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $checkbox.prop('disabled', true);
                    } else {
                        $checkbox.prop('checked', false);
                        alert(response.data.message);
                    }
                },
                error: function() {
                    $checkbox.prop('checked', false);
                    alert('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
                }
            });
        }
    });
});