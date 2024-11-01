<?php
/**
 * Plugin main class.
 *
 * @package Splendid\SalesBooster
 */

namespace Splendid\SalesBooster;

use Splendid\SalesBoosterVendor\AbstractPlugin;

/**
 * Main plugin class. The most important flow decisions are made here.
 *
 * @package Splendid\SalesBooster
 */
class Plugin extends AbstractPlugin {
	/**
	 * Name of the Splendid Sales Booster cross-sells custom database relation table.
	 */
	const PLUGIN_DB_TABLE_NAME = 'wcssb_cross_sells';

	/**
	 * Path of the main plugin directory.
	 *
	 * @var string
	 */
	protected $plugin_dir = '';

	/**
	 * Current version of plugin.
	 *
	 * @var string
	 */
	protected $plugin_version = '';

	/**
	 * This stores the IDs of products that just has been added to cart.
	 *
	 * @var array
	 */
	protected $added_cross_sell_product_ids = array();

	/**
	 * This stores the ID of the product that just has been added to cart.
	 *
	 * @var int
	 */
	protected $added_to_cart_product_id = 0;

	/**
	 * This stores enqueued Drawers.
	 *
	 * @var array
	 */
	protected $enqueued_drawers = array();

	/**
	 * Plugin constructor.
	 *
	 * @param array $plugin_info Plugin info.
	 */
	public function __construct( $plugin_info ) {
		parent::__construct( $plugin_info );

		$this->plugin_url       = $this->plugin_info['plugin_url'];
		$this->plugin_namespace = $this->plugin_info['text_domain'];
		$this->plugin_dir       = $this->plugin_info['plugin_dir'];
		$this->plugin_version   = $this->plugin_info['version'];

		$this->docs_url     = esc_url( __( 'https://wordpress.org/plugins/splendid-sales-booster/', 'splendid-sales-booster' ) );
		$this->settings_url = admin_url( 'admin.php?page=wc-settings&tab=products&section=splendid-sales-booster' );
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
		parent::init();

		wp_cache_add_non_persistent_groups( 'wcssb' );

		require_once trailingslashit( $this->plugin_dir ) . 'src/Plugin/wcssb-cross-sells-functions.php';
	}

	/**
	 * Integrate with WordPress and with other plugins using action/filter system.
	 *
	 * @return void
	 */
	public function hooks() {
		parent::hooks();

		// Custom Data Store.
		add_filter( 'woocommerce_data_stores', array( $this, 'register_woocommerce_custom_data_store' ), 10, 1 );

		// Plugin settings.
		add_filter( 'woocommerce_get_sections_products', array( $this, 'register_woocommerce_settings_wcssb_section' ), 10, 1 );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_plugin_settings_fields' ), 10, 2 );

		// Product options.
		add_action( 'woocommerce_product_options_related', array( $this, 'display_product_cross_sell_options' ), 10, 0 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_cross_sells_options' ), 10, 1 );

		// Frontend Product page section.
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_product_cross_sells_section' ), 90, 0 );
		add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html_wcssb_dropdown_variation_attribute_options' ), 99, 2 );

		// WooCommerce REST API endpoint.
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'register_wc_rest_api_section_endpoint' ), 10, 1 );

		// Frontend `Add To Cart` drawer.
		add_action( 'woocommerce_ajax_added_to_cart', array( $this, 'enqueue_add_to_cart_message_fragment' ), 10, 1 );
		add_filter( 'wc_add_to_cart_message_html', array( $this, 'mark_add_to_cart_message_html_for_drawer' ), 99, 2 );
		add_filter( 'woocommerce_add_success', array( $this, 'process_add_to_cart_message_for_drawer' ), 99, 1 );
		add_filter( 'wc_get_template', array( $this, 'custom_add_to_cart_message_template' ), 99, 5 );

		// Custom AJAX product search action.
		add_action( 'wp_ajax_wcssb_json_search_products', array( $this, 'handle_wp_ajax_json_custom_search_products' ), 10, 0 );

		// Adding products to cart.
		add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_add_cross_sells_to_cart' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'store_cart_item_added_with_product_id_via_ajax' ), 20, 2 );
		add_filter( 'wc_add_to_cart_message_html', array( $this, 'append_cross_sells_to_added_to_cart_notice' ), 10, 1 );

		// Storing item's added with product IDs.
		add_action( 'woocommerce_remove_cart_item', array( $this, 'update_cart_items_added_with_product_ids' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_item_added_with_product_ids' ), 10, 4 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_itemmeta_added_with_product_id' ), 10, 1 );

		// Delete all product's Splendid Sales Booster cross-sell relations on product post delete.
		add_action( 'delete_post', array( $this, 'delete_product_relations' ), 10, 1 );
		add_action( 'woocommerce_delete_product', array( $this, 'delete_product_relations' ), 10, 1 );
		add_action( 'woocommerce_delete_product_variation', array( $this, 'delete_product_relations' ), 10, 1 );
		add_action( 'wp_trash_post', array( $this, 'delete_subscription_variation_relations' ), 10, 1 );

		// WooCommerce tools.
		add_filter( 'woocommerce_debug_tools', array( $this, 'register_cross_sell_relations_delete_tool' ), 20, 1 );
	}

	/**
	 * Plugin activated in WordPress.
	 *
	 * @return void
	 */
	public function activate() {
		$this->create_db_table();

		// Set default Splendid Sales Booster cross-sells section location.
		if ( false === get_option( 'wcssb_section_location' ) ) {
			add_option( 'wcssb_section_location', 'drawer' );
		}
	}

	/**
	 * Create custom DB table on plugin activation.
	 *
	 * @return void
	 */
	private function create_db_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}" . self::PLUGIN_DB_TABLE_NAME . " (
				relation_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				parent_product_id bigint(20) UNSIGNED NOT NULL,
				product_id bigint(20) UNSIGNED NOT NULL,
				product_discount decimal(10,5) NOT NULL DEFAULT '0',
				product_discount_type varchar(100) NOT NULL DEFAULT 'none',
				product_cta longtext,
				product_order bigint(20) UNSIGNED NOT NULL,
				PRIMARY KEY (relation_id),
				UNIQUE KEY relation (parent_product_id, product_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		add_option( 'wcssb_db_version', $this->plugin_version );
	}

	/**
	 * Append JS scripts in the WordPress admin panel.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( ! in_array( $screen_id, array( 'product', 'product_page_splendid-sales-booster' ), true ) ) {
			return;
		}

		wp_register_style( 'wcssb-product', esc_url( $this->get_plugin_assets_url() . 'css/admin/product.min.css' ), array(), $this->plugin_version );

		wp_register_script( 'wcssb-product', esc_url( $this->get_plugin_assets_url() . 'js/admin/product.min.js' ), array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), $this->plugin_version, true );
	}

	/**
	 * Append JS scripts in WordPress.
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		wp_register_script( 'wcssb-add-to-cart-variation', esc_url( $this->get_plugin_assets_url() . 'js/front/add-to-cart-variation.min.js' ), array( 'jquery' ), $this->plugin_version, true );

		wp_localize_script(
			'wcssb-add-to-cart-variation',
			'wcssbAddToCartVariationSettings',
			array(
				'i18n_make_a_selection_text' => __( 'Please select some product options before adding this product to your cart.', 'splendid-sales-booster' ),
			)
		);

		if ( \wcssb_is_section_location_enabled( 'product_page' ) ) {
			if ( is_product() ) {
				wp_enqueue_script( 'wcssb-add-to-cart-variation' );

				wp_enqueue_style( 'wcssb-section', esc_url( $this->get_plugin_assets_url() . 'css/front/section.min.css' ), array(), $this->plugin_version );
			}
		}

		if ( \wcssb_is_section_location_enabled( 'drawer' ) ) {
			if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) {
				$this->enqueue_drawer( 'add_to_cart' );
			}
		}
	}

	/**
	 * Register custom Woocommerce Data Store for Splendid Sales Booster cross-sells.
	 *
	 * @param array $stores WooCommerce Data Stores.
	 *
	 * @return array
	 */
	public function register_woocommerce_custom_data_store( $stores ) {
		$stores['wcssb-cross-sell'] = '\Splendid\SalesBooster\CrossSellDataStore';

		return $stores;
	}

	/**
	 * Register WooCommerce Settings Product tab WCSSB section.
	 *
	 * @param array $sections WooCommerce settings sections.
	 *
	 * @return array
	 */
	public function register_woocommerce_settings_wcssb_section( $sections ) {
		$sections['splendid-sales-booster'] = __( 'Splendid Sales Booster', 'splendid-sales-booster' );

		return $sections;
	}

	/**
	 * Add setting fields to WCSSB section of the WooCommerce Settings.
	 *
	 * @param array  $settings WooCommerce settings.
	 * @param string $current_section Current settings section.
	 *
	 * @return array
	 */
	public function add_plugin_settings_fields( $settings = array(), $current_section = '' ) {
		if ( 'splendid-sales-booster' !== $current_section ) {
			return $settings;
		}

		// Make sure that we have Splendid Sales Booster cross-sells section location defined.
		$section_location = \wcssb_get_section_location();

		$settings = array(
			array(
				'id'   => 'wcssb_settings',
				'name' => __( 'Splendid Sales Booster cross-sells', 'splendid-sales-booster' ),
				'type' => 'title',
				// translators: 1: Link to plugin Docs 2: Link label.
				'desc' => sprintf( '<a href="%s" target="_blank">%s</a>', $this->docs_url, __( 'View plugin documentation', 'splendid-sales-booster' ) . ' &rarr;' ),
			),
			array(
				'title'    => __( 'Display cross-sells section', 'splendid-sales-booster' ),
				'id'       => 'wcssb_section_location',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width:300px;',
				'default'  => 'drawer',
				'desc'     => sprintf(
				// translators: %s: Link to plugin Docs.
					__( 'The location that the Splendid Sales Booster cross-sells section should be displayed. Learn more in <a href="%s" target="_blank">plugin documentation</a>.', 'splendid-sales-booster' ),
					$this->docs_url . '#faq'
				),
				'options'  => array(
					'drawer'       => __( 'In a drawer', 'splendid-sales-booster' ),
					'product_page' => __( 'On product page', 'splendid-sales-booster' ),
				),
			),
			array(
				'id'          => 'wcssb_section_default_title',
				'name'        => __( 'Cross-sells section default title', 'splendid-sales-booster' ),
				'type'        => 'text',
				'desc'        => __( 'This will be used on all product pages by default. You can also set it per each product individually.', 'splendid-sales-booster' ),
				'default'     => \wcssb_get_default_wcq_section_title_value(),
				/* translators: %s: default section title */
				'placeholder' => sprintf( __( 'e.g. %s&hellip;', 'splendid-sales-booster' ), \wcssb_get_default_wcq_section_title_value() ),
			),
			array(
				'id'      => 'wcssb_show_images',
				'name'    => __( 'Product images', 'splendid-sales-booster' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Show product images', 'splendid-sales-booster' ),
				'default' => 'yes',
			),
			array(
				'id'   => 'wcssb_settings',
				'type' => 'sectionend',
			),
		);

		return $settings;
	}

	/**
	 * Display Splendid Sales Booster cross-sells options in product's Related tab.
	 *
	 * @return void
	 */
	public function display_product_cross_sell_options() {
		global $product_object;

		if ( ! in_array( $product_object->get_type(), array( 'simple', 'variable', 'subscription', 'variable-subscription' ), true ) ) {
			return;
		}

		$cross_sell_products = array();

		$cross_sells = \wcssb_get_cross_sells_by_parent_product_id( $product_object->get_id() );

		if ( false !== $cross_sells && ! empty( $cross_sells ) ) {
			foreach ( (array) $cross_sells as $cross_sell ) {
				$cross_sell_product = wc_get_product( $cross_sell->get_product_id() );
				if ( ! $cross_sell_product ) {
					continue;
				}

				$cross_sell_products[ $product_object->get_id() ][] = array(
					'row_key'               => $cross_sell->get_id(),
					'parent_key'            => $product_object->get_id(),
					'product_id'            => $cross_sell->get_product_id(),
					'product_name'          => wcssb_format_product_name( $cross_sell_product->get_formatted_name(), $cross_sell_product ),
					'product_cta'           => $cross_sell->get_product_cta(),
				);
			}
		}

		wp_localize_script(
			'wcssb-product',
			'wcssbProductCrossSells',
			array(
				'products' => $cross_sell_products,
				'strings'  => array(
					'i18n_remove_product_row' => __( 'Are you sure you want to remove this product from Splendid Sales Booster cross-sells?', 'splendid-sales-booster' ),
				),
			)
		);

		wp_enqueue_script( 'wcssb-product' );

		wp_enqueue_style( 'wcssb-product' );

		include trailingslashit( $this->plugin_dir ) . 'views/admin/product-options.php';
		include trailingslashit( $this->plugin_dir ) . 'views/admin/tmpl-wcssb-product-row.php';
	}

	/**
	 * Handle single product Splendid Sales Booster cross-sells options save action.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return void
	 */
	public function save_product_cross_sells_options( $product_id = 0 ) {
		if ( isset( $_POST['woocommerce_meta_nonce'] ) ) {
			// phpcs:ignore
			$nonce = strval( wc_clean( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) );
			if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) {
				return;
			}
		} else {
			return;
		}

		if ( ! isset( $_POST['_wcssb_section_title'] ) && ! isset( $_POST['_wcssb_products'] ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		// Save WCSSB section title.
		if ( isset( $_POST['_wcssb_section_title'] ) ) {

			// phpcs:ignore
			$section_title = strval( wc_clean( wp_unslash( $_POST['_wcssb_section_title'] ) ) );

			$product->update_meta_data( '_wcssb_section_title', $section_title );
			$product->save();

		}

		// Save WCSSB products.
		$this->save_cross_sell_products_form( $product_id );
	}

	/**
	 * Save Splendid Sales Booster cross-sells from $_POST array.
	 *
	 * @param int|\WP_Error $parent_id Parent ID to save products for.
	 * @param int           $parent_key Parent key to get the products from.
	 *
	 * @return void
	 */
	public function save_cross_sell_products_form( $parent_id = 0, $parent_key = 0 ) {
		if ( ! $parent_id || is_wp_error( $parent_id ) ) {
			return;
		}

		if ( ! $parent_key ) {
			$parent_key = $parent_id;
		}

		$valid_relation_ids = array();
		// phpcs:ignore
		if ( isset( $_POST['_wcssb_products'][ $parent_key ] ) ) {

			// phpcs:ignore
			$changes = wc_clean( wp_unslash( $_POST['_wcssb_products'][ $parent_key ] ) );
			if ( is_array( $changes ) && ! empty( $changes ) ) {

				$cross_sell_i = 0;
				foreach ( $changes as $cross_sell_row_key => $cross_sell ) {

					if ( isset( $cross_sell['product_id'] ) && $cross_sell['product_id'] && wc_get_product( $cross_sell['product_id'] ) ) {

						$cross_sell_i++;

						if ( isset( $cross_sell['is_new_row'] ) && 1 === (int) $cross_sell['is_new_row'] ) {
							$relation = new CrossSellRelation();
						} else {
							$relation = new CrossSellRelation( (int) $cross_sell_row_key );
						}

						$relation->set_product_id( (int) $cross_sell['product_id'] );
						$relation->set_parent_id( (int) $parent_id );

						$relation->set_product_cta( $cross_sell['product_cta'] );
						$relation->set_product_order( $cross_sell_i );

						$relation->save();

						$valid_relation_ids[] = $relation->get_id();

					}
				}
			}
		}

		// Remove all previous Splendid Sales Booster cross-sell relations of this parent product (now deleted or invalid).
		\wcssb_delete_cross_sells_by_post_id( $parent_id, 'parent', $valid_relation_ids );
	}

	/**
	 * Display frontend product page Splendid Sales Booster cross-sells section.
	 *
	 * @return void
	 */
	public function display_product_cross_sells_section() {
		if ( ! \wcssb_is_section_location_enabled( 'product_page' ) ) {
			return;
		}

		global $product;
		if ( ! $product ) {
			return;
		}

		$cross_sell_products = \wcssb_get_product_cross_sell_products( $product );

		if ( empty( $cross_sell_products ) ) {
			return;
		}

		$cross_sells_section_title = \wcssb_get_product_section_title( $product->get_id() );

		include trailingslashit( $this->plugin_dir ) . 'views/front/single-product-section.php';
	}

	/**
	 * Register WooCommerce REST API Product's Splendid Sales Booster cross-sells section endpoint.
	 *
	 * @param array $controllers List of registered controllers.
	 *
	 * @return array Registered controllers.
	 */
	public function register_wc_rest_api_section_endpoint( $controllers = array() ) {
		require_once trailingslashit( $this->plugin_dir ) . 'src/Plugin/CrossSellRESTSectionController.php';

		$controllers['wc/v3']['wcssb-section'] = 'Splendid\SalesBooster\CrossSellRestSectionController';

		return $controllers;
	}

	/**
	 * Enqueue a `Add to cart` message HTML for the Product that just has been added to cart to WooCommerce AJAX cart fragments if the `drawer` section location is enabled.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return void
	 */
	public function enqueue_add_to_cart_message_fragment( $product_id = 0 ) {
		if ( ! \wcssb_is_section_location_enabled( 'drawer' ) ) {
			return;
		}

		if ( isset( $_REQUEST['wcssbAddingWithProductId'] ) ) {
			return;
		}

		$this->added_to_cart_product_id = $product_id;

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'include_add_to_cart_message_fragment' ), 10, 1 );
	}

	/**
	 * Include a `Add to cart` message HTML for the Product that just has been added to cart to WooCommerce AJAX cart fragments if the `drawer` section location is enabled.
	 *
	 * @param array $fragments Cart fragments.
	 *
	 * @return array
	 */
	public function include_add_to_cart_message_fragment( $fragments = array() ) {
		remove_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'include_add_to_cart_message_fragment' ), 10 );

		if ( ! \wcssb_is_section_location_enabled( 'drawer' ) ) {
			return $fragments;
		}

		if ( ! $this->added_to_cart_product_id ) {
			return $fragments;
		}

		$message = wc_add_to_cart_message( $this->added_to_cart_product_id, false, true );

		$message = apply_filters( 'woocommerce_add_success', $message );

		$fragments['.js-wcssb-ajax-added-to-cart'] = $message;

		return $fragments;
	}

	/**
	 * Mark a `Add to cart` message HTML with a Product ID if the `drawer` section location is enabled.
	 *
	 * @param string $message  Message HTML.
	 * @param array  $products Product ID list.
	 *
	 * @return string
	 */
	public function mark_add_to_cart_message_html_for_drawer( $message = '', $products = array() ) {
		if ( ! \wcssb_is_section_location_enabled( 'drawer' ) ) {
			return $message;
		}

		// Determine Product IDs.
		$added_product_id = '';

		if ( ! empty( $products ) ) {
			foreach ( $products as $product_id => $qty ) {
				$added_product_id = $product_id;

				break;
			}
		}

		$message .= '<span data-wcssb-added-product-id="' . esc_attr( $added_product_id ) . '"></span>';

		return $message;
	}

	/**
	 * Process a `Add to cart` message HTML if the `drawer` section location is enabled.
	 *
	 * @param string $message Message HTML.
	 *
	 * @return string
	 */
	public function process_add_to_cart_message_for_drawer( $message = '' ) {
		if ( ! \wcssb_is_section_location_enabled( 'drawer' ) ) {
			return $message;
		}

		preg_match( '/data-wcssb-added-product-id="([0-9]+)"/', $message, $matches );

		if ( ! isset( $matches[1] ) ) {
			return $message;
		}

		preg_match( '/.*(\<a[^\>]*(button wc\-forward)[^\>]*\>.*\<\/a\>).*/', $message, $matches );

		if ( isset( $matches[2] ) ) {
			$button = $matches[1];

			$message = str_replace( $button, '', $message );

			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				$wp_button_class = '';

				if ( function_exists( 'wc_wp_theme_get_element_class_name' ) ) {
					$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? wc_wp_theme_get_element_class_name( 'button' ) : '';
				}

				// Remove `Countinue shopping` button class.
				$button = str_replace( 'wc-forward', '', $button );

				if ( '' !== $wp_button_class ) {
					$button = str_replace( $wp_button_class, '', $button );
				}

				// Prepend `View cart` button.
				$button = sprintf( '<a href="%s" tabindex="1" class="button wc-forward %s alt js-wcssb-forward">%s</a>', esc_url( wc_get_cart_url() ), esc_attr( $wp_button_class ), esc_html__( 'View cart', 'woocommerce' ) ) . $button;
			} else {
				// Add `View cart` button custom class.
				$button = str_replace( $matches[2], $matches[2] . ' alt js-wcssb-forward', $button );
			}

			// Wrap message text.
			$message = '<h3>' . trim( $message ) . '</h3>' . $button;
		}

		// Wrap the whole message.
		$message = '<div class="js-wcssb-add-to-cart-message wcssb-add-to-cart-message" style="width: 0; height: 0; overflow: hidden;">' . $message . '</div>';

		return $message;
	}

	/**
	 * Use custom WooCommerce `Add to cart` message notices template if the `drawer` section location is enabled.
	 *
	 * @param string $template      Template path.
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments.
	 * @param string $template_path Template path.
	 * @param string $default_path  Default path.
	 *
	 * @return string
	 */
	public function custom_add_to_cart_message_template( $template = '', $template_name = '', $args = array(), $template_path = '', $default_path = '' ) {
		if ( 'notices/success.php' != $template_name ) {
			return $template;
		}

		if ( ! \wcssb_is_section_location_enabled( 'drawer' ) ) {
			return $template;
		}

		if ( ! isset( $args['notices'][0]['notice'] ) ) {
			return $template;
		}

		$message = $args['notices'][0]['notice'];

		preg_match( '/data-wcssb-added-product-id="([0-9]+)"/', $message, $matches );

		if ( ! isset( $matches[1] ) ) {
			return $template;
		}

		$this->added_to_cart_product_id = absint( $matches[1] );

		$this->enqueue_drawer( 'add_to_cart' );

		$template = trailingslashit( $this->plugin_dir ) . 'views/front/add-to-cart-message.php';

		return $template;
	}

	/**
	 * Enqueue Splendid Sales Booster cross-sells drawer.
	 *
	 * @param string $drawer Drawer slug.
	 *
	 * @return void
	 */
	public function enqueue_drawer( $drawer = 'add_to_cart' ) {
		if ( ! $drawer ) {
			return;
		}

		add_action( 'wp_footer', array( $this, 'include_drawers' ), 10 );

		if ( ! in_array( $drawer, $this->enqueued_drawers ) ) {
			$this->enqueued_drawers[] = $drawer;
		}
	}

	/**
	 * Include frontend Splendid Sales Booster cross-sell drawers and enqueue their dependencies.
	 *
	 * @return void
	 */
	public function include_drawers() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( empty( $this->enqueued_drawers ) ) {
			return;
		}

		wp_register_script( 'wcssb-drawers', esc_url( $this->get_plugin_assets_url() . 'js/front/drawers.min.js' ), array( 'jquery', 'wp-util' ), $this->plugin_version, true );

		$drawers_data = array(
			'drawers'  => array(),
			'rest_url' => esc_url_raw( get_rest_url() ),
		);

		if ( in_array( 'add_to_cart', $this->enqueued_drawers ) ) {
			$drawers_data['drawers']['add_to_cart'] = array(
				'wc_ajax_url'             => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'i18n_view_cart'          => esc_attr__( 'View cart', 'woocommerce' ),
				'i18n_continue_shopping'  => esc_attr__( 'Continue shopping', 'woocommerce' ),
				'cart_url'                => apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null ),
				'is_cart'                 => is_cart(),
				'cart_redirect_after_add' => get_option( 'woocommerce_cart_redirect_after_add' ),
			);

			if ( $this->added_to_cart_product_id ) {
				$product = wc_get_product( $this->added_to_cart_product_id );

				if ( $product ) {
					$drawers_data['drawers']['add_to_cart']['section'] = array(
						'id'            => (int) absint( $product->get_id() ),
						'title'         => esc_html( $product->get_title() ),
						'section_title' => esc_html( \wcssb_get_product_section_title( $product->get_id() ) ),
						'products'      => \wcssb_get_product_cross_sell_products( $product ),
					);
				}
			}
		}

		wp_localize_script( 'wcssb-drawers', 'wcssbDrawersSettings', $drawers_data );

		wp_enqueue_script( 'wcssb-drawers' );

		if ( in_array( 'add_to_cart', $this->enqueued_drawers ) ) {
			wp_enqueue_script( 'wcssb-add-to-cart-variation' );

			wp_enqueue_style( 'wcssb-section', esc_url( $this->get_plugin_assets_url() . 'css/front/section.min.css' ), array(), $this->plugin_version );
		}

		wp_enqueue_style( 'wcssb-drawers', esc_url( $this->get_plugin_assets_url() . 'css/front/drawers.min.css' ), array(), $this->plugin_version );

		if ( in_array( 'add_to_cart', $this->enqueued_drawers ) ) {
			include trailingslashit( $this->plugin_dir ) . 'views/front/add-to-cart-drawer.php';

			include trailingslashit( $this->plugin_dir ) . 'views/front/tmpl-wcssb-drawer-product-row.php';
		}

		include trailingslashit( $this->plugin_dir ) . 'views/front/drawer-mask.php';

		$this->enqueued_drawers = array();
	}

	/**
	 * Handle custom AJAX product search action (searching simple products, variable products, variations, simple subscriptions and subscription variations only).
	 *
	 * @return void
	 */
	public function handle_wp_ajax_json_custom_search_products() {
		check_ajax_referer( 'search-products', 'security' );

		$term = '';

		if ( isset( $_GET['term'] ) ) {
			// phpcs:ignore
			$term = strval( wc_clean( wp_unslash( $_GET['term'] ) ) );
		}

		if ( empty( $term ) ) {
			wp_die();
		}

		if ( ! empty( $_GET['limit'] ) ) {
			$limit = absint( $_GET['limit'] );
		} else {
			$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
		}

		$exclude_ids = array();

		if ( isset( $_GET['exclude'] ) && ! empty( $_GET['exclude'] ) ) {

			$exclude = array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) );
			foreach ( $exclude as $exclude_product_id ) {

				$exclude_ids[] = $exclude_product_id;

				// Exclude also product variations.
				$exclude_product = wc_get_product( $exclude_product_id );
				if ( $exclude_product && in_array( $exclude_product->get_type(), array( 'variable', 'variable-subscription' ) ) ) {
					$exclude_ids = array_merge( $exclude_ids, $exclude_product->get_children() );
				}
			}
			$exclude_ids = array_filter( array_unique( $exclude_ids ) );
		}

		$product_args    = array(
			'type'    => array( 'simple', 'variable', 'variation', 'subscription' ),
			'exclude' => $exclude_ids,
			'limit'   => $limit,
			's'       => $term,
		);
		$product_objects = (array) wc_get_products( $product_args );

		$product_objects = array_filter( $product_objects, 'wc_products_array_filter_readable' );
		$products        = array();

		foreach ( $product_objects as $product_object ) {
			$formatted_name = wcssb_format_product_name( $product_object->get_formatted_name(), $product_object );

			$products[ $product_object->get_id() ] = rawurldecode( $formatted_name );
		}

		wp_send_json( apply_filters( 'woocommerce_json_search_found_products', $products ) );
	}

	/**
	 * Handle add-to-cart selected Splendid Sales Booster cross-sell products with parent product action.
	 *
	 * @param string $added_with_cart_item_key Cart item key.
	 * @param int    $added_with_product_id Cart item product ID.
	 *
	 * @return void
	 */
	public function maybe_add_cross_sells_to_cart( $added_with_cart_item_key = '', $added_with_product_id = 0 ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// phpcs:ignore
		$requested_items = isset( $_REQUEST['wcssb-add-to-cart'] ) && is_array( $_REQUEST['wcssb-add-to-cart'] ) ? (array) wp_unslash( $_REQUEST['wcssb-add-to-cart'] ) : array();

		if ( empty( $requested_items ) ) {
			return;
		}

		// Make sure that $added_product_ids is emptied.
		$this->added_cross_sell_product_ids = array();

		// Make sure that Splendid Sales Booster cross-sells will be added to cart only once in single request.
		remove_action( 'woocommerce_add_to_cart', array( $this, 'maybe_add_cross_sells_to_cart' ), 10 );

		$added_product_ids = array();

		foreach ( $requested_items as $cross_sell_product_id ) {
			$cross_sell_product_id = absint( wp_unslash( $cross_sell_product_id ) );

			$adding_to_cart = wc_get_product( $cross_sell_product_id );
			if ( ! $adding_to_cart ) {
				continue;
			}

			$added = false;

			if ( in_array( $adding_to_cart->get_type(), array( 'simple', 'subscription' ) ) ) {

				$added = WC()->cart->add_to_cart( $cross_sell_product_id, 1 );

			} elseif ( in_array( $adding_to_cart->get_type(), array( 'variation', 'subscription_variation' ) ) ) {

				$cross_sell_variation_id = $cross_sell_product_id;

				$cross_sell_variation = wc_get_product( $cross_sell_variation_id );
				if ( $cross_sell_variation ) {
					$cross_sell_product_id           = $cross_sell_variation->get_parent_id();
					$cross_sell_variation_attributes = wc_get_product_variation_attributes( $cross_sell_variation_id );

					$added = WC()->cart->add_to_cart( $cross_sell_product_id, 1, $cross_sell_variation_id, $cross_sell_variation_attributes );
				}
			}

			if ( false !== $added ) {
				$added_product_ids[] = $adding_to_cart->get_id();

				$this->add_cart_item_added_with_product_id( $added, $added_with_product_id );
			}
		}

		if ( ! empty( $added_product_ids ) ) {
			$this->added_cross_sell_product_ids = $added_product_ids;
		}
	}

	/**
	 * Append product's add-to-cart success notice with selected Splendid Sales Booster cross-sell product titles.
	 *
	 * @param string $message Message content.
	 *
	 * @return string
	 */
	public function append_cross_sells_to_added_to_cart_notice( $message = '' ) {
		// phpcs:ignore
		$requested_items = isset( $_REQUEST['wcssb-add-to-cart'] ) && is_array( $_REQUEST['wcssb-add-to-cart'] ) ? (array) wp_unslash( $_REQUEST['wcssb-add-to-cart'] ) : array();

		if ( empty( $requested_items ) ) {
			return $message;
		}

		// Get IDs of products that just has been succesfully added to cart during this request.
		$added_product_ids = $this->added_cross_sell_product_ids;
		if ( empty( $added_product_ids ) ) {
			return $message;
		}

		$cross_sell_items  = array();
		$cross_sell_titles = array();

		foreach ( $added_product_ids as $cross_sell_product_id ) {
			$cross_sell_product = wc_get_product( $cross_sell_product_id );

			if ( $cross_sell_product ) {
				if ( in_array( $cross_sell_product->get_type(), array( 'variation', 'subscription_variation' ), true ) ) {
					$cross_sell_product = wc_get_product( $cross_sell_product->get_parent_id() );
				}

				if ( empty( $cross_sell_items[ $cross_sell_product->get_id() ] ) ) {
					$item_name_in_quotes = sprintf(
						/* translators: %s: product name */
						_x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'splendid-sales-booster' ),
						wp_strip_all_tags( $cross_sell_product->get_title() )
					);

					$cross_sell_items[ $cross_sell_product->get_id() ] = array(
						'title' => apply_filters( 'woocommerce_add_to_cart_item_name_in_quotes', $item_name_in_quotes, $cross_sell_product->get_id() ),
						'qty'   => 0,
					);
				}

				$cross_sell_items[ $cross_sell_product->get_id() ]['qty']++;
			}
		}

		if ( ! empty( $cross_sell_items ) ) {
			foreach ( $cross_sell_items as $cross_sell_product_id => $cross_sell_item ) {
				$cross_sell_titles[] = apply_filters( 'woocommerce_add_to_cart_qty_html', $cross_sell_item['qty'] . ' &times; ', $cross_sell_product_id ) . $cross_sell_item['title'];
			}
		}

		if ( ! empty( $cross_sell_titles ) ) {
			$message = rtrim( $message, ' ' );
			/* translators: %s: products' names */
			$message .= ' (' . sprintf( __( 'Together with %s', 'splendid-sales-booster' ), wc_format_list_of_items( $cross_sell_titles ) ) . ').';
		}

		// We do not no longer need $added_product_ids array contents, so make sure that it is emptied.
		$this->added_cross_sell_product_ids = array();

		return $message;
	}

	/**
	 * Add item's added with product ID to cart item meta.
	 *
	 * @param string|bool $cart_item_key Cart Item key.
	 * @param int         $added_with_product_id Product ID.
	 *
	 * @return void
	 */
	public function add_cart_item_added_with_product_id( $cart_item_key = '', $added_with_product_id = 0 ) {
		if ( ! $cart_item_key || ! $added_with_product_id ) {
			return;
		}

		// Store product ID.
		$added_with_product_ids = array();

		$cart = WC()->cart->get_cart();

		if ( isset( $cart[ $cart_item_key ]['wcssb_added_with_product_ids'] ) ) {
			$added_with_product_ids = (array) $cart[ $cart_item_key ]['wcssb_added_with_product_ids'];
		}
		$added_with_product_ids[] = (int) $added_with_product_id;
		$added_with_product_ids   = array_filter( array_unique( $added_with_product_ids ) );

		// Update cart.
		WC()->cart->cart_contents[ $cart_item_key ]['wcssb_added_with_product_ids'] = $added_with_product_ids;

		WC()->cart->set_session();
	}

	/**
	 * Store item's added with product ID as cart item meta when adding it to Cart via an WC AJAX `add_to_cart` action.
	 *
	 * @param string $cart_item_key Cart Item key.
	 * @param int    $product_id Product ID.
	 *
	 * @return void
	 */
	public function store_cart_item_added_with_product_id_via_ajax( $cart_item_key = '', $product_id = 0 ) {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_REQUEST['wc-ajax'] ) || 'add_to_cart' != $_REQUEST['wc-ajax'] ) {
			return;
		}

		if ( ! isset( $_REQUEST['wcssbAddingWithProductId'] ) ) {
			return;
		}

		$adding_with_product_id = absint( wp_unslash( $_REQUEST['wcssbAddingWithProductId'] ) );

		$adding_with_product = wc_get_product( $adding_with_product_id );
		if ( ! $adding_with_product ) {
			return;
		}

		$this->add_cart_item_added_with_product_id( $cart_item_key, $adding_with_product->get_id() );
	}

	/**
	 * Update cart items `wcssb_added_with_product_ids` meta when removing cart item.
	 *
	 * @param string   $cart_item_key Cart item key.
	 * @param \WC_Cart $cart Cart object.
	 *
	 * @return void
	 */
	public function update_cart_items_added_with_product_ids( $cart_item_key, $cart ) {
		$cart = WC()->cart->get_cart();

		$remove_product_id = (int) $cart[ $cart_item_key ]['product_id'];

		foreach ( $cart as $cart_item_key => $cart_item ) {
			// Remove product ID.
			if ( isset( $cart[ $cart_item_key ]['wcssb_added_with_product_ids'] ) && is_array( $cart[ $cart_item_key ]['wcssb_added_with_product_ids'] ) && in_array( $remove_product_id, $cart[ $cart_item_key ]['wcssb_added_with_product_ids'], true ) ) {

				$added_with_product_ids = array_diff( (array) $cart[ $cart_item_key ]['wcssb_added_with_product_ids'], array( $remove_product_id ) );

				if ( ! empty( $added_with_product_ids ) ) {
					WC()->cart->cart_contents[ $cart_item_key ]['wcssb_added_with_product_ids'] = $added_with_product_ids;
				} else {
					unset( WC()->cart->cart_contents[ $cart_item_key ]['wcssb_added_with_product_ids'] );
				}

				// Update cart.
				WC()->cart->set_session();
			}
		}
	}

	/**
	 * Save `wcssb_added_with_product_ids` cart item meta as `_wcssb_added_with_product_id` order item metas.
	 *
	 * @param \WC_Order_Item_Product $item Order item object.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values Cart item values.
	 * @param \WC_Order              $order Order object.
	 *
	 * @return void
	 */
	public function save_order_item_added_with_product_ids( $item, $cart_item_key, $values, $order ) {
		// Do not save meta for items from WooCommerce Subscriptions object.
		if ( function_exists( 'wcs_is_subscription' ) && \wcs_is_subscription( $order ) ) {
			return;
		}

		// Save product IDs.
		if ( isset( $values['wcssb_added_with_product_ids'] ) && is_array( $values['wcssb_added_with_product_ids'] ) && ! empty( $values['wcssb_added_with_product_ids'] ) ) {

			foreach ( $values['wcssb_added_with_product_ids'] as $added_with_product_id ) {
				$item->add_meta_data( '_wcssb_added_with_product_id', (string) $added_with_product_id, false );
			}
		}
	}

	/**
	 * Do not display `_wcssb_added_with_product_id` order item meta.
	 *
	 * @param array $hidden Hidden order item meta keys.
	 *
	 * @return array
	 */
	public function hide_order_itemmeta_added_with_product_id( $hidden = array() ) {
		$hidden[] = '_wcssb_added_with_product_id';

		return $hidden;
	}

	/**
	 * Delete all product's Splendid Sales Booster cross-sell relations on product post delete.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function delete_product_relations( $post_id ) {
		\wcssb_delete_cross_sells_by_post_id( $post_id, 'both' );
	}

	/**
	 * Delete all subscription variation's Splendid Sales Booster cross-sell relations on product post trash.
	 *
	 * @param int $variation_id Post ID.
	 *
	 * @return void
	 */
	public function delete_subscription_variation_relations( $variation_id ) {
		$product = wc_get_product( $variation_id );

		if ( ! $product || 'subscription_variation' != $product->get_type() ) {
			return;
		}

		\wcssb_delete_cross_sells_by_post_id( $variation_id, 'both' );
	}

	/**
	 * Register the `Delete Splendid Sales Booster cross-sell products` tool on the WooCommerce > Status > Tools page.
	 *
	 * @param array $debug_tools Available debug tool registrations.
	 *
	 * @return array Filtered debug tool registrations.
	 */
	public function register_cross_sell_relations_delete_tool( $debug_tools ) {
		$debug_tools['delete_wcssb_relations'] = array(
			'name'   => __( 'Delete Splendid Sales Booster cross-sell products', 'splendid-sales-booster' ),
			'button' => __( 'Delete cross-sells', 'splendid-sales-booster' ),
			'desc'   => sprintf(
				'<strong class="red">%1$s</strong> %2$s',
				__( 'Note:', 'woocommerce' ),
				__( 'This option will delete Splendid Sales Booster cross-sell relations from ALL of your products, use with caution. This action cannot be reversed.', 'splendid-sales-booster' )
			),
			'callback' => array( $this, 'run_cross_sell_relations_delete_tool' ),
		);

		return $debug_tools;
	}

	/**
	 * `Delete Splendid Sales Booster cross-sell products` tool callback.
	 *
	 * @return string|bool
	 */
	public function run_cross_sell_relations_delete_tool() {
		$data_store = \WC_Data_Store::load( 'wcssb-cross-sell' );
		if ( ! is_a( $data_store, 'WC_Data_Store' ) || '\Splendid\SalesBooster\CrossSellDataStore' !== $data_store->get_current_class_name() ) {
			return false;
		}

		$data_store->delete_all_relations();

		return __( 'Splendid Sales Booster cross-sell products successfully deleted.', 'splendid-sales-booster' );
	}

	/**
	 * Returns an array of allowed HTML tags and attributes for `wcssb_dropdown_variation_attribute_options` context.
	 *
	 * @since 1.2.0
	 *
	 * @param array  $allowedtags Array of allowed HTML tags and their allowed attributes.
	 * @param string $context The context for which to retrieve tags.
	 *
	 * @return array
	 */
	public function wp_kses_allowed_html_wcssb_dropdown_variation_attribute_options( $allowedtags = array(), $context = '' ) {
		if ( 'wcssb_dropdown_variation_attribute_options' !== $context ) {
			return $allowedtags;
		}

		$allowedtags = array(
			'select' => array(
				'id'                  => array(),
				'data-attribute_name' => array(),
			),
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
		);

		return $allowedtags;
	}
}
