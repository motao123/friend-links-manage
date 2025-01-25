<?php
function flm_admin_menu() {
    add_menu_page(
        '友情链接管理',
        '友情链接',
        'manage_options',
        'friend-links-manager',
        'flm_admin_page',
        'dashicons-admin-links',
        6
    );
}
add_action('admin_menu', 'flm_admin_menu');

function flm_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';

    // 处理审核操作
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action = sanitize_text_field($_GET['action']);
        $id = intval($_GET['id']);

        if ($action === 'approve') {
            // 获取链接信息
            $link = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            ));

            if ($link) {
                // 插入到 WordPress 默认的 wp_links 表中
                wp_insert_link(array(
                    'link_name' => $link->name,
                    'link_url' => $link->url,
                    'link_description' => $link->description,
                    'link_visible' => 'Y' // 设置为可见
                ));

                // 更新状态为已审核
                $wpdb->update($table_name, array('status' => 'approved'), array('id' => $id));
                echo '<div class="notice notice-success"><p>链接已通过审核并添加到链接管理器！</p></div>';
            }
        } elseif ($action === 'reject') {
            $wpdb->update($table_name, array('status' => 'rejected'), array('id' => $id));
            echo '<div class="notice notice-warning"><p>链接已拒绝！</p></div>';
        } elseif ($action === 'delete') {
            $wpdb->delete($table_name, array('id' => $id));
            echo '<div class="notice notice-error"><p>链接已删除！</p></div>';
        }
    }

    // 获取所有申请
    $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

    // 输出管理页面
    echo '<div class="wrap">';
    echo '<h1>友情链接管理</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>网站名称</th><th>URL</th><th>描述</th><th>状态</th><th>操作</th></tr></thead>';
    echo '<tbody>';
    foreach ($links as $link) {
        echo '<tr>';
        echo '<td>' . esc_html($link->id) . '</td>';
        echo '<td>' . esc_html($link->name) . '</td>';
        echo '<td><a href="' . esc_url($link->url) . '" target="_blank">' . esc_url($link->url) . '</a></td>';
        echo '<td>' . esc_html($link->description) . '</td>';
        echo '<td>' . esc_html($link->status) . '</td>';
        echo '<td>';
        if ($link->status === 'pending') {
            echo '<a href="?page=friend-links-manager&action=approve&id=' . $link->id . '">通过</a> | ';
            echo '<a href="?page=friend-links-manager&action=reject&id=' . $link->id . '">拒绝</a> | ';
        }
        echo '<a href="?page=friend-links-manager&action=delete&id=' . $link->id . '">删除</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}