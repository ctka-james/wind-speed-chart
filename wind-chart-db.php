<?php
defined('ABSPATH') || exit;

// 建立資料表
function wsc_create_wind_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_speed_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        location VARCHAR(50) NOT NULL,
        data LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// 刪除資料表
function wsc_delete_wind_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_speed_data';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// 插入資料（供爬蟲呼叫）
function wsc_insert_wind_data($location, $json_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_speed_data';

    $wpdb->insert($table_name, [
        'location' => $location,
        'data' => maybe_serialize($json_data),
    ]);
}

// 取得最新資料
function wsc_get_latest_wind_data($location = 'Guanyin') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_speed_data';

    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT data FROM $table_name WHERE location = %s ORDER BY created_at DESC LIMIT 1",
        $location
    ));

    return $result ? maybe_unserialize($result->data) : null;
}
