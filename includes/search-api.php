<?php
// includes/search-api.php
use WP_REST_Request;

// ショートコード [status_search]
function wpss_search_form_shortcode() {
  ob_start();
  ?>
  <form id="status-search-form" class="container p-3 border rounded bg-light">
    <!-- ここにHTMLフォーム（レアリティ, バージョン, ステータス入力, 条件, ソート選択, 検索ボタン）を -->
    <div id="search-results"></div>
  </form>
  <?php
  return ob_get_clean();
}
add_shortcode('status_search', 'wpss_search_form_shortcode');

// REST API: /wp-json/character-stats/search
function wpss_search_api(WP_REST_Request $req) {
  global $wpdb;
  $table = $wpdb->prefix . 'character_stats';

  // 基本クエリ
  $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$table} WHERE 1=1";
  $params = [];

  // フィルタ（rarity, versions, 各ステータス条件）…（省略）
  // 複合ソート対応
  $sort_by    = $req['sort_by'];
  $sort_order = ($req['sort_order'] === 'desc') ? 'DESC' : 'ASC';
  if ($sort_by === 'atk_mgatk') {
    $sql .= " ORDER BY (attack+magic_attack) {$sort_order}";
  } elseif ($sort_by === 'def_mgdef') {
    $sql .= " ORDER BY (defense+magic_defense) {$sort_order}";
  } else {
    $valid = ['hp','attack','magic_attack','defense','magic_defense','agility'];
    if (in_array($sort_by, $valid)) {
      $sql .= " ORDER BY {$sort_by} {$sort_order}";
    }
  }

  // ページネーション（LIMIT OFFSET）…（省略）
  $prepared = $wpdb->prepare($sql, …$params);
  $results  = $wpdb->get_results($prepared, ARRAY_A);
  $total    = $wpdb->get_var("SELECT FOUND_ROWS()");
  $pages    = ceil($total / 100);

  return rest_ensure_response([
    'data'         => $results,
    'total_pages'  => $pages,
    'current_page' => (int)$req['page'],
  ]);
}
add_action('rest_api_init', function(){
  register_rest_route('character-stats','/search',[
    'methods'             => 'GET',
    'callback'            => 'wpss_search_api',
    'permission_callback' => '__return_true',
  ]);
});
