<?php
if (!defined('ABSPATH')) {
    exit;
}

// 加载 WP_List_Table 基类
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// 共用的审核通过逻辑
function flm_approve_link($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';

    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    if ($link && $link->status !== 'approved') {
        wp_insert_link(array(
            'link_name'        => $link->name,
            'link_url'         => $link->url,
            'link_description' => $link->description,
            'link_visible'     => 'Y',
        ));
        $wpdb->update($table_name, array('status' => 'approved'), array('id' => $id));
    }
}

class FLM_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => 'friend-link',
            'plural'   => 'friend-links',
            'ajax'     => false,
        ));
    }

    public function get_columns() {
        return array(
            'cb'          => '<input type="checkbox" />',
            'id'          => 'ID',
            'name'        => '网站名称',
            'url'         => 'URL',
            'email'       => '邮箱',
            'description' => '描述',
            'status'      => '状态',
            'created_at'  => '提交时间',
        );
    }

    public function get_sortable_columns() {
        return array(
            'id'         => array('id', true),
            'status'     => array('status', false),
            'created_at' => array('created_at', true),
        );
    }

    public function get_bulk_actions() {
        return array(
            'approve' => '通过',
            'reject'  => '拒绝',
            'delete'  => '删除',
        );
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="flm_ids[]" value="%d" />', $item->id);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
                return esc_html($item->id);
            case 'name':
                return esc_html($item->name);
            case 'url':
                return '<a href="' . esc_url($item->url) . '" target="_blank" rel="noopener">' . esc_url($item->url) . '</a>';
            case 'email':
                return $item->email ? esc_html($item->email) : '—';
            case 'description':
                return $item->description ? esc_html(mb_strimwidth($item->description, 0, 60, '...')) : '—';
            case 'created_at':
                return $item->created_at ? esc_html($item->created_at) : '—';
            default:
                return '—';
        }
    }

    public function column_status($item) {
        $badges = array(
            'pending'  => '<span class="flm-badge flm-badge-pending">待审核</span>',
            'approved' => '<span class="flm-badge flm-badge-approved">已通过</span>',
            'rejected' => '<span class="flm-badge flm-badge-rejected">已拒绝</span>',
        );
        return isset($badges[$item->status]) ? $badges[$item->status] : esc_html($item->status);
    }

    public function column_name($item) {
        $actions = array();

        if ($item->status === 'pending') {
            $actions['approve'] = sprintf(
                '<a href="%s" class="flm-action-approve">%s</a>',
                $this->get_action_url('approve', $item->id), '通过'
            );
            $actions['reject'] = sprintf(
                '<a href="%s" class="flm-action-reject">%s</a>',
                $this->get_action_url('reject', $item->id), '拒绝'
            );
        }
        if ($item->status === 'rejected') {
            $actions['approve'] = sprintf(
                '<a href="%s" class="flm-action-approve">%s</a>',
                $this->get_action_url('approve', $item->id), '通过'
            );
        }
        $actions['delete'] = sprintf(
            '<a href="%s" class="flm-action-delete submitdelete">%s</a>',
            $this->get_action_url('delete', $item->id), '删除'
        );

        return sprintf('<strong>%s</strong> %s', esc_html($item->name), $this->row_actions($actions));
    }

    private function get_action_url($action, $id) {
        return wp_nonce_url(
            add_query_arg(
                array('page' => 'friend-links-manager', 'action' => $action, 'id' => $id),
                admin_url('admin.php')
            ),
            'flm_' . $action . '_' . $id,
            'flm_nonce'
        );
    }

    public function extra_tablenav($which) {
        if ($which === 'top') {
            $current_status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
            echo '<div class="alignleft actions">';
            echo '<label class="screen-reader-text" for="flm-status-filter">按状态筛选</label>';
            echo '<select name="status" id="flm-status-filter">';
            echo '<option value="">全部状态</option>';
            echo '<option value="pending"' . selected($current_status, 'pending', false) . '>待审核</option>';
            echo '<option value="approved"' . selected($current_status, 'approved', false) . '>已通过</option>';
            echo '<option value="rejected"' . selected($current_status, 'rejected', false) . '>已拒绝</option>';
            echo '</select>';
            echo '<button type="submit" class="button" id="flm-filter-submit" name="filter_action" value="1">筛选</button>';
            echo '</div>';
        }
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'friend_links';

        $per_page = 20;
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $where = "WHERE 1=1";
        if (!empty($_REQUEST['status'])) {
            $status = sanitize_text_field($_REQUEST['status']);
            if (in_array($status, array('pending', 'approved', 'rejected'), true)) {
                $where .= $wpdb->prepare(" AND status = %s", $status);
            }
        }

        $orderby = 'id';
        $order = 'DESC';
        if (!empty($_GET['orderby']) && in_array($_GET['orderby'], array('id', 'status', 'created_at'), true)) {
            $orderby = sanitize_text_field($_GET['orderby']);
        }
        if (!empty($_GET['order']) && in_array(strtoupper($_GET['order']), array('ASC', 'DESC'), true)) {
            $order = strtoupper($_GET['order']);
        }

        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results(
            "SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT $per_page OFFSET $offset"
        );

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }

    public function no_items() {
        echo '<div class="flm-empty-state"><p>暂无友情链接申请</p></div>';
    }
}

add_action('admin_menu', 'flm_admin_menu');
function flm_admin_menu() {
    add_menu_page(
        '友情链接管理', '友情链接', 'manage_options',
        'friend-links-manager', 'flm_admin_page',
        'dashicons-admin-links', 6
    );
}

// 单条操作：admin_init 阶段处理，确保 redirect 在 headers sent 之前
add_action('admin_init', 'flm_handle_single_action');
function flm_handle_single_action() {
    if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'friend-links-manager') {
        return;
    }
    if (!isset($_GET['action']) || !isset($_GET['id']) || !isset($_GET['flm_nonce'])) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';
    $action = sanitize_text_field($_GET['action']);
    $id = intval($_GET['id']);

    if (!in_array($action, array('approve', 'reject', 'delete'), true)) {
        return;
    }
    if (!wp_verify_nonce($_GET['flm_nonce'], 'flm_' . $action . '_' . $id)) {
        wp_die('安全验证失败，请重试。');
    }
    if (!current_user_can('manage_options')) {
        wp_die('您没有权限执行此操作。');
    }

    if ($action === 'approve') {
        flm_approve_link($id);
        $msg = '链接已通过审核';
    } elseif ($action === 'reject') {
        $wpdb->update($table_name, array('status' => 'rejected'), array('id' => $id));
        $msg = '链接已拒绝';
    } elseif ($action === 'delete') {
        $wpdb->delete($table_name, array('id' => $id));
        $msg = '链接已删除';
    }

    wp_safe_redirect(admin_url('admin.php?page=friend-links-manager&flm_notice=' . urlencode($msg)));
    exit;
}

// 批量操作：admin_post 阶段处理
add_action('admin_post_flm_bulk_action', 'flm_handle_bulk_action');
function flm_handle_bulk_action() {
    if (!current_user_can('manage_options')) {
        wp_die('您没有权限执行此操作。');
    }

    check_admin_referer('bulk-friend-links');

    if (!isset($_POST['flm_ids']) || !is_array($_POST['flm_ids'])) {
        wp_safe_redirect(admin_url('admin.php?page=friend-links-manager'));
        exit;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';

    $action = '';
    if (!empty($_POST['action']) && $_POST['action'] !== '-1') {
        $action = sanitize_text_field($_POST['action']);
    } elseif (!empty($_POST['action2']) && $_POST['action2'] !== '-1') {
        $action = sanitize_text_field($_POST['action2']);
    }
    if (!$action || !in_array($action, array('approve', 'reject', 'delete'), true)) {
        wp_safe_redirect(admin_url('admin.php?page=friend-links-manager'));
        exit;
    }

    $ids = array_map('intval', $_POST['flm_ids']);
    $count = 0;

    foreach ($ids as $id) {
        if ($action === 'approve') {
            flm_approve_link($id);
            $count++;
        } elseif ($action === 'reject') {
            $wpdb->update($table_name, array('status' => 'rejected'), array('id' => $id));
            $count++;
        } elseif ($action === 'delete') {
            $wpdb->delete($table_name, array('id' => $id));
            $count++;
        }
    }

    $labels = array('approve' => '通过', 'reject' => '拒绝', 'delete' => '删除');
    wp_safe_redirect(admin_url('admin.php?page=friend-links-manager&flm_notice=' . urlencode(sprintf('已%s %d 条链接', $labels[$action], $count))));
    exit;
}

function flm_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('您没有权限访问此页面。');
    }

    if (isset($_GET['flm_notice'])) {
        $notice = sanitize_text_field(urldecode($_GET['flm_notice']));
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
    }

    echo '<div class="wrap flm-admin-wrap">';
    echo '<h1>友情链接管理</h1>';

    $list_table = new FLM_List_Table();
    $list_table->prepare_items();

    echo '<form id="flm-list-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('bulk-friend-links');
    echo '<input type="hidden" name="action" value="flm_bulk_action" />';
    echo '<input type="hidden" name="page" value="friend-links-manager" />';
    $list_table->display();
    echo '</form>';

    echo '</div>';
}

add_action('admin_head', 'flm_admin_styles');
function flm_admin_styles() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'toplevel_page_friend-links-manager') {
        return;
    }
    ?>
    <style>
    .flm-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; line-height: 1.6; }
    .flm-badge-pending  { background: #fff3cd; color: #856404; }
    .flm-badge-approved { background: #d4edda; color: #155724; }
    .flm-badge-rejected { background: #f8d7da; color: #721c24; }
    .flm-action-approve { color: #00a32a !important; }
    .flm-action-reject  { color: #dba617 !important; }
    .flm-action-delete  { color: #d63638 !important; }
    .flm-empty-state { text-align: center; padding: 40px 20px; color: #999; font-size: 14px; }
    .flm-empty-state::before { content: "\f103"; font-family: dashicons; font-size: 48px; display: block; margin-bottom: 10px; color: #ccc; }
    </style>
    <script>
    jQuery(function($) {
        $('#flm-list-form').on('click', '#flm-filter-submit', function(e) {
            e.preventDefault();
            var status = $('#flm-status-filter').val();
            var url = '<?php echo esc_js(admin_url('admin.php')); ?>?page=friend-links-manager';
            if (status) url += '&status=' + encodeURIComponent(status);
            window.location.href = url;
        });
    });
    </script>
    <?php
}
