<?php
/**
 * Plugin Name:       Dane Store
 * Description:       امکانات فروشگاه نمونه «دانه»: فیلدهای تسویه حساب ایرانی، مشخصات قهوه (درجه برشت، خاستگاه، طعم)، ارقام فارسی و ابزار راه‌اندازی یک‌کلیکی دموی فروشگاه (ابزارها ← دموی فروشگاه دانه).
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Portfolio project
 * License:           GPL-2.0-or-later
 * Text Domain:       dane-store
 *
 * @package DaneStore
 */

defined( 'ABSPATH' ) || exit;

define( 'DANE_STORE_VERSION', '1.1.0' );
define( 'DANE_STORE_FILE', __FILE__ );
define( 'DANE_STORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'DANE_STORE_URL', plugin_dir_url( __FILE__ ) );

// Declare compatibility: order storage (HPOS) yes; Cart/Checkout blocks no,
// because this store uses the classic checkout (most Iranian payment gateways support only the classic checkout).
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', DANE_STORE_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', DANE_STORE_FILE, false );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>افزونه Dane Store به ووکامرس نیاز دارد. ابتدا ووکامرس را نصب و فعال کنید.</p></div>';
				}
			);
			return;
		}

		require_once DANE_STORE_DIR . 'includes/i18n.php';
		require_once DANE_STORE_DIR . 'includes/product-meta.php';
		require_once DANE_STORE_DIR . 'includes/checkout.php';
		require_once DANE_STORE_DIR . 'includes/setup.php';
		require_once DANE_STORE_DIR . 'includes/photos.php';
	}
);
