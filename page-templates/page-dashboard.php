<?php
/*
Template Name: Dashboard
*/

// וידוא שהמשתמש מחובר
if (!is_user_logged_in()) {
    // הפניה לדף התחברות מעוצב במקום הפעלת מודל התחברות
    $login_page = get_page_by_path('login');
    if ($login_page) {
        wp_redirect(get_permalink($login_page->ID));
    } else {
        // אם דף ההתחברות לא קיים, הפנה לדף הבית
        wp_redirect(home_url('/'));
    }
    exit;
}

get_header();
$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_options');
?>

<div class="container-fluid vw-100 mt-4 mb-5 rtl" id="medmaster-dashboard">
    <div class="row w-100">
        <div class="col-12">
            <header class="mb-4 d-flex justify-content-between align-items-center">
                <h1>כל מה שחדש ומעניין</h1>
                <?php if ($is_admin) : ?>
                    <div class="admin-actions">
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUpdateModal">
                            <i class="bi bi-plus-circle"></i> עדכון חדש
                        </button>
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus"></i> משתמש חדש
                        </button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#manageUsersModal">
                            <i class="bi bi-people"></i> ניהול משתמשים
                        </button>
                    </div>
                <?php endif; ?>
            </header>

            <div id="updates-list" class="row">
                <?php
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                $args = array(
                    'post_type' => 'updates',
                    'posts_per_page' => 10,
                    'paged' => $paged,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'post_status' => $is_admin ? array('publish', 'future') : 'publish',
                );

                $updates_query = new WP_Query($args);

                if ($updates_query->have_posts()) :
                    while ($updates_query->have_posts()) : $updates_query->the_post();
                        $update_id = get_the_ID();
                        $publish_date = get_the_date('d/m/Y');
                        $post_status = get_post_status($update_id);
                        $is_future_post = ($post_status === 'future');

                        // דלג על פוסטים עתידיים עבור משתמשים שאינם מנהלים
                        if (!$is_admin && $is_future_post) {
                            continue;
                        }

                        $tags = get_the_terms($update_id, 'update_tag');
                        $tag_html = '';
                        $tag_color = '';
                        if ($tags && !is_wp_error($tags)) {
                            $tag_data = $tags[0];
                            $tag_color = get_term_meta($tag_data->term_id, 'tag_color', true);
                            $style = $tag_color ? 'style="background-color:' . esc_attr($tag_color) . '; color: #fff; border-color:' . esc_attr($tag_color) . '"' : '';
                            $tag_html = '<span class="badge me-2 update-tag" ' . $style . '>' . esc_html($tag_data->name) . '</span>';
                        }

                        $content = get_the_content();
                        $excerpt = wp_trim_words(get_the_excerpt(), 30, '...');
                        $has_more = (strlen($content) > strlen($excerpt) + 20);
                        ?>
                        <div class="col-md-12 mb-3 update-card-wrapper" data-update-id="<?php echo $update_id; ?>">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title d-flex justify-content-between">
                                        <?php the_title(); ?>
                                        <?php if ($is_admin) : ?>
                                            <div class="admin-update-actions">
                                                <button class="btn btn-sm btn-outline-secondary me-1 edit-update-btn" title="ערוך עדכון">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-update-btn" title="מחק עדכון">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="card-subtitle mb-2 text-muted">
                                        <?php echo $tag_html; ?>
                                        <span class="publish-date">
                                            <?php echo $publish_date; ?>
                                            <?php if ($is_admin && $is_future_post) : ?>
                                                <span class="badge bg-warning text-dark ms-1">עתידי (<?php echo get_the_date('d/m/Y H:i', $update_id); ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                    </p>
                                    <div class="card-text update-content">
                                        <?php if ($has_more) : ?>
                                            <div class="excerpt-content"><?php echo wpautop($excerpt); ?></div>
                                            <div class="full-content" style="display:none;"><?php echo wpautop($content); ?></div>
                                            <a href="#" class="read-more-toggle small">קרא עוד <i class="bi bi-caret-down-fill"></i></a>
                                        <?php else : ?>
                                            <?php echo wpautop($content); ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($is_admin) :
                                        $read_count = medmaster_get_update_read_count($update_id);
                                        $total_reps = medmaster_get_total_representatives_count();
                                    ?>
                                        <button class="btn btn-sm btn-outline-info mt-2 view-read-status-btn"
                                                data-bs-toggle="modal" data-bs-target="#readStatusModal"
                                                data-update-id="<?php echo $update_id; ?>"
                                                data-update-title="<?php echo esc_attr(get_the_title()); ?>">
                                            <i class="bi bi-eye"></i> <?php printf('%d/%d קראו', $read_count, $total_reps); ?>
                                        </button>
                                    <?php else :
                                        $has_read = medmaster_has_user_read_update($current_user->ID, $update_id);
                                    ?>
                                        <div class="form-check mt-3">
                                            <input class="form-check-input mark-as-read-checkbox" type="checkbox"
                                                id="readCheck-<?php echo $update_id; ?>"
                                                data-update-id="<?php echo $update_id; ?>"
                                                <?php checked($has_read); ?> <?php disabled($has_read); ?>>
                                            <label class="form-check-label" for="readCheck-<?php echo $update_id; ?>">
                                                קראתי
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;

                    // עימוד (פגינציה)
                    echo '<div class="col-12 mt-4">';
                    $big = 999999999;
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $updates_query->max_num_pages,
                        'prev_text' => '« קודם',
                        'next_text' => 'הבא »',
                        'type'  => 'list',
                    ));
                    echo '</div>';

                    wp_reset_postdata();
                else :
                    echo '<div class="col-12"><div class="alert alert-info">לא נמצאו עדכונים.</div></div>';
                endif;
                ?>
            </div><!-- /#updates-list -->
        </div>
    </div>
</div>

<?php
// כאן נוסיף את כל המודלים שלנו

// 1. מודל התחברות (מוצג רק למשתמשים לא מחוברים)
if (!is_user_logged_in()) :
?>
<!-- מודל התחברות -->
<div class="modal fade" id="loginModal" tabindex="-1" data-bs-backdrop="false" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">התחברות למערכת</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="login-form">
                    <div class="mb-3">
                        <label for="user_login" class="form-label">אימייל</label>
                        <input type="text" class="form-control" id="user_login" name="user_login" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_password" class="form-label">סיסמה</label>
                        <input type="password" class="form-control" id="user_password" name="user_password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                        <label class="form-check-label" for="remember_me">זכור אותי</label>
                    </div>
                    <div class="alert" id="login-message" style="display: none;"></div>
                    <?php wp_nonce_field('medmaster_ajax_nonce', 'security'); ?>
                    <button type="submit" class="btn btn-primary w-100">התחבר</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
// 2. מודל הוספת משתמש חדש (למנהלים בלבד)
if ($is_admin) :
?>
<!-- מודל הוספת משתמש -->
<div class="modal fade" data-bs-backdrop="false" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <h5 class="modal-title" id="addUserModalLabel">הוספת משתמש חדש</h5>
            </div>
            <div class="modal-body">
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
                    <div class="alert" id="add-user-message" style="display: none;"></div>
                    <?php wp_nonce_field('medmaster_ajax_nonce', 'security'); ?>
                    <button type="submit" class="btn btn-primary w-100">הוסף משתמש</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- תיקון מודל ניהול משתמשים -->
<div class="modal fade" data-bs-backdrop="false" id="manageUsersModal" tabindex="-1" aria-labelledby="manageUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <h5 class="modal-title" id="manageUsersModalLabel">ניהול משתמשים</h5>
            </div>
            <div class="modal-body">
                <div id="users-table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>שם</th>
                                <th>אימייל</th>
                                <th>סוג משתמש</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <?php
                            $all_users = get_users(array(
                                'meta_query' => array(
                                    'relation' => 'OR',
                                    array(
                                        'key' => 'wp_capabilities',
                                        'value' => 'administrator',
                                        'compare' => 'LIKE'
                                    ),
                                    array(
                                        'key' => 'wp_capabilities',
                                        'value' => 'representative',
                                        'compare' => 'LIKE'
                                    )
                                )
                            ));
                            
                            if (empty($all_users)) {
                                echo '<tr><td colspan="4" class="text-center">לא נמצאו משתמשים</td></tr>';
                            } else {
                                foreach ($all_users as $user_item) {
                                    $user_roles = get_userdata($user_item->ID)->roles;
                                    
                                    if (in_array('administrator', $user_roles) || in_array('representative', $user_roles)) {
                                        $role_display = in_array('administrator', $user_roles) ? 'מנהל' : 'נציג';
                                        echo '<tr data-user-id="' . $user_item->ID . '">';
                                        echo '<td>' . esc_html($user_item->display_name) . '</td>';
                                        echo '<td>' . esc_html($user_item->user_email) . '</td>';
                                        echo '<td>' . $role_display . '</td>';
                                        echo '<td>';
                                        echo '<button class="btn btn-sm btn-outline-secondary me-1 reset-password-btn" title="שנה סיסמה" data-user-id="' . $user_item->ID . '" data-user-name="' . esc_attr($user_item->display_name) . '"><i class="bi bi-key"></i></button>';
                                        
                                        if ($current_user->ID != $user_item->ID) {
                                            echo '<button class="btn btn-sm btn-outline-danger delete-user-btn" title="מחק משתמש" data-user-id="' . $user_item->ID . '" data-user-name="' . esc_attr($user_item->display_name) . '"><i class="bi bi-trash"></i></button>';
                                        }
                                        
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert" id="users-message" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">סגור</button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">הוסף משתמש חדש</button>
            </div>
        </div>
    </div>
</div>

<!-- מודל איפוס סיסמה -->
<div class="modal fade" data-bs-backdrop="false" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">איפוס סיסמה</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reset-password-form">
                    <input type="hidden" id="reset_user_id" name="user_id" value="">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">סיסמה חדשה</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="alert" id="reset-password-message" style="display: none;"></div>
                    <?php wp_nonce_field('medmaster_ajax_nonce', 'security'); ?>
                    <button type="submit" class="btn btn-primary w-100">אפס סיסמה</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- מודל הוספת עדכון חדש -->
<div class="modal fade" data-bs-backdrop="false" id="addUpdateModal" tabindex="-1" aria-labelledby="addUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <h5 class="modal-title" id="addUpdateModalLabel">הוספת עדכון חדש</h5>
            </div>
            <div class="modal-body">
                <form id="add-update-form">
                    <div class="mb-3">
                        <label for="update_title" class="form-label">כותרת</label>
                        <input type="text" class="form-control" id="update_title" name="update_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="update_tag" class="form-label">תגית</label>
                        <div class="d-flex">
                            <select class="form-select me-2" id="update_tag" name="update_tag">
                                <option value="">בחר תגית</option>
                                <?php 
                                $update_tags = get_terms(array(
                                    'taxonomy' => 'update_tag',
                                    'hide_empty' => false,
                                ));
                                foreach ($update_tags as $tag) : ?>
                                    <option value="<?php echo $tag->term_id; ?>"><?php echo $tag->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="add-new-tag-btn">תגית חדשה</button>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="new-tag-container" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="new_tag_name" class="form-label">שם התגית</label>
                                <input type="text" class="form-control" id="new_tag_name" placeholder="שם התגית">
                            </div>
                            <div class="col-md-4">
                                <label for="new_tag_color" class="form-label">צבע</label>
                                <input type="color" class="form-control form-control-color" id="new_tag_color" value="#0d6efd">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-primary w-100" type="button" id="save-new-tag-btn">שמור</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="update_publish_date" class="form-label">תאריך פרסום</label>
                        <input type="date" class="form-control" id="update_publish_date" name="update_publish_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                        <small class="form-text text-muted">השאר כתאריך הנוכחי לפרסום מיידי או הגדר תאריך עתידי לפרסום אוטומטי.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="update_content" class="form-label">תוכן</label>
                        <textarea class="form-control" id="update_content" name="update_content" rows="6" required></textarea>
                    </div>
                    
                    <div class="alert" id="add-update-message" style="display: none;"></div>
                    <?php wp_nonce_field('medmaster_ajax_nonce', 'security'); ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="add-update-submit">הוסף עדכון</button>
            </div>
        </div>
    </div>
</div>

<!-- מודל סטטוס קריאה -->
<div class="modal fade" data-bs-backdrop="false" id="readStatusModal" tabindex="-1" aria-labelledby="readStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="readStatusModalLabel">סטטוס קריאה: <span id="readStatusUpdateTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="read-status-content">
                    <p>טוען נתונים...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">סגור</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // אם המשתמש לא מחובר, הצג חלון התחברות אוטומטית בטעינת הדף
    <?php if (!is_user_logged_in()) : ?>
    var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
    <?php endif; ?>
    
    // טיפול בטופס התחברות
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#login-message');
        
        $message.removeClass('alert-danger alert-success')
                .addClass('alert-info')
                .html(medmaster_ajax.loading_message)
                .show();
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
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
    
    <?php if ($is_admin) : ?>
    // טיפול בהוספת משתמש חדש
    $('#add-user-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#add-user-message');
        
        $message.removeClass('alert-danger alert-success')
                .addClass('alert-info')
                .html(medmaster_ajax.loading_message)
                .show();
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_ajax_register',
                user_name: $('#user_name').val(),
                user_email: $('#user_email').val(),
                user_password: $('#user_password').val(),
                user_role: $('#user_role').val(),
                security: $form.find('input[name="security"]').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);
                    $form[0].reset();
                    
                    // סגירת המודל ורענון הדף אחרי 2 שניות
                    setTimeout(function() {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                        if (modal) {
                            modal.hide();
                        }
                        location.reload();
                    }, 2000);
                } else {
                    $message.removeClass('alert-info').addClass('alert-danger').html(response.data.message);
                }
            },
            error: function() {
                $message.removeClass('alert-info').addClass('alert-danger').html('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
            }
        });
    });
    
    // טיפול במחיקת משתמש
    $('.delete-user-btn').on('click', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        
        if (confirm('האם אתה בטוח שברצונך למחוק את המשתמש "' + userName + '"?')) {
            var $message = $('#users-message');
            
            $message.removeClass('alert-danger alert-success')
                    .addClass('alert-info')
                    .html(medmaster_ajax.loading_message)
                    .show();
            
            $.ajax({
                url: medmaster_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'medmaster_ajax_delete_user',
                    user_id: userId,
                    security: $('input[name="security"]:first').val()
                },
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);
                        
                        // הסרת השורה מהטבלה
                        $('tr[data-user-id="' + userId + '"]').fadeOut(500, function() {
                            $(this).remove();
                        });
                    } else {
                        $message.removeClass('alert-info').addClass('alert-danger').html(response.data.message);
                    }
                },
                error: function() {
                    $message.removeClass('alert-info').addClass('alert-danger').html('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
                }
            });
        }
    });
    
    // טיפול באיפוס סיסמה
    $('.reset-password-btn').on('click', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        
        $('#resetPasswordModalLabel').text('איפוס סיסמה: ' + userName);
        $('#reset_user_id').val(userId);
    });
    
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#reset-password-message');
        
        $message.removeClass('alert-danger alert-success')
                .addClass('alert-info')
                .html(medmaster_ajax.loading_message)
                .show();
        
                $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_ajax_reset_password',
                user_id: $('#reset_user_id').val(),
                new_password: $('#new_password').val(),
                security: $form.find('input[name="security"]').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);
                    $form[0].reset();
                    
                    // סגירת המודל אחרי 2 שניות
                    setTimeout(function() {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal'));
                        if (modal) {
                            modal.hide();
                        }
                    }, 2000);
                } else {
                    $message.removeClass('alert-info').addClass('alert-danger').html(response.data.message);
                }
            },
            error: function() {
                $message.removeClass('alert-info').addClass('alert-danger').html('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
            }
        });
    });
    
    // טיפול בהצגת טופס תגית חדשה
    $('#add-new-tag-btn').on('click', function() {
        $('#new-tag-container').slideDown();
    });
    
    // טיפול בשמירת תגית חדשה
    $('#save-new-tag-btn').on('click', function() {
        var tagName = $('#new_tag_name').val().trim();
        var tagColor = $('#new_tag_color').val();
        
        if (!tagName) {
            alert('אנא הזן שם לתגית');
            return;
        }
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_ajax_add_tag',
                tag_name: tagName,
                tag_color: tagColor,
                security: $('input[name="security"]:first').val()
            },
            success: function(response) {
                if (response.success) {
                    // הוסף את התגית החדשה לרשימה
                    $('#update_tag').append(new Option(response.data.term_name, response.data.term_id, true, true));
                    
                    // נקה ואז הסתר את אזור הוספת התגית
                    $('#new_tag_name').val('');
                    $('#new_tag_color').val('#0d6efd');
                    $('#new-tag-container').slideUp();
                    
                    // הצג הודעת הצלחה
                    alert('התגית נוצרה בהצלחה');
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
            }
        });
    });
    
    // טיפול בהוספת עדכון חדש
    $('#add-update-submit').on('click', function() {
        var $form = $('#add-update-form');
        var $message = $('#add-update-message');
        
        // בדיקת תקינות השדות
        var title = $('#update_title').val().trim();
        var content = $('#update_content').val().trim();
        
        if (!title) {
            alert('אנא הזן כותרת לעדכון');
            return;
        }
        
        if (!content) {
            alert('אנא הזן תוכן לעדכון');
            return;
        }
        
        $message.removeClass('alert-danger alert-success')
                .addClass('alert-info')
                .html(medmaster_ajax.loading_message)
                .show();
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_ajax_add_update',
                update_title: title,
                update_content: content,
                update_tag: $('#update_tag').val(),
                update_publish_date: $('#update_publish_date').val(),
                security: $form.find('input[name="security"]').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);
                    $form[0].reset();
                    
                    // סגירת המודל ורענון הדף אחרי 2 שניות
                    setTimeout(function() {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('addUpdateModal'));
                        if (modal) {
                            modal.hide();
                        }
                        location.reload();
                    }, 2000);
                } else {
                    $message.removeClass('alert-info').addClass('alert-danger').html(response.data.message);
                }
            },
            error: function() {
                $message.removeClass('alert-info').addClass('alert-danger').html('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
            }
        });
    });
    
    // טיפול בצפייה בסטטוס קריאה
    $('.view-read-status-btn').on('click', function() {
        var updateId = $(this).data('update-id');
        var updateTitle = $(this).data('update-title');
        
        $('#readStatusUpdateTitle').text(updateTitle);
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_ajax_get_read_status',
                update_id: updateId,
                security: $('input[name="security"]:first').val()
            },
            success: function(response) {
                if (response.success) {
                    var usersData = response.data;
                    var html = '<table class="table table-striped">';
                    html += '<thead><tr><th>שם</th><th>אימייל</th><th>סטטוס</th><th>זמן קריאה</th></tr></thead>';
                    html += '<tbody>';
                    
                    if (usersData.length === 0) {
                        html += '<tr><td colspan="4" class="text-center">אין משתמשים מסוג נציג במערכת</td></tr>';
                    } else {
                        for (var i = 0; i < usersData.length; i++) {
                            var user = usersData[i];
                            var statusHtml = user.has_read 
                                ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i> נקרא</span>' 
                                : '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> לא נקרא</span>';
                            
                            html += '<tr>';
                            html += '<td>' + user.name + '</td>';
                            html += '<td>' + user.email + '</td>';
                            html += '<td>' + statusHtml + '</td>';
                            html += '<td>' + (user.read_timestamp || '---') + '</td>';
                            html += '</tr>';
                        }
                    }
                    
                    html += '</tbody></table>';
                    $('#read-status-content').html(html);
                } else {
                    $('#read-status-content').html('<div class="alert alert-danger">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('#read-status-content').html('<div class="alert alert-danger">שגיאת שרת. אנא נסה שוב מאוחר יותר.</div>');
            }
        });
    });
    
    // טיפול במחיקת עדכון
    $('.delete-update-btn').on('click', function() {
        var $updateCard = $(this).closest('.update-card-wrapper');
        var updateId = $updateCard.data('update-id');
        var updateTitle = $updateCard.find('.card-title').text().trim();
        
        if (confirm('האם אתה בטוח שברצונך למחוק את העדכון "' + updateTitle + '"?')) {
            $.ajax({
                url: medmaster_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'medmaster_ajax_delete_update',
                    update_id: updateId,
                    security: $('input[name="security"]:first').val()
                },
                success: function(response) {
                    if (response.success) {
                        // הסרת כרטיס העדכון מהדף
                        $updateCard.fadeOut(500, function() {
                            $(this).remove();
                            
                            // הצגת הודעת הצלחה
                            $('#updates-list').prepend(
                                '<div class="col-12 mb-3"><div class="alert alert-success">' + 
                                response.data.message + 
                                '</div></div>'
                            );
                            
                            // הסרת ההודעה אחרי 3 שניות
                            setTimeout(function() {
                                $('#updates-list .alert').fadeOut(500, function() {
                                    $(this).parent().remove();
                                });
                            }, 3000);
                        });
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
                }
            });
        }
    });
    <?php endif; ?>
    
   
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
    
$(document).ready(function() {
    // הסרת מאזינים קיימים ויצירת חדשים
    $(document).off('click', '.read-more-toggle').on('click', '.read-more-toggle', function(e) {
        e.preventDefault();
        var $content = $(this).closest('.update-content');
        var $excerpt = $content.find('.excerpt-content');
        var $fullContent = $content.find('.full-content');
        
        if ($excerpt.is(':visible')) {
            $excerpt.slideUp(300, function() {
                $fullContent.slideDown(300);
            });
            $(this).html('סגור <i class="bi bi-caret-up-fill"></i>');
        } else {
            $fullContent.slideUp(300, function() {
                $excerpt.slideDown(300);
            });
            $(this).html('קרא עוד <i class="bi bi-caret-down-fill"></i>');
        }
    });
});
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
    });
    
    // וידוא פעולת מודלים - בוטסטרפ 5
    document.addEventListener('DOMContentLoaded', function() {
        // בדיקת גרסת Bootstrap
        console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Loaded' : 'Not loaded');
        
        // אם יש בעיה עם פעולת המודלים, להפעיל את הקוד הבא:
        const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', function() {
                const targetSelector = this.getAttribute('data-bs-target');
                if (!targetSelector) return;
                
                const modalElement = document.querySelector(targetSelector);
                if (!modalElement) return;
                
                try {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } catch (e) {
                    console.error('Error showing modal:', e);
                }
            });
        });
    });
});
</script>

<?php get_footer(); ?>