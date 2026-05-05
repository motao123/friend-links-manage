<form id="flm-form" method="post">
    <?php wp_nonce_field('flm_submit_form', 'flm_nonce'); ?>

    <div class="flm-honeypot" aria-hidden="true">
        <label for="flm_website">网站</label>
        <input type="text" id="flm_website" name="flm_website" tabindex="-1" autocomplete="off" />
    </div>

    <div class="flm-field">
        <label for="flm_name">网站名称 <span class="flm-required">*</span></label>
        <input type="text" id="flm_name" name="flm_name" required maxlength="100" placeholder="请输入网站名称">
    </div>

    <div class="flm-field">
        <label for="flm_url">网站URL <span class="flm-required">*</span></label>
        <input type="url" id="flm_url" name="flm_url" required maxlength="255" placeholder="https://example.com">
    </div>

    <div class="flm-field">
        <label for="flm_logo_url">网站Logo URL</label>
        <input type="url" id="flm_logo_url" name="flm_logo_url" maxlength="255" placeholder="https://example.com/logo.png">
    </div>

    <div class="flm-field">
        <label for="flm_email">联系邮箱</label>
        <input type="email" id="flm_email" name="flm_email" maxlength="100" placeholder="admin@example.com">
    </div>

    <div class="flm-field">
        <label for="flm_description">网站描述</label>
        <textarea id="flm_description" name="flm_description" rows="3" maxlength="500" placeholder="简要描述您的网站（可选）"></textarea>
    </div>

    <div class="flm-field">
        <input type="hidden" name="flm_submit" value="1" />
        <button type="submit" class="flm-submit-btn">提交申请</button>
    </div>
</form>

<script>
(function() {
    var form = document.getElementById('flm-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        var name = form.querySelector('#flm_name');
        var url = form.querySelector('#flm_url');
        var btn = form.querySelector('.flm-submit-btn');

        if (!name.value.trim()) {
            e.preventDefault();
            name.focus();
            return;
        }
        if (!url.value.trim()) {
            e.preventDefault();
            url.focus();
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.textContent = '提交中...';
        }
    });
})();
</script>
