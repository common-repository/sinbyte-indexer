<?php
/**
 * @author Trung Huynh (HuTaNaTu)
 * @copyright 2021 by Trung Huynh (HuTaNaTu)
 *
 * @package Sinbyte Indexer
 *
 * @since 1.0.0
 */
/**
 * Plugin Name:       Sinbyte Indexer
 * Plugin URI:        https://sinbyte.com/plugin-wordpress-sinbyte-indexer/
 * Description:       Plugin will help you index links of post/page to Google Search in within1-3 hours.
 * Version:           1.2.3
 * Author:            Sinbyte
 * Author URI:        https://sinbyte.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       wp-sbi
 * Domain Path:       languages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/vendor/autoload.php';
if ( ! defined( 'WP_SBI_FILE' ) ) {
	define( 'WP_SBI_FILE', __FILE__ );
}
if ( ! defined( 'WP_SBI_DIR' ) ) {
	define( 'WP_SBI_DIR', trailingslashit( plugin_dir_path( WP_SBI_FILE ) ) );
}
if ( ! defined( 'WP_SBI_URL' ) ) {
	define( 'WP_SBI_URL', trailingslashit( plugins_url( '', WP_SBI_FILE ) ) );
}
function wp_sbi() {
	return WP_SBI\Main::instance();
}
$GLOBALS['wp-sbi'] = wp_sbi();