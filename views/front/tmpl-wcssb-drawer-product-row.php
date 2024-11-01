<?php
/**
 * Splendid Sales Booster cross-sells drawer cross-sell product template.
 *
 * @package Splendid\SalesBooster
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/template" id="tmpl-wcssb-drawer-product-row">
	<tr class="js-wcssb-cross-sell-item wcssb-cross-sell-item">
		<?php if ( 'no' !== get_option( 'wcssb_show_images' ) ) : ?>
			<# if ( data.image_html ) { #>
				<td class="wcssb-cross-sell-item-image">
					<# if ( data.permalink ) { #>
						<a target="_blank" href="{{ data.permalink }}">
					<# } #>
						{{{ data.image_html }}}
					<# if ( data.permalink ) { #>
						</a>
					<# } #>
				</td>
			<# } #>
		<?php endif; ?>

		<td class="js-wcssb-cross-sell-item-product wcssb-cross-sell-item-product">
			<# if ( data.cta ) { #>
				<h4 class="wcssb-cross-sell-item-cta">
					{{ data.cta }}
				</h4>
			<# } #>

			<# if ( ! data.permalink ) { #>
				<span class="wcssb-cross-sell-item-name">
			<# } else { #>
				<a class="wcssb-cross-sell-item-name" target="_blank" href="{{ data.permalink }}">
			<# } #>
				{{{ data.title }}}
			<# if ( ! data.permalink ) { #>
				</span>
			<# } else { #>
					<svg class="wcssb-cross-sell-item-name-icon" xmlns="http://www.w3.org/2000/svg" version="1.1" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M9 3h8v8l-2-1V6.92l-5.6 5.59-1.41-1.41L14.08 5H10zm3 12v-3l2-2v7H3V6h8L9 8H5v7h7z"/></svg>
				</a>
			<# } #>

			<# if ( data.formatted_attributes.length ) { #>
				<dl class="variation wcssb-cross-sell-item-variation">
					<# var i; for( i = 0; i < data.formatted_attributes.length; i++ ) { #>
						<dt class="{{ data.formatted_attributes[i].html_class }}">{{ data.formatted_attributes[i].name }}:</dt>
						<dd class="{{ data.formatted_attributes[i].html_class }}">{{{ data.formatted_attributes[i].options_html }}}</dd>
					<# } #>
				</dl>
			<# } #>

			<# if ( 'variable' === data.type ) { #>
				<div class="js-wcssb-variations-form wcssb-variations-form" data-product_variations="{{{ data.variations_json }}}">
					<table class="js-wcssb-variations wcssb-variations has-background" cellspacing="0" role="presentation">
						<tbody>
							<# var i; for( i = 0; i < data.variations_attributes.length; i++ ) { #>
								<tr>
									<th><label for="{{ data.variations_attributes[i].select_id }}">{{ data.variations_attributes[i].label }}</label></th>
									<td>
										{{{ data.variations_attributes[i].select_html }}}
										<# if ( ( data.variations_attributes.length - 1 ) === i ) { #>
											<div class="js-wcssb-reset-variations wcssb-reset-variations <# if ( ! data.show_reset_link ) { #>wcssb-hidden<# } #>">
												<a href="#"><?php esc_html_e( 'Clear', 'splendid-sales-booster' ); ?></a>
											</div>
										<# } #>
									</td>
								</tr>
							<# } #>
						</tbody>
					</table>
				</div>
			<# } #>

			<# if ( data.discounted_price_html ) { #>
				<p class="price js-wcssb-cross-sell-item-price wcssb-cross-sell-item-price"
					<# if ( 'variable' == data.type ) { #>
						data-wcssb_o_content="{{ data.discounted_price_html }}"
					<# } #>
				>
					<# if ( 'variable' != data.type ) { #>
						{{{ data.discounted_price_html }}}
					<# } else { #>
						{{{ data.default_discounted_price_html }}}
					<# } #>
				</p>
			<# } #>

			<# if ( 'onbackorder' == data.stock_status && 'variable' != data.type ) { #>
				{{{ data.stock_html }}}
			<# } #>

			<a href="{{ data.add_to_cart_url }}" data-quantity="1" class="js-wcssb-ajax-add-to-cart button product_type_{{ data.type }} add_to_cart_button" data-product_id="<# if ( 'variable' != data.type ) { #>{{ data.id }}<# } else { #>{{ data.default_variation_id }}<# } #>" data-product_sku="<# if ( 'variable' != data.type ) { #>{{ data.sku }}<# } #>" aria-label="{{ data.add_to_cart_description }}" rel="nofollow" data-wcssb-adding-with-product-id="{{ data.adding_with_product_id }}">
				<?php esc_html_e( 'Add to cart', 'woocommerce' ); ?>
			</a>
		</td>
	</tr>
</script>
