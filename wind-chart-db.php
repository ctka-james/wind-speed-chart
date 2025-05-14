<?php
defined('ABSPATH') || exit;

// 新增風速來源表
function wsc_create_source_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_sources';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        location VARCHAR(50) NOT NULL,
        location_zhtw VARCHAR(50) NOT NULL,
        source_url TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// 風速資料存放資料表
function wsc_create_data_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_speed_data';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        location VARCHAR(100) NOT NULL,
        url TEXT NOT NULL,
        script_content LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// 建立資料表
function wsc_create_wind_table() {
    wsc_create_data_table();   // <-- 風速資料存放
    wsc_create_source_table(); // <-- 來源網站網址
}

// 刪除資料表
function wsc_delete_wind_table() {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wind_speed_data");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wind_sources");
}

/**
 * 插入風速資料（供爬蟲呼叫）
 *
 * @param string $location 地點名稱
 * @param string $url 網址
 * @param string $script_content 抓取的 script 資料（建議為純文字或 JSON 字串）
 * @return int|false 新增資料的 ID，失敗則回傳 false
 */
function wsc_insert_wind_data($location, $url, $script_content) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_speed_data';

    $result = $wpdb->insert($table_name, [
        'location'        => sanitize_text_field($location),
        'url'             => esc_url_raw($url),
        'script_content'  => maybe_serialize($script_content),
        'created_at'      => current_time('mysql'),
    ], [
        '%s', '%s', '%s', '%s'
    ]);

    return $result ? $wpdb->insert_id : false;
}

/**
 * 取得指定地點的最新風速資料
 *
 * @param string $location 地點名稱（預設為 Guanyin）
 * @return mixed 抓取結果（通常為 JSON 字串或陣列），若無資料則為 null
 */
function wsc_get_latest_wind_data($location = 'Guanyin') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wind_speed_data';

    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT script_content FROM $table_name WHERE location = %s ORDER BY created_at DESC LIMIT 1",
        $location
    ));

    return $result ? maybe_unserialize($result->script_content) : null;
}
