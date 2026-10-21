<?php
/**
 * Persian presentation helpers:
 * - right-to-left direction for Persian sites, even if the language pack has not been downloaded yet
 * - Persian digits in prices
 * - fallback Persian strings for the storefront, used only when the WooCommerce Persian language pack is missing
 *
 * @package DaneStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the site language is Persian.
 */
function dane_is_persian() {
	return 0 === strpos( determine_locale(), 'fa' );
}

/**
 * Convert Latin digits to Persian digits.
 *
 * @param string $text Text.
 */
function dane_fa_digits( $text ) {
	return strtr( (string) $text, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

/**
 * Convert Latin digits to Persian in HTML: only visible text, never inside tags or entities like &#x062A;.
 *
 * @param string $html HTML.
 */
function dane_fa_digits_html( $html ) {
	$parts = preg_split( '/(<[^>]*>)/', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( '' === $part || '<' === $part[0] ) {
			continue;
		}
		$parts[ $i ] = preg_replace_callback(
			'/&#?[a-z0-9]+;|\d+/i',
			static function ( $m ) {
				return '&' === $m[0][0] ? $m[0] : dane_fa_digits( $m[0] );
			},
			$part
		);
	}
	return implode( '', $parts );
}

/**
 * Convert Persian and Arabic digits to Latin digits (for validating user input).
 *
 * @param string $text Text.
 */
function dane_latin_digits( $text ) {
	return strtr(
		(string) $text,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);
}

// Right-to-left even when the fa_IR language files are not installed yet.
add_action(
	'init',
	static function () {
		global $wp_locale;
		if ( dane_is_persian() && $wp_locale instanceof WP_Locale && 'rtl' !== $wp_locale->text_direction ) {
			$wp_locale->text_direction = 'rtl';
		}
	},
	0
);

// Persian digits in prices. Only HTML output: stored values stay numeric.
add_filter(
	'wc_price',
	static function ( $html ) {
		return dane_is_persian() ? dane_fa_digits_html( $html ) : $html;
	}
);

/**
 * Fallback storefront strings. Applied only when WooCommerce returned the English original,
 * so an installed language pack always wins.
 */
function dane_fallback_strings() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array(
		'Add to cart'                                              => 'افزودن به سبد خرید',
		'Select options'                                           => 'انتخاب گزینه‌ها',
		'Read more'                                                => 'مشاهده محصول',
		'Sale!'                                                    => 'تخفیف',
		'Out of stock'                                             => 'ناموجود',
		'In stock'                                                 => 'موجود',
		'%s in stock'                                              => '%s عدد در انبار',
		'View cart'                                                => 'مشاهده سبد خرید',
		'Continue shopping'                                        => 'ادامه خرید',
		'Product'                                                  => 'محصول',
		'Price'                                                    => 'قیمت',
		'Quantity'                                                 => 'تعداد',
		'Product quantity'                                         => 'تعداد محصول',
		'Subtotal'                                                 => 'جمع جزء',
		'Subtotal:'                                                => 'جمع جزء:',
		'Total'                                                    => 'مبلغ کل',
		'Total:'                                                   => 'مبلغ کل:',
		'Shipping'                                                 => 'ارسال',
		'Shipping:'                                                => 'ارسال:',
		'Remove this item'                                         => 'حذف این محصول',
		'Coupon:'                                                  => 'کد تخفیف:',
		'Coupon code'                                              => 'کد تخفیف',
		'Apply coupon'                                             => 'اعمال کد تخفیف',
		'Update cart'                                              => 'به‌روزرسانی سبد',
		'Cart totals'                                              => 'جمع سبد خرید',
		'Proceed to checkout'                                      => 'ادامه و تسویه حساب',
		'Shipping to %s.'                                          => 'ارسال به %s.',
		'Change address'                                           => 'تغییر نشانی',
		'Calculate shipping'                                       => 'محاسبه هزینه ارسال',
		'Update'                                                   => 'به‌روزرسانی',
		'Free shipping'                                            => 'ارسال رایگان',
		'Coupon code applied successfully.'                        => 'کد تخفیف اعمال شد.',
		'Coupon code removed successfully.'                        => 'کد تخفیف حذف شد.',
		'[Remove]'                                                 => '[حذف]',
		'Your cart is currently empty.'                            => 'سبد خرید شما خالی است.',
		'Return to shop'                                           => 'بازگشت به فروشگاه',
		'Cart updated.'                                            => 'سبد خرید به‌روز شد.',
		'Billing details'                                          => 'اطلاعات گیرنده',
		'Ship to a different address?'                             => 'ارسال به نشانی دیگر؟',
		'Additional information'                                   => 'توضیحات تکمیلی',
		'Order notes'                                              => 'یادداشت سفارش',
		'Your order'                                               => 'سفارش شما',
		'Place order'                                              => 'ثبت سفارش',
		'Have a coupon?'                                           => 'کد تخفیف دارید؟',
		'Click here to enter your code'                            => 'برای وارد کردن کد اینجا را بزنید',
		'If you have a coupon code, please apply it below.'       => 'اگر کد تخفیف دارید، آن را در کادر زیر وارد کنید.',
		'optional'                                                 => 'اختیاری',
		'required'                                                 => 'الزامی',
		'%s is a required field.'                                  => '%s را وارد کنید.',
		'Please read and accept the terms and conditions to proceed with your order.' => 'برای ثبت سفارش، قوانین و مقررات را بخوانید و بپذیرید.',
		'Thank you. Your order has been received.'                 => 'سپاسگزاریم. سفارش شما ثبت شد.',
		'Order number:'                                            => 'شماره سفارش:',
		'Date:'                                                    => 'تاریخ:',
		'Email:'                                                   => 'ایمیل:',
		'Payment method:'                                          => 'روش پرداخت:',
		'Order details'                                            => 'جزئیات سفارش',
		'Billing address'                                          => 'نشانی صورتحساب',
		'Shipping address'                                         => 'نشانی ارسال',
		'via %s'                                                   => 'از طریق %s',
		'Note:'                                                    => 'یادداشت:',
		'Description'                                              => 'توضیحات',
		'Related products'                                         => 'محصولات مرتبط',
		'You may also like&hellip;'                                => 'شاید این‌ها را هم بپسندید',
		'SKU:'                                                     => 'شناسه محصول:',
		'N/A'                                                      => 'ندارد',
		'Choose an option'                                         => 'یک گزینه انتخاب کنید',
		'Clear'                                                    => 'پاک کردن',
		'Sorry, this product is unavailable. Please choose a different combination.' => 'این ترکیب موجود نیست. گزینه دیگری انتخاب کنید.',
		'Please select some product options before adding this product to your cart.' => 'پیش از افزودن به سبد، وزن و نوع آسیاب را انتخاب کنید.',
		'Default sorting'                                          => 'مرتب‌سازی پیش‌فرض',
		'Sort by popularity'                                       => 'پرفروش‌ترین',
		'Sort by average rating'                                   => 'بیشترین امتیاز',
		'Sort by latest'                                           => 'جدیدترین',
		'Sort by price: low to high'                               => 'ارزان‌ترین',
		'Sort by price: high to low'                               => 'گران‌ترین',
		'Shop order'                                               => 'ترتیب فروشگاه',
		'No products were found matching your selection.'         => 'محصولی با این مشخصات پیدا نشد.',
		'Login'                                                    => 'ورود',
		'Username or email address'                                => 'نام کاربری یا ایمیل',
		'Password'                                                 => 'رمز عبور',
		'Remember me'                                              => 'مرا به خاطر بسپار',
		'Log in'                                                   => 'ورود',
		'Lost your password?'                                      => 'رمز عبور را فراموش کرده‌اید؟',
		'Dashboard'                                                => 'پیشخوان',
		'Orders'                                                   => 'سفارش‌ها',
		'Addresses'                                                => 'نشانی‌ها',
		'Account details'                                          => 'جزئیات حساب',
		'Log out'                                                  => 'خروج',
		'Cash on delivery'                                         => 'پرداخت در محل',
		'Direct bank transfer'                                     => 'کارت به کارت',
		'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.' => 'برای این نشانی روش پرداختی فعال نیست. لطفاً با پشتیبانی تماس بگیرید.',
		'Order received'                                           => 'سفارش ثبت شد',
		'Search products&hellip;'                                  => 'جست‌وجوی محصول…',
		'Search'                                                   => 'جست‌وجو',
		'Price range: %1$s through %2$s'                           => 'بازه قیمت: %1$s تا %2$s',
		'Current price is: %s.'                                    => 'قیمت فعلی: %s.',
		'Original price was: %s.'                                  => 'قیمت قبلی: %s.',
		'Select options for &ldquo;%s&rdquo;'                      => 'انتخاب گزینه‌های «%s»',
		'This product has multiple variants. The options may be chosen on the product page' => 'این محصول چند گزینه دارد؛ گزینه‌ها را در صفحه محصول انتخاب کنید',
		'%s quantity'                                              => 'تعداد %s',
		'Remove item'                                              => 'حذف کالا',
		'Thumbnail image'                                          => 'تصویر کالا',
		'Coupon: %s'                                               => 'کد تخفیف: %s',
		'Select a country / region&hellip;'                        => 'انتخاب کشور…',
		'Select an option&hellip;'                                 => 'انتخاب کنید…',
		'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.' => 'جاوااسکریپت مرورگر شما خاموش است. پیش از ثبت سفارش دکمه %1$sبه‌روزرسانی مبلغ%2$s را بزنید تا مبلغ نهایی درست محاسبه شود.',
		'Update totals'                                            => 'به‌روزرسانی مبلغ',
		'The following problems were found:'                       => 'این موارد را اصلاح کنید:',
		'Required'                                                 => 'الزامی',
		'Phone'                                                    => 'شماره موبایل',
		'Price'                                                    => 'قیمت',
		'terms and conditions'                                     => 'قوانین و مقررات',
		'Invalid billing email address'                            => 'ایمیل وارد‌شده معتبر نیست.',
	);
	return $map;
}

/**
 * Context strings: key is "context|text".
 */
function dane_fallback_context_strings() {
	return array(
		'checkout-validation|Billing %s' => '%s',
		'checkout-validation|Shipping %s' => '%s (ارسال)',
		'breadcrumb|Home'                => 'خانه',
		'with first and last result|Showing %1$d&ndash;%2$d of %3$d results' => 'نمایش %1$d تا %2$d از %3$d محصول',
		'Page title|Shop'                => 'فروشگاه',
		'Page title|Cart'                => 'سبد خرید',
		'Page title|Checkout'            => 'تسویه حساب',
		'flash|Sale!'                    => 'تخفیف',
		'shipping packages|Shipment'     => 'ارسال',
		'shipping packages|Shipment %d'  => 'مرسوله %d',
	);
}

if ( dane_is_persian() ) {
	add_filter(
		'gettext_woocommerce',
		static function ( $translation, $text ) {
			if ( $translation === $text ) {
				$map = dane_fallback_strings();
				if ( isset( $map[ $text ] ) ) {
					return $map[ $text ];
				}
			}
			return $translation;
		},
		10,
		2
	);

	add_filter(
		'gettext_with_context_woocommerce',
		static function ( $translation, $text, $context ) {
			if ( $translation === $text ) {
				$map = dane_fallback_context_strings();
				$key = $context . '|' . $text;
				if ( isset( $map[ $key ] ) ) {
					return $map[ $key ];
				}
				$plain = dane_fallback_strings();
				if ( isset( $plain[ $text ] ) ) {
					return $plain[ $text ];
				}
			}
			return $translation;
		},
		10,
		3
	);

	add_filter(
		'ngettext_woocommerce',
		static function ( $translation, $single, $plural, $number ) {
			if ( $translation !== $single && $translation !== $plural ) {
				return $translation;
			}
			$map = array(
				'%s has been added to your cart.' => '«%s» به سبد خرید اضافه شد.',
				'%s have been added to your cart.' => '%s به سبد خرید اضافه شدند.',
				'Showing the single result'       => 'نمایش تنها محصول',
				'Showing all %d results'          => 'نمایش همه %d محصول',
				'Showing all %1$d result'         => 'نمایش %1$d محصول',
				'Showing all %1$d results'        => 'نمایش همه %1$d محصول',
				'Category:'                       => 'دسته:',
				'Categories:'                     => 'دسته‌ها:',
				'Tag:'                            => 'برچسب:',
				'Tags:'                           => 'برچسب‌ها:',
			);
			$key = 1 === (int) $number ? $single : $plural;
			return isset( $map[ $key ] ) ? $map[ $key ] : $translation;
		},
		10,
		4
	);

	// Typographic quotes around product names in notices read oddly in Persian.
	add_filter(
		'wc_add_to_cart_message_html',
		static function ( $message ) {
			return str_replace( array( '&ldquo;', '&rdquo;' ), '', $message );
		}
	);
}

if ( dane_is_persian() ) {
	// Province names: WooCommerce ships "Tehran (تهران)"; show only the Persian name.
	add_filter(
		'woocommerce_states',
		static function ( $states ) {
			if ( ! empty( $states['IR'] ) ) {
				foreach ( $states['IR'] as $code => $name ) {
					if ( preg_match( '/\(([^)]+)\)/u', $name, $m ) ) {
						$states['IR'][ $code ] = $m[1];
					}
				}
				asort( $states['IR'] );
			}
			return $states;
		},
		20
	);

	add_filter(
		'woocommerce_countries',
		static function ( $countries ) {
			if ( isset( $countries['IR'] ) && ! preg_match( '/\p{Arabic}/u', $countries['IR'] ) ) {
				$countries['IR'] = 'ایران';
			}
			return $countries;
		},
		20
	);

	add_filter(
		'gettext_default',
		static function ( $translation, $text ) {
			return ( $translation === $text && 'Skip to content' === $text ) ? 'رفتن به محتوا' : $translation;
		},
		10,
		2
	);
}

// Variation names: "اتیوپی یرگاچفه – ۵۰۰ گرم، آسیاب اسپرسو" (Persian comma between attributes).
add_filter(
	'woocommerce_product_variation_title',
	static function ( $title, $product, $base, $suffix ) {
		if ( ! dane_is_persian() || '' === $suffix ) {
			return $title;
		}
		return $base . ' – ' . str_replace( ', ', '، ', $suffix );
	},
	10,
	4
);
