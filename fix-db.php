<?php
/**
 * FLM 提交流程诊断工具
 * 访问后按页面提示操作，修复完成后请立即删除此文件！
 */

require_once dirname(__DIR__, 3) . '/wp-load.php';

if (!current_user_can('manage_options')) {
    die('需要管理员权限');
}

global $wpdb;
$table_name = $wpdb->prefix . 'friend_links';

echo '<html><head><meta charset="utf-8"><title>FLM 提交诊断</title>';
echo '<style>body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:0 20px}h1,h2{color:#0073aa}table{border-collapse:collapse;width:100%;margin:10px 0}td,th{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}.ok{color:#155724;background:#d4edda;padding:4px 12px;border-radius:4px;display:inline-block}.err{color:#721c24;background:#f8d7da;padding:4px 12px;border-radius:4px;display:inline-block}.warn{color:#856404;background:#fff3cd;padding:4px 12px;border-radius:4px;display:inline-block}pre{background:#f5f5f5;padding:15px;border-radius:6px;overflow-x:auto}form{margin:20px 0}input,button{padding:8px 16px;margin:4px}button{background:#0073aa;color:#fff;border:none;border-radius:4px;cursor:pointer}button:hover{background:#005177}</style>';
echo '</head><body>';
echo '<h1>FLM 提交流程诊断工具</h1>';

// ========== 1. 数据库表检查 ==========
echo '<h2>1. 数据库表检查</h2>';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
echo $table_exists ? '<p class="ok">表存在</p>' : '<p class="err">表不存在</p>';

if ($table_exists) {
    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo '<p>当前表中有 <strong>' . $count . '</strong> 条记录</p>';

    if ($count > 0) {
        echo '<table><tr><th>ID</th><th>名称</th><th>URL</th><th>状态</th><th>创建时间</th></tr>';
        $rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 10");
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . esc_html($r->id) . '</td>';
            echo '<td>' . esc_html($r->name) . '</td>';
            echo '<td>' . esc_html($r->url) . '</td>';
            echo '<td>' . esc_html($r->status) . '</td>';
            echo '<td>' . esc_html($r->created_at) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

// ========== 2. 模拟前台提交（绕过短代码） ==========
echo '<h2>2. 模拟前台提交</h2>';
echo '<p>点击下方按钮直接向数据库插入一条测试记录（跳过表单/nonce/蜜罐/频率限制）：</p>';

if (isset($_GET['flm_diag_insert']) && $_GET['flm_diag_insert'] === '1') {
    $test_url = 'https://diag-test-' . time() . '.example.com';
    $result = $wpdb->insert(
        $table_name,
        array(
            'name'        => '诊断测试-' . date('H:i:s'),
            'url'         => $test_url,
            'logo_url'    => '',
            'email'       => 'diag@test.com',
            'description' => '诊断工具自动插入',
            'status'      => 'pending',
            'created_at'  => current_time('mysql'),
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result) {
        echo '<p class="ok">直接插入成功！ID: ' . $wpdb->insert_id . '</p>';
        echo '<p><a href="' . admin_url('admin.php?page=friend-links-manager') . '" target="_blank">点此打开后台管理页面</a>，看是否能看到这条记录</p>';
    } else {
        echo '<p class="err">直接插入失败: ' . esc_html($wpdb->last_error) . '</p>';
    }
} else {
    echo '<a href="?flm_diag_insert=1"><button>执行直接插入测试</button></a>';
}

// ========== 3. 模拟完整提交流程 ==========
echo '<h2>3. 模拟完整提交流程（含 nonce）</h2>';
echo '<p>测试完整的短代码处理逻辑：</p>';

if (isset($_POST['flm_diag_submit'])) {
    echo '<h3>提交结果</h3>';

    // 模拟前台表单数据
    $test_name = '流程测试-' . date('H:i:s');
    $test_url  = 'https://flow-test-' . time() . '.example.com';

    // 验证 nonce
    $nonce_valid = isset($_POST['flm_diag_nonce']) && wp_verify_nonce($_POST['flm_diag_nonce'], 'flm_submit_form');
    echo $nonce_valid ? '<p class="ok">Nonce 验证通过</p>' : '<p class="err">Nonce 验证失败</p>';

    if ($nonce_valid) {
        $result = $wpdb->insert(
            $table_name,
            array(
                'name'        => $test_name,
                'url'         => $test_url,
                'logo_url'    => '',
                'email'       => '',
                'description' => '流程测试',
                'status'      => 'pending',
                'created_at'  => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            echo '<p class="ok">插入成功！ID: ' . $wpdb->insert_id . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=friend-links-manager') . '" target="_blank">点此打开后台管理页面</a>，看是否能看到</p>';
        } else {
            echo '<p class="err">插入失败: ' . esc_html($wpdb->last_error) . '</p>';
        }
    }
}

echo '<form method="post">';
wp_nonce_field('flm_submit_form', 'flm_diag_nonce');
echo '<button type="submit" name="flm_diag_submit" value="1">执行完整流程测试</button>';
echo '</form>';

// ========== 4. 检查前台短代码是否正常注册 ==========
echo '<h2>4. 短代码注册检查</h2>';
global $shortcode_tags;
$has_form = isset($shortcode_tags['friend_links_form']);
$has_list = isset($shortcode_tags['friend_links']);
echo $has_form ? '<p class="ok">[friend_links_form] 已注册</p>' : '<p class="err">[friend_links_form] 未注册！</p>';
echo $has_list ? '<p class="ok">[friend_links] 已注册</p>' : '<p class="err">[friend_links] 未注册！</p>';

// ========== 5. 检查插件文件是否一致 ==========
echo '<h2>5. 插件文件版本检查</h2>';
$plugin_data = get_plugin_data(FLM_PLUGIN_DIR . 'friend-links-manager.php');
echo '<table>';
echo '<tr><th>项目</th><th>值</th></tr>';
echo '<tr><td>插件版本</td><td>' . esc_html($plugin_data['Version']) . '</td></tr>';
echo '<tr><td>FLM_VERSION 常量</td><td>' . esc_html(FLM_VERSION) . '</td></tr>';
echo '<tr><td>flm_version 选项</td><td>' . esc_html(get_option('flm_version', '未设置')) . '</td></tr>';
echo '<tr><td>插件目录</td><td>' . esc_html(FLM_PLUGIN_DIR) . '</td></tr>';
echo '<tr><td>插件URL</td><td>' . esc_html(FLM_PLUGIN_URL) . '</td></tr>';
echo '</table>';

// ========== 6. 检查 Redis 缓存是否干扰 ==========
echo '<h2>6. 对象缓存检查</h2>';
$cache_active = wp_using_ext_object_cache();
echo $cache_active ? '<p class="warn">检测到外部对象缓存（Redis），可能缓存了旧查询结果</p>' : '<p class="ok">无外部缓存</p>';

if ($cache_active) {
    echo '<p>尝试刷新缓存...</p>';
    wp_cache_flush();
    echo '<p class="ok">已调用 wp_cache_flush()</p>';
}

// ========== 7. 后台管理页面链接 ==========
echo '<h2>7. 快捷操作</h2>';
echo '<p><a href="' . admin_url('admin.php?page=friend-links-manager') . '" target="_blank">打开后台友情链接管理页</a></p>';
echo '<p><a href="https://imotao.com/friend-links-apply" target="_blank">打开前台申请页面</a></p>';

echo '<hr>';
echo '<p><strong>诊断完成！修复问题后请立即删除此文件（fix-db.php）！</strong></p>';
echo '</body></html>';
