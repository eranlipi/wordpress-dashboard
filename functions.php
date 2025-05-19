<?php
/**
 * Med-Master Updates System Functions
 */

// =====================================================
// Register custom post types and taxonomies
// =====================================================

function medmaster_register_post_types() {
    register_post_type('updates', [
        'labels' => [
            'name' => 'Updates',
            'singular_name' => 'Update',
            'add_new' => 'New Update',
            'add_new_item' => 'Add New Update',
            'edit_item' => 'Edit Update',
        ],
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => ['title', 'editor', 'author'],
        'show_in_rest' => true,
        'menu_position' => 25,
    ]);

    register_taxonomy('update_tag', 'updates', [
        'labels' => [
            'name' => 'Update Tags',
            'singular_name' => 'Update Tag',
            'add_new_item' => 'Add New Tag',
        ],
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'medmaster_register_post_types');

// =====================================================
// Create custom database tables
// =====================================================

function medmaster_create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        update_id bigint(20) NOT NULL,
        read_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_update (user_id,update_id),
        KEY user_id (user_id),
        KEY update_id (update_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    return dbDelta($sql);
}

register_activation_hook(__FILE__, 'medmaster_create_tables');

function medmaster_check_table_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        medmaster_create_tables();
    }
}
add_action('init', 'medmaster_check_table_exists');

// =====================================================
// Add representative role
// =====================================================

function medmaster_add_representative_role() {
    if (!get_role('representative')) {
        add_role('representative', 'Representative', [
            'read' => true,
            'representative' => true
        ]);
    }
}
register_activation_hook(__FILE__, 'medmaster_add_representative_role');
add_action('init', 'medmaster_add_representative_role');

// =====================================================
// Enqueue scripts and styles
// =====================================================

function medmaster_enqueue_scripts() {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css');
    wp_enqueue_style('medmaster-style', get_stylesheet_directory_uri() . '/css/medmaster-style.css');
    
    wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
    wp_enqueue_script('medmaster-scripts', get_stylesheet_directory_uri() . '/js/medmaster-scripts.js', ['jquery'], null, true);
    
    wp_localize_script('medmaster-scripts', 'medmaster_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('medmaster_ajax_nonce'),
        'loading_message' => 'Processing data, please wait...',
        'home_url' => home_url('/'),
    ]);
}
add_action('wp_enqueue_scripts', 'medmaster_enqueue_scripts');

// =====================================================
// Helper functions
// =====================================================

function medmaster_has_user_read_update($user_id, $update_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        medmaster_create_tables();
        return false;
    }
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND update_id = %d",
        $user_id, $update_id
    ));
    
    return $result > 0;
}

function medmaster_get_update_read_count($update_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        medmaster_create_tables();
        return 0;
    }
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE update_id = %d",
        $update_id
    ));
}

function medmaster_get_total_representatives_count() {
    return count(get_users(['role' => 'representative']));
}

// =====================================================
// AJAX Login
// =====================================================

function medmaster_ajax_login() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    $user_email = isset($_POST['user_login']) ? sanitize_text_field($_POST['user_login']) : '';
    $password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
    $remember = isset($_POST['remember_me']) && $_POST['remember_me'] === 'true';
    
    if (empty($user_email) || empty($password)) {
        wp_send_json_error(['message' => 'Please fill in all required fields']);
        wp_die();
    }
    
    $credentials = array(
        'user_login' => $user_email,
        'user_password' => $password,
        'remember' => $remember
    );
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Incorrect username or password']);
        wp_die();
    }
    
    wp_send_json_success([
        'message' => 'Login successful, redirecting...',
        'redirect' => home_url('/')
    ]);
    wp_die();
}
add_action('wp_ajax_nopriv_medmaster_ajax_login', 'medmaster_ajax_login');

// =====================================================
// AJAX Add User
// =====================================================

function medmaster_ajax_add_user() {
    check_ajax_referer('medmaster_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to add users']);
        wp_die();
    }
    
    $user_name = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : '';
    $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    $user_password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
    $user_role = isset($_POST['user_role']) ? sanitize_text_field($_POST['user_role']) : 'representative';
    
    if (empty($user_name) || empty($user_email) || empty($user_password)) {
        wp_send_json_error(['message' => 'Please fill in all required fields']);
        wp_die();
    }
    
    if (!is_email($user_email)) {
        wp_send_json_error(['message' => 'Invalid email address']);
        wp_die();
    }
    
    if (email_exists($user_email)) {
        wp_send_json_error(['message' => 'Email already exists in the system']);
        wp_die();
    }
    
    $user_id = wp_insert_user([
        'user_login' => $user_email,
        'user_pass' => $user_password,
        'user_email' => $user_email,
        'display_name' => $user_name,
        'role' => $user_role
    ]);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
        wp_die();
    }
    
    $site_name = get_bloginfo('name');
    $login_url = home_url('/');
    $to = $user_email;
    $subject = sprintf('Welcome to %s Updates System', $site_name);
    $message = sprintf('Hello %s,

An account has been created for you in our updates system.

Your login details:
Username: %s
Password: %s

You can login at: %s

Best regards,
%s', $user_name, $user_email, $user_password, $login_url, $site_name);
    
    $mail_sent = wp_mail($to, $subject, $message);
    
    if ($mail_sent) {
        wp_send_json_success(['message' => 'User created successfully and email sent with login details']);
    } else {
        wp_send_json_success(['message' => 'User created successfully, but there was an error sending the email']);
    }
    wp_die();
}
add_action('wp_ajax_medmaster_add_user', 'medmaster_ajax_add_user');

// =====================================================
// AJAX Delete User
// =====================================================

function medmaster_ajax_delete_user() {
    check_ajax_referer('medmaster_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to delete users']);
        wp_die();
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if ($user_id === 0) {
        wp_send_json_error(['message' => 'Invalid user ID']);
        wp_die();
    }
    
    if ($user_id === get_current_user_id()) {
        wp_send_json_error(['message' => 'Cannot delete current user']);
        wp_die();
    }
    
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    $result = wp_delete_user($user_id);
    
    if ($result) {
        wp_send_json_success(['message' => 'User deleted successfully']);
    } else {
        wp_send_json_error(['message' => 'Error deleting user']);
    }
    wp_die();
}
add_action('wp_ajax_medmaster_delete_user', 'medmaster_ajax_delete_user');

// =====================================================
// AJAX Reset Password
// =====================================================

function medmaster_ajax_reset_password() {
    check_ajax_referer('medmaster_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to reset passwords']);
        wp_die();
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    if ($user_id === 0 || empty($new_password)) {
        wp_send_json_error(['message' => 'Missing required data']);
        wp_die();
    }
    
    wp_set_password($new_password, $user_id);
    
    $user_data = get_userdata($user_id);
    if ($user_data) {
        $site_name = get_bloginfo('name');
        $login_url = home_url('/');
        
        $subject = sprintf('Your password in %s system has been reset', $site_name);
        $message = sprintf('Hello %s,

Your password in the updates system has been reset by an administrator.

Your new login details:
Username: %s
New Password: %s

You can login at: %s

Best regards,
%s', $user_data->display_name, $user_data->user_email, $new_password, $login_url, $site_name);
        
        wp_mail($user_data->user_email, $subject, $message);
    }
    
    wp_send_json_success(['message' => 'Password reset successfully']);
    wp_die();
}
add_action('wp_ajax_medmaster_reset_user_password', 'medmaster_ajax_reset_password');

// =====================================================
// AJAX Add Update
// =====================================================

function medmaster_ajax_add_update() {
    check_ajax_referer('medmaster_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to add updates']);
        wp_die();
    }
    
    $title = isset($_POST['update_title']) ? sanitize_text_field($_POST['update_title']) : '';
    $content = isset($_POST['update_content']) ? wp_kses_post($_POST['update_content']) : '';
    $tag_id = isset($_POST['update_tag']) ? intval($_POST['update_tag']) : 0;
    $publish_date = isset($_POST['update_publish_date']) ? sanitize_text_field($_POST['update_publish_date']) : '';
    
    if (empty($title) || empty($content)) {
        wp_send_json_error(['message' => 'Please fill in title and content']);
        wp_die();
    }
    
    $post_status = 'publish';
    $post_date = current_time('mysql');
    
    if (!empty($publish_date)) {
        $publish_timestamp = strtotime($publish_date);
        
        if ($publish_timestamp > current_time('timestamp')) {
            $post_status = 'future';
            $post_date = date('Y-m-d H:i:s', $publish_timestamp);
        }
    }
    
    $update_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $post_status,
        'post_date'    => $post_date,
        'post_type'    => 'updates',
    );
    
    $update_id = wp_insert_post($update_data);
    
    if (is_wp_error($update_id)) {
        wp_send_json_error(['message' => $update_id->get_error_message()]);
        wp_die();
    }
    
    if ($tag_id > 0) {
        wp_set_object_terms($update_id, $tag_id, 'update_tag');
    }
    
    wp_send_json_success(['message' => 'Update created successfully']);
    wp_die();
}
add_action('wp_ajax_medmaster_add_update', 'medmaster_ajax_add_update');

// =====================================================
// AJAX Add Tag
// =====================================================

function medmaster_ajax_add_tag() {
    check_ajax_referer('medmaster_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to add tags']);
        wp_die();
    }
    
    $tag_name = isset($_POST['tag_name']) ? sanitize_text_field($_POST['tag_name']) : '';
    $tag_color = isset($_POST['tag_color']) ? sanitize_hex_color($_POST['tag_color']) : '';
    
    if (empty($tag_name)) {
        wp_send_json_error(['message' => 'Please enter a tag name']);
        wp_die();
    }
    
    $existing_term = term_exists($tag_name, 'update_tag');
    if ($existing_term) {
        wp_send_json_error(['message' => 'Tag already exists']);
        wp_die();
    }
    
    $term = wp_insert_term($tag_name, 'update_tag');
    
    if (is_wp_error($term)) {
        wp_send_json_error(['message' => $term->get_error_message()]);
        wp_die();
    }
    
    if (!empty($tag_color)) {
        update_term_meta($term['term_id'], 'tag_color', $tag_color);
    }
    
    wp_send_json_success([
        'message' => 'Tag created successfully',
        'term_id' => $term['term_id'],
        'name' => $tag_name
    ]);
    wp_die();
}
add_action('wp_ajax_medmaster_add_update_tag', 'medmaster_ajax_add_tag');

// =====================================================
// AJAX Mark as Read
// =====================================================

function medmaster_ajax_mark_as_read() {
    if (!wp_verify_nonce($_POST['nonce'], 'medmaster_ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }
    
    $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not logged in']);
        wp_die();
    }
    
    if ($update_id === 0) {
        wp_send_json_error(['message' => 'Invalid update ID']);
        wp_die();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        medmaster_create_tables();
    }
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND update_id = %d",
        $user_id, $update_id
    ));
    
    if ($existing > 0) {
        wp_send_json_success(['message' => 'Update already marked as read']);
        wp_die();
    }
    
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'update_id' => $update_id,
            'read_time' => current_time('mysql')
        ],
        ['%d', '%d', '%s']
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
    } else {
        wp_send_json_success(['message' => 'Update marked as read successfully']);
    }
    
    wp_die();
}
add_action('wp_ajax_medmaster_mark_as_read', 'medmaster_ajax_mark_as_read');
add_action('wp_ajax_nopriv_medmaster_mark_as_read', 'medmaster_ajax_mark_as_read');

// =====================================================
// AJAX Get Read Status
// =====================================================

function medmaster_ajax_get_read_status() {
    check_ajax_referer('medmaster_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to view read status']);
        wp_die();
    }
    
    $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
    
    if ($update_id === 0) {
        wp_send_json_error(['message' => 'Invalid update ID']);
        wp_die();
    }
    
    $representatives = get_users(['role' => 'representative']);
    $users_data = [];
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    foreach ($representatives as $user) {
        $read_data = $wpdb->get_row($wpdb->prepare(
            "SELECT read_time FROM $table_name WHERE user_id = %d AND update_id = %d",
            $user->ID, $update_id
        ));
        
        $users_data[] = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'has_read' => $read_data ? true : false,
            'read_timestamp' => $read_data ? date_i18n('d/m/Y H:i', strtotime($read_data->read_time)) : null
        ];
    }
    
    wp_send_json_success($users_data);
    wp_die();
}
add_action('wp_ajax_medmaster_get_read_status', 'medmaster_ajax_get_read_status');

// =====================================================
// AJAX Delete Update
// =====================================================

function medmaster_ajax_delete_update() {
    check_ajax_referer('medmaster_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to delete updates']);
        wp_die();
    }
    
    $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
    
    if ($update_id === 0) {
        wp_send_json_error(['message' => 'Invalid update ID']);
        wp_die();
    }
    
    $result = wp_delete_post($update_id, true);
    
    if ($result) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'update_read_status';
        $wpdb->delete($table_name, ['update_id' => $update_id], ['%d']);
        
        wp_send_json_success(['message' => 'Update deleted successfully']);
    } else {
        wp_send_json_error(['message' => 'Error deleting update']);
    }
    wp_die();
}
add_action('wp_ajax_medmaster_delete_update', 'medmaster_ajax_delete_update');

// =====================================================
// Create dashboard page
// =====================================================

function medmaster_create_dashboard_page() {
    $dashboard_exists = get_page_by_path('dashboard');
    
    if (!$dashboard_exists) {
        $dashboard_page = array(
            'post_title'    => 'Updates System',
            'post_content'  => '',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'dashboard',
            'page_template' => 'page-dashboard.php'
        );
        
        $page_id = wp_insert_post($dashboard_page);
        
        if ($page_id && !is_wp_error($page_id)) {
            update_option('page_on_front', $page_id);
            update_option('show_on_front', 'page');
        }
    }
}
register_activation_hook(__FILE__, 'medmaster_create_dashboard_page');

function medmaster_check_dashboard_page() {
    if (!get_option('medmaster_dashboard_created')) {
        medmaster_create_dashboard_page();
        update_option('medmaster_dashboard_created', true);
    }
}
add_action('init', 'medmaster_check_dashboard_page');

function medmaster_redirect_to_dashboard($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('representative', $user->roles) && !in_array('administrator', $user->roles)) {
            $dashboard_page = get_page_by_path('dashboard');
            if ($dashboard_page) {
                return get_permalink($dashboard_page->ID);
            }
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'medmaster_redirect_to_dashboard', 10, 3);

// =====================================================
// Additional fixes
// =====================================================

function medmaster_enqueue_fixes() {
    wp_add_inline_style('medmaster-style', '
        #medmaster-dashboard {
            width: 100% !important;
            max-width: 100% !important;
        }
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 15px;
            padding-right: 15px;
        }
        .row {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }
        .col-12 {
            padding-left: 15px;
            padding-right: 15px;
        }
        .login-container .card {
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
       
    ');
}
add_action('wp_enqueue_scripts', 'medmaster_enqueue_fixes', 20);

?>