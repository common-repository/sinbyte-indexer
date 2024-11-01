<?php
/**
 * @author Trung Huynh (HuTaNaTu)
 * @copyright 2021 by Trung Huynh (HuTaNaTu)
 *
 * @package Sinbyte Indexer
 *
 * @since 1.0.0
 */

namespace WP_SBI;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Setup {
	static function install() {
		self::check_requirements();
		self::default_settings();
	}

	static function check_requirements() {
		if ( version_compare( phpversion(), '5.3.29', '<=' ) ) {
			wp_die( 'PHP 5.3 or lower detected. ' . Main::PLUGIN_NAME . ' requires PHP 5.6 or greater.' );
		}
	}

	static function default_settings() {
		$default = [
			'enable' => 'yes',
		];
		$options = get_option( Admin_Menus::OPTION_NAME );
		if ( ! $options ) {
			$options = [];
			update_option( Admin_Menus::OPTION_NAME, wp_parse_args( $options, $default ) );
		}
	}

	static function deactivate() {
	}

	static function uninstall() {
		self::deactivate();
	}
}