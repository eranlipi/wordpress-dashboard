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

    jQuery(document).ready(function($) {
    
    // טיפול בכפתור "קרא עוד" - גרסה משופרת
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
    
    // וידוא שהפונקציה לא מופעלת כפול
    $('.read-more-toggle').off('click').on('click', function(e) {
        e.preventDefault();
        var $content = $(this).closest('.update-content');
        var $excerpt = $content.find('.excerpt-content');
        var $fullContent = $content.find('.full-content');
        
        if ($excerpt.is(':visible')) {
            $excerpt.hide();
            $fullContent.show();
            $(this).html('סגור <i class="bi bi-caret-up-fill"></i>');
        } else {
            $fullContent.hide();
            $excerpt.show();
            $(this).html('קרא עוד <i class="bi bi-caret-down-fill"></i>');
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
    
    // מניעת כפילות קודי ג'אווהסקריפט שכבר קיימים בדף
    // וידוא שפונקציונליות כמו טיפול בכפתור "קרא עוד" פועלת גם בעמודים אחרים
    if ($('.read-more-toggle').length && $('.read-more-toggle').data('initialized') !== true) {
        $('.read-more-toggle').on('click', function(e) {
            e.preventDefault();
            var $content = $(this).closest('.update-content');
            var $excerpt = $content.find('.excerpt-content');
            var $fullContent = $content.find('.full-content');
            
            if ($excerpt.is(':visible')) {
                $excerpt.hide();
                $fullContent.show();
                $(this).html('סגור <i class="bi bi-caret-up-fill"></i>');
            } else {
                $fullContent.hide();
                $excerpt.show();
                $(this).html('קרא עוד <i class="bi bi-caret-down-fill"></i>');
            }
        }).data('initialized', true);
    }
    
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
});