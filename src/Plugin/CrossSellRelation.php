<?php
/**
 * Represents a single Splendid Sales Booster cross-sell relation.
 *
 * @package Splendid\SalesBooster
 */

namespace Splendid\SalesBooster;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CrossSellRelation class.
 *
 * @package Splendid\SalesBooster
 */
class CrossSellRelation extends \WC_Data {

	/**
	 * Splendid Sales Booster cross-sell ID.
	 *
	 * @var int|null
	 */
	protected $id = null; // @phpstan-ignore-line

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'wcssb_cross_sell';

	/**
	 * Splendid Sales Booster cross-sell Data.
	 *
	 * @var array
	 */
	protected $data = array(
		'product_id'            => '',
		'parent_id'             => '',
		'product_cta'           => '',
		'product_order'         => 0,
	);

	/**
	 * Constructor for cross-sells.
	 *
	 * @param int $cross_sell Splendid Sales Booster cross-sell relation ID to load from the DB.
	 */
	public function __construct( $cross_sell = null ) {
		parent::__construct();

		if ( is_numeric( $cross_sell ) && ! empty( $cross_sell ) ) {
			$this->set_id( $cross_sell );
		} elseif ( 0 === $cross_sell ) {
			$this->set_id( 0 );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = \WC_Data_Store::load( 'wcssb-cross-sell' );
		if ( false === $this->get_object_read() ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * --------------------------------------------------------------------------
	 * Getters
	 * --------------------------------------------------------------------------
	 */

	/**
	 * Returns the unique ID for this object.
	 *
	 * @return int|null
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get Splendid Sales Booster cross-sell product id.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Get Splendid Sales Booster cross-sell parent id (product).
	 *
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Get Splendid Sales Booster cross-sell product CTA.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_product_cta( $context = 'view' ) {
		return $this->get_prop( 'product_cta', $context );
	}

	/**
	 * Get Splendid Sales Booster cross-sell product order.
	 *
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_product_order( $context = 'view' ) {
		return $this->get_prop( 'product_order', $context );
	}

	/**
	 * --------------------------------------------------------------------------
	 * Setters
	 * --------------------------------------------------------------------------
	 */

	/**
	 * Set Splendid Sales Booster cross-sell product id.
	 *
	 * @param int $set Value to set.
	 *
	 * @return void
	 */
	public function set_product_id( $set ) {
		$this->set_prop( 'product_id', absint( $set ) );
	}

	/**
	 * Set Splendid Sales Booster cross-sell parent id (product).
	 *
	 * @param int $set Value to set.
	 *
	 * @return void
	 */
	public function set_parent_id( $set ) {
		$this->set_prop( 'parent_id', absint( $set ) );
	}

	/**
	 * Set Splendid Sales Booster cross-sell parent product CTA.
	 *
	 * @param string $set Value to set.
	 *
	 * @return void
	 */
	public function set_product_cta( $set ) {
		$this->set_prop( 'product_cta', wc_clean( $set ) );
	}

	/**
	 * Set Splendid Sales Booster cross-sell product order. Value to set.
	 *
	 * @param int $set Value to set.
	 *
	 * @return void
	 */
	public function set_product_order( $set ) {
		$this->set_prop( 'product_order', absint( $set ) );
	}

	/**
	 * --------------------------------------------------------------------------
	 * Other
	 * --------------------------------------------------------------------------
	 */

	/**
	 * Save Splendid Sales Booster cross-sell data to the database.
	 *
	 * @return int|null
	 */
	public function save() {

		if ( ! is_a( $this->data_store, 'WC_Data_Store' ) || '\Splendid\SalesBooster\CrossSellDataStore' !== $this->data_store->get_current_class_name() ) {
			return $this->get_id();
		}

		/**
		 * Trigger action before saving to the DB. Allows you to adjust object props before save.
		 *
		 * @param \WC_Data       $relation The object being saved.
		 * @param \WC_Data_Store $data_store The data store persisting the data.
		 */
		do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

		if ( null !== $this->get_id() ) {
			$this->data_store->update( $this );
		} else {
			$this->data_store->create( $this );
		}

		/**
		 * Trigger action after saving to the DB.
		 *
		 * @param \WC_Data       $relation The object being saved.
		 * @param \WC_Data_Store $data_store The data store persisting the data.
		 */
		do_action( 'woocommerce_after_' . $this->object_type . '_object_save', $this, $this->data_store );

		return $this->get_id();
	}

	/**
	 * Get Splendid Sales Booster cross-sell relation discounted price to display.
	 *
	 * @param int $product_id Product ID to get the price for.
	 *
	 * @return string|bool Product price HTML or false if product doesn't exist.
	 */
	public function get_discounted_price_html( $product_id = 0 ) {
		if ( ! $product_id ) {
			$product_id = $this->get_product_id();
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}

		// Return product's default price.
		return $product->get_price_html();
	}
}
