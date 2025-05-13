<?php
defined('ABSPATH') || exit;

// 建立後台設定頁面
add_action('admin_menu', function () {
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

    // 編輯或刪除單筆資料
    if (isset($_GET['action'], $_GET['id']) && is_numeric($_GET['id'])) {
        $id = intval($_GET['id']);
        $action = $_GET['action'];

        if ($action === 'delete') {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>已刪除 ID：' . esc_html($id) . '</p></div>';
        } elseif ($action === 'edit') {
            $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        }
    }

    // 判斷新增或更新資料
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wsc_submit'])) {
        $location = sanitize_text_field($_POST['location']);
        $location_zhtw = sanitize_text_field($_POST['location_zhtw']);
        $url = esc_url_raw($_POST['source_url']);

        if (!empty($_POST['editing_id'])) {
            // 更新資料
            $wpdb->update($table, [
                'location' => $location,
                'location_zhtw' => $location_zhtw,
                'source_url' => $url
            ], ['id' => intval($_POST['editing_id'])]);

            echo '<div class="updated"><p>資料已更新！</p></div>';
        } else {
            // 新增資料
            $wpdb->insert($table, [
                'location' => $location,
                'location_zhtw' => $location_zhtw,
                'source_url' => $url
            ]);
            echo '<div class="updated"><p>資料已新增！</p></div>';
        }
    }

    // 取得現有資料
    $sources = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    ?>

    <div class="wrap">
        <h1>風速資料來源設定</h1>

        <h2><?= isset($editing) ? '編輯資料來源' : '新增資料來源' ?></h2>
        <form method="post">
            <?php if (isset($editing)): ?>
                <input type="hidden" name="editing_id" value="<?= esc_attr($editing->id) ?>">
            <?php endif; ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="location">地點代碼 (英文)</label></th>
                    <td><input name="location" id="location" type="text" required class="regular-text"
                               value="<?= esc_attr($editing->location ?? '') ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="location_zhtw">地點中文名稱</label></th>
                    <td><input name="location_zhtw" id="location_zhtw" type="text" required class="regular-text"
                               value="<?= esc_attr($editing->location_zhtw ?? '') ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="source_url">資料來源網址</label></th>
                    <td><input name="source_url" id="source_url" type="url" required class="large-text"
                               value="<?= esc_attr($editing->source_url ?? '') ?>"></td>
                </tr>
            </table>
            <p>
                <input type="submit" name="wsc_submit" class="button-primary"
                       value="<?= isset($editing) ? '更新資料' : '新增來源' ?>">
                <?php if (isset($editing)): ?>
                    <a href="<?= admin_url('options-general.php?page=wind-chart-source') ?>" class="button">取消編輯</a>
                <?php endif; ?>
            </p>
        </form>

        <h2>現有資料來源</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>地點代碼</th>
                    <th>地點名稱</th>
                    <th>來源網址</th>
                    <th>建立時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sources)): ?>
                    <tr><td colspan="6">目前沒有資料。</td></tr>
                <?php else: ?>
                    <?php foreach ($sources as $src): ?>
                        <tr>
                            <td><?= esc_html($src->id) ?></td>
                            <td><?= esc_html($src->location) ?></td>
                            <td><?= esc_html($src->location_zhtw) ?></td>
                            <td><a href="<?= esc_url($src->source_url) ?>" target="_blank"><?= esc_html($src->source_url) ?></a></td>
                            <td><?= esc_html($src->created_at) ?></td>
                            <td>
                                <a href="<?= admin_url('options-general.php?page=wind-chart-source&action=edit&id=' . esc_attr($src->id)) ?>" class="button">編輯</a>
                                <a href="<?= admin_url('options-general.php?page=wind-chart-source&action=delete&id=' . esc_attr($src->id)) ?>" class="button" onclick="return confirm('確定要刪除這筆資料嗎？');">刪除</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2>執行爬蟲</h2>
        <form method="post">
            <input type="hidden" name="wsc_run_crawler" value="1">
        <p><input type="submit" class="button button-secondary" value="立即執行 Wind Chart 爬蟲"></p>
        </form>
    <?php
    // 執行爬蟲動作
    if (isset($_POST['wsc_run_crawler'])) {
        echo '<div class="updated"><p>' . wsc_run_crawler() . '</p></div>';
    }

//////////////////////////////////////////////////////////////////////////////////////
    //除錯區
    echo '<pre>';
    echo 'WSC_CRAWLER_PATH: ' . WSC_CRAWLER_PATH . "\n";
    echo 'WSC_CRAWLER_EXEC: ' . WSC_CRAWLER_EXEC . "\n";
    echo '</pre>';
//////////////////////////////////////////////////////////////////////////////////////

    ?>

    <?php
}

// 執行爬蟲 wind-Chart.py
function wsc_run_crawler() {
    global $wpdb;
    $table = $wpdb->prefix . 'wind_sources';

    // 取出所有 source_url
    $sources = $wpdb->get_results("SELECT id, location, location_zhtw, source_url FROM $table");

    if (empty($sources)) {
        return '無可用資料來源。';
    }

    // 將資料輸出為 JSON 並寫入暫存檔（例如 /tmp/wind_sources.json）
    $json_path = sys_get_temp_dir() . '/wind_sources.json';
    file_put_contents($json_path, json_encode($sources));

    // 組合指令：執行 Python 並將 JSON 路徑當成參數
    $cmd = escapeshellcmd("python3 " . WIND_CHART_PYTHON_PATH . " " . escapeshellarg($json_path));
    exec($cmd . " 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        return "爬蟲執行失敗，錯誤輸出：<br>" . nl2br(implode("\n", $output));
    }

    return "爬蟲執行成功：<br>" . nl2br(implode("\n", $output));
}

