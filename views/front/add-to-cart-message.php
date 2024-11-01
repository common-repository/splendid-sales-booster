<?php
/**
 * Custom Add to cart message notices.
 *
 * @package Splendid\SalesBooster
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $notices ) {
	return;
}

$allowed_tags = array_replace_recursive(
	wp_kses_allowed_html( 'post' ),
	array(
		'a' => array(
			'tabindex' => true,
		),
	)
);

foreach ( $notices as $notice ) {
	echo wp_kses( $notice['notice'], $allowed_tags );
}
