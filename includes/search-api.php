<?php
// includes/search-api.php

use WP_REST_Request;

/**
 * ショートコード [status_search]
 */
function wpss_search_form_shortcode() {
    ob_start();
    ?>
    <form id="status-search-form" class="container p-3 border rounded bg-light">
      <div class="row">
        <!-- レアリティ -->
        <div class="col-md-3">
          <label><strong>レアリティ</strong></label><br>
          <select id="rarity" class="form-control">
            <option value="L" selected>L</option>
            <option value="E">E</option>
            <option value="R">R</option>
            <option value="U">U</option>
          </select>
        </div>
        <!-- バージョン -->
        <div class="col-md-3">
          <label><strong>バージョン</strong></label><br>
          <input type="checkbox" id="version_v1" value="v1"> v1
          <input type="checkbox" id="version_v2" value="v2" checked> v2
        </div>
        <!-- ステータス検索 -->
        <div class="col-md-6">
          <label><strong>ステータス検索</strong></label><br>
          <div class="row">
            <?php
            $stats = [
              'hp'            => 'HPを入力',
              'attack'        => '攻撃を入力',
              'magic_attack'  => '魔攻を入力',
              'defense'       => '防御を入力',
              'magic_defense' => '魔防を入力',
              'agility'       => '敏捷を入力',
            ];
            foreach ($stats as $key => $ph) {
              ?>
              <div class="col-md-4">
                <input type="number" id="<?php echo $key; ?>" class="form-control" placeholder="<?php echo $ph; ?>">
                <select id="<?php echo $key; ?>_condition" class="form-control">
                  <option value=">=" selected>以上</option>
                  <option value="<=">以下</option>
                  <option value="=">イコール</option>
                </select>
              </div>
              <?php
            }
            ?>
          </div>
        </div>
      </div>

      <div class="text-center mt-3">
        <button type="button" id="search-button" class="btn btn-primary">検索</button>
      </div>

      <!-- 検索条件表示 -->
      <div class="mt-2 mb-3">
        <strong>検索レアリティ：</strong><span id="current-rarity"></span>
      </div>

      <!-- 結果表示エリア -->
      <div id="search-results" class="mt-4"></div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('status_search', 'wpss_search_form_shortcode');


/**
 * REST API: /wp-json/character-stats/search
 */
function wpss_search_api( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'character_stats';

    // ベースクエリ
    $sql    = "SELECT SQL_CALC_FOUND_ROWS * FROM {$table} WHERE 1=1";
    $params = [];

    // フィルター：rarity
    if ( $req['rarity'] ) {
        $sql      .= " AND rarity = %s";
        $params[]  = $req['rarity'];
    }

    // フィルター：version
    $versions = [];
    if ( $req['version_v1'] ) $versions[] = 'v1';
    if ( $req['version_v2'] ) $versions[] = 'v2';
    if ( $versions ) {
        $ph = implode(',', array_fill(0, count($versions), '%s'));
        $sql   .= " AND version IN ({$ph})";
        $params = array_merge($params, $versions);
    }

    // ステータス検索条件
    $fields = ['hp','attack','magic_attack','defense','magic_defense','agility'];
    foreach ($fields as $f) {
        if ($req[$f] !== null && $req[$f] !== '') {
            $sql      .= " AND {$f} " . esc_sql($req[$f . '_condition']) . " %d";
            $params[] = (int)$req[$f];
        }
    }

    // ソート
    $sort_by    = $req['sort_by'];
    $sort_order = ($req['sort_order'] === 'desc') ? 'DESC' : 'ASC';
    if ($sort_by === 'atk_mgatk') {
        $sql .= " ORDER BY (attack+magic_attack) {$sort_order}";
    } elseif ($sort_by === 'def_mgdef') {
        $sql .= " ORDER BY (defense+magic_defense) {$sort_order}";
    } elseif (in_array($sort_by, $fields, true)) {
        $sql .= " ORDER BY {$sort_by} {$sort_order}";
    }

    // ページネーション
    $page     = (int)$req['page'] ?: 1;
    $per_page = 100;
    $offset   = ($page - 1) * $per_page;
    $sql     .= " LIMIT %d OFFSET %d";
    $params[] = $per_page;
    $params[] = $offset;

    // 実行
    $prepared = $wpdb->prepare($sql, ...$params);
    $results  = $wpdb->get_results($prepared, ARRAY_A);

    // 総件数
    $total    = $wpdb->get_var("SELECT FOUND_ROWS()");
    $pages    = ceil($total / $per_page);

    return rest_ensure_response([
        'data'         => $results,
        'total_pages'  => $pages,
        'current_page' => $page,
    ]);
}
add_action('rest_api_init', function(){
    register_rest_route('character-stats','/search',[
        'methods'             => 'GET',
        'callback'            => 'wpss_search_api',
        'permission_callback' => '__return_true',
    ]);
});
