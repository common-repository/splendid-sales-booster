<?php
/**
 * Single product Splendid Sales Booster cross-sells section.
 *
 * @package Splendid\SalesBooster
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( $cross_sells_section_title ) : ?>
	<h3 class="wcssb-cross-sells-section-title">
		<?php echo esc_html( $cross_sells_section_title ); ?>
	</h3>
<?php endif; ?>

<table cellspacing="0" class="shop_table wcssb-cross-sells">
	<tbody>
		<?php
		foreach ( $cross_sell_products as $cross_sell_product ) :
			?>
			<tr class="js-wcssb-cross-sell-item wcssb-cross-sell-item">
				<td class="wcssb-cross-sell-item-cb">
					<label>
						<input type="checkbox" name="wcssb-add-to-cart[]" id="wcssb-add-to-cart-<?php echo esc_attr( $cross_sell_product['id'] ); ?>" value="<?php echo 'variable' !== $cross_sell_product['type'] ? esc_attr( $cross_sell_product['id'] ) : esc_attr( $cross_sell_product['default_variation_id'] ); ?>">
						<span class="screen-reader-text">
						<?php
							/* translators: %s: product name */
							echo esc_html( sprintf( __( 'Add also &ldquo;%s&rdquo; to cart', 'splendid-sales-booster' ), $cross_sell_product['title'] ) );
						?>
						</span>
					</label>
				</td>

				<?php if ( 'no' !== get_option( 'wcssb_show_images' ) ) : ?>
					<td class="wcssb-cross-sell-item-image">
						<?php
						if ( ! $cross_sell_product['permalink'] ) :
							echo wp_kses_post( $cross_sell_product['image_html'] );
						else :
							?>
							<a target="_blank" href="<?php echo esc_url( $cross_sell_product['permalink'] ); ?>">
								<?php echo wp_kses_post( $cross_sell_product['image_html'] ); ?>
							</a>
						<?php endif; ?>
					</td>
				<?php endif; ?>

				<td class="js-wcssb-cross-sell-item-product wcssb-cross-sell-item-product">
					<?php if ( $cross_sell_product['cta'] ) : ?>
						<h4 class="wcssb-cross-sell-item-cta">
							<?php echo esc_html( $cross_sell_product['cta'] ); ?>
						</h4>
					<?php endif; ?>

					<?php if ( ! $cross_sell_product['permalink'] ) : ?>
						<span class="wcssb-cross-sell-item-name">
					<?php else : ?>
						<a class="wcssb-cross-sell-item-name" target="_blank" href="<?php echo esc_url( $cross_sell_product['permalink'] ); ?>">
						<?php
					endif;

						echo esc_html( $cross_sell_product['title'] );

					if ( ! $cross_sell_product['permalink'] ) :
						?>
						</span>
					<?php else : ?>

							<svg class="wcssb-cross-sell-item-name-icon" xmlns="http://www.w3.org/2000/svg" version="1.1" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M9 3h8v8l-2-1V6.92l-5.6 5.59-1.41-1.41L14.08 5H10zm3 12v-3l2-2v7H3V6h8L9 8H5v7h7z"/></svg>

						</a>
					<?php endif; ?>

					<?php
					if ( ! empty( $cross_sell_product['formatted_attributes'] ) ) :
						?>
						<dl class="variation wcssb-cross-sell-item-variation">
						<?php foreach ( $cross_sell_product['formatted_attributes'] as $attribute ) : ?>
								<dt class="<?php echo esc_attr( $attribute['html_class'] ); ?>"><?php echo esc_html( $attribute['name'] ); ?>:</dt>
								<dd class="<?php echo esc_attr( $attribute['html_class'] ); ?>"><?php echo wp_kses_post( $attribute['options_html'] ); ?></dd>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>

					<?php if ( 'variable' === $cross_sell_product['type'] ) : ?>
						<div class="js-wcssb-variations-form wcssb-variations-form" data-product_variations="<?php echo $cross_sell_product['variations_json']; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
							<table class="js-wcssb-variations wcssb-variations" cellspacing="0" role="presentation">
								<tbody>
									<?php foreach ( $cross_sell_product['variations_attributes'] as $attribute_i => $attribute ) : ?>
										<tr>
											<th><label for="<?php echo esc_attr( $attribute['select_id'] ); ?>"><?php echo esc_html( $attribute['label'] ); ?></label></th>
											<td>
												<?php
												echo wp_kses( $attribute['select_html'], 'wcssb_dropdown_variation_attribute_options' );

												if ( ( count( $cross_sell_product['variations_attributes'] ) - 1 ) === $attribute_i ) :
													?>
													<div class="js-wcssb-reset-variations wcssb-reset-variations <?php echo $cross_sell_product['show_reset_link'] ? '' : 'wcssb-hidden'; ?>">
														<a href="#"><?php esc_html_e( 'Clear', 'splendid-sales-booster' ); ?></a>
													</div>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>

					<?php
					if ( $cross_sell_product['discounted_price_html'] ) :
						?>
						<p class="price js-wcssb-cross-sell-item-price wcssb-cross-sell-item-price" <?php echo 'variable' !== $cross_sell_product['type'] ? '' : 'data-wcssb_o_content="' . esc_attr( wp_kses_post( $cross_sell_product['discounted_price_html'] ) ) . '"'; ?>>
						<?php
						if ( 'variable' !== $cross_sell_product['type'] ) {
							echo wp_kses_post( $cross_sell_product['discounted_price_html'] );
						} else {
							echo wp_kses_post( $cross_sell_product['default_discounted_price_html'] );
						}
						?>
						</p>
					<?php endif; ?>

					<?php if ( 'onbackorder' === $cross_sell_product['stock_status'] && 'variable' !== $cross_sell_product['type'] ) : ?>
						<?php echo wp_kses_post( $cross_sell_product['stock_html'] ); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
