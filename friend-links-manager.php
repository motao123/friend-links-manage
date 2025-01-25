<?php
/*
Plugin Name: 友情链接管理器
Description: 允许用户在前台提交友情链接申请，管理员可以在后台审核。
Version: 1.0
Author: 陌涛
Author URI: https://imotao.com/
Plugin Name: friend-links-manager.php
Plugin URI: https://imotao.com/8807.html
*/

if (!defined('ABSPATH')) {
    exit; // 防止直接访问文件
}

// 定义插件常量
define('FLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLM_PLUGIN_URL', plugin_dir_url(__FILE__));

// 加载插件文件
require_once FLM_PLUGIN_DIR . 'includes/frontend-page.php';
require_once FLM_PLUGIN_DIR . 'includes/admin-page.php';

// 注册激活和卸载钩子
register_activation_hook(__FILE__, 'flm_create_table_and_page');
register_uninstall_hook(__FILE__, 'flm_drop_table_and_page');

// 创建数据库表和前台页面
function flm_create_table_and_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';
    $charset_collate = $wpdb->get_charset_collate();

    // 创建数据库表
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        url varchar(255) NOT NULL,
        description text,
        status varchar(20) DEFAULT 'pending',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // 创建前台页面
    $page_title = '友情链接申请';
    $page_content = '[friend_links_form]'; // 使用短代码
    $page_check = get_page_by_path('friend-links-apply');
    if (!$page_check) {
        $page = array(
            'post_title'    => $page_title,
            'post_content'  => $page_content,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
            'post_name'     => 'friend-links-apply'
        );
        wp_insert_post($page);
    }
}

// 删除数据库表和前台页面
function flm_drop_table_and_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // 删除前台页面
    $page = get_page_by_path('friend-links-apply');
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}