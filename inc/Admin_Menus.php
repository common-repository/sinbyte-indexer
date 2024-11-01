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

class Admin_Menus {
	const PAGE = Main::PLUGIN_SLUG;
	const OPTION_NAME = Main::OPTION_NAME;
	const OPTION_NAME_SINBYTE = Main::OPTION_NAME . '-sinbyte';
	const OPTION_NAME_LOG = Main::OPTION_NAME . '-log';
	const ROLE = 'manage_options';
	var $menu_title;
	var $page_title;

	function __construct() {
		$this->page_title = Main::PLUGIN_NAME;
		$this->menu_title = 'Sinbyte Indexer';

		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_options' ] );
	}

	function add_page() {
		add_menu_page(
			$this->page_title,
			$this->menu_title,
			self::ROLE,
			self::PAGE,
			[ $this, 'display_page' ]
		);
	}

	function display_page() {
		echo '<div class="wrap">';
		printf( '<h2>%s</h2>', $this->page_title );
		settings_errors();
		$this->tabs();
		$active_tab = 'general';
		if ( ! empty( $_GET['tab'] ) ) {
			$active_tab = trim( $_GET['tab'] );
		}
		switch ( $active_tab ) {
			default:
			case 'general':
				echo '<form method="post" action="' . admin_url( 'options.php' ) . '">';
				settings_fields( self::OPTION_NAME );
				do_settings_sections( self::OPTION_NAME );
				submit_button();
				echo '</form>';
				break;
			case 'sinbyte':
				echo '<form method="post" action="' . admin_url( 'options.php' ) . '">';
				settings_fields( self::OPTION_NAME_SINBYTE );
				do_settings_sections( self::OPTION_NAME_SINBYTE );
				submit_button();
				echo '</form>';
				break;
			case 'console':
				printf( '<h2>%s</h2>', __( 'Console', 'wp-sbi' ) );
				echo '<form method="post">';
				Main::console_handle();
				echo '<table class="form-table">';
				echo '<tbody>';
				echo '<tr>';
				echo '<th>';
				printf( '<label for="urls">%s</label>', __( 'Instant Indexing', 'wp-sbi' ) );
				echo '</th>';
				echo '<td>';
				echo '<textarea name="urls" rows="10" cols="50" class="large-text code"></textarea>';
				printf( '<p class="description">%s</p>', __( 'URLs (one per line)', 'wp-sbi' ) );
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<input type="hidden" name="wp_sbi_console" value="console" />';
				echo '<input type="hidden" name="wp_sbi_console_nonce" value="' . wp_create_nonce( 'wp_sbi_console' ) . '" />';
				submit_button( __( 'Send to API', 'wp-sbi' ) );
				echo '</form>';
				echo '</div>';
				break;
			case 'log':
				printf( '<h2>%s</h2>', __( 'Log', 'wp-sbi' ) );
				$logs = get_option( self::OPTION_NAME_LOG );
				if ( ! is_array( $logs ) ) {
					printf( '<p>%s</p>', __( 'Not found', 'wp-sbi' ) );
					break;
				}
				echo '<table class="wp-sbi-log">';

				echo '<tr>';
				printf( '<th>%s</th>', __( 'Time', 'wp-sbi' ) );
				printf( '<th>%s</th>', __( 'Name', 'wp-sbi' ) );
				printf( '<th>%s</th>', __( 'URLs', 'wp-sbi' ) );
				printf( '<th>%s</th>', __( 'Status', 'wp-sbi' ) );
				echo '</tr>';

				foreach ( $logs as $log ) {
					echo '<tr>';
					printf( '<td>%s</td>', esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $log['time'] ) ) ) );
					printf( '<td>%s</td>', esc_html( $log['name'] ) );
					printf( '<td>%s</td>', nl2br( esc_html( implode( "\n", $log['urls'] ) ) ) );
					printf( '<td>%s</td>', esc_html( ucfirst( $log['status'] ) ) );
					echo '</tr>';
				}

				echo '</table>';
				break;
		}
		echo '</div>';
	}

	function tabs() {
		$tabs       = [
			'general' => __( 'General options', 'wp-sbi' ),
			'sinbyte' => __( 'Sinbyte Indexer', 'wp-sbi' ),
			'console' => __( 'Console', 'wp-sbi' ),
			'log'     => __( 'Log', 'wp-sbi' ),
		];
		$active_tab = 'general';
		if ( ! empty( $_GET['tab'] ) ) {
			$tab = trim( $_GET['tab'] );
			if ( in_array( $tab, array_keys( $tabs ) ) ) {
				$active_tab = $tab;
			}
		}
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $slug => $title ) {
			printf( '<a href="%s" class="nav-tab %s">%s</a>',
				add_query_arg(
					[
						'page' => self::PAGE,
						'tab'  => $slug
					],
					admin_url( 'admin.php' )
				),
				( $active_tab == $slug ) ? 'nav-tab-active' : null,
				esc_html( $title )
			);
		}
		echo '</h2>';
	}

	function register_options() {
		// general
		$section_id = self::PAGE . '_general_section';
		add_settings_section(
			$section_id,
			sprintf( '%s', __( 'General Options', 'wp-sbi' ) ),
			[ $this, 'display_section' ],
			self::OPTION_NAME
		);
		add_settings_field(
			'enable',
			__( 'Enable', 'wp-sbi' ),
			[ $this, 'render_fields' ],
			self::OPTION_NAME,
			$section_id,
			[
				'label_for'   => 'enable',
				'type'        => 'switch',
				'option_name' => self::OPTION_NAME
			]
		);


		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			[ $this, 'validate_options' ]
		);

		// sinbyte
		$section_id = self::PAGE . '_sinbyte_section';
		add_settings_section(
			$section_id,
			sprintf( '%s', __( 'Sinbyte.Com', 'wp-sbi' ) ),
			[ $this, 'display_section' ],
			self::OPTION_NAME_SINBYTE
		);
		add_settings_field(
			'api_key',
			__( 'API key', 'wp-sbi' ),
			[ $this, 'render_fields' ],
			self::OPTION_NAME_SINBYTE,
			$section_id,
			[
				'label_for'   => 'api_key',
				'type'        => 'text',
				'req'         => true,
				'option_name' => self::OPTION_NAME_SINBYTE
			]
		);
		add_settings_field(
			'method',
			__( 'Method', 'wp-sbi' ),
			[ $this, 'render_fields' ],
			self::OPTION_NAME_SINBYTE,
			$section_id,
			[
				'label_for'   => 'method',
				'type'        => 'radio',
				'data'        => [
					'tools'     => __( 'Link website: do not add email API into Google Search Console => indexing depends on the domain\'s trust', 'wp-sbi' ),
					'money_site' => __( 'Link website: must add email API into Google Search Console => indexed 70-80% within 1-3 days. -- Help: in Google Search Console, navigate to Settings / Users and permissions / Add user / Enter the email API address: sinbyte@sinbyte.iam.gserviceaccount.com and select the permission as OWNER (required).', 'wp-sbi' ),
				],
				'req'         => true,
				'option_name' => self::OPTION_NAME_SINBYTE
			]
		);


		register_setting(
			self::OPTION_NAME_SINBYTE,
			self::OPTION_NAME_SINBYTE,
			[ $this, 'validate_options' ]
		);
	}

	function display_section( $args ) {
		switch ( $args['id'] ) {
			case 'wp-sbi_sinbyte_section':
				printf( '<p>%s</p>', __( 'Sinbyte Indexer offers a unique solution to indexing and pinging your backlinks. 100% Google safe, and guaranteed results.', 'wp-sbi' ) );
				printf( '<p>%s</p>', __( 'You must have a Basic or above plan to use the API. Once logged in, you cand find your API key under the Quick Submit Links.', 'wp-sbi' ) );
				printf( '<p><a href="%s" target="_blank">%s</a></p>', esc_url( 'https://app.sinbyte.com/accounts/register/' ), __( 'Register for an account at sinbyte.com', 'wp-sbi' ) );
				break;
		}
	}

	function validate_options( $fields ) {
		if ( ! empty( $fields['api_key'] ) ) {
			$api_key = $fields['api_key'];
			$api_key = str_replace( '_', '', $api_key );
			$api_key = preg_replace( '/[^A-z0-9]+/', '', $api_key );
			if ( ! empty( $api_key ) ) {
				$fields['api_key'] = $api_key;
			} else {
				unset( $fields['api_key'] );
			}
		}

		return $fields;
	}

	function render_fields( array $args ) {
		$id          = $args['label_for'];
		$option_name = self::OPTION_NAME;
		if ( ! empty( $args['option_name'] ) ) {
			$option_name = $args['option_name'];
		}
		$options = get_option( $option_name );
		$value   = $options[ $id ] ?? null;
		$req     = ( ! empty( $args['req'] ) && $args['req'] == true ) ? ' required ' : '';
		switch ( $args['type'] ) {
			case 'none':
			default:
				break;
			case 'radio':
				$data = $args['data'];
				foreach ( $data as $val => $txt ) {
					echo '<label><input type="radio" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="' . esc_attr( $val ) . '" ' . checked( $value, $val, false ) . ' />' . esc_html( $txt ) . '</label>';
				}
				break;
			case 'text':
				echo '<input type="text" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" value="' . esc_attr( $value ) . '" size="50" ' . esc_html( $req ) . '/>';
				break;
			case 'switch':
				echo '<input type="checkbox" name="' . esc_attr( $option_name . '[' . $id . ']' ) . '" id="' . esc_attr( $option_name . '_' . $id ) . '" class="switch-input" value="yes" ' . checked( $value, 'yes', false ) . ' ' . esc_html( $req ) . '/>';
				echo '<label for="' . esc_attr( $option_name . '_' . $id ) . '" class="switch"></label>';
				break;
		}
	}
}