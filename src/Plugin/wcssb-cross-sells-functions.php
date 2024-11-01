<?php
/**
 * Splendid Sales Booster cross-sells WooCommerce Functions
 *
 * @package Splendid\SalesBooster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Splendid Sales Booster cross-sells section title
 *
 * @param int $product_id Product ID.
 *
 * @return string Product's Splendid Sales Booster cross-sells section title.
 */
function wcssb_get_product_section_title( $product_id = 0 ) {

	$title = get_option( 'wcssb_section_default_title', wcssb_get_default_wcq_section_title_value() );

	// Maybe get title from specific product.
	$product = wc_get_product( $product_id );
	if ( $product ) {
		$product_wcssb_section_title = $product->get_meta( '_wcssb_section_title', true );
		if ( $product_wcssb_section_title ) {
			$title = $product_wcssb_section_title;
		}
	}

	return apply_filters( 'wcssb_section_title', $title, $product_id );
}

/**
 * Get plugin's default Splendid Sales Booster cross-sells section title setting value
 *
 * @return string
 */
function wcssb_get_default_wcq_section_title_value() {
	return __( 'You may also like', 'splendid-sales-booster' );
}

/**
 * Get Splendid Sales Booster cross-sell relation object by product_id and/or parent_id.
 *
 * @param int $product_id Product ID.
 * @param int $parent_id Product's parent ID (product).
 *
 * @return bool|\Splendid\SalesBooster\CrossSellRelation
 */
function wcssb_get_cross_sell_by_product_and_parent_ids_pair( $product_id = 0, $parent_id = 0 ) {
	if ( ! $product_id ) {
		return false;
	}

	if ( ! $parent_id ) {
		$parent_id = get_the_ID();
	}

	$data_store = \WC_Data_Store::load( 'wcssb-cross-sell' );
	if ( ! is_a( $data_store, 'WC_Data_Store' ) || '\Splendid\SalesBooster\CrossSellDataStore' !== $data_store->get_current_class_name() ) {
		return false;
	}

	$relation_id = $data_store->get_relation_id_by_product_and_parent_ids_pair( $parent_id, $product_id );

	if ( ! $relation_id ) {
		return false;
	}

	try {
		return new \Splendid\SalesBooster\CrossSellRelation( $relation_id );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Get Splendid Sales Booster cross-sell relations by parent product ID.
 *
 * @param int  $product_id Parent product ID.
 *
 * @return bool|array Array of CrossSellRelation objects.
 */
function wcssb_get_cross_sells_by_parent_product_id( $product_id = 0 ) {
	$product_id = absint( $product_id );

	if ( ! $product_id ) {
		return false;
	}

	$data_store = \WC_Data_Store::load( 'wcssb-cross-sell' );
	if ( ! is_a( $data_store, 'WC_Data_Store' ) || '\Splendid\SalesBooster\CrossSellDataStore' !== $data_store->get_current_class_name() ) {
		return false;
	}

	$relations = array();

	$relation_ids = $data_store->get_relation_ids_by_post_id( $product_id, 'parent' );

	if ( ! empty( $relation_ids ) ) {
		foreach ( $relation_ids as $relation_id ) {
			$relation = new \Splendid\SalesBooster\CrossSellRelation( $relation_id );

			if ( ! $relation->get_id() ) {
				continue;
			}

			if ( ! $relation->get_product_id() ) {
				continue;
			}

			$relations[ (int) $relation->get_product_id() ] = $relation;
		}
	}

	if ( isset( $relations[ (int) $product_id ] ) ) {
		unset( $relations[ (int) $product_id ] );
	}

	$relations = array_values( $relations );

	return $relations;
}

/**
 * Delete Splendid Sales Booster cross-sell relations by post_id (product).
 *
 * @param int    $post_id Post ID (product).
 * @param string $relation_type Relation type. Possible values: 'product', 'parent', 'both'.
 * @param array  $exclude_relation_ids An array of relation IDs to be excluded.
 *
 * @return bool
 */
function wcssb_delete_cross_sells_by_post_id( $post_id = 0, $relation_type = 'product', $exclude_relation_ids = array() ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return false;
	}

	$exclude_relation_ids = array_filter( $exclude_relation_ids );
	$exclude_relation_ids = array_map( 'absint', $exclude_relation_ids );

	$data_store = \WC_Data_Store::load( 'wcssb-cross-sell' );
	if ( ! is_a( $data_store, 'WC_Data_Store' ) || '\Splendid\SalesBooster\CrossSellDataStore' !== $data_store->get_current_class_name() ) {
		return false;
	}

	$relations = $data_store->get_relation_ids_by_post_id( $post_id, $relation_type );
	if ( ! empty( $relations ) ) {
		foreach ( $relations as $relation_id ) {
			$relation_id = absint( $relation_id );

			if ( in_array( $relation_id, $exclude_relation_ids, true ) ) {
				continue;
			}

			$relation = new \Splendid\SalesBooster\CrossSellRelation( $relation_id );

			$data_store->delete( $relation );
		}
	}

	return true;
}

/**
 * Format product name string.
 *
 * @param string      $name Product name unformatted.
 * @param \WC_Product $product A Product object.
 *
 * @return string Product name formatted.
 */
function wcssb_format_product_name( $name, $product ) {
	$name = trim( wp_strip_all_tags( (string) preg_replace( '/<span .*?class="(.*?description.*?)">(.*?)<\/span>/', ' $2', $name ) ) );

	if ( $product && 'variable' === $product->get_type() ) {
		$name .= ' - ' . __( 'Any variation', 'splendid-sales-booster' );
	}

	return $name;
}

/**
 * Gets a list of product variation attributes for display on the frontend.
 *
 * @param \WC_Product $product A Product object.
 *
 * @return array
 */
function wcssb_get_formatted_product_variation_attributes( $product ) {

	if ( ! $product->get_id() ) {
		return array();
	}

	if ( ! in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ) {
		return array();
	}

	$product_attributes = $product->get_attributes();
	if ( ! is_array( $product_attributes ) ) {
		return array();
	}

	$attributes = array();

	// Variation values are shown only if they are not found in the title as of 3.0.
	// This is because variation titles display the attributes.
	foreach ( $product_attributes as $name => $value ) {
		$taxonomy = wc_attribute_taxonomy_name( str_replace( 'pa_', '', urldecode( $name ) ) );

		if ( taxonomy_exists( $taxonomy ) ) {
			// If this is a term slug, get the term's nice name.
			$term = get_term_by( 'slug', $value, $taxonomy, ARRAY_A );
			if ( false !== $term ) {
				$term = (array) $term;
				if ( isset( $term['name'] ) ) {
					$value = $term['name'];
				}
			}

			$label = wc_attribute_label( $taxonomy );
		} else {
			// If this is a custom option slug, get the options name.
			$value = apply_filters( 'woocommerce_variation_option_name', $value, null, $taxonomy, $product );
			$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $product );
		}

		// Check the nicename against the title.
		if ( '' === $value || wc_is_attribute_in_product_name( $value, $product->get_name() ) ) {
			continue;
		}

		$attributes[] = array(
			'name'         => $label,
			'html_class'   => sanitize_html_class( 'variation-' . $label ),
			'options_html' => wpautop( $value ),
		);
	}

	if ( count( $attributes ) > 0 ) {
		return $attributes;
	}

	return array();
}

/**
 * Get Splendid Sales Booster cross-sells section location.
 *
 * @return string Splendid Sales Booster cross-sells section location.
 */
function wcssb_get_section_location() {
	$location = get_option( 'wcssb_section_location' );

	// Make sure to set `drawer` if no other is selected.
	if ( false === get_option( 'wcssb_section_location' ) ) {
		$location = 'drawer';

		add_option( 'wcssb_section_location', $location );
	}

	return $location;
}

/**
 * Check if provided Splendid Sales Booster cross-sells section location is enabled.
 *
 * @param string $location Location to check.
 *
 * @return bool Is location enabled.
 */
function wcssb_is_section_location_enabled( $location = '' ) {
	return wcssb_get_section_location() === $location;
}

/**
 * Get Splendid Sales Booster cross-sell products for a product or product variation.
 *
 * @param WC_Product|WC_Product_Variation $product Product instance.
 *
 * @return array
 */
function wcssb_get_product_cross_sell_products( $product ) {
	if ( ! in_array( $product->get_type(), array( 'simple', 'variable', 'subscription', 'variable-subscription' ), true ) ) {
		return array();
	}

	// Get Splendid Sales Booster cross-sell products.
	$cross_sell_product_ids = array();

	$cross_sells = \wcssb_get_cross_sells_by_parent_product_id( $product->get_id() );
	if ( false !== $cross_sells && ! empty( $cross_sells ) ) {
		foreach ( (array) $cross_sells as $cross_sell_relation ) {
			$cross_sell_product_ids[] = $cross_sell_relation->get_product_id();
		}
	}

	if ( empty( $cross_sell_product_ids ) ) {
		return array();
	}

	// Do not display current product.
	$cross_sell_product_ids = array_diff( $cross_sell_product_ids, array( $product->get_id() ) );

	// Do not display current product's variations.
	if ( in_array( $product->get_type(), array( 'variable', 'variable-subscription' ) ) ) {
		$cross_sell_product_ids = array_diff( $cross_sell_product_ids, $product->get_children() );
	}

	if ( empty( $cross_sell_product_ids ) ) {
		return array();
	}

	$cross_sell_product_args = array(
		'type'         => array( 'simple', 'variable', 'variation', 'subscription' ),
		'stock_status' => array( 'instock', 'onbackorder' ),
		'include'      => $cross_sell_product_ids,
		'limit'        => -1,
		'orderby'      => 'post__in',
		'order'        => 'ASC',
		'paginate'     => false,
	);
	$cross_sell_products     = wc_get_products( $cross_sell_product_args );

	if ( empty( $cross_sell_products ) ) {
		return array();
	}

	// Build products data.
	$products_data = array();

	foreach ( (array) $cross_sell_products as $cross_sell_product ) {
		if ( ! $cross_sell_product->is_purchasable() ) {
			continue;
		}

		$cross_sell = false;

		foreach ( (array) $cross_sells as $cross_sell_relation ) {
			if ( $cross_sell_product->get_id() != $cross_sell_relation->get_product_id() ) {
				continue;
			}

			$cross_sell = $cross_sell_relation;

			break;
		}

		if ( ! $cross_sell ) {
			continue;
		}

		// Do not display variation products that have `Any attribute` option.
		if ( in_array( $cross_sell_product->get_type(), array( 'variation', 'subscription_variation' ) ) ) {
			$product_attributes = $cross_sell_product->get_attributes();

			if ( empty( $product_attributes ) || in_array( null, $product_attributes, true ) || in_array( '', $product_attributes, true ) ) {
				continue;
			}
		}

		$discounted_price_html = $cross_sell->get_discounted_price_html();

		$formatted_attributes = wcssb_get_formatted_product_variation_attributes( $cross_sell_product );

		foreach ( $formatted_attributes as $key => $attibute ) {
			if ( isset( $attibute['name'] ) ) {
				$formatted_attributes[ $key ]['name'] = esc_html( $attibute['name'] );
			}

			if ( isset( $attibute['html_class'] ) ) {
				$formatted_attributes[ $key ]['html_class'] = esc_attr( $attibute['html_class'] );
			}

			if ( isset( $attibute['options_html'] ) ) {
				$formatted_attributes[ $key ]['options_html'] = wp_kses_post( $attibute['options_html'] );
			}
		}

		$variations_json       = null;
		$variations_attributes = array();

		$default_attributes            = array();
		$default_variation_id          = 0;
		$default_discounted_price_html = $discounted_price_html;

		if ( 'variable' == $cross_sell_product->get_type() ) {
			// Get available variations.
			$available_variations = $cross_sell_product->get_available_variations();

			if ( empty( $available_variations ) ) {
				continue;
			}

			$variations           = array();
			$available_attributes = array();

			foreach ( $available_variations as $available_variation ) {
				// Do not display variations that are not purchasable, active nor visible.
				if ( empty( $available_variation['is_purchasable'] ) || empty( $available_variation['is_purchasable'] ) || empty( $available_variation['variation_is_active'] ) || empty( $available_variation['variation_is_visible'] ) ) {
					continue;
				}

				// Do not display outofstock variations.
				if ( empty( $available_variation['is_in_stock'] ) && empty( $available_variation['backorders_allowed'] ) ) {
					continue;
				}

				// Do not display variations that have `Any attribute` option.
				if ( empty( $available_variation['attributes'] ) || in_array( null, $available_variation['attributes'], true ) || in_array( '', $available_variation['attributes'], true ) ) {
					continue;
				}

				$variation_product = wc_get_product( $available_variation['variation_id'] );
				if ( ! $variation_product ) {
					continue;
				}

				$variations[] = array(
					'attributes'            => $available_variation['attributes'],
					'discounted_price_html' => wp_kses_post( (string) $cross_sell->get_discounted_price_html( $variation_product->get_id() ) ),
					'sku'                   => esc_attr( $available_variation['sku'] ),
					'variation_id'          => (int) absint( $available_variation['variation_id'] ),
					'stock_status'          => esc_attr( $variation_product->get_stock_status() ),
					'stock_html'            => wp_kses_post( $available_variation['availability_html'] ),
				);

				// Store available attribute options.
				foreach ( $available_variation['attributes'] as $attribute_name => $option ) {
					if ( empty( $available_attributes[ $attribute_name ] ) ) {
						$available_attributes[ $attribute_name ] = array();
					}

					if ( in_array( $option, $available_attributes[ $attribute_name ], true ) ) {
						continue;
					}

					$available_attributes[ $attribute_name ][] = $option;
				}
			}

			if ( empty( $variations ) ) {
				continue;
			}

			$variations_json = wp_json_encode( $variations );

			// Get possible attribute options.
			$attributes = $cross_sell_product->get_variation_attributes();

			if ( empty( $attributes ) ) {
				continue;
			}

			foreach ( $attributes as $attribute_name => $options ) {
				$attribute_key = 'attribute_' . sanitize_title( $attribute_name );

				if ( empty( $options ) || empty( $available_attributes[ $attribute_key ] ) ) {
					continue 2;
				}

				// Get only available attribute options.
				$options = array_intersect( $options, $available_attributes[ $attribute_key ] );

				// Get default attribute.
				$default_attribute = $cross_sell_product->get_variation_default_attribute( $attribute_name );

				if ( ! empty( $default_attribute ) && in_array( $default_attribute, $options, true ) ) {
					$default_attributes[ $attribute_key ] = $default_attribute;
				} else {
					$default_attribute = null;
				}

				$select_id = sanitize_title( $attribute_name ) . '-' . $cross_sell_product->get_id();

				$select_html = wcssb_dropdown_variation_attribute_options(
					array(
						'options'   => $options,
						'attribute' => $attribute_name,
						'product'   => $cross_sell_product,
						'selected'  => $default_attribute,
						'id'        => $select_id,
					)
				);

				if ( '' === $select_html ) {
					continue 2;
				}

				$variations_attributes[] = array(
					'label'       => esc_html( wc_attribute_label( $attribute_name ) ),
					'select_id'   => esc_attr( $select_id ),
					'select_html' => wp_kses( $select_html, 'wcssb_dropdown_variation_attribute_options' ),
				);
			}

			// Find default variation based on default attributes.
			if ( count( $attributes ) === count( $default_attributes ) ) {
				$data_store = \WC_Data_Store::load( 'product' );

				$default_variation_id = $data_store->find_matching_product_variation( $cross_sell_product, $default_attributes );

				// Check if default variation is available.
				if ( ! empty( $default_variation_id ) && ! in_array( $default_variation_id, wp_list_pluck( $variations, 'variation_id' ), true ) ) {
					$default_variation_id = 0;
				}

				// Get the variation discounted price HTML.
				if ( ! empty( $default_variation_id ) ) {
					$default_discounted_price_html = $cross_sell->get_discounted_price_html( $default_variation_id );
				}
			}
		}

		$products_data[] = array(
			'id'                            => (int) absint( $cross_sell_product->get_id() ),
			'title'                         => esc_html( $cross_sell_product->get_name() ),
			'permalink'                     => $cross_sell_product->is_visible() ? esc_url( $cross_sell_product->get_permalink() ) : '',
			'type'                          => esc_attr( $cross_sell_product->get_type() ),
			'sku'                           => esc_attr( $cross_sell_product->get_sku() ),
			'discounted_price_html'         => wp_kses_post( (string) $discounted_price_html ),
			'stock_status'                  => esc_attr( $cross_sell_product->get_stock_status() ),
			'stock_html'                    => wp_kses_post( wc_get_stock_html( $cross_sell_product ) ),
			'image_html'                    => wp_kses_post( $cross_sell_product->get_image() ),
			'cta'                           => esc_html( $cross_sell->get_product_cta() ),
			'formatted_attributes'          => $formatted_attributes,
			'add_to_cart_url'               => esc_attr( '?' . parse_url( $cross_sell_product->add_to_cart_url(), PHP_URL_QUERY ) ),
			'add_to_cart_description'       => esc_attr( $cross_sell_product->add_to_cart_description() ),
			'variations_json'               => function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true ),
			'variations_attributes'         => $variations_attributes,
			'show_reset_link'               => (bool) count( $default_attributes ) > 0,
			'default_variation_id'          => (int) absint( $default_variation_id ),
			'default_discounted_price_html' => wp_kses_post( (string) $default_discounted_price_html ),
		);
	}

	return $products_data;
}

/**
 * Return a list of variation attributes for use in Splendid Sales Booster cross-sell section forms.
 *
 * @since 1.2.0
 *
 * @param array $args Arguments.
 *
 * @return string Dropdown HTML.
 */
function wcssb_dropdown_variation_attribute_options( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'options'   => false,
			'attribute' => false,
			'product'   => false,
			'selected'  => false,
			'id'        => '',
		)
	);

	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];
	$id        = $args['id'];

	if ( ! $product ) {
		return '';
	}

	$html  = '<select id="' . esc_attr( $id ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';
	$html .= '<option value="">' . esc_html( __( 'Choose an option', 'splendid-sales-booster' ) ) . '</option>';

	if ( ! empty( $options ) ) {
		if ( taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms(
				$product->get_id(),
				$attribute,
				array(
					'fields' => 'all',
				)
			);

			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options, true ) ) {
					$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( ! empty( $args['selected'] ) ? $args['selected'] : '' ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
				$selected = sanitize_title( ! empty( $args['selected'] ) ? $args['selected'] : '' ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
				$html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
			}
		}
	}

	$html .= '</select>';

	return $html;
}
