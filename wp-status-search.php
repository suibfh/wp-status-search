<?php
/*
 Plugin Name: WPステータスサーチ
 Description: ステータス検索＋ソート＋ページネーション機能
 Version:     1.0.0
 Author:      あなたの名前
*/
if (! defined('ABSPATH')) exit;

// まずは install.php だけ読み込む
require_once __DIR__ . '/includes/install.php';
//require_once __DIR__ . '/includes/search-api.php';

function wpss_enqueue_assets() {
  wp_enqueue_script('status-search-js', plugins_url('assets/js/status-search.js', __FILE__), ['jquery'], null, true);
  wp_enqueue_style('status-search-css', plugins_url('assets/css/status-search.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'wpss_enqueue_assets');
