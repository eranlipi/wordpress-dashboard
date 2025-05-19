<?php
/*
Template Name: Add User
*/

// בדיקה שהמשתמש מחובר והוא מנהל
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>הוספת משתמש חדש</h5>
                    <button type="button" class="close-btn" onclick="window.location.href='<?php echo get_permalink(get_page_by_path('dashboard')); ?>'">×</button>
                </div>
                <div class="card-body">
                    <p>הזן את פרטי המשתמש החדש להוספה למערכת</p>
                    
                    <form id="add-user-form">
                        <div class="mb-3">
                            <label for="user_name" class="form-label">שם</label>
                            <input type="text" class="form-control" id="user_name" name="user_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="user_email" class="form-label">אימייל</label>
                            <input type="email" class="form-control" id="user_email" name="user_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="user_password" class="form-label">סיסמה</label>
                            <input type="password" class="form-control" id="user_password" name="user_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="user_role" class="form-label">סוג משתמש</label>
                            <select class="form-select" id="user_role" name="user_role">
                                <option value="representative">משתמש רגיל</option>
                                <option value="administrator">מנהל</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='<?php echo get_permalink(get_page_by_path('dashboard')); ?>'">ביטול</button>
                            <button type="submit" class="btn btn-primary">הוסף משתמש</button>
                        </div>
                    </form>
                    
                    <div id="add-user-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-user-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        $('#add-user-message').html('<div class="alert alert-info">מוסיף משתמש...</div>');
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=medmaster_add_user&nonce=' + medmaster_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    $('#add-user-message').html('<div class="alert alert-success">' + response.data.message + '</div>');
                    $('#add-user-form')[0].reset();
                    
                    // הפניה ללוח הבקרה אחרי 2 שניות
                    setTimeout(function() {
                        window.location.href = '<?php echo get_permalink(get_page_by_path('dashboard')); ?>';
                    }, 2000);
                } else {
                    $('#add-user-message').html('<div class="alert alert-danger">' + (response.data.message || 'שגיאה בהוספת המשתמש.') + '</div>');
                }
            },
            error: function() {
                $('#add-user-message').html('<div class="alert alert-danger">שגיאת שרת בהוספת המשתמש.</div>');
            }
        });
    });
});
</script>

<?php get_footer(); ?>