<?php
defined('ABSPATH') || exit;

function wsc_wind_chart_shortcode($atts) {
    $atts = shortcode_atts([
        'location' => 'Guanyin',
    ], $atts);

    $data = wsc_get_latest_wind_data($atts['location']);

    if (!$data) {
        return '<p>目前沒有風速資料。</p>';
    }

    ob_start();
    ?>
    <div id="wind-speed-chart" style="height: 400px; width: 100%;"></div>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        Highcharts.chart('wind-speed-chart', <?php echo json_encode($data); ?>);
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('wind_chart', 'wsc_wind_chart_shortcode');
