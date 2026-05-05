<?php
/**
 * FLM 数据库修复工具
 * 访问 https://你的域名/wp-content/plugins/friend-links-manage/fix-db.php 即可自动修复
 * 修复完成后请立即删除此文件！
 */

// 加载 WordPress 环境
require_once dirname(__DIR__, 2) . '/wp-load.php';

if (!current_user_can('manage_options')) {
    die('需要管理员权限');
}

global $wpdb;
$table_name = $wpdb->prefix . 'friend_links';

echo '<html><head><meta charset="utf-8"><title>FLM 数据库修复</title>';
echo '<style>body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:0 20px}h1{color:#0073aa}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}.ok{color:#155724;background:#d4edda;padding:2px 8px;border-radius:4px}.err{color:#721c24;background:#f8d7da;padding:2px 8px;border-radius:4px}.warn{color:#856404;background:#fff3cd;padding:2px 8px;border-radius:4px}</style>';
echo '</head><body>';
echo '<h1>FLM 数据库修复工具</h1>';

// 1. 检查表是否存在
echo '<h2>1. 检查数据表</h2>';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
if ($table_exists) {
    echo '<p class="ok">数据表存在: ' . esc_html($table_name) . '</p>';
} else {
    echo '<p class="err">数据表不存在，正在创建...</p>';
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
    $result = $wpdb->query($sql);
    echo $result !== false ? '<p class="ok">创建成功</p>' : '<p class="err">创建失败: ' . esc_html($wpdb->last_error) . '</p>';
}

// 2. 检查列
echo '<h2>2. 检查表结构</h2>';
$columns = $wpdb->get_col("DESC $table_name", 0);
echo '<table><tr><th>列名</th><th>状态</th></tr>';

$required = array('id', 'name', 'url', 'logo_url', 'email', 'description', 'status', 'created_at');
foreach ($required as $col) {
    $exists = in_array($col, $columns, true);
    echo '<tr><td>' . esc_html($col) . '</td><td class="' . ($exists ? 'ok' : 'err') . '">' . ($exists ? '存在' : '缺失') . '</td></tr>';
}
echo '</table>';

// 3. 修复缺失的列
echo '<h2>3. 修复缺失列</h2>';
$added = 0;
$add_sql = array(
    'logo_url'   => "ALTER TABLE $table_name ADD COLUMN logo_url varchar(255) DEFAULT '' AFTER url",
    'email'      => "ALTER TABLE $table_name ADD COLUMN email varchar(100) DEFAULT '' AFTER logo_url",
    'created_at' => "ALTER TABLE $table_name ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER status",
);

foreach ($add_sql as $col => $sql) {
    if (!in_array($col, $columns, true)) {
        $result = $wpdb->query($sql);
        if ($result !== false) {
            echo '<p class="ok">已添加列: ' . esc_html($col) . '</p>';
            $added++;
        } else {
            echo '<p class="err">添加列失败 ' . esc_html($col) . ': ' . esc_html($wpdb->last_error) . '</p>';
        }
    } else {
        echo '<p class="warn">列已存在，跳过: ' . esc_html($col) . '</p>';
    }
}

if ($added === 0 && count(array_intersect($required, $columns)) === count($required)) {
    echo '<p class="ok">所有列完整，无需修复</p>';
}

// 4. 修复 name 列长度
echo '<h2>4. 检查 name 列长度</h2>';
$row = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'name'");
if ($row) {
    if (preg_match('/varchar\((\d+)\)/', $row->Type, $m)) {
        $len = (int) $m[1];
        if ($len > 100) {
            $wpdb->query("ALTER TABLE $table_name MODIFY name varchar(100) NOT NULL");
            echo '<p class="ok">已将 name 从 varchar(' . $len . ') 修改为 varchar(100)</p>';
        } else {
            echo '<p class="ok">name 列长度正常: varchar(' . $len . ')</p>';
        }
    }
}

// 5. 最终表结构
echo '<h2>5. 修复后表结构</h2>';
$final_columns = $wpdb->get_results("DESC $table_name");
echo '<table><tr><th>列名</th><th>类型</th><th>允许NULL</th><th>默认值</th></tr>';
foreach ($final_columns as $col) {
    echo '<tr>';
    echo '<td>' . esc_html($col->Field) . '</td>';
    echo '<td>' . esc_html($col->Type) . '</td>';
    echo '<td>' . esc_html($col->Null) . '</td>';
    echo '<td>' . esc_html($col->Default ?? 'NULL') . '</td>';
    echo '</tr>';
}
echo '</table>';

// 6. 测试插入
echo '<h2>6. 测试插入</h2>';
$test_result = $wpdb->insert(
    $table_name,
    array(
        'name'        => 'FLM测试链接',
        'url'         => 'https://test.example.com/flm-test',
        'logo_url'    => '',
        'email'       => 'test@example.com',
        'description' => '这是一条自动测试数据',
        'status'      => 'pending',
        'created_at'  => current_time('mysql'),
    ),
    array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
);

if ($test_result) {
    $test_id = $wpdb->insert_id;
    echo '<p class="ok">测试插入成功，ID: ' . $test_id . '</p>';
    // 删除测试数据
    $wpdb->delete($table_name, array('id' => $test_id));
    echo '<p class="ok">测试数据已清理</p>';
} else {
    echo '<p class="err">测试插入失败: ' . esc_html($wpdb->last_error) . '</p>';
}

echo '<hr>';
echo '<p><strong>修复完成！请立即删除此文件（fix-db.php）！</strong></p>';
echo '</body></html>';
