<?php
/**
	Plugin Name: Splendid Sales Booster for WooCommerce
	Plugin URI: https://splendidplugins.com/sales-booster/
	Description: The easiest to use plugin for cross-selling.
	Version: 1.3.0
	Author: GeekRoom.agency
	Author URI: https://geekroom.agency
	Text Domain: splendid-sales-booster
	Domain Path: /lang/
	Requires at least: 5.3
	WC requires at least: 4.0
	WC tested up to: 9.3
	Requires PHP: 7.4

	@package \Splendid\SalesBooster

	Copyright 2023 GeekRoom.pl s.c.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$plugin_version           = '1.3.0';
$plugin_release_timestamp = '2023-03-29 13:58';

$plugin_name        = 'Splendid Sales Booster for WooCommerce';
$plugin_class_name  = '\Splendid\SalesBooster\Plugin';
$plugin_text_domain = 'splendid-sales-booster';
$plugin_slug        = 'splendid-sales-booster';
$plugin_file        = __FILE__;
$plugin_dir         = __DIR__;

$requirements = array(
	'php' => '7.4',
	'wp'  => '5.3',
	'wc'  => '4.0',
);

/**
 * Declare plugin compatibility with WooCommerce features.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

require __DIR__ . '/src/plugin-init.php';
