<?php
/**
 * Plugin Name: Wind Speed Chart
 * Description: 爬取中央氣象署資料，使用 Highcharts 呈現 24 小時風速圖
 * Version: 1.0
 * Author: Jame Tsai
 */

defined('ABSPATH') || exit;

// 定義 Python 爬蟲路徑（新的資料夾 wp-crawler）
define('WSC_CRAWLER_PATH', plugin_dir_path(__FILE__) . '../private/wp-crawler/');
define('WSC_CRAWLER_EXEC', WSC_CRAWLER_PATH . 'wind-Chart.py');

// 載入功能模組
require_once plugin_dir_path(__FILE__) . 'wind-chart-db.php';
require_once plugin_dir_path(__FILE__) . 'wind-chart-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'wind-chart-admin.php';

// 啟用時建立資料表
register_activation_hook(__FILE__, 'wsc_create_wind_table');

// 停用後移除資料表（⚠️只在完全刪除 plugin 時觸發）
register_uninstall_hook(__FILE__, 'wsc_delete_wind_table');
