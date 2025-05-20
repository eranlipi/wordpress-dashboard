<?php
/*
Template Name: Manage Users
*/

// בדיקה שהמשתמש מחובר והוא מנהל
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

get_header();
$current_user = wp_get_current_user();
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>ניהול משתמשים</h5>
                    <button type="button" class="close-btn" onclick="window.location.href='<?php echo get_permalink(get_page_by_path('dashboard')); ?>'">×</button>
                </div>
                <div class="card-body">
                    <?php
                    // בדוק אם יש משתמשים לפני יצירת הטבלה
                    $all_users = get_users(array('role__in' => array('administrator', 'representative')));
                    
                    if (empty($all_users)) {
                        echo '<div class="alert alert-info">לא נמצאו משתמשים במערכת.</div>';
                    } else {
                    ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>שם</th>
                                <th>אימייל</th>
                                <th>סוג משתמש</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($all_users as $user_item) {
                                // וודא שיש למשתמש תפקידים מוגדרים
                                if (!isset($user_item->roles) || !is_array($user_item->roles)) {
                                    continue;
                                }
                                
                                $role_display = in_array('administrator', $user_item->roles) ? 'מנהל' : 'נציג';
                                echo '<tr data-user-id="' . $user_item->ID . '">';
                                echo '<td>' . esc_html($user_item->display_name) . '</td>';
                                echo '<td>' . esc_html($user_item->user_email) . '</td>';
                                echo '<td>' . $role_display . '</td>';
                                echo '<td class="d-flex">';
                                echo '<button class="btn btn-sm btn-outline-secondary reset-password-btn me-1" title="שנה סיסמה" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" data-user-id="' . $user_item->ID . '" data-user-name="' . esc_attr($user_item->display_name) . '"><i class="bi bi-key"></i></button>';
                                
                                if ($current_user->ID != $user_item->ID) { // לא ניתן למחוק את עצמך
                                    echo '<button class="btn btn-sm btn-outline-danger delete-user-btn" title="מחק משתמש" data-user-id="' . $user_item->ID . '" data-user-name="' . esc_attr($user_item->display_name) . '"><i class="bi bi-trash"></i></button>';
                                }
                                
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php } ?>
                    
                    <div class="mt-3 text-end">
                        <a href="<?php echo get_permalink(get_page_by_path('add-user')); ?>" class="btn btn-success">
                            <i class="bi bi-person-plus"></i> הוסף משתמש חדש
                        </a>
                    </div>
                    
                    <div id="user-management-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- מודל לשינוי סיסמה -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reset-password-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">שינוי סיסמה</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reset_user_id" name="user_id" value="">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">סיסמה חדשה</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div id="reset-password-message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" class="btn btn-primary">שמור</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('Page manage users script loaded'); // הוספת לוג לבדיקה
    
    // כפתור מחיקת משתמש
    $('.delete-user-btn').on('click', function() {
        const userRow = $(this).closest('tr');
        const userId = userRow.data('user-id');
        const userName = userRow.find('td').first().text().trim();
        
        console.log('Delete user clicked:', userId, userName); // הוספת לוג לבדיקה
        
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
                    console.log('Delete user response:', response); // הוספת לוג לבדיקה
                    
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
                    console.error('Delete user error:', error, xhr.responseText); // הוספת לוג לבדיקה
                    $('#user-management-message').html('<div class="alert alert-danger">שגיאת שרת במחיקת המשתמש.</div>');
                }
            });
        }
    });
    
    // כפתור שינוי סיסמה
    $('.reset-password-btn').on('click', function() {
        const userRow = $(this).closest('tr');
        const userId = userRow.data('user-id');
        const userName = userRow.find('td').first().text().trim();
        
        console.log('Reset password clicked:', userId, userName); // הוספת לוג לבדיקה
        
        $('#resetPasswordModalLabel').text(`שינוי סיסמה: ${userName}`);
        $('#reset_user_id').val(userId);
        $('#new_password').val('');
        $('#reset-password-message').html('');
        
        const resetPasswordModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
        resetPasswordModal.show();
    });
    
    // שליחת טופס שינוי סיסמה
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const userId = $('#reset_user_id').val();
        const newPassword = $('#new_password').val();
        
        console.log('Reset password form submitted:', userId); // הוספת לוג לבדיקה
        
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
                console.log('Reset password response:', response); // הוספת לוג לבדיקה
                
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
                console.error('Reset password error:', error, xhr.responseText); // הוספת לוג לבדיקה
                $('#reset-password-message').html('<div class="alert alert-danger">שגיאת שרת בשינוי הסיסמה.</div>');
            }
        });
    });
});
</script>

<?php get_footer(); ?>