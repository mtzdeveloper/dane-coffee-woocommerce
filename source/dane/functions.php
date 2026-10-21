<?php
/**
 * Dane theme setup.
 *
 * @package Dane
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_editor_style( 'assets/css/store.css' );
	}
);

// Store styles load after WooCommerce's own so they can override it without !important.
add_action(
	'wp_enqueue_scripts',
	static function () {
		$theme = wp_get_theme();
		wp_enqueue_style( 'dane-store', get_theme_file_uri( 'assets/css/store.css' ), array(), $theme->get( 'Version' ) );
	},
	20
);

/**
 * Cart link with item count for the header: [dane_cart_link].
 */
function dane_cart_link() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	$count = WC()->cart->get_cart_contents_count();
	$label = $count ? sprintf( 'سبد خرید، %s کالا', number_format_i18n( $count ) ) : 'سبد خرید خالی است';
	$num   = function_exists( 'dane_fa_digits' ) ? dane_fa_digits( $count ) : $count;
	return sprintf(
		'<a class="dane-cart-link" href="%1$s" aria-label="%2$s"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M6 7h12l-1.2 11.2a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.8L6 7Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 9V6a3 3 0 0 1 6 0v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span class="dane-cart-link__count%3$s">%4$s</span></a>',
		esc_url( wc_get_cart_url() ),
		esc_attr( $label ),
		$count ? '' : ' is-empty',
		esc_html( $num )
	);
}
add_shortcode( 'dane_cart_link', 'dane_cart_link' );

// Keep the header count in sync after AJAX add-to-cart.
add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( $fragments ) {
		$fragments['a.dane-cart-link'] = dane_cart_link();
		return $fragments;
	}
);

// Four products per row on the shop and category pages, twelve per page.
add_filter( 'loop_shop_columns', static fn() => 4 );
add_filter( 'loop_shop_per_page', static fn() => 12 );
add_filter(
	'woocommerce_output_related_products_args',
	static function ( $args ) {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);

// Breadcrumb separator that reads correctly right-to-left.
add_filter(
	'woocommerce_breadcrumb_defaults',
	static function ( $defaults ) {
		$defaults['delimiter'] = '<span class="dane-crumb-sep" aria-hidden="true"> / </span>';
		return $defaults;
	}
);

// Shortcode blocks inside patterns are rendered after WordPress has run do_shortcode() on the template,
// so product shortcodes on the home page would print as plain text. Render them at block level instead.
add_filter(
	'render_block_core/shortcode',
	static function ( $content ) {
		return false !== strpos( $content, '[' ) ? do_shortcode( $content ) : $content;
	}
);

// One continuous page: no default block gap between header, main and footer.
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_add_inline_style( 'dane-store', '.wp-site-blocks > * + * { margin-block-start: 0; }' );
	},
	21
);

// WooCommerce auto-inserts its own account and mini-cart blocks next to the header navigation in block themes.
// The theme has its own cart link, so remove them to avoid two cart icons.
add_filter(
	'hooked_block_types',
	static function ( $hooked ) {
		return array_values( array_diff( (array) $hooked, array( 'woocommerce/customer-account', 'woocommerce/mini-cart' ) ) );
	},
	20
);
