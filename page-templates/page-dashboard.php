<?php
/*
Template Name: Dashboard
*/

if (!is_user_logged_in()) {
    $login_page = get_page_by_path('login');
    if ($login_page) {
        wp_redirect(get_permalink($login_page->ID));
    } else {
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
            <header class="mb-4 d-flex justify-content-between align-items-center flex-column">
                <h1 class="text-center">מערכת עדכונים וחדשות – מד-מאסטר</h1>
                <h3 class="text-center">כל מה שחדש וחשוב לדעת!</h3>
                <?php if ($is_admin) : ?>
                    <div class="admin-actions align-self-end">
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUpdateModal">
                            <i class="bi bi-plus-circle"></i> הוסף עדכון
                        </button>
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus"></i> הוסף משתמש
                        </button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#manageUsersModal">
                            <i class="bi bi-people"></i> ניהול משתמשים
                        </button>
                    </div>

                    <div class="modal fade" data-bs-backdrop="false" id="editUpdateModal" tabindex="-1" aria-labelledby="editUpdateModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editUpdateModalLabel">עריכת עדכון</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="edit-update-form">
                                        <input type="hidden" id="edit_update_id" name="update_id" value="">

                                        <div class="mb-3">
                                            <label for="edit_update_title" class="form-label">כותרת</label>
                                            <input type="text" class="form-control" id="edit_update_title" name="edit_update_title" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit_update_tag" class="form-label">תגית</label>
                                            <select class="form-select" id="edit_update_tag" name="edit_update_tag">
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
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit_update_publish_date" class="form-label">תאריך פרסום</label>
                                            <input type="date" class="form-control" id="edit_update_publish_date" name="edit_update_publish_date">
                                            <small class="form-text text-muted">השאר ריק לפרסום מיידי או הגדר תאריך עתידי לפרסום אוטומטי.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit_update_content" class="form-label">תוכן</label>
                                            <?php
                                            wp_editor('', 'edit_update_content', array(
                                                'textarea_name' => 'edit_update_content',
                                                'textarea_rows' => 6,
                                                'media_buttons' => true,
                                                'teeny' => false,
                                                'tinymce' => array(
                                                    'directionality' => 'rtl',
                                                ),
                                            ));
                                            ?>
                                        </div>

                                        <div class="alert" id="edit-update-message" style="display: none;"></div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                                    <button type="button" class="btn btn-primary" id="edit-update-submit">שמור שינויים</button>
                                </div>
                            </div>
                        </div>
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
                        <div class="col-md-12 mb-3 update-card-wrapper" data-update-id="<?php echo $update_id; ?>" data-update-date="<?php echo get_the_date('c'); ?>">
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
                                            <span class="badge ms-1 <?php echo $is_future_post ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                                <?php if ($is_future_post) : ?>
                                                    Scheduled (<?php echo get_the_date('d/m/Y H:i', $update_id); ?>)
                                                <?php else : ?>
                                                    Public
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                    </p>
                                    <div class="card-text update-content">
                                        <?php if ($has_more) : ?>
                                            <div class="excerpt-content"><?php echo wpautop(make_clickable($excerpt)); ?></div>
                                            <div class="full-content" style="display:none;"><?php echo wpautop(make_clickable($content)); ?></div>
                                            <a href="#" class="read-more-toggle small">קרא עוד <i class="bi bi-caret-down-fill"></i></a>
                                        <?php else : ?>
                                            <?php echo wpautop(make_clickable($content)); ?>
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
                                            <i class="bi bi-eye"></i> <?php printf('%d/%d נקרא', $read_count, $total_reps); ?>
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
                                                קראתי את זה
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                <?php endwhile;

                    echo '<div class="col-12 mt-4">';
                    $big = 999999999;
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $updates_query->max_num_pages,
                        'prev_text' => '« הקודם',
                        'next_text' => 'הבא »',
                        'type'  => 'list',
                    ));
                    echo '</div>';

                    wp_reset_postdata();
                else :
                    echo '<div class="col-12"><div class="alert alert-info">לא נמצאו עדכונים.</div></div>';
                endif;
                ?>
            </div>
        </div>
    </div>
</div>

<?php if (!is_user_logged_in()) : ?>
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

<?php if ($is_admin) : ?>
    <div class="modal fade" data-bs-backdrop="false" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">הוסף משתמש חדש</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                <option value="representative">נציג</option>
                                <option value="administrator">מנהל</option>
                            </select>
                        </div>
                        <div class="alert" id="add-user-message" style="display: none;"></div>
                        <button type="submit" class="btn btn-primary w-100">הוסף משתמש</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="false" id="manageUsersModal" tabindex="-1" aria-labelledby="manageUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageUsersModalLabel">ניהול משתמשים</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="users-table-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p>טוען משתמשים...</p>
                        </div>
                    </div>
                    <div id="user-management-message" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">סגור</button>
                    <a href="<?php echo get_permalink(14); ?>" class="btn btn-success">
                        <i class="bi bi-person-plus"></i> הוסף משתמש חדש
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="false" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">שינוי סיסמה</h5>
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
                        <button type="submit" class="btn btn-primary w-100">שנה סיסמה</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="false" id="addUpdateModal" tabindex="-1" aria-labelledby="addUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUpdateModalLabel">הוסף עדכון חדש</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <small class="form-text text-muted">השאר כתאריך נוכחי לפרסום מיידי או קבע תאריך עתידי לפרסום אוטומטי.</small>
                        </div>

                        <div class="mb-3">
                            <label for="update_content" class="form-label">תוכן</label>
                            <?php
                            wp_editor('', 'update_content', array(
                                'textarea_name' => 'update_content',
                                'textarea_rows' => 6,
                                'media_buttons' => true,
                                'teeny' => false,
                                'tinymce' => array(
                                    'directionality' => 'rtl',
                                ),
                            ));
                            ?>
                        </div>

                        <div class="alert" id="add-update-message" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                    <button type="button" class="btn btn-primary" id="add-update-submit">הוסף עדכון</button>
                </div>
            </div>
        </div>
    </div>

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

        <?php if (!is_user_logged_in()) : ?>
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        <?php endif; ?>

        $('#login-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $message = $('#login-message');

            $message.removeClass('alert-danger alert-success')
                .addClass('alert-info')
                .html('מתחבר למערכת, אנא המתן...')
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
            $('#add-user-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $message = $('#add-user-message');

                var formData = {
                    action: 'medmaster_add_user',
                    user_name: $('#user_name').val(),
                    user_email: $('#user_email').val(),
                    user_password: $('#user_password').val(),
                    user_role: $('#user_role').val(),
                    nonce: medmaster_ajax.nonce
                };

                $message.removeClass('alert-danger alert-success')
                    .addClass('alert-info')
                    .html('מוסיף משתמש...')
                    .show();

                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);
                            $form[0].reset();

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
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText);
                        $message.removeClass('alert-info').addClass('alert-danger').html('שגיאת שרת: ' + error);
                    }
                });
            });

            $('.delete-user-btn').on('click', function() {
                var userId = $(this).data('user-id');
                var userName = $(this).data('user-name');

                if (confirm('האם אתה בטוח שברצונך למחוק את המשתמש "' + userName + '"?')) {
                    var $message = $('#users-message');

                    $message.removeClass('alert-danger alert-success')
                        .addClass('alert-info')
                        .html('מוחק משתמש...')
                        .show();

                    $.ajax({
                        url: medmaster_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'medmaster_delete_user',
                            user_id: userId,
                            nonce: medmaster_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);

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

            $('.reset-password-btn').on('click', function() {
                var userId = $(this).data('user-id');
                var userName = $(this).data('user-name');

                $('#resetPasswordModalLabel').text('שינוי סיסמה: ' + userName);
                $('#reset_user_id').val(userId);
            });

            $('#reset-password-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $message = $('#reset-password-message');

                $message.removeClass('alert-danger alert-success')
                    .addClass('alert-info')
                    .html('משנה סיסמה...')
                    .show();

                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'medmaster_reset_user_password',
                        user_id: $('#reset_user_id').val(),
                        new_password: $('#new_password').val(),
                        nonce: medmaster_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);
                            $form[0].reset();

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

            $('#add-new-tag-btn').on('click', function() {
                $('#new-tag-container').slideDown();
            });

            $('#save-new-tag-btn').on('click', function() {
                var tagName = $('#new_tag_name').val().trim();
                var tagColor = $('#new_tag_color').val();

                if (!tagName) {
                    alert('נא להזין שם תגית');
                    return;
                }

                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'medmaster_add_update_tag',
                        tag_name: tagName,
                        tag_color: tagColor,
                        nonce: medmaster_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#update_tag').append(new Option(response.data.name, response.data.term_id, true, true));

                            $('#new_tag_name').val('');
                            $('#new_tag_color').val('#0d6efd');
                            $('#new-tag-container').slideUp();

                            alert('תגית נוצרה בהצלחה');
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('שגיאת שרת. אנא נסה שוב מאוחר יותר.');
                    }
                });
            });

            $('#add-update-submit').on('click', function() {
                var $form = $('#add-update-form');
                var $message = $('#add-update-message');

                var title = $('#update_title').val().trim();
                var content = tinymce.get('update_content').getContent();

                if (!title) {
                    alert('נא להזין כותרת לעדכון');
                    return;
                }

                if (!content) {
                    alert('נא להזין תוכן לעדכון');
                    return;
                }

                $message.removeClass('alert-danger alert-success')
                    .addClass('alert-info')
                    .html('מוסיף עדכון...')
                    .show();

                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'medmaster_add_update',
                        update_title: title,
                        update_content: content,
                        update_tag: $('#update_tag').val(),
                        update_publish_date: $('#update_publish_date').val(),
                        nonce: medmaster_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);
                            $form[0].reset();
                            tinymce.get('update_content').setContent('');

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

            $('.view-read-status-btn').on('click', function() {
                var updateId = $(this).data('update-id');
                var updateTitle = $(this).data('update-title');

                $('#readStatusUpdateTitle').text(updateTitle);

                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'medmaster_get_read_status',
                        update_id: updateId,
                        nonce: medmaster_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var usersData = response.data;
                            var html = '<table class="table table-striped">';
                            html += '<thead><tr><th>שם</th><th>אימייל</th><th>סטטוס</th></tr></thead>';
                            html += '<tbody>';

                            if (usersData.length === 0) {
                                html += '<tr><td colspan="4" class="text-center">אין נציגים במערכת</td></tr>';
                            } else {
                                for (var i = 0; i < usersData.length; i++) {
                                    var user = usersData[i];
                                    var statusHtml = user.has_read ?
                                        '<span class="text-success"><i class="bi bi-check-circle-fill"></i> נקרא</span>' :
                                        '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> לא נקרא</span>';

                                    html += '<tr>';
                                    html += '<td>' + user.name + '</td>';
                                    html += '<td>' + user.email + '</td>';
                                    html += '<td>' + statusHtml + '</td>';
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

            $('.delete-update-btn').on('click', function() {
                var $updateCard = $(this).closest('.update-card-wrapper');
                var updateId = $updateCard.data('update-id');
                var updateTitle = $updateCard.find('.card-title').text().trim();

                if (confirm('האם אתה בטוח שברצונך למחוק את העדכון "' + updateTitle + '"?')) {
                    $.ajax({
                        url: medmaster_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'medmaster_delete_update',
                            update_id: updateId,
                            nonce: medmaster_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $updateCard.fadeOut(500, function() {
                                    $(this).remove();

                                    $('#updates-list').prepend(
                                        '<div class="col-12 mb-3"><div class="alert alert-success">' +
                                        response.data.message +
                                        '</div></div>'
                                    );

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
            // כפתור עריכת עדכון
            $('.edit-update-btn').on('click', function() {
                const $updateCard = $(this).closest('.update-card-wrapper');
                const updateId = $updateCard.data('update-id');

                // איפוס טופס העריכה
                $('#edit-update-form')[0].reset();
                $('#edit-update-message').hide();
                $('#edit_update_id').val(updateId);
                if (tinymce.get('edit_update_content')) {
                    tinymce.get('edit_update_content').setContent('');
                }

                // טעינת נתוני העדכון
                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'medmaster_get_update',
                        update_id: updateId,
                        nonce: medmaster_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            $('#edit_update_title').val(data.title);
                            if (tinymce.get('edit_update_content')) {
                                tinymce.get('edit_update_content').setContent(data.content);
                            } else {
                                $('#edit_update_content').val(data.content);
                            }
                            $('#edit_update_tag').val(data.tag_id);
                            $('#edit_update_publish_date').val(data.publish_date);

                            // פתיחת המודל
                            const editModal = new bootstrap.Modal(document.getElementById('editUpdateModal'));
                            editModal.show();
                        } else {
                            alert(response.data.message || 'שגיאה בטעינת העדכון');
                        }
                    },
                    error: function() {
                        alert('שגיאת שרת בטעינת העדכון');
                    }
                });
            });

            // שמירת העדכון המעודכן
            $('#edit-update-submit').on('click', function() {
                const updateId = $('#edit_update_id').val();
                const title = $('#edit_update_title').val().trim();
                const content = tinymce.get('edit_update_content').getContent();
                const tagId = $('#edit_update_tag').val();
                const publishDate = $('#edit_update_publish_date').val();

                if (!title) {
                    alert('נא להזין כותרת לעדכון');
                    return;
                }

                if (!content) {
                    alert('נא להזין תוכן לעדכון');
                    return;
                }

                const $message = $('#edit-update-message');
                $message.removeClass('alert-danger alert-success')
                    .addClass('alert-info')
                    .html('שומר שינויים...')
                    .show();

                $.ajax({
                    url: medmaster_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'medmaster_edit_update',
                        update_id: updateId,
                        update_title: title,
                        update_content: content,
                        update_tag: tagId,
                        update_publish_date: publishDate,
                        nonce: medmaster_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('alert-info').addClass('alert-success').html(response.data.message);

                            // הפניה ללוח הבקרה אחרי 2 שניות
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $message.removeClass('alert-info').addClass('alert-danger').html(
                                response.data.message || 'שגיאה בשמירת העדכון'
                            );
                        }
                    },
                    error: function() {
                        $message.removeClass('alert-info').addClass('alert-danger').html('שגיאת שרת בשמירת העדכון');
                    }
                });
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

        document.addEventListener('DOMContentLoaded', function() {
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