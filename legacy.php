<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP filter callback for 'admin_body_class'. Adds 'rtl' to
 * the body classes list, if is_rtl() is TRUE.
 *
 * @since 1.1.1
 *
 * @param string $classes
 * @return string $classes
 */
function humanstxt_admin_body_class( $classes ) {
	if ( is_rtl() && strpos( $classes, 'rtl' ) === false ) {
		$classes .= ' rtl ';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'humanstxt_admin_body_class' );

if ( !function_exists( 'esc_textarea' ) ) :
/**
 * Escaping for textarea values introduced in WordPress 3.1.
 * Source: http://codex.wordpress.org/Function_Reference/esc_textarea
 */
function esc_textarea( $text ) {
	$safe_text = htmlspecialchars( $text, ENT_QUOTES );
	return apply_filters( 'esc_textarea', $safe_text, $text );
}
endif;

?>