<?php
/**
 * Iranian checkout: fields in the order Iranians expect, Persian labels,
 * 10-digit postcode and 11-digit mobile validation (Persian digits accepted), optional email.
 *
 * @package DaneStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hide the country row when the store sells to a single country (it can only be Iran).
 */
function dane_country_row_class() {
	$single = function_exists( 'WC' ) && WC()->countries && 1 === count( WC()->countries->get_allowed_countries() );
	return $single ? array( 'form-row-wide', 'dane-country-hidden' ) : array( 'form-row-wide' );
}

/**
 * Merge field settings while keeping WooCommerce's own CSS classes (address-field, update_totals_on_change).
 * Those classes drive the AJAX recalculation of shipping and payment when the province changes,
 * so only the layout classes (form-row-first/last/wide) are replaced.
 *
 * @param array $field    Existing field.
 * @param array $settings Our settings.
 */
function dane_merge_field( $field, $settings ) {
	if ( isset( $settings['class'] ) ) {
		$keep              = array_diff( isset( $field['class'] ) ? (array) $field['class'] : array(), array( 'form-row-first', 'form-row-last', 'form-row-wide' ) );
		$settings['class'] = array_values( array_unique( array_merge( $keep, $settings['class'] ) ) );
	}
	return array_merge( $field, $settings );
}

/**
 * Shared address field settings: label, priority, layout class.
 */
function dane_address_layout() {
	return array(
		'first_name' => array( 'label' => 'نام', 'priority' => 10, 'class' => array( 'form-row-first' ) ),
		'last_name'  => array( 'label' => 'نام خانوادگی', 'priority' => 20, 'class' => array( 'form-row-last' ) ),
		'country'    => array( 'label' => 'کشور', 'priority' => 30, 'class' => dane_country_row_class() ),
		'state'      => array( 'label' => 'استان', 'priority' => 40, 'class' => array( 'form-row-first' ), 'required' => true ),
		'city'       => array( 'label' => 'شهر', 'priority' => 50, 'class' => array( 'form-row-last' ), 'required' => true ),
		'address_1'  => array(
			'label'       => 'نشانی کامل',
			'placeholder' => 'خیابان، کوچه، پلاک، واحد',
			'priority'    => 60,
			'class'       => array( 'form-row-wide' ),
			'required'    => true,
		),
		'postcode'   => array(
			'label'             => 'کد پستی',
			'placeholder'       => '۱۰ رقم، بدون خط تیره',
			'priority'          => 70,
			'class'             => array( 'form-row-first' ),
			'required'          => true,
			'custom_attributes' => array( 'inputmode' => 'numeric', 'dir' => 'ltr', 'maxlength' => '12' ),
		),
		// WooCommerce 8.6+ treats phone as an address field and reorders it in JavaScript, so it is set here.
		'phone'      => array(
			'label'             => 'شماره موبایل',
			'placeholder'       => '09xxxxxxxxx',
			'priority'          => 80,
			'class'             => array( 'form-row-last' ),
			'required'          => true,
			'custom_attributes' => array( 'inputmode' => 'numeric', 'dir' => 'ltr' ),
		),
	);
}

add_filter(
	'woocommerce_default_address_fields',
	static function ( $fields ) {
		unset( $fields['company'], $fields['address_2'] );
		foreach ( dane_address_layout() as $key => $settings ) {
			if ( isset( $fields[ $key ] ) ) {
				$fields[ $key ] = dane_merge_field( $fields[ $key ], $settings );
			}
		}
		return $fields;
	},
	20
);

// WooCommerce reorders and relabels fields per country in JavaScript. Make Iran match the layout above.
add_filter(
	'woocommerce_get_country_locale',
	static function ( $locale ) {
		$ir = isset( $locale['IR'] ) ? $locale['IR'] : array();
		foreach ( dane_address_layout() as $key => $settings ) {
			$ir[ $key ] = array_merge(
				isset( $ir[ $key ] ) ? $ir[ $key ] : array(),
				array_intersect_key( $settings, array_flip( array( 'label', 'priority', 'required', 'placeholder' ) ) )
			);
		}
		$ir['postcode']['hidden']  = false;
		$ir['address_2']['hidden'] = true;
		$ir['company']['hidden']   = true;
		$locale['IR']              = $ir;
		return $locale;
	},
	20
);

add_filter(
	'woocommerce_billing_fields',
	static function ( $fields ) {
		if ( isset( $fields['billing_phone'] ) ) {
			$layout                  = dane_address_layout();
			$fields['billing_phone'] = dane_merge_field( $fields['billing_phone'], $layout['phone'] );
		}
		if ( isset( $fields['billing_email'] ) ) {
			$fields['billing_email'] = dane_merge_field(
				$fields['billing_email'],
				array(
					'label'       => 'ایمیل',
					'required'    => false,
					'priority'    => 90,
					'class'       => array( 'form-row-wide' ),
					'description' => 'برای دریافت رسید سفارش؛ اختیاری.',
				)
			);
		}
		return $fields;
	},
	20
);

add_filter(
	'woocommerce_checkout_fields',
	static function ( $fields ) {
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = 'یادداشت سفارش';
			$fields['order']['order_comments']['placeholder'] = 'مثلاً زمان مناسب تحویل یا توضیح برای پیک';
		}
		return $fields;
	},
	20
);

/* ---------- Normalise and validate ---------- */

/**
 * Persian digits → Latin, remove spaces and dashes.
 *
 * @param string $value Posted value.
 */
function dane_normalize_number_field( $value ) {
	return preg_replace( '/[\s\-]+/u', '', dane_latin_digits( $value ) );
}

foreach ( array( 'billing_phone', 'billing_postcode', 'shipping_postcode' ) as $dane_field ) {
	add_filter( 'woocommerce_process_checkout_field_' . $dane_field, 'dane_normalize_number_field' );
}

add_action(
	'woocommerce_after_checkout_validation',
	static function ( $data, $errors ) {
		if ( ! empty( $data['billing_phone'] ) && ! preg_match( '/^09\d{9}$/', $data['billing_phone'] ) ) {
			$errors->add( 'validation', '<strong>شماره موبایل</strong> باید ۱۱ رقم باشد و با ۰۹ شروع شود.', array( 'id' => 'billing_phone' ) );
		}
		$postcodes = array( 'billing_postcode' => 'کد پستی' );
		if ( ! empty( $data['ship_to_different_address'] ) ) {
			$postcodes['shipping_postcode'] = 'کد پستی نشانی ارسال';
		}
		foreach ( $postcodes as $key => $label ) {
			if ( ! empty( $data[ $key ] ) && ! preg_match( '/^\d{10}$/', $data[ $key ] ) ) {
				$errors->add( 'validation', '<strong>' . esc_html( $label ) . '</strong> باید دقیقاً ۱۰ رقم باشد.', array( 'id' => $key ) );
			}
		}
	},
	10,
	2
);

// Iranian postcodes are checked above with a Persian message; skip WooCommerce's generic check to avoid a duplicate error.
add_filter(
	'woocommerce_validate_postcode',
	static function ( $valid, $postcode, $country ) {
		return 'IR' === $country ? true : $valid;
	},
	10,
	3
);

// Postcodes are shown without WooCommerce's dash formatting.
add_filter(
	'woocommerce_format_postcode',
	static function ( $postcode, $country ) {
		return 'IR' === $country ? dane_normalize_number_field( $postcode ) : $postcode;
	},
	10,
	2
);

// When an order qualifies for free shipping, don't also offer the paid method of the same zone.
add_filter(
	'woocommerce_package_rates',
	static function ( $rates ) {
		$free = array_filter(
			$rates,
			static function ( $rate ) {
				return 'free_shipping' === $rate->get_method_id();
			}
		);
		return $free ? $free : $rates;
	},
	20
);
