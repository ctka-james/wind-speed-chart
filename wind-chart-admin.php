<?php
defined('ABSPATH') || exit;

// 建立後台設定頁面
add_action('admin_menu', function() {
    add_options_page(
        'Wind Chart 資料來源設定',
        'Wind Chart 資料來源',
        'manage_options',
        'wind-chart-source',
        'wsc_render_admin_page'
    );
});

function wsc_render_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'wind_sources';

    // 資料新增處理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wsc_add_source'])) {
        $location = sanitize_text_field($_POST['location']);
        $url = esc_url_raw($_POST['source_url']);

        $wpdb->insert($table, [
            'location' => $location,
            'source_url' => $url
        ]);
        echo '<div class="updated"><p>資料來源已新增</p></div>';
    }

    // 取得現有資料
    $sources = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    ?>

    <div class="wrap">
        <h1>風速資料來源設定</h1>

        <h2>新增資料來源</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="location">地點名稱</label></th>
                    <td><input name="location" id="location" type="text" required class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="source_url">資料網址</label></th>
                    <td><input name="source_url" id="source_url" type="url" required class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button('新增資料來源', 'primary', 'wsc_add_source'); ?>
        </form>

        <h2>現有資料來源</h2>
        <table class="widefat">
            <thead>
                <tr><th>ID</th><th>地點</th><th>來源網址</th><th>建立時間</th></tr>
            </thead>
            <tbody>
                <?php foreach ($sources as $src): ?>
                    <tr>
                        <td><?= esc_html($src->id) ?></td>
                        <td><?= esc_html($src->location) ?></td>
                        <td><a href="<?= esc_url($src->source_url) ?>" target="_blank"><?= esc_html($src->source_url) ?></a></td>
                        <td><?= esc_html($src->created_at) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
