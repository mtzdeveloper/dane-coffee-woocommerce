<?php
/**
 * Coffee details on products: roast level (1–5), origin, tasting notes.
 * Editable by the store owner in the product editor; shown on product cards and the product page.
 *
 * @package DaneStore
 */

defined( 'ABSPATH' ) || exit;

const DANE_META_ROAST  = '_dane_roast';
const DANE_META_ORIGIN = '_dane_origin';
const DANE_META_NOTES  = '_dane_notes';

/**
 * Roast level labels.
 */
function dane_roast_labels() {
	return array(
		''  => 'بدون درجه برشت (برای دمی‌افزار و لوازم)',
		'1' => '۱ — خیلی روشن',
		'2' => '۲ — روشن',
		'3' => '۳ — متوسط',
		'4' => '۴ — متوسط رو به تیره',
		'5' => '۵ — تیره',
	);
}

/* ---------- Product editor fields ---------- */

add_action(
	'woocommerce_product_options_general_product_data',
	static function () {
		echo '<div class="options_group dane-coffee-fields">';
		woocommerce_wp_select(
			array(
				'id'          => DANE_META_ROAST,
				'label'       => 'درجه برشت',
				'options'     => dane_roast_labels(),
				'desc_tip'    => true,
				'description' => 'روی کارت محصول به‌صورت پنج نقطه نمایش داده می‌شود.',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => DANE_META_ORIGIN,
				'label'       => 'خاستگاه',
				'placeholder' => 'مثلاً: اتیوپی، یرگاچفه',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => DANE_META_NOTES,
				'label'       => 'طعم‌ها',
				'placeholder' => 'مثلاً: کارامل، سیب قرمز، شکلات شیری',
				'desc_tip'    => true,
				'description' => 'با ویرگول فارسی (،) جدا کنید.',
			)
		);
		echo '</div>';
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	static function ( WC_Product $product ) {
		// Nonce is verified by WooCommerce before this action runs.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$roast = isset( $_POST[ DANE_META_ROAST ] ) ? sanitize_text_field( wp_unslash( $_POST[ DANE_META_ROAST ] ) ) : '';
		$roast = in_array( $roast, array( '1', '2', '3', '4', '5' ), true ) ? $roast : '';
		$product->update_meta_data( DANE_META_ROAST, $roast );
		foreach ( array( DANE_META_ORIGIN, DANE_META_NOTES ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$product->update_meta_data( $key, $value );
		}
		// phpcs:enable
	}
);

/* ---------- Storefront output ---------- */

/**
 * Roast meter markup.
 *
 * @param int    $level Roast level 1–5.
 * @param string $size  "s" for cards, "l" for the product page.
 */
function dane_roast_meter( $level, $size = 's' ) {
	$level = (int) $level;
	if ( $level < 1 || $level > 5 ) {
		return '';
	}
	$names = array( 1 => 'خیلی روشن', 2 => 'روشن', 3 => 'متوسط', 4 => 'متوسط رو به تیره', 5 => 'تیره' );
	$dots  = '';
	for ( $i = 1; $i <= 5; $i++ ) {
		$dots .= '<span class="dane-roast__dot' . ( $i <= $level ? ' is-on' : '' ) . '"></span>';
	}
	return sprintf(
		'<span class="dane-roast dane-roast--%1$s" role="img" aria-label="%2$s"><span class="dane-roast__dots" aria-hidden="true">%3$s</span><span class="dane-roast__label">%4$s</span></span>',
		esc_attr( $size ),
		esc_attr( sprintf( 'درجه برشت %s از ۵: %s', dane_fa_digits( $level ), $names[ $level ] ) ),
		$dots,
		esc_html( 'برشت ' . $names[ $level ] )
	);
}

// Product card: origin + roast meter under the title.
add_action(
	'woocommerce_after_shop_loop_item_title',
	static function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$origin = $product->get_meta( DANE_META_ORIGIN );
		$roast  = $product->get_meta( DANE_META_ROAST );
		if ( ! $origin && ! $roast ) {
			return;
		}
		echo '<div class="dane-card-meta">';
		if ( $origin ) {
			echo '<span class="dane-card-meta__origin">' . esc_html( $origin ) . '</span>';
		}
		echo dane_roast_meter( $roast, 's' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in function.
		echo '</div>';
	},
	4
);

// Product page: coffee profile box under the short description.
add_action(
	'woocommerce_single_product_summary',
	static function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$origin = $product->get_meta( DANE_META_ORIGIN );
		$roast  = $product->get_meta( DANE_META_ROAST );
		$notes  = $product->get_meta( DANE_META_NOTES );
		if ( ! $origin && ! $roast && ! $notes ) {
			return;
		}
		echo '<dl class="dane-profile">';
		if ( $origin ) {
			echo '<div><dt>خاستگاه</dt><dd>' . esc_html( $origin ) . '</dd></div>';
		}
		if ( $roast ) {
			echo '<div><dt>درجه برشت</dt><dd>' . dane_roast_meter( $roast, 'l' ) . '</dd></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( $notes ) {
			echo '<div><dt>طعم‌ها</dt><dd>' . esc_html( $notes ) . '</dd></div>';
		}
		echo '</dl>';
	},
	22
);

// A coffee with every variation out of stock: no options form, just the stock message.
add_action(
	'woocommerce_single_product_summary',
	static function () {
		global $product;
		if ( $product instanceof WC_Product && $product->is_type( 'variable' ) && ! $product->is_in_stock() ) {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			echo '<p class="stock out-of-stock">' . esc_html( apply_filters( 'woocommerce_out_of_stock_message', '' ) ) . '</p>';
		}
	},
	29
);

// Show the stock state as a clear sentence on products that are out of stock.
add_filter(
	'woocommerce_out_of_stock_message',
	static function () {
		return 'این محصول فعلاً ناموجود است. پس از برشت بعدی دوباره موجود می‌شود.';
	}
);

// Variable products: "from <lowest price>" instead of a min–max range.
add_filter(
	'woocommerce_variable_price_html',
	static function ( $html, $product ) {
		$min = $product->get_variation_price( 'min', true );
		$max = $product->get_variation_price( 'max', true );
		if ( '' === $min || $min === $max ) {
			return $html;
		}
		$regular_min = $product->get_variation_regular_price( 'min', true );
		$price       = $product->is_on_sale() && $regular_min > $min ? wc_format_sale_price( $regular_min, $min ) : wc_price( $min );
		return '<span class="dane-price-from">از</span> ' . $price;
	},
	10,
	2
);
