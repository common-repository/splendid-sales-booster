<?php
/**
 * Product's Splendid Sales Booster cross-sells options.
 *
 * @package Splendid\SalesBooster
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="options_group show_if_simple show_if_variable hidden">

	<h3 class="wcssb-product-options-title">
		<?php esc_html_e( 'Splendid Sales Booster cross-sells', 'splendid-sales-booster' ); ?>
	</h3>

	<?php
		woocommerce_wp_text_input(
			array(
				'id'          => '_wcssb_section_title',
				'label'       => __( 'Section title', 'splendid-sales-booster' ),
				'placeholder' => get_option( 'wcssb_section_default_title', wcssb_get_default_wcq_section_title_value() ),
				'desc_tip'    => true,
				'description' => __( 'This will be shown above Splendid Sales Booster cross-sell products list.', 'splendid-sales-booster' ),
			)
		);
		?>

	<div class="wcssb-products-wrap">
		<ul class="wcssb-products js-wcssb-products" data-parent-key="<?php echo absint( $product_object->get_id() ); ?>"></ul>
		<div class="button button-primary wcssb-add-product-row-button js-wcssb-add-product-row-button">
			<?php echo esc_html( __( 'Add product', 'splendid-sales-booster' ) ); ?>
		</div>
	</div>

</div>
