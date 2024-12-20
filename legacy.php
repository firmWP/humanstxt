<?php
/**
 * Humanstxt legacy functions
 *
 * @package Humanstxt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP filter callback for 'admin_body_class'. Adds 'rtl' to
 * the body classes list, if is_rtl() is TRUE.
 *
 * @since 1.1.1
 *
 * @param string $classes The classes list.
 * @return string $classes The modified classes list.
 */
function humanstxt_admin_body_class( $classes ) {
	if ( is_rtl() && strpos( $classes, 'rtl' ) === false ) {
		$classes .= ' rtl ';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'humanstxt_admin_body_class' );
