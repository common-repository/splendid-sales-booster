<?php
/**
 * Splendid Sales Booster cross-sells products template.
 *
 * @package Splendid\SalesBooster
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product_object;

?>

<script type="text/template" id="tmpl-wcssb-product-row">
	<li class="wcssb-product-row js-wcssb-product-row" data-product-key="{{ data.row_key }}">

		<div class="wcssb-product-row-head js-wcssb-product-row-head">

			<div class="wcssb-product-row-head-title-wrap">

				<p class="form-field wcssb_row_product_id_field">
					<label for="wcssb_row_product_id-{{ data.row_key }}">
						<?php esc_html_e( 'Product', 'splendid-sales-booster' ); ?>
					</label>
					<?php echo wp_kses_post( wc_help_tip( __( 'Select the product that you want to be promoted with the current product.', 'splendid-sales-booster' ) . ' ' . __( 'Supported types: simple, variable, variation, simple subscription, subscription variation', 'splendid-sales-booster' ) ) ); ?>
					<select class="wc-product-search" style="width: 80%;" id="wcssb_row_product_id-{{ data.row_key }}" name="_wcssb_products[{{ data.parent_key }}][{{ data.row_key }}][product_id]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'splendid-sales-booster' ); ?>" data-action="wcssb_json_search_products" <?php echo $product_object ? 'data-exclude="' . intval( $product_object->get_id() ) . '"' : ''; ?> data-allow_clear="false">
						<# if ( data.product_id ) { #>
							<option selected="selected" value="{{ data.product_id }}">{{ data.product_name }}</option>
						<# } #>
					</select>
				</p>

			</div>

			<div class="wcssb-remove-product-row js-wcssb-remove-product-row">
				<?php esc_html_e( 'Remove', 'splendid-sales-booster' ); ?>
			</div>

		</div>

		<div class="wcssb-product-row-body">

			<p class="form-field wcssb_row_product_cta_field">
				<label for="wcssb_row_product_cta-{{ data.row_key }}">
					<?php esc_html_e( 'Product\'s call-to-action', 'splendid-sales-booster' ); ?>
				</label>
				<?php echo wp_kses_post( wc_help_tip( __( 'This text will be shown above the product title.', 'splendid-sales-booster' ) ) ); ?>
				<input type="text" class="short" name="_wcssb_products[{{ data.parent_key }}][{{ data.row_key }}][product_cta]" id="wcssb_row_product_cta-{{ data.row_key }}" value="{{ data.product_cta }}">
			</p>

		</div>

		<# if ( data.is_new_row ) { #>
			<input type="hidden" name="_wcssb_products[{{ data.parent_key }}][{{ data.row_key }}][is_new_row]" value="1">
		<# } #>

	</li>
</script>
