<?php
/**
 * Splendid Abstract Plugin
 *
 * @package Splendid\SalesBoosterVendor
 */

namespace Splendid\SalesBoosterVendor;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base plugin with most basic functionalities used by every Splendid plugin. Based on WP Desk Plugin Builder code.
 *
 * @package Splendid\SalesBoosterVendor
 */
abstract class AbstractPlugin {
	/**
	 * Most info about plugin internals.
	 *
	 * @var array
	 */
	protected $plugin_info;

	/**
	 * Unique string for this plugin in [a-z_]+ format.
	 *
	 * @var string
	 */
	protected $plugin_namespace;

	/**
	 * Absolute URL to the plugin dir.
	 *
	 * @var string
	 */
	protected $plugin_url;

	/**
	 * Absolute URL to the plugin docs.
	 *
	 * @var string
	 */
	protected $docs_url;

	/**
	 * Absolute URL to the plugin settings url.
	 *
	 * @var string
	 */
	protected $settings_url;

	/**
	 * Support URL.
	 *
	 * @var string
	 */
	protected $support_url;

	/**
	 * AbstractPlugin constructor.
	 *
	 * @param array $plugin_info
	 */
	public function __construct( $plugin_info ) {
		$this->plugin_info = $plugin_info;
		$this->plugin_namespace = \strtolower( $plugin_info['plugin_dir'] );
		$this->plugin_url = $this->plugin_info['plugin_url'];
	}

	/**
	 * Initializes plugin external state.
	 *
	 * The plugin internal state is initialized in the constructor and the plugin should be internally consistent after creation.
	 * The external state includes hooks execution, communication with other plugins, integration with WC etc.
	 *
	 * @return void
	 */
	public function init() {
		$this->hooks();
	}

	/**
	 * Returns absolute path to the plugin dir.
	 *
	 * @return string
	 */
	public function get_plugin_file_path() {
		return $this->plugin_info['plugin_file_name'];
	}

	/**
	 * Returns plugin text domain.
	 *
	 * @return string
	 */
	public function get_text_domain() {
		return $this->plugin_info['text_domain'];
	}

	/**
	 * Returns unique string for plugin in [a-z_]+ format. Can be used as plugin id in various places like plugin slug etc.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->plugin_namespace;
	}

	/**
	 * Returns plugin absolute URL.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return \esc_url( \trailingslashit( $this->plugin_url ) );
	}

	/**
	 * Returns plugin absolute URL to dir with front end assets.
	 *
	 * @return string
	 */
	public function get_plugin_assets_url() {
		return \esc_url( \trailingslashit( $this->get_plugin_url() . 'assets' ) );
	}

	/**
	 * Integrate with WordPress and with other plugins using action/filter system.
	 *
	 * @return void
	 */
	protected function hooks() {
		\add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		\add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		\add_action( 'plugins_loaded', array( $this, 'load_plugin_text_domain' ) );
		\add_filter( 'plugin_action_links_' . \plugin_basename( $this->get_plugin_file_path() ), array( $this, 'links_filter' ) );
	}

	/**
	 * Initialize plugin test domain. This is a hook function. Do not execute directly.
	 *
	 * @return void
	 */
	public function load_plugin_text_domain() {
		\load_plugin_textdomain( $this->get_text_domain(), \false, $this->get_namespace() . '/lang/' );
	}

	/**
	 * Append JS scripts in the WordPress admin panel. This is a hook function. Do not execute directly.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
	}

	/**
	 * Append JS scripts in WordPress. This is a hook function. Do not execute directly.
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
	}

	/**
	 * Initialize plugin admin links. This is a hook function. Do not execute directly.
	 *
	 * @param string[] $links
	 *
	 * @return string[]
	 */
	public function links_filter( $links ) {
		$support_link = 'https://wordpress.org/support/plugin/' . $this->get_text_domain() . '/';

		if ( $this->support_url ) {
			$support_link = $this->support_url;
		}

		$plugin_links = array( '<a target="_blank" href="' . $support_link . '">' . \__( 'Support', 'splendid-sales-booster' ) . '</a>' );

		$links = \array_merge( $plugin_links, $links );

		if ( $this->docs_url ) {
			$plugin_links = array( '<a target="_blank" href="' . $this->docs_url . '">' . \__( 'Docs', 'splendid-sales-booster' ) . '</a>' );

			$links = \array_merge( $plugin_links, $links );
		}

		if ( $this->settings_url ) {
			$plugin_links = array( '<a href="' . $this->settings_url . '">' . \__( 'Settings', 'splendid-sales-booster' ) . '</a>' );

			$links = \array_merge( $plugin_links, $links );
		}

		return $links;
	}
}
