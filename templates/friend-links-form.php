<form id="flm-form" method="post">
    <?php wp_nonce_field('flm_submit_form', 'flm_nonce'); ?>
    <div id="flm-message"></div> <!-- 消息容器 -->
    <label for="flm_name">网站名称:</label>
    <input type="text" id="flm_name" name="flm_name" required>
    <br>
    <label for="flm_url">网站URL:</label>
    <input type="url" id="flm_url" name="flm_url" required>
    <br>
    <label for="flm_description">网站描述:（可选）</label>
    <textarea id="flm_description" name="flm_description"></textarea>
    <br>
    <button type="submit" name="flm_submit">提交申请</button>
</form>