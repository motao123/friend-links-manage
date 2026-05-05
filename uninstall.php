<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "friend_links");

$page = get_page_by_path('friend-links-apply');
if ($page) {
    wp_delete_post($page->ID, true);
}

delete_option('flm_version');
