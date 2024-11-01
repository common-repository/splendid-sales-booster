<?php
/**
 * Splendid Sales Booster cross-sells Add To Cart drawer.
 *
 * @package Splendid\SalesBooster
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="js-wcssb-drawer wcssb-drawer wcssb-add-to-cart-drawer woocommerce" data-wcssb-drawer="add_to_cart" style="display: none;">
	<div class="js-wcssb-drawer-header wcssb-drawer-header">
		<span class="js-wcssb-drawer-close wcssb-drawer-close">
			<svg class="wcssb-drawer-close-icon" xmlns="http://www.w3.org/2000/svg" version="1.1" width="17" height="17" viewBox="0 0 17 17" aria-hidden="true"><path d="M2.267 16.565l5.917-5.9 5.865 5.865 2.267-2.232-5.883-5.883 5.623-5.623L13.773.543 8.167 6.149 2.526.509.277 2.758l5.641 5.641L0 14.333z"/></svg>
		</span>

		<div class="wcssb-drawer-accent"></div>

		<div class="js-wcssb-drawer-title wcssb-drawer-title"></div>
	</div>

	<div class="js-wcssb-drawer-section wcssb-drawer-section js-wcssb-allow-scroll">
		<div class="wcssb-drawer-section-placeholder">
			<div class="wcssb-placeholder-mask section-title-first-right"></div>
			<div class="wcssb-placeholder-mask section-title-second"></div>
			<div class="wcssb-placeholder-mask product-first"></div>
			<div class="wcssb-placeholder-mask product-second-middle"></div>
			<div class="wcssb-placeholder-mask product-second-right"></div>
			<div class="wcssb-placeholder-mask product-third"></div>
			<div class="wcssb-placeholder-mask product-fourth-middle"></div>
			<div class="wcssb-placeholder-mask product-fourth-right"></div>
			<div class="wcssb-placeholder-mask product-fifth"></div>
		</div>

		<div class="js-wcssb-drawer-section-title wcssb-drawer-section-title">
			<h3 class="wcssb-cross-sells-section-title"></h3>
		</div>

		<table cellspacing="0" class="js-wcssb-drawer-cross-sells wcssb-drawer-cross-sells shop_table wcssb-cross-sells">
			<tbody></tbody>
		</table>
	</div>

	<?php do_action( 'wcssb_after_add_to_cart_drawer_section' ); ?>
</div>
