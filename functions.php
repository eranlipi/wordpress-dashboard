<?php
/**
 * Med-Master Updates System Functions
 */

// =====================================================
// רישום סוגי תוכן מותאם אישית ותקסונומיות
// =====================================================

function medmaster_register_post_types() {
    // רישום סוג פוסט מותאם 'updates'
    register_post_type('updates', [
        'labels' => [
            'name' => __('עדכונים', 'medmaster'),
            'singular_name' => __('עדכון', 'medmaster'),
            'add_new' => __('עדכון חדש', 'medmaster'),
            'add_new_item' => __('הוסף עדכון חדש', 'medmaster'),
            'edit_item' => __('ערוך עדכון', 'medmaster'),
        ],
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => ['title', 'editor', 'author'],
        'show_in_rest' => true,
        'menu_position' => 25,
    ]);

    // רישום טקסונומיה 'update_tag'
    register_taxonomy('update_tag', 'updates', [
        'labels' => [
            'name' => __('תגיות עדכון', 'medmaster'),
            'singular_name' => __('תגית עדכון', 'medmaster'),
            'add_new_item' => __('הוסף תגית חדשה', 'medmaster'),
        ],
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'medmaster_register_post_types');

// =====================================================
// יצירת טבלאות מסד נתונים מותאמות אישית
// =====================================================

function medmaster_create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            update_id bigint(20) NOT NULL,
            read_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_update (user_id,update_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'medmaster_create_tables');

// =====================================================
// הוספת תפקיד נציג (representative)
// =====================================================

function medmaster_add_representative_role() {
    if (!get_role('representative')) {
        add_role('representative', __('נציג', 'medmaster'), [
            'read' => true,
            'representative' => true
        ]);
    }
}
register_activation_hook(__FILE__, 'medmaster_add_representative_role');
add_action('init', 'medmaster_add_representative_role');

// =====================================================
// העמסת סקריפטים וסגנונות (bootstrap ו-CSS מותאם)
// =====================================================

function medmaster_enqueue_scripts() {
    // Bootstrap CSS from CDN
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    
    // Bootstrap Icons
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css');
    
    // Custom CSS
    wp_enqueue_style('medmaster-style', get_stylesheet_directory_uri() . '/css/medmaster-style.css');
    
    // Bootstrap JS
    wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
    
    // TinyMCE for WYSIWYG editor in modals
    wp_enqueue_editor();
    
    // Custom JS
    wp_enqueue_script('medmaster-scripts', get_stylesheet_directory_uri() . '/js/medmaster-scripts.js', ['jquery'], null, true);
    
    // Localize script with AJAX URL and nonce
    wp_localize_script('medmaster-scripts', 'medmaster_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('medmaster_ajax_nonce'),
        'loading_message' => __('מעבד נתונים, אנא המתן...', 'medmaster'),
        'home_url' => home_url('/'),
    ]);

    
}
add_action('wp_enqueue_scripts', 'medmaster_enqueue_scripts');

// =====================================================
// פונקציות עזר
// =====================================================

function medmaster_has_user_read_update($user_id, $update_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND update_id = %d",
        $user_id, $update_id
    ));
    
    return $result > 0;
}

function medmaster_get_update_read_count($update_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE update_id = %d",
        $update_id
    ));
}

function medmaster_get_total_representatives_count() {
    $users = count(get_users(['role' => 'representative']));
    return $users;
}

// =====================================================
// AJAX הרשמה למערכת
// =====================================================

function medmaster_ajax_register() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    // בדיקה שרק מנהלים יכולים להוסיף משתמשים
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('אין לך הרשאות להוסיף משתמשים', 'medmaster')
        ]);
        wp_die();
    }
    
    $user_name = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : '';
    $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    $user_password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
    $user_role = isset($_POST['user_role']) ? sanitize_text_field($_POST['user_role']) : 'representative';
    
    // בדיקות תקינות
    if (empty($user_name) || empty($user_email) || empty($user_password)) {
        wp_send_json_error([
            'message' => __('אנא מלא את כל השדות הנדרשים', 'medmaster')
        ]);
        wp_die();
    }
    
    if (!is_email($user_email)) {
        wp_send_json_error([
            'message' => __('כתובת האימייל אינה תקינה', 'medmaster')
        ]);
        wp_die();
    }
    
    // בדיקה אם המשתמש כבר קיים
    if (email_exists($user_email)) {
        wp_send_json_error([
            'message' => __('כתובת האימייל כבר קיימת במערכת', 'medmaster')
        ]);
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
        wp_send_json_error([
            'message' => $user_id->get_error_message()
        ]);
        wp_die();
    }
    
    // שליחת מייל למשתמש החדש עם פרטי ההתחברות שלו
    $site_name = get_bloginfo('name');
    $login_url = home_url('/');
    
    $subject = sprintf(__('ברוך הבא למערכת העדכונים של %s', 'medmaster'), $site_name);
    $message = '';
    $message .= sprintf(__('שלום %s,', 'medmaster'), $user_name) . "\n\n";
    $message .= __('נוצר עבורך חשבון במערכת העדכונים שלנו.', 'medmaster') . "\n\n";
    $message .= __('פרטי ההתחברות שלך:', 'medmaster') . "\n";
    $message .= __('שם משתמש: ', 'medmaster') . $user_email . "\n";
    $message .= __('סיסמה: ', 'medmaster') . $user_password . "\n\n";
    $message .= __('ניתן להתחבר בכתובת הבאה:', 'medmaster') . "\n";
    $message .= $login_url . "\n\n";
    $message .= __('בברכה,', 'medmaster') . "\n";
    $message .= $site_name;
    
    wp_mail($user_email, $subject, $message);
    
    wp_send_json_success([
        'message' => __('המשתמש נוצר בהצלחה ונשלח אליו מייל עם פרטי ההתחברות', 'medmaster')
    ]);
    wp_die();
}
add_action('wp_ajax_medmaster_ajax_register', 'medmaster_ajax_register');
add_action('wp_ajax_nopriv_medmaster_ajax_register', 'medmaster_ajax_register');

// =====================================================
// AJAX התחברות למערכת
// =====================================================

function medmaster_ajax_login() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    $user_email = isset($_POST['user_login']) ? sanitize_text_field($_POST['user_login']) : '';
    $password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
    $remember = isset($_POST['remember_me']) && $_POST['remember_me'] === 'true';
    
    // בדיקות תקינות
    if (empty($user_email) || empty($password)) {
        wp_send_json_error([
            'message' => __('אנא מלא את כל השדות הנדרשים', 'medmaster')
        ]);
        wp_die();
    }
    
    $credentials = array(
        'user_login' => $user_email,
        'user_password' => $password,
        'remember' => $remember
    );
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error([
            'message' => __('שם המשתמש או הסיסמה שגויים', 'medmaster')
        ]);
        wp_die();
    }
    
    wp_send_json_success([
        'message' => __('התחברות בוצעה בהצלחה, מפנה...', 'medmaster'),
        'redirect' => home_url('/')
    ]);
    wp_die();
}
add_action('wp_ajax_nopriv_medmaster_ajax_login', 'medmaster_ajax_login');

// =====================================================
// AJAX מחיקת משתמש
// =====================================================

function medmaster_ajax_delete_user() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('אין לך הרשאות למחוק משתמשים', 'medmaster')
        ]);
        wp_die();
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if ($user_id === 0) {
        wp_send_json_error([
            'message' => __('לא התקבל מזהה משתמש', 'medmaster')
        ]);
        wp_die();
    }
    
    // מניעת מחיקה עצמית
    if ($user_id === get_current_user_id()) {
        wp_send_json_error([
            'message' => __('לא ניתן למחוק את המשתמש הנוכחי', 'medmaster')
        ]);
        wp_die();
    }
    
    // מחיקת המשתמש
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    $result = wp_delete_user($user_id);
    
    if ($result) {
        wp_send_json_success([
            'message' => __('המשתמש נמחק בהצלחה', 'medmaster')
        ]);
    } else {
        wp_send_json_error([
            'message' => __('ארעה שגיאה במחיקת המשתמש', 'medmaster')
        ]);
    }
    wp_die();
}
add_action('wp_ajax_medmaster_ajax_delete_user', 'medmaster_ajax_delete_user');

// =====================================================
// AJAX איפוס סיסמה למשתמש
// =====================================================

function medmaster_ajax_reset_password() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('אין לך הרשאות לאיפוס סיסמאות', 'medmaster')
        ]);
        wp_die();
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    if ($user_id === 0 || empty($new_password)) {
        wp_send_json_error([
            'message' => __('לא התקבלו כל הנתונים הנדרשים', 'medmaster')
        ]);
        wp_die();
    }
    
    // איפוס סיסמה
    wp_set_password($new_password, $user_id);
    
    // שליחת מייל למשתמש עם הסיסמה החדשה
    $user_data = get_userdata($user_id);
    if ($user_data) {
        $site_name = get_bloginfo('name');
        $login_url = home_url('/');
        
        $subject = sprintf(__('הסיסמה שלך במערכת %s אופסה', 'medmaster'), $site_name);
        $message = '';
        $message .= sprintf(__('שלום %s,', 'medmaster'), $user_data->display_name) . "\n\n";
        $message .= __('סיסמתך במערכת העדכונים אופסה על ידי מנהל.', 'medmaster') . "\n\n";
        $message .= __('פרטי ההתחברות החדשים שלך:', 'medmaster') . "\n";
        $message .= __('שם משתמש: ', 'medmaster') . $user_data->user_email . "\n";
        $message .= __('סיסמה חדשה: ', 'medmaster') . $new_password . "\n\n";
        $message .= __('ניתן להתחבר בכתובת הבאה:', 'medmaster') . "\n";
        $message .= $login_url . "\n\n";
        $message .= __('בברכה,', 'medmaster') . "\n";
        $message .= $site_name;
        
        wp_mail($user_data->user_email, $subject, $message);
    }
    
    wp_send_json_success([
        'message' => __('הסיסמה אופסה בהצלחה', 'medmaster')
    ]);
    wp_die();
}
add_action('wp_ajax_medmaster_ajax_reset_password', 'medmaster_ajax_reset_password');

// =====================================================
// AJAX הוספת עדכון חדש
// =====================================================

function medmaster_ajax_add_update() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('אין לך הרשאות להוסיף עדכונים', 'medmaster')
        ]);
        wp_die();
    }
    
    $title = isset($_POST['update_title']) ? sanitize_text_field($_POST['update_title']) : '';
    $content = isset($_POST['update_content']) ? wp_kses_post($_POST['update_content']) : '';
    $tag_id = isset($_POST['update_tag']) ? intval($_POST['update_tag']) : 0;
    $publish_date = isset($_POST['update_publish_date']) ? sanitize_text_field($_POST['update_publish_date']) : '';
    
    if (empty($title) || empty($content)) {
        wp_send_json_error([
            'message' => __('אנא מלא כותרת ותוכן לעדכון', 'medmaster')
        ]);
        wp_die();
    }
    
    // הגדרת סטטוס הפרסום ותאריך
    $post_status = 'publish';
    $post_date = current_time('mysql');
    
    if (!empty($publish_date)) {
        $publish_timestamp = strtotime($publish_date);
        
        // אם תאריך עתידי, הגדר כ-future
        if ($publish_timestamp > current_time('timestamp')) {
            $post_status = 'future';
            $post_date = date('Y-m-d H:i:s', $publish_timestamp);
        }
    }
    
    // יצירת עדכון חדש
    $update_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $post_status,
        'post_date'    => $post_date,
        'post_type'    => 'updates',
    );
    
    $update_id = wp_insert_post($update_data);
    
    if (is_wp_error($update_id)) {
        wp_send_json_error([
            'message' => $update_id->get_error_message()
        ]);
        wp_die();
    }
    
    // קישור תגית אם נבחרה
    if ($tag_id > 0) {
        wp_set_object_terms($update_id, $tag_id, 'update_tag');
    }
    
    wp_send_json_success([
        'message' => __('העדכון נוצר בהצלחה', 'medmaster')
    ]);
    wp_die();
}
add_action('wp_ajax_medmaster_ajax_add_update', 'medmaster_ajax_add_update');

// =====================================================
// AJAX הוספת תגית חדשה
// =====================================================

function medmaster_ajax_add_tag() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('אין לך הרשאות להוסיף תגיות', 'medmaster')
        ]);
        wp_die();
    }
    
    $tag_name = isset($_POST['tag_name']) ? sanitize_text_field($_POST['tag_name']) : '';
    $tag_color = isset($_POST['tag_color']) ? sanitize_hex_color($_POST['tag_color']) : '';
    
    if (empty($tag_name)) {
        wp_send_json_error([
            'message' => __('אנא הזן שם לתגית', 'medmaster')
        ]);
        wp_die();
    }
    
    // בדיקה אם התגית כבר קיימת
    $existing_term = term_exists($tag_name, 'update_tag');
    if ($existing_term) {
        wp_send_json_error([
            'message' => __('התגית כבר קיימת במערכת', 'medmaster')
        ]);
        wp_die();
    }
    
    // יצירת תגית חדשה
    $term = wp_insert_term($tag_name, 'update_tag');
    
    if (is_wp_error($term)) {
        wp_send_json_error([
            'message' => $term->get_error_message()
        ]);
        wp_die();
    }
    
    // שמירת צבע לתגית
    if (!empty($tag_color)) {
        update_term_meta($term['term_id'], 'tag_color', $tag_color);
    }
    
    wp_send_json_success([
        'message' => __('התגית נוצרה בהצלחה', 'medmaster'),
        'term_id' => $term['term_id'],
        'term_name' => $tag_name,
        'term_color' => $tag_color
    ]);
    wp_die();
}
add_action('wp_ajax_medmaster_ajax_add_tag', 'medmaster_ajax_add_tag');

// =====================================================
// AJAX סימון עדכון כנקרא
// =====================================================

function medmaster_ajax_mark_as_read() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
    $user_id = get_current_user_id();
    
    if ($update_id === 0 || $user_id === 0) {
        wp_send_json_error([
            'message' => __('לא התקבלו כל הנתונים הנדרשים', 'medmaster')
        ]);
        wp_die();
    }
    
    // בדיקה אם כבר נקרא
    if (medmaster_has_user_read_update($user_id, $update_id)) {
        wp_send_json_success([
            'message' => __('העדכון כבר סומן כנקרא', 'medmaster')
        ]);
        wp_die();
    }
    
    // הוספת רשומה לטבלת קריאה
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'update_id' => $update_id,
            'read_time' => current_time('mysql')
        ],
        ['%d', '%d', '%s']
    );
    
    if ($result) {
        wp_send_json_success([
            'message' => __('העדכון סומן כנקרא בהצלחה', 'medmaster')
        ]);
    } else {
        wp_send_json_error([
            'message' => __('ארעה שגיאה בסימון העדכון כנקרא', 'medmaster')
        ]);
    }
    wp_die();
}
add_action('wp_ajax_medmaster_ajax_mark_as_read', 'medmaster_ajax_mark_as_read');

// =====================================================
// AJAX קבלת סטטוס קריאה של עדכון
// =====================================================

function medmaster_ajax_get_read_status() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('אין לך הרשאות לצפייה בסטטוס קריאה', 'medmaster')
        ]);
        wp_die();
    }
    
    $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
    
    if ($update_id === 0) {
        wp_send_json_error([
            'message' => __('לא התקבל מזהה עדכון', 'medmaster')
        ]);
        wp_die();
    }
    
    // קבלת כל הנציגים
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
add_action('wp_ajax_medmaster_ajax_get_read_status', 'medmaster_ajax_get_read_status');

// =====================================================
// AJAX מחיקת עדכון
// =====================================================

function medmaster_ajax_delete_update() {
    check_ajax_referer('medmaster_ajax_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('אין לך הרשאות למחיקת עדכונים', 'medmaster')
        ]);
        wp_die();
    }
    
    $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
    
    if ($update_id === 0) {
        wp_send_json_error([
            'message' => __('לא התקבל מזהה עדכון', 'medmaster')
        ]);
        wp_die();
    }
    
    // מחיקת העדכון
    $result = wp_delete_post($update_id, true);
    
    if ($result) {
        // מחיקת רשומות הקריאה הקשורות
        global $wpdb;
        $table_name = $wpdb->prefix . 'update_read_status';
        $wpdb->delete($table_name, ['update_id' => $update_id], ['%d']);
        
        wp_send_json_success([
            'message' => __('העדכון נמחק בהצלחה', 'medmaster')
        ]);
    } else {
        wp_send_json_error([
            'message' => __('ארעה שגיאה במחיקת העדכון', 'medmaster')
        ]);
    }
    wp_die();
}
add_action('wp_ajax_medmaster_ajax_delete_update', 'medmaster_ajax_delete_update');

// =====================================================
// יצירת דף לוח הבקרה בהפעלת התבנית
// =====================================================

function medmaster_create_dashboard_page() {
    $dashboard_exists = get_page_by_path('dashboard');
    
    if (!$dashboard_exists) {
        $dashboard_page = array(
            'post_title'    => __('מערכת עדכונים', 'medmaster'),
            'post_content'  => '',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'dashboard',
            'page_template' => 'page-dashboard.php'
        );
        
        // הוספת הדף
        $page_id = wp_insert_post($dashboard_page);
        
        if ($page_id && !is_wp_error($page_id)) {
            // הגדרה כדף הראשי אם רצוי
            update_option('page_on_front', $page_id);
            update_option('show_on_front', 'page');
        }
    }
}
register_activation_hook(__FILE__, 'medmaster_create_dashboard_page');
// מכיוון שזוהי תבנית בת, נפעיל גם ב-init עם בדיקת דגל
function medmaster_check_dashboard_page() {
    if (!get_option('medmaster_dashboard_created')) {
        medmaster_create_dashboard_page();
        update_option('medmaster_dashboard_created', true);
    }
}
add_action('init', 'medmaster_check_dashboard_page');

// הפניית נציגים ללוח הבקרה לאחר התחברות במקום לפאנל הניהול
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



// בסוף קובץ functions.php, הוסף את הקוד הבא:

// =====================================================
// תיקונים לבעיות במודלים ובדאשבורד
// =====================================================

function medmaster_enqueue_fixes() {
    // הוספת סקריפטים ו-CSS לתיקון בעיות
    wp_enqueue_script('medmaster-fixes', get_stylesheet_directory_uri() . '/js/medmaster-fixes.js', ['jquery'], '1.0', true);
    
    // הוספת סגנונות לתיקון בעיות רוחב
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
    ');
}
add_action('wp_enqueue_scripts', 'medmaster_enqueue_fixes', 20);

/**
 * בדיקת תקינות טבלת סטטוס קריאה
 * ויצירתה אם לא קיימת
 */
function medmaster_check_read_status_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // הטבלה לא קיימת - צור אותה
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            update_id bigint(20) NOT NULL,
            read_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_update (user_id,update_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // הוסף תיעוד ללוג
        error_log('Table ' . $table_name . ' was created successfully');
    }
}
add_action('init', 'medmaster_check_read_status_table');

/**
 * פונקציות עזר - גרסאות בטוחות
 */
function medmaster_has_user_read_update_safe($user_id, $update_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    // בדיקה שהטבלה קיימת
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        medmaster_check_read_status_table();
        return false;
    }
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND update_id = %d",
        $user_id, $update_id
    ));
    
    return $result > 0;
}

function medmaster_get_update_read_count_safe($update_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'update_read_status';
    
    // בדיקה שהטבלה קיימת
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        medmaster_check_read_status_table();
        return 0;
    }
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE update_id = %d",
        $update_id
    ));
}

// החלף את הפונקציות המקוריות עם הגרסאות הבטוחות
if (!function_exists('medmaster_has_user_read_update') || !function_exists('medmaster_get_update_read_count')) {
    function medmaster_has_user_read_update($user_id, $update_id) {
        return medmaster_has_user_read_update_safe($user_id, $update_id);
    }
    
    function medmaster_get_update_read_count($update_id) {
        return medmaster_get_update_read_count_safe($update_id);
    }
}