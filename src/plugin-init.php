<?php
/**
 * Splendid Plugin Init
 *
 * @package Splendid\SalesBoosterVendor
 */

namespace Splendid\SalesBoosterVendor;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

// Code in PHP >= 5.3 but understandable by older parsers
if ( \PHP_VERSION_ID >= 50300 ) {
	require_once $plugin_dir . '/vendor/autoload.php';

	$plugin = null;

	$plugin_info = array(
		'plugin_file_name' => \plugin_basename( $plugin_file ),
		'plugin_dir'       => $plugin_dir,
		'plugin_url'       => \plugins_url( \dirname( \plugin_basename( $plugin_file ) ) ),
		'class_name'       => $plugin_class_name,
		'version'          => $plugin_version,
		'plugin_slug'      => $plugin_slug,
		'plugin_name'      => $plugin_name,
		'release_date'     => $plugin_release_timestamp,
		'text_domain'      => $plugin_text_domain,
	);

	\load_plugin_textdomain( $plugin_info['text_domain'], \false, \basename( $plugin_info['plugin_dir'] ) . '/lang/' );

	$requirements_errors = array();

	if ( ! \version_compare( \PHP_VERSION, $requirements['php'], '>=' ) ) {
		/* translators: 1: Name of a plugin 2: Requred PHP version */
		$requirements_errors[] = \sprintf( \__( 'The &#8220;%1$s&#8221; plugin cannot run on PHP versions older than %2$s. Please contact your host and ask them to upgrade.', 'splendid-sales-booster' ), \esc_html( $plugin_info['plugin_name'] ), $requirements['php'] );
	}

	if ( ! \version_compare( \get_bloginfo( 'version' ), $requirements['wp'], '>=' ) ) {
		/* translators: 1: Name of a plugin 2: Requred WP version */
		$requirements_errors[] = \sprintf( \__( 'The &#8220;%1$s&#8221; plugin cannot run on WordPress versions older than %2$s. Please update WordPress.', 'splendid-sales-booster' ), \esc_html( $plugin_info['plugin_name'] ), $requirements['wp'] );
	}


	if ( empty( $requirements_errors ) ) {
		$plugin = new $plugin_info['class_name']( $plugin_info );

		\register_activation_hook( $plugin_info['plugin_file_name'], array( $plugin, 'activate' ) );
	}

	\add_action(
		'activated_plugin',
		static function ( $plugin_file, $network_wide = \false ) {
			if ( ! $network_wide ) {
				$option_name = 'plugin_activation_' . $plugin_file;

				$activation_date = \get_option( $option_name, '' );

				if ( '' === $activation_date ) {
					$activation_date = \current_time( 'mysql' );

					\update_option( $option_name, $activation_date );
				}
			}
		}
	);

	\add_action(
		'plugins_loaded',
		static function () use ( $plugin, $requirements, $requirements_errors, $plugin_info ) {
			if ( ! empty( $requirements['wc'] ) ) {
				if ( ! \defined( 'WC_VERSION' ) ) {
					/* translators: 1: Name of a plugin 2: Name of a required plugin */
					$requirements_errors[] = \sprintf( \__( 'The &#8220;%1$s&#8221; plugin cannot run without %2$s active. Please install and activate %2$s plugin.', 'splendid-sales-booster' ), \esc_html( $plugin_info['plugin_name'] ), 'WooCommerce' );
				} elseif ( \version_compare( \WC_VERSION, $requirements['wc'], '<' ) ) {
					/* translators: 1: Name of a plugin 2: Required version of a plugin 3: Name of a required plugin */
					$requirements_errors[] = \sprintf( \__( 'The &#8220;%1$s&#8221; plugin requires at least %2$s version of %3$s to work correctly. Please update it to its latest release.', 'splendid-sales-booster' ), \esc_html( $plugin_info['plugin_name'] ), $requirements['wc'], 'WooCommerce' );
				}
			}

			if ( empty( $requirements_errors ) ) {
				if ( ! $plugin ) {
					$plugin = new $plugin_info['class_name']( $plugin_info );
				}

				$plugin->init();
			} else {
				\add_action(
					'admin_notices',
					static function () use ( $requirements_errors ) {
						foreach ( $requirements_errors as $error ) {
							echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
						}
					}
				);
			}
		},
		-50
	);
} else {
	// phpcs:ignore
	$php52_function = \create_function( '', 'echo \'<div class="error"><p><strong style="color: red;">Splendid plugins cannot run on PHP versions older than 5.3. Please contact your host and ask them to upgrade.</strong></p></div>\';' );

	\add_action( 'admin_notices', $php52_function );
}
