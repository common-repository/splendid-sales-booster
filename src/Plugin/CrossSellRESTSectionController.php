<?php
/**
 * REST API Product's Splendid Sales Booster cross-sells section controller.
 *
 * @package Splendid\SalesBooster
 */

namespace Splendid\SalesBooster;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Product's Splendid Sales Booster cross-sells section controller class.
 *
 * @package Splendid\SalesBooster
 */
class CrossSellRESTSectionController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'wcssb-section';

	/**
	 * Register the routes for product reviews.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_section' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check permissions.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function check_permission( $request ) {
		return true;
	}

	/**
	 * Get Splendid Sales Booster cross-sells section.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_section( $request ) {
		$product = wc_get_product( (int) absint( $request['id'] ) );

		if ( ! $product || 0 === $product->get_id() ) {
			return new \WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$data = array();

		$fields = $this->get_fields_for_response( $request );

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$data['id'] = (int) absint( $product->get_id() );
					break;
				case 'title':
					$data['title'] = esc_html( $product->get_title() );
					break;
				case 'section_title':
					$data['section_title'] = esc_html( \wcssb_get_product_section_title( $product->get_id() ) );
					break;
				case 'products':
					$data['products'] = \wcssb_get_product_cross_sell_products( $product );
					break;
			}
		}

		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get the Splendid Sales Booster cross-sells section schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'wcssb-section',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'title'         => array(
					'description' => __( 'Product title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'section_title' => array(
					'description' => __( 'Section title', 'splendid-sales-booster' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'products'      => array(
					'description' => __( 'List of products.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                    => array(
								'description' => __( 'Product ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'title'                 => array(
								'description' => __( 'Product title.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'permalink'             => array(
								'description' => __( 'Product URL.', 'woocommerce' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'type'                  => array(
								'description' => __( 'Product type.', 'woocommerce' ),
								'type'        => 'string',
								'default'     => 'simple',
								'enum'        => array_keys( wc_get_product_types() ),
								'context'     => array( 'view' ),
							),
							'sku'                   => array(
								'description' => __( 'Unique identifier.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'discounted_price_html' => array(
								'description' => __( 'Price formatted in HTML.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'stock_status'          => array(
								'description' => __( 'Controls the stock status of the product.', 'woocommerce' ),
								'type'        => 'string',
								'default'     => 'instock',
								'enum'        => array_keys( wc_get_product_stock_status_options() ),
								'context'     => array( 'view' ),
							),
							'stock_html'            => array(
								'description' => __( 'Stock formatted in HTML.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'image_html'            => array(
								'description' => __( 'Image formatted in HTML.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'cta'                   => array(
								'description' => __( 'Product call-to-action.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'formatted_attributes'            => array(
								'description' => __( 'List of formatted attributes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'name'            => array(
											'description' => __( 'Attribute name.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view' ),
										),
										'html_class' => array(
											'description' => __( 'Attribute name HTML class.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view' ),
											'readonly'    => true,
										),
										'options_html'    => array(
											'description' => __( 'List of available term names of the attribute formatted in HTML.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view' ),
											'readonly'    => true,
										),
									),
								),
							),
							'add_to_cart_url'          => array(
								'description' => __( 'Product add to cart URL.', 'woocommerce' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view' ),
							),
							'add_to_cart_description'  => array(
								'description' => __( 'Product add to cart button text description.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'variations_json' => array(
								'description' => __( 'List of variations and their data formatted in JSON.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'variations_attributes' => array(
								'description' => __( 'List of variation attributes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'label'        => array(
											'description' => __( 'Attribute name.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view' ),
										),
										'select_id'    => array(
											'description' => __( 'Attribute select HTML id.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view' ),
											'readonly'    => true,
										),
										'select_html' => array(
											'description' => __( 'Attribute select formatted in HTML.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view' ),
											'readonly'    => true,
										),
									),
								),
							),
							'show_reset_link' => array(
								'description' => __( 'Whether to show variation form reset link.', 'woocommerce' ),
								'type'        => 'bool',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'default_variation_id' => array(
								'description' => __( 'Default variation ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'default_discounted_price_html' => array(
								'description' => __( 'Default price formatted in HTML.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);

		return $schema;
	}
}
