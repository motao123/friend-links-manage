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

define('FLM_VERSION', '2.0');
define('FLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once FLM_PLUGIN_DIR . 'includes/admin-page.php';
require_once FLM_PLUGIN_DIR . 'includes/frontend-page.php';

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

// 插件升级：自动应用数据库变更
add_action('plugins_loaded', 'flm_check_upgrade');
function flm_check_upgrade() {
    if (version_compare(get_option('flm_version', '0'), FLM_VERSION, '<')) {
        flm_activate();
    }
}
