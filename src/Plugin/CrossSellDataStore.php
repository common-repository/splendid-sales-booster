<?php
/**
 * Custom WooCommerce Data Store for Splendid Sales Booster cross-sells.
 *
 * @package Splendid\SalesBooster
 */

namespace Splendid\SalesBooster;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Splendid Sales Booster cross-sell Data Store.
 *
 * @package Splendid\SalesBooster
 */
class CrossSellDataStore extends \WC_Data_Store_WP implements \WC_Object_Data_Store_Interface {

	/**
	 * Method to create a new Splendid Sales Booster cross-sell relation.
	 *
	 * @param CrossSellRelation $relation Splendid Sales Booster cross-sell relation object.
	 *
	 * @return void
	 */
	public function create( &$relation ) {
		global $wpdb;
		// phpcs:ignore
		$wpdb->insert(
			$wpdb->prefix . 'wcssb_cross_sells',
			array(
				'product_id'            => $relation->get_product_id(),
				'parent_product_id'     => $relation->get_parent_id(),
				'product_cta'           => $relation->get_product_cta(),
				'product_order'         => $relation->get_product_order(),
			)
		);
		$relation->set_id( $wpdb->insert_id );
		$relation->apply_changes();
	}

	/**
	 * Update relation in the database.
	 *
	 * @param CrossSellRelation $relation Splendid Sales Booster cross-sell relation object.
	 *
	 * @return void
	 */
	public function update( &$relation ) {
		global $wpdb;
		if ( $relation->get_id() ) {
			// phpcs:ignore
			$wpdb->update(
				$wpdb->prefix . 'wcssb_cross_sells',
				array(
					'product_id'            => $relation->get_product_id(),
					'parent_product_id'     => $relation->get_parent_id(),
					'product_cta'           => $relation->get_product_cta(),
					'product_order'         => $relation->get_product_order(),
				),
				array( 'relation_id' => $relation->get_id() )
			);
		}
		$relation->apply_changes();
	}

	/**
	 * Method to read a Splendid Sales Booster cross-sell relation from the database.
	 *
	 * @param CrossSellRelation $relation Splendid Sales Booster cross-sell relation object.
	 * @throws \Exception If invalid data store.
	 *
	 * @return void
	 */
	public function read( &$relation ) {
		global $wpdb;

		$relation_data = false;

		if ( 0 !== $relation->get_id() ) {
			// phpcs:ignore
			$relation_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT product_id, parent_product_id AS `parent_id`, product_order, product_cta FROM {$wpdb->prefix}wcssb_cross_sells WHERE relation_id = %d LIMIT 1",
					$relation->get_id()
				)
			);
		}

		if ( $relation_data ) {
			$relation->set_product_id( $relation_data->product_id );
			$relation->set_parent_id( $relation_data->parent_id );
			$relation->set_product_cta( $relation_data->product_cta );
			$relation->set_product_order( $relation_data->product_order );
			$relation->set_object_read( true );
			do_action( 'woocommerce_wcssb_cross_sell_loaded', $relation );
		} else {
			throw new \Exception( __( 'Invalid data store.', 'splendid-sales-booster' ) );
		}
	}

	/**
	 * Deletes a Splendid Sales Booster cross-sell relation from the database.
	 *
	 * @param  CrossSellRelation $relation Splendid Sales Booster cross-sell relation object.
	 * @param  array                  $args Array of args to pass to the delete method.
	 *
	 * @return bool result
	 */
	public function delete( &$relation, $args = array() ) {
		$relation_id = $relation->get_id();

		if ( ! $relation_id ) {
			return false;
		}

		global $wpdb;

		// Delete relation.
		// phpcs:ignore
		$wpdb->delete( $wpdb->prefix . 'wcssb_cross_sells', array( 'relation_id' => $relation_id ) );

		$relation->set_id( 0 );

		do_action( 'woocommerce_delete_wcssb_cross_sell', $relation_id );

		return true;
	}

	/**
	 * Return a relation id by its pair of product_id and parent_id.
	 *
	 * @param  int $parent_id ID of product parent (product).
	 * @param  int $product_id Id of the product.
	 *
	 * @return int An id of relation.
	 */
	public function get_relation_id_by_product_and_parent_ids_pair( $parent_id = 0, $product_id = 0 ) {
		if ( ! $parent_id || ! $product_id ) {
			return 0;
		}

		global $wpdb;
		// phpcs:ignore
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT relation_id FROM {$wpdb->prefix}wcssb_cross_sells WHERE product_id = %d AND parent_product_id = %d LIMIT 1;", $product_id, $parent_id ) );
	}

	/**
	 * Return an ordered list of relations by post_id (product) with its relation type (as parent, product or in both directions)
	 *
	 * @param  int    $post_id Id of the post (product).
	 * @param  string $relation_type Type of the relation to search for. Possible values: 'product', 'parent', 'both'.
	 *
	 * @return array An array of relation_ids.
	 */
	public function get_relation_ids_by_post_id( $post_id = 0, $relation_type = 'product' ) {
		if ( ! in_array( $relation_type, array( 'product', 'parent', 'both' ), true ) ) {
			return array();
		}

		$cache_group = 'wcssb';
		$cache_key   = 'post_' . $post_id . '_' . $relation_type . '_relation_ids';

		$relation_ids = wp_cache_get( $cache_key, $cache_group, false, $cache_found );
		if ( false === $cache_found ) {
			global $wpdb;

			$relation_ids = array();

			if ( 'product' === $relation_type ) {

				// phpcs:ignore
				$relation_ids = $wpdb->get_col( $wpdb->prepare( "SELECT relation_id FROM {$wpdb->prefix}wcssb_cross_sells WHERE product_id = %d ORDER BY product_order ASC, relation_id ASC;", $post_id ) );

			} elseif ( 'parent' === $relation_type ) {

				// phpcs:ignore
				$relation_ids = $wpdb->get_col( $wpdb->prepare( "SELECT relation_id FROM {$wpdb->prefix}wcssb_cross_sells WHERE parent_product_id = %d ORDER BY product_order ASC, relation_id ASC;", $post_id ) );

			} else {

				// phpcs:ignore
				$relation_ids = $wpdb->get_col( $wpdb->prepare( "SELECT relation_id FROM {$wpdb->prefix}wcssb_cross_sells WHERE ( parent_product_id = %d OR product_id = %d ) ORDER BY product_order ASC, relation_id ASC;", $post_id, $post_id ) );

			}

			wp_cache_set( $cache_key, $relation_ids, $cache_group );
		}

		return $relation_ids;
	}

	/**
	 * Deletes all Splendid Sales Booster cross-sell relations from the database.
	 *
	 * @return void
	 */
	public function delete_all_relations() {
		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wcssb_cross_sells;" );
	}

}
