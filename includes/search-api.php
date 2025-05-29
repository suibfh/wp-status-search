<?php
// includes/search-api.php

// ショートコード [status_search]
function wpss_search_form_shortcode() {
    ob_start();
    ?>
    <form id="status-search-form" class="container p-3 border rounded bg-light">
      <div class="row">
        <div class="col-md-3">
          <label><strong>レアリティ</strong></label><br>
          <select id="rarity" class="form-control">
            <option value="L" selected>L</option>
            <option value="E">E</option>
            <option value="R">R</option>
            <option value="U">U</option>
          </select>
        </div>
        <div class="col-md-3">
          <label><strong>バージョン</strong></label><br>
          <input type="checkbox" id="version_v1" value="v1"> v1
          <input type="checkbox" id="version_v2" value="v2" checked> v2
        </div>
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
            <div class="col-md-4">
              <input type="number" id="atk_mgatk" class="form-control" placeholder="攻撃＋魔攻を入力">
              <select id="atk_mgatk_condition" class="form-control">
                <option value=">=" selected>以上</option>
                <option value="<=">以下</option>
                <option value="=">イコール</option>
              </select>
            </div>
            <div class="col-md-4">
              <input type="number" id="def_mgdef" class="form-control" placeholder="防御＋魔防を入力">
              <select id="def_mgdef_condition" class="form-control">
                <option value=">=" selected>以上</option>
                <option value="<=">以下</option>
                <option value="=">イコール</option>
              </select>
            </div>
            </div>
        </div>
      </div>
      <div class="text-center mt-3">
        <button type="button" id="search-button" class="btn btn-primary">検索</button>
      </div>
      <div id="search-results" class="mt-4"></div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('status_search', 'wpss_search_form_shortcode');


// REST API: /wp-json/character-stats/search
function wpss_search_api( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'character_stats';

    // ベースクエリ
    $sql    = "SELECT SQL_CALC_FOUND_ROWS * FROM {$table} WHERE 1=1";
    $params = [];

    // フィルター：rarity
    if ( $req['rarity'] ) {
        $sql     .= " AND rarity = %s";
        $params[] = $req['rarity'];
    }

    // フィルター：version
    $versions = [];
    if ( $req['version_v1'] ) $versions[] = 'v1';
    if ( $req['version_v2'] ) $versions[] = 'v2';
    if ( ! empty( $versions ) ) {
        // placeholders for each version
        $placeholders = implode( ',', array_fill( 0, count( $versions ), '%s' ) );
        $sql         .= " AND version IN ({$placeholders})";
        $params       = array_merge( $params, $versions );
    }

    // フィルター：各ステータス
    $fields = [ 'hp','attack','magic_attack','defense','magic_defense','agility' ];
    foreach ( $fields as $f ) {
        if ( $req[ $f ] !== null && $req[ $f ] !== '' ) {
            $cond     = esc_sql( $req[ $f . '_condition' ] );
            $sql     .= " AND {$f} {$cond} %d";
            $params[] = (int) $req[ $f ];
        }
    }

    // フィルター：攻撃＋魔攻 (追加)
    if ( $req['atk_mgatk'] !== null && $req['atk_mgatk'] !== '' ) {
        $cond     = esc_sql( $req['atk_mgatk_condition'] );
        $sql     .= " AND (attack + magic_attack) {$cond} %d";
        $params[] = (int) $req['atk_mgatk'];
    }

    // フィルター：防御＋魔防 (追加)
    if ( $req['def_mgdef'] !== null && $req['def_mgdef'] !== '' ) {
        $cond     = esc_sql( $req['def_mgdef_condition'] );
        $sql     .= " AND (defense + magic_defense) {$cond} %d";
        $params[] = (int) $req['def_mgdef'];
    }

    // ソート処理
    $sort_by    = $req['sort_by'];
    $sort_order = ( $req['sort_order'] === 'desc' ) ? 'DESC' : 'ASC';

    if ( $sort_by === 'atk_mgatk' ) {
        $sql .= " ORDER BY (attack+magic_attack) {$sort_order}";
    } elseif ( $sort_by === 'def_mgdef' ) {
        $sql .= " ORDER BY (defense+magic_defense) {$sort_order}";
    } else {
        if ( in_array( $sort_by, $fields, true ) ) {
            $sql .= " ORDER BY {$sort_by} {$sort_order}";
        }
    }

    // ページネーション
    $page           = (int) $req['page'] ?: 1;
    $per_page       = 100;
    $offset         = ( $page - 1 ) * $per_page;
    $sql           .= " LIMIT %d OFFSET %d";
    $params[]       = $per_page;
    $params[]       = $offset;

    // 実行
    $prepared = $wpdb->prepare( $sql, ...$params );
    $results  = $wpdb->get_results( $prepared, ARRAY_A );

    // 総件数取得
    $total_rows = $wpdb->get_var( "SELECT FOUND_ROWS()" );
    $total_pages = ceil( $total_rows / $per_page );

    return rest_ensure_response( [
        'data'         => $results,
        'total_pages'  => $total_pages,
        'current_page' => $page,
    ] );
}
add_action( 'rest_api_init', function(){
    register_rest_route( 'character-stats', '/search', [
        'methods'             => 'GET',
        'callback'            => 'wpss_search_api',
        'permission_callback' => '__return_true',
    ] );
} );
