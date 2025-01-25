<?php
function flm_frontend_form() {
    // 初始化消息变量
    $message = '';

    // 处理表单提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flm_submit'])) {
        // 验证CSRF令牌
        if (!isset($_POST['flm_nonce']) || !wp_verify_nonce($_POST['flm_nonce'], 'flm_submit_form')) {
            $message = '<p class="flm-error">安全验证失败，请重试！</p>';
        } else {
            // 获取并清理表单数据
            $name = sanitize_text_field($_POST['flm_name']);
            $url = esc_url_raw($_POST['flm_url']);
            $description = sanitize_textarea_field($_POST['flm_description']);

            // 验证必填字段
            if (empty($name) || empty($url)) {
                $message = '<p class="flm-error">请填写所有必填字段！</p>';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) { // 检查URL格式
                $message = '<p class="flm-error">请输入有效的URL！</p>';
            } else {
                global $wpdb;
                $table_name = $wpdb->prefix . 'friend_links';

                // 检查是否已经存在相同的URL
                $existing_link = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE url = %s",
                    $url
                ));

                if (!$existing_link) {
                    // 插入数据
                    $wpdb->insert(
                        $table_name,
                        array(
                            'name' => $name,
                            'url' => $url,
                            'description' => $description,
                            'status' => 'pending'
                        ),
                        array('%s', '%s', '%s', '%s') // 数据类型
                    );

                    // 重定向到成功页面，避免重复提交
                    wp_redirect(add_query_arg('flm_status', 'success', get_permalink()));
                    exit;
                } else {
                    $message = '<p class="flm-error">该URL已经提交过了，请勿重复提交！</p>';
                }
            }
        }
    }

    // 显示成功消息（通过重定向后的GET参数）
    if (isset($_GET['flm_status']) && $_GET['flm_status'] === 'success') {
        $message = '<p class="flm-success">申请已提交，等待管理员审核！</p>';
    }

    // 加载样式和脚本
    wp_enqueue_style('flm-style', FLM_PLUGIN_URL . 'assets/css/style.css');
    wp_enqueue_script('flm-script', FLM_PLUGIN_URL . 'assets/js/script.js', array('jquery'), null, true);

    // 输出消息（如果有）
    $output = $message;

    // 加载模板文件
    ob_start();
    include FLM_PLUGIN_DIR . 'templates/friend-links-form.php';
    $output .= ob_get_clean();

    return $output;
}

// 注册短代码
add_shortcode('friend_links_form', 'flm_frontend_form');