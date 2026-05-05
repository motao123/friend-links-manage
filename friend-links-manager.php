<?php
/*
Plugin Name: 友情链接管理器
Description: 允许用户在前台提交友情链接申请，管理员可以在后台审核。支持友链展示、批量操作等。
Version: 2.0
Author: 陌涛
Author URI: https://imotao.com/
Plugin URI: https://imotao.com/8807.html
License: GPL-3.0
*/

if (!defined('ABSPATH')) {
    exit;
}

// 诊断：确认此文件是否被加载
file_put_contents(WP_CONTENT_DIR . '/flm-debug.log', date('H:i:s') . " 主文件已加载\n", FILE_APPEND);

define('FLM_VERSION', '2.0');
define('FLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once FLM_PLUGIN_DIR . 'includes/admin-page.php';
require_once FLM_PLUGIN_DIR . 'includes/frontend-page.php';

// 前台表单提交处理 —— 挂在 init 上，最早时机，避免 headers already sent
add_action('init', 'flm_handle_form_submit_early');
function flm_handle_form_submit_early() {
    $log = WP_CONTENT_DIR . '/flm-debug.log';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['flm_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['flm_nonce'], 'flm_submit_form')) {
        return;
    }

    file_put_contents($log, date('H:i:s') . " [init] 友链提交开始处理\n", FILE_APPEND);

    // 蜜罐检查
    $honeypot = isset($_POST['flm_website']) ? $_POST['flm_website'] : '';
    file_put_contents($log, date('H:i:s') . " 蜜罐值=[$honeypot]\n", FILE_APPEND);
    if (!empty($honeypot)) {
        file_put_contents($log, date('H:i:s') . " 蜜罐触发，丢弃\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', 'success', get_permalink()));
        exit;
    }

    $name        = sanitize_text_field($_POST['flm_name']);
    $url         = esc_url_raw($_POST['flm_url']);
    $logo_url    = isset($_POST['flm_logo_url']) ? esc_url_raw($_POST['flm_logo_url']) : '';
    $email       = isset($_POST['flm_email']) ? sanitize_email($_POST['flm_email']) : '';
    $description = isset($_POST['flm_description']) ? sanitize_textarea_field($_POST['flm_description']) : '';

    // 校验
    $error = '';
    if (mb_strlen($name) > 100) $error = 'name_too_long';
    elseif (empty($name) || empty($url)) $error = 'empty_fields';
    elseif (!filter_var($url, FILTER_VALIDATE_URL)) $error = 'invalid_url';

    if ($error) {
        file_put_contents($log, date('H:i:s') . " 校验失败: $error\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', $error, get_permalink()));
        exit;
    }
    file_put_contents($log, date('H:i:s') . " 校验通过 name=$name url=$url\n", FILE_APPEND);

    // 频率限制
    $rate_key = 'flm_rate_' . md5($_SERVER['REMOTE_ADDR']);
    if (get_transient($rate_key)) {
        file_put_contents($log, date('H:i:s') . " 频率限制\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', 'rate_limit', get_permalink()));
        exit;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';

    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE url = %s", $url));
    if ($existing) {
        file_put_contents($log, date('H:i:s') . " URL重复\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', 'duplicate', get_permalink()));
        exit;
    }

    $inserted = $wpdb->insert($table_name, array(
        'name' => $name, 'url' => $url, 'logo_url' => $logo_url,
        'email' => $email, 'description' => $description,
        'status' => 'pending', 'created_at' => current_time('mysql'),
    ), array('%s','%s','%s','%s','%s','%s','%s'));

    if ($inserted) {
        file_put_contents($log, date('H:i:s') . " 插入成功! ID=" . $wpdb->insert_id . "\n", FILE_APPEND);
        set_transient($rate_key, time(), 60);
        wp_redirect(add_query_arg('flm_status', 'success', get_permalink()));
    } else {
        file_put_contents($log, date('H:i:s') . " 插入失败: " . $wpdb->last_error . "\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', 'db_error', get_permalink()));
    }
    exit;
}

// 插件激活：创建/升级数据库表、创建前台页面
register_activation_hook(__FILE__, 'flm_activate');
function flm_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        url varchar(255) NOT NULL,
        logo_url varchar(255) DEFAULT '',
        email varchar(100) DEFAULT '',
        description text,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY idx_status (status),
        KEY idx_url (url)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // dbDelta 可能因格式问题静默失败，手动补列
    flm_add_missing_columns($table_name);

    // 创建前台页面
    if (!get_page_by_path('friend-links-apply')) {
        wp_insert_post(array(
            'post_title'   => '友情链接申请',
            'post_content' => '[friend_links_form]',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_type'    => 'page',
            'post_name'    => 'friend-links-apply'
        ));
    }

    update_option('flm_version', FLM_VERSION);
}

// 手动检测并添加缺失的列（从 v1.0 升级时 dbDelta 可能不生效）
function flm_add_missing_columns($table_name) {
    global $wpdb;

    $columns = $wpdb->get_col("DESC $table_name", 0);
    $missing = array(
        'logo_url'   => "ALTER TABLE $table_name ADD COLUMN logo_url varchar(255) DEFAULT '' AFTER url",
        'email'      => "ALTER TABLE $table_name ADD COLUMN email varchar(100) DEFAULT '' AFTER logo_url",
        'created_at' => "ALTER TABLE $table_name ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER status",
    );

    foreach ($missing as $col => $sql) {
        if (!in_array($col, $columns, true)) {
            $wpdb->query($sql);
        }
    }

    // v1.0 name 是 varchar(255)，v2.0 改为 varchar(100)
    $row = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'name'");
    if ($row && preg_match('/varchar\((\d+)\)/', $row->Type, $m) && (int) $m[1] > 100) {
        $wpdb->query("ALTER TABLE $table_name MODIFY name varchar(100) NOT NULL");
    }
}

// 插件升级：自动应用数据库变更
add_action('plugins_loaded', 'flm_check_upgrade');
function flm_check_upgrade() {
    if (version_compare(get_option('flm_version', '0'), FLM_VERSION, '<')) {
        flm_activate();
    }
}
