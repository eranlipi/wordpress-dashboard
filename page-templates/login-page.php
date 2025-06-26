<?php
/*
Template Name: Login Page
*/

// אם המשתמש כבר מחובר, הפנה אותו ללוח הבקרה
if (is_user_logged_in()) {
    wp_redirect(get_permalink(get_page_by_path('dashboard')));
    exit;
}

get_header('minimal'); // שימוש בכותרת מינימלית
?>

<div class="container login-container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0 text-center"><?php the_title(); ?></h3>
                </div>
                <div class="card-body p-4">
                    <?php
                    // בדיקה אם יש הודעת שגיאה
                    $login_error = isset($_GET['login']) && $_GET['login'] === 'failed' ? true : false;

                    if ($login_error) {
                        echo '<div class="alert alert-danger mb-3">שם משתמש או סיסמה שגויים. נסה שנית.</div>';
                    }
                    ?>

                    <form id="login-form" class="rtl">
                        <div class="mb-3">
                            <label for="user_login" class="form-label">אימייל</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="text" class="form-control" id="user_login" name="user_login" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="user_password" class="form-label">סיסמה</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="user_password" name="user_password" required>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" checked>
                            <label class="form-check-label" for="remember_me">זכור אותי</label>
                        </div>

                        <div class="alert" id="login-message" style="display: none;"></div>
                        <?php wp_nonce_field('medmaster_ajax_nonce', 'security'); ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>התחבר
                            </button>
                        </div>
                    </form>

                    <?php if (get_option('users_can_register')) : ?>
                        <div class="text-center mt-3">
                            <p class="mb-0">אין לך חשבון? <a href="<?php echo wp_registration_url(); ?>">הירשם כאן</a></p>
                        </div>
                    <?php endif; ?>
                    <?php /*
                    <div class="text-center mt-2">
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="text-muted">שכחת את הסיסמה?</a>
                    </div>
                    */ ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#login-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $message = $('#login-message');

            $message.removeClass('alert-danger alert-success')
                .addClass('alert-info')
                .html('מתחבר למערכת, אנא המתן...')
                .show();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'medmaster_ajax_login',
                    user_login: $('#user_login').val(),
                    user_password: $('#user_password').val(),
                    remember_me: $('#remember_me').is(':checked'),
                    security: $form.find('input[name="security"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);

                        // הפנייה לדף הראשי אחרי 1 שניות
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        $message.removeClass('alert-info').addClass('alert-danger').html(response.data.message);
                    }
                },
                error: function() {
                    $message.removeClass('alert-info').addClass('alert-danger').html('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
                }
            });
        });
    });
</script>

<?php get_footer('minimal'); // שימוש בפוטר מינימלי 
?>