<?php
if (!defined('ABSPATH')) {
    exit;
}

// 在 init 阶段处理前台表单提交（任何输出之前），避免 headers already sent
add_action('template_redirect', 'flm_handle_form_submit');
function flm_handle_form_submit() {
    $log = WP_CONTENT_DIR . '/flm-debug.log';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['flm_submit'])) {
        return;
    }

    file_put_contents($log, date('H:i:s') . " POST收到\n", FILE_APPEND);
    file_put_contents($log, date('H:i:s') . " flm_website=[" . (isset($_POST['flm_website']) ? $_POST['flm_website'] : 'UNSET') . "]\n", FILE_APPEND);
    file_put_contents($log, date('H:i:s') . " flm_nonce=" . (isset($_POST['flm_nonce']) ? 'SET' : 'MISSING') . "\n", FILE_APPEND);

    // 蜜罐检查
    if (!empty($_POST['flm_website'])) {
        file_put_contents($log, date('H:i:s') . " 蜜罐触发!提交被丢弃\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', 'success', get_permalink()));
        exit;
    }

    if (!isset($_POST['flm_nonce']) || !wp_verify_nonce($_POST['flm_nonce'], 'flm_submit_form')) {
        file_put_contents($log, date('H:i:s') . " Nonce验证失败\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', 'nonce_error', get_permalink()));
        exit;
    }

    file_put_contents($log, date('H:i:s') . " Nonce通过\n", FILE_APPEND);

    $name        = sanitize_text_field($_POST['flm_name']);
    $url         = esc_url_raw($_POST['flm_url']);
    $logo_url    = isset($_POST['flm_logo_url']) ? esc_url_raw($_POST['flm_logo_url']) : '';
    $email       = isset($_POST['flm_email']) ? sanitize_email($_POST['flm_email']) : '';
    $description = isset($_POST['flm_description']) ? sanitize_textarea_field($_POST['flm_description']) : '';

    // 校验
    $error = '';
    if (mb_strlen($name) > 100) {
        $error = 'name_too_long';
    } elseif (empty($name) || empty($url)) {
        $error = 'empty_fields';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'invalid_url';
    } elseif ($logo_url && !filter_var($logo_url, FILTER_VALIDATE_URL)) {
        $error = 'invalid_logo_url';
    } elseif ($email && !is_email($email)) {
        $error = 'invalid_email';
    }

    if ($error) {
        file_put_contents($log, date('H:i:s') . " 校验失败: $error\n", FILE_APPEND);
        wp_redirect(add_query_arg('flm_status', $error, get_permalink()));
        exit;
    }

    file_put_contents($log, date('H:i:s') . " 校验通过, name=$name, url=$url\n", FILE_APPEND);

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

    file_put_contents($log, date('H:i:s') . " 准备插入数据库...\n", FILE_APPEND);

    $inserted = $wpdb->insert(
        $table_name,
        array(
            'name'        => $name,
            'url'         => $url,
            'logo_url'    => $logo_url,
            'email'       => $email,
            'description' => $description,
            'status'      => 'pending',
            'created_at'  => current_time('mysql'),
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

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

// 友链申请表单短代码
function flm_frontend_form() {
    $message = null;

    // 根据重定向参数显示提示
    if (isset($_GET['flm_status'])) {
        $messages = array(
            'success'         => array('type' => 'success', 'text' => '申请已提交，等待管理员审核！'),
            'nonce_error'     => array('type' => 'error',   'text' => '安全验证失败，请重试！'),
            'empty_fields'    => array('type' => 'error',   'text' => '请填写所有必填字段！'),
            'name_too_long'   => array('type' => 'error',   'text' => '网站名称不能超过100个字符！'),
            'invalid_url'     => array('type' => 'error',   'text' => '请输入有效的URL！'),
            'invalid_logo_url'=> array('type' => 'error',   'text' => 'Logo URL 格式不正确！'),
            'invalid_email'   => array('type' => 'error',   'text' => '邮箱格式不正确！'),
            'rate_limit'      => array('type' => 'error',   'text' => '提交过于频繁，请稍后再试！'),
            'duplicate'       => array('type' => 'error',   'text' => '该URL已经提交过了，请勿重复提交！'),
            'db_error'        => array('type' => 'error',   'text' => '提交失败，请稍后重试！'),
        );
        $status = sanitize_text_field($_GET['flm_status']);
        if (isset($messages[$status])) {
            $message = $messages[$status];
        }
    }

    wp_enqueue_style('flm-style', FLM_PLUGIN_URL . 'assets/css/style.css', array(), FLM_VERSION);

    $output = '';
    if (!empty($message)) {
        $output .= sprintf(
            '<div class="flm-toast flm-toast-%s"><span class="flm-toast-icon">%s</span>%s</div>',
            esc_attr($message['type']),
            $message['type'] === 'success' ? '&#10003;' : '&#10007;',
            esc_html($message['text'])
        );
    }

    ob_start();
    include FLM_PLUGIN_DIR . 'templates/friend-links-form.php';
    $output .= ob_get_clean();

    return $output;
}
add_shortcode('friend_links_form', 'flm_frontend_form');

// 友链展示短代码 [friend_links]
function flm_display_links($atts) {
    $atts = shortcode_atts(array(
        'orderby' => 'id',
        'order'   => 'DESC',
        'count'   => 0,
    ), $atts, 'friend_links');

    global $wpdb;
    $table_name = $wpdb->prefix . 'friend_links';

    $allowed_orderby = array('id', 'name', 'created_at');
    $orderby = in_array($atts['orderby'], $allowed_orderby, true) ? $atts['orderby'] : 'id';
    $order = strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC';
    $limit = (int) $atts['count'];

    $sql = "SELECT * FROM $table_name WHERE status = 'approved' ORDER BY $orderby $order";
    if ($limit > 0) {
        $sql .= $wpdb->prepare(" LIMIT %d", $limit);
    }

    $links = $wpdb->get_results($sql);
    if (!$links) {
        return '';
    }

    wp_enqueue_style('flm-style', FLM_PLUGIN_URL . 'assets/css/style.css', array(), FLM_VERSION);

    $html = '<div class="flm-links-grid">';
    foreach ($links as $link) {
        $initial = esc_attr(mb_substr($link->name, 0, 1));

        $html .= '<a class="flm-link-card" href="' . esc_url($link->url) . '" target="_blank" rel="noopener">';
        if ($link->logo_url) {
            $html .= '<img class="flm-link-logo" src="' . esc_url($link->logo_url) . '" alt="' . esc_attr($link->name) . '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'" />';
            $html .= '<div class="flm-link-logo flm-link-logo-fallback" style="display:none">' . esc_html($initial) . '</div>';
        } else {
            $html .= '<div class="flm-link-logo flm-link-logo-fallback">' . esc_html($initial) . '</div>';
        }
        $html .= '<div class="flm-link-info">';
        $html .= '<span class="flm-link-name">' . esc_html($link->name) . '</span>';
        if ($link->description) {
            $html .= '<span class="flm-link-desc">' . esc_html(mb_strimwidth($link->description, 0, 80, '...')) . '</span>';
        }
        $html .= '</div>';
        $html .= '</a>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('friend_links', 'flm_display_links');
