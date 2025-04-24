<?php
// includes/install.php

function wpss_activate_plugin() {
  global $wpdb;
  $table = $wpdb->prefix . 'character_stats';
  $charset = $wpdb->get_charset_collate();

  // テーブル作成
  $sql = "CREATE TABLE IF NOT EXISTS {$table} (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(2) NOT NULL,
    rarity VARCHAR(1) NOT NULL,
    hp INT NOT NULL,
    attack INT NOT NULL,
    magic_attack INT NOT NULL,
    defense INT NOT NULL,
    magic_defense INT NOT NULL,
    agility INT NOT NULL
  ) {$charset};";
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);

  // 初期データ投入（空のときのみ）
  $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
  if ($count === 0) {
    $csv = plugin_dir_path(__FILE__) . '../data/statuses.csv';
    if (($h = @fopen($csv, 'r')) !== false) {
      fgetcsv($h); // ヘッダ読み飛ばし
      while ($row = fgetcsv($h)) {
        list($version,$rarity,$hp,$atk,$matk,$def,$mdef,$agi) = $row;
        $wpdb->insert($table, [
          'version'       => $version,
          'rarity'        => $rarity,
          'hp'            => (int)$hp,
          'attack'        => (int)$atk,
          'magic_attack'  => (int)$matk,
          'defense'       => (int)$def,
          'magic_defense' => (int)$mdef,
          'agility'       => (int)$agi,
        ]);
      }
      fclose($h);
    }
  }
}
register_activation_hook(__DIR__ . '/../wp-status-search.php', 'wpss_activate_plugin');
