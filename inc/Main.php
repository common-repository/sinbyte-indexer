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

class Main {
	const PLUGIN_NAME = 'WP Sinbyte Indexer';
	const PLUGIN_SLUG = 'wp-sbi';
	const OPTION_NAME = 'wp-sbi';
	const VERSION = '1.2.3';
	const LOG_LIMIT = 50;
	const API_ENDPOINT = 'https://app.sinbyte.com/api/indexing/';
	protected static $instance;

	function __construct() {
		$this->define_constants();
		$this->init_hooks();
		add_action( 'init', [ $this, 'init' ] );
	}

	private function define_constants() {
		// URL's
		define( 'WP_SBI_ASSETS_URL', trailingslashit( WP_SBI_URL . 'assets' ) );
		define( 'WP_SBI_CSS_URL', trailingslashit( WP_SBI_ASSETS_URL . 'css' ) );
	}

	private function init_hooks() {
		register_activation_hook( WP_SBI_FILE, [ 'WP_SBI\Setup', 'install' ] );
		register_deactivation_hook( WP_SBI_FILE, [ 'WP_SBI\Setup', 'deactivate' ] );
		register_uninstall_hook( WP_SBI_FILE, [ 'WP_SBI\Setup', 'uninstall' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ] );
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_filter( 'plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );

		$options = get_option( Admin_Menus::OPTION_NAME );
		$enable  = $options['enable'] ?? null;
		if ( $enable === 'yes' ) {
			add_action( 'save_post', [ $this, 'save_post' ], 10, 3 );
		}

	}

	function admin_enqueue_styles( $hook ) {
		if ( $hook == 'toplevel_page_' . Admin_Menus::PAGE ) {
			$ver = self::VERSION;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$ver = rand( 1, 9999999 );
			}
			wp_enqueue_style( self::PLUGIN_SLUG . '-admin', WP_SBI_CSS_URL . self::PLUGIN_SLUG . '-admin.css', array(), $ver );
		}

		return;
	}

	static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	function init() {
		new Admin_Menus();
	}

	function load_textdomain() {
		load_plugin_textdomain( 'wp-sbi', false, basename( dirname( WP_SBI_FILE ) ) . '/languages/' );
	}

	function plugin_action_links( $links, $file ) {
		if ( strpos( $file, basename( WP_SBI_FILE ) ) !== false ) {
			$settings_url = add_query_arg( 'page', Admin_Menus::PAGE, admin_url( 'admin.php' ) );
			$newLinks     = [
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					$settings_url,
					__( 'Settings', 'wp-sbi' )
				),
			];

			$links = array_merge( $newLinks, $links );
		}

		return $links;
	}

	function save_post( $post_id, $post, $update ) {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-post_' . $post_id ) ) {
			return false;
		}

		$status = get_post_status( $post_id );
		if ( ! in_array( $status, [ 'publish' ] ) ) {
			return;
		}
		$url = get_permalink( $post_id );
		if ( ! $url ) {
			return;
		}
		$submitted = get_post_meta( $post_id, '_wp_sbi_submitted', true );
		if ( $submitted ) {
			return;
		}
		$title = get_the_title( $post_id );
		$name  = get_bloginfo( 'name' );
		$name  .= ' - ' . $title;
		if ( self::submit( [ $url ], $name ) ) {
			update_post_meta( $post_id, '_wp_sbi_submitted', true );
		}
	}

	static function submit( array $urls, $name = null ) {
		$option_name = Admin_Menus::OPTION_NAME_SINBYTE;
		$options     = get_option( $option_name );
		if ( empty( $options['api_key'] ) ) {
			return;
		}
		$api_key = $options['api_key'];
		if ( ! $name ) {
			$name = get_bloginfo( 'name' );
		}
		$dripfeed = 1;
		$method   = 'tools';
		if ( ! empty( $options['method'] ) && in_array( $options['method'], [ 'tools', 'money_site' ] ) ) {
			$method = $options['method'];
		}
		$urls         = array_values( $urls ); // reset key after unset
		$headers      = [
			'Content-Type' => 'application/json'
		];
		$data         = [
			'apikey'   => $api_key,
			'name'     => $name,
			'dripfeed' => $dripfeed,
			'method'   => $method,
			'urls'     => $urls
		];
		$data         = json_encode( $data );
		$api_endpoint = self::API_ENDPOINT;
		$rs           = wp_remote_post( $api_endpoint, [
			'body'    => $data,
			'headers' => $headers
		] );
		if ( ! is_wp_error( $rs ) ) {
			$body = wp_remote_retrieve_body( $rs );
			$json = json_decode( $body );
			if ( $json && ! empty( $json->status ) && $json->status == 'ok' ) {

				self::log( $urls, 'ok', $name );

				return true;
			}
		}
		self::log( $urls, 'error', $name );

		return false;

	}

	static function console_handle() {
		if (
			empty( $_POST['wp_sbi_console'] )
			|| empty( $_POST['wp_sbi_console_nonce'] )
		) {
			return;
		}
		$notice         = '<div class="notice notice-error notice-inline is-dismissible"><p>%s</p></div>';
		$notice_success = '<div class="notice notice-success notice-inline is-dismissible"><p>%s</p></div>';
		if ( ! wp_verify_nonce( $_POST['wp_sbi_console_nonce'], 'wp_sbi_console' ) || $_POST['wp_sbi_console'] !== 'console' ) {
			printf( $notice, __( 'Security check not passed!', 'wp-sbi' ) );

			return;
		}
		if ( empty( $_POST['urls'] ) ) {
			printf( $notice, __( 'No valid URL', 'wp-sbi' ) );

			return;
		}
		$urls = trim( $_POST['urls'] );
		$urls = explode( "\n", $urls );
		$urls = array_map( function ( $url ) {
			$url = trim( $url );
			if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
				return $url;
			}

			return false;
		}, $urls );
		$urls = array_unique( (array) array_filter( $urls, 'strlen' ) );
		if ( count( $urls ) < 1 ) {
			printf( $notice, __( 'No valid URL', 'wp-sbi' ) );

			return;
		}
		$submit = self::submit( $urls );
		if ( $submit ) {
			printf( $notice_success, __( 'Success!', 'wp-sbi' ) );
		} else {
			printf( $notice, __( 'Error!', 'wp-sbi' ) );
		}

		return;
	}

	static function log( array $urls, $status, $name = null ) {
		$logs = get_option( Admin_Menus::OPTION_NAME_LOG );
		if ( ! is_array( $logs ) ) {
			$logs = [];
		}
		$current_time_gmt = current_time( 'timestamp', true );
		$log              = [
			'time'   => $current_time_gmt,
			'name'   => $name,
			'urls'   => $urls,
			'status' => $status
		];
		array_unshift( $logs, $log ); // add log to begin logs
		$limit = self::LOG_LIMIT;
		if ( count( $logs ) > $limit ) {
			$logs = array_slice( $logs, 0, $limit );
		}
		update_option( Admin_Menus::OPTION_NAME_LOG, $logs );
	}
}