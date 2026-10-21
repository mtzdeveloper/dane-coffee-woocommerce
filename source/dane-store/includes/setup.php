<?php
/**
 * One-click demo setup: Tools → دموی فروشگاه دانه, or `wp dane setup`.
 * Safe to run more than once: existing items (matched by SKU, slug or name) are kept, settings are re-applied.
 *
 * @package DaneStore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo store builder.
 */
class Dane_Setup {

	/**
	 * Log lines shown to the user.
	 *
	 * @var string[]
	 */
	private $log = array();

	/**
	 * Demo data from assets/demo-data.json.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Category term IDs by slug.
	 *
	 * @var int[]
	 */
	private $cats = array();

	/**
	 * Run every step and return the log.
	 *
	 * @param bool $activate_theme Switch to the Dane theme if it is installed.
	 * @return string[]
	 */
	public function run( $activate_theme = true ) {
		$json       = file_get_contents( DANE_STORE_DIR . 'assets/demo-data.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->data = json_decode( $json, true );
		if ( ! is_array( $this->data ) ) {
			return array( 'خطا: فایل داده نمونه خوانده نشد.' );
		}

		wp_raise_memory_limit( 'admin' );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		if ( $activate_theme ) {
			$this->activate_theme();
		}
		$this->store_settings();
		$this->categories();
		$this->attributes();
		$this->products();
		$this->photos();
		$pages = $this->pages();
		$this->woocommerce_pages( $pages );
		$this->shipping_and_payments();
		$this->coupon();
		$this->navigation();
		$this->cleanup_defaults();

		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules( false );
		update_option( 'dane_demo_version', DANE_STORE_VERSION );
		$this->log[] = 'نشانی‌ها (پیوند یکتا) روی «نام نوشته» تنظیم شد.';
		$this->log[] = 'راه‌اندازی کامل شد.';
		return $this->log;
	}

	/* ---------- Steps ---------- */

	private function activate_theme() {
		$theme = wp_get_theme( 'dane' );
		if ( $theme->exists() && 'dane' !== get_stylesheet() ) {
			switch_theme( 'dane' );
			$this->log[] = 'پوسته «دانه» فعال شد.';
		} elseif ( ! $theme->exists() ) {
			$this->log[] = 'هشدار: پوسته «دانه» نصب نیست؛ ظاهر فروشگاه با پوسته فعلی نمایش داده می‌شود.';
		}
	}

	private function store_settings() {
		$options = array(
			'blogname'                                => 'قهوه دانه',
			'blogdescription'                         => 'قهوه تازه‌برشته، هر هفته',
			'timezone_string'                         => 'Asia/Tehran',
			'woocommerce_coming_soon'                 => 'no',
			'woocommerce_store_pages_only'            => 'no',
			'woocommerce_default_country'             => 'IR:THR',
			'woocommerce_store_address'               => 'نشانی نمونه — این فروشگاه واقعی نیست',
			'woocommerce_store_city'                  => 'تهران',
			'woocommerce_store_postcode'              => '0000000000',
			'woocommerce_allowed_countries'           => 'specific',
			'woocommerce_specific_allowed_countries'  => array( 'IR' ),
			'woocommerce_ship_to_countries'           => '',
			'woocommerce_ship_to_destination'         => 'billing',
			'woocommerce_currency'                    => 'IRT',
			'woocommerce_currency_pos'                => 'right_space',
			'woocommerce_price_thousand_sep'          => '٬',
			'woocommerce_price_decimal_sep'           => '/',
			'woocommerce_price_num_decimals'          => 0,
			'woocommerce_calc_taxes'                  => 'no',
			'woocommerce_enable_reviews'              => 'no',
			'woocommerce_enable_ajax_add_to_cart'     => 'yes',
			'woocommerce_cart_redirect_after_add'     => 'no',
			'woocommerce_enable_shipping_calc'        => 'yes',
			'woocommerce_shipping_cost_requires_address' => 'no',
			'woocommerce_enable_guest_checkout'       => 'yes',
			'woocommerce_enable_checkout_login_reminder' => 'no',
			'woocommerce_enable_signup_and_login_from_checkout' => 'no',
			'woocommerce_enable_coupons'              => 'yes',
			'woocommerce_checkout_terms_and_conditions_checkbox_text' => '[terms] را خوانده‌ام و می‌پذیرم.',
			'woocommerce_checkout_privacy_policy_text' => 'اطلاعات شما فقط برای پردازش و ارسال سفارش استفاده می‌شود.',
			'woocommerce_registration_privacy_policy_text' => 'اطلاعات شما فقط برای مدیریت حساب کاربری استفاده می‌شود.',
			'woocommerce_email_from_name'             => 'قهوه دانه',
			'woocommerce_email_base_color'            => '#A8233A',
			'woocommerce_email_footer_text'           => 'قهوه دانه — فروشگاه نمونه (نمونه‌کار شخصی)',
			'woocommerce_thumbnail_cropping'          => '1:1',
			'woocommerce_onboarding_profile'          => array( 'skipped' => true ),
			'woocommerce_task_list_hidden'            => 'yes',
			'woocommerce_show_marketplace_suggestions' => 'no',
			'woocommerce_allow_tracking'              => 'no',
		);
		foreach ( $options as $key => $value ) {
			update_option( $key, $value );
		}
		$this->log[] = 'تنظیمات فروشگاه اعمال شد: ایران، تومان، ارقام بدون اعشار، خرید مهمان، بدون مالیات، دیدگاه‌های محصول خاموش.';

		if ( 0 !== strpos( get_locale(), 'fa' ) ) {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			wp_download_language_pack( 'fa_IR' );
			update_option( 'WPLANG', 'fa_IR' ); // WordPress keeps the old value if the Persian files are missing.
			$this->log[] = 0 === strpos( get_option( 'WPLANG' ), 'fa' )
				? 'زبان سایت فارسی شد. برای ترجمه کامل ووکامرس: پیشخوان ← به‌روزرسانی‌ها ← به‌روزرسانی ترجمه‌ها.'
				: 'هشدار: بسته زبان فارسی دانلود نشد و زبان سایت تغییر نکرد. از تنظیمات ← عمومی ← زبان سایت، «فارسی» را انتخاب و ذخیره کنید، سپس این ابزار را دوباره اجرا کنید.';
		}
	}

	private function categories() {
		foreach ( $this->data['categories'] as $cat ) {
			$term = get_term_by( 'slug', $cat['slug'], 'product_cat' );
			if ( ! $term ) {
				$created = wp_insert_term( $cat['name'], 'product_cat', array( 'slug' => $cat['slug'], 'description' => $cat['description'] ) );
				if ( is_wp_error( $created ) ) {
					$this->log[] = 'خطا در ساخت دسته ' . $cat['name'] . ': ' . $created->get_error_message();
					continue;
				}
				$this->cats[ $cat['slug'] ] = (int) $created['term_id'];
			} else {
				$this->cats[ $cat['slug'] ] = (int) $term->term_id;
			}
		}
		$this->log[] = 'دسته‌بندی‌ها: ' . dane_fa_digits( count( $this->cats ) ) . ' دسته آماده است.';
	}

	private function attributes() {
		foreach ( $this->data['attributes'] as $slug => $attr ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			if ( ! wc_attribute_taxonomy_id_by_name( $taxonomy ) ) {
				$id = wc_create_attribute(
					array(
						'name'         => $attr['name'],
						'slug'         => $slug,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => false,
					)
				);
				if ( is_wp_error( $id ) ) {
					$this->log[] = 'خطا در ساخت ویژگی ' . $attr['name'] . ': ' . $id->get_error_message();
					continue;
				}
			}
			// The taxonomy is registered on the next request; register it now so terms can be added.
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, array( 'product', 'product_variation' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
			}
			foreach ( $attr['terms'] as $order => $term ) {
				if ( ! get_term_by( 'slug', $term[0], $taxonomy ) ) {
					$created = wp_insert_term( $term[1], $taxonomy, array( 'slug' => $term[0] ) );
					if ( ! is_wp_error( $created ) ) {
						update_term_meta( $created['term_id'], 'order', $order );
					}
				}
			}
		}
		$this->log[] = 'ویژگی‌های «وزن» و «آسیاب» با گزینه‌هایشان آماده است.';
	}

	private function products() {
		$made    = 0;
		$skipped = 0;
		foreach ( $this->data['products'] as $p ) {
			if ( wc_get_product_id_by_sku( $p['sku'] ) ) {
				++$skipped;
				continue;
			}
			$id = 'variable' === $p['type'] ? $this->variable_product( $p ) : $this->simple_product( $p );
			if ( $id ) {
				++$made;
			}
		}
		$this->log[] = dane_fa_digits( sprintf( 'محصولات: %d محصول ساخته شد، %d محصول از قبل وجود داشت.', $made, $skipped ) );
	}

	/**
	 * Product, gallery, category and hero photos.
	 * On a site set up with an earlier version, the old illustrations are swapped for the photos;
	 * images the owner uploaded themselves are never touched.
	 */
	private function photos() {
		$swapped = 0;
		foreach ( $this->data['products'] as $p ) {
			$id = wc_get_product_id_by_sku( $p['sku'] );
			if ( ! $id ) {
				continue;
			}
			$product = wc_get_product( $id );
			$sku     = strtolower( $p['sku'] );
			$changed = false;
			if ( $this->replaceable( $product->get_image_id() ) ) {
				$image = $this->attach_image( 'photo-' . $sku . '.webp', $p['name'] );
				if ( $image && $image !== (int) $product->get_image_id() ) {
					$product->set_image_id( $image );
					$changed = true;
				}
			}
			if ( ! $product->get_gallery_image_ids() ) {
				$extra = $this->attach_image( 'photo-' . $sku . '-2.webp', $p['name'] );
				if ( $extra ) {
					$product->set_gallery_image_ids( array( $extra ) );
					$changed = true;
				}
			}
			if ( $changed ) {
				$product->save();
				++$swapped;
			}
		}

		foreach ( array( 'coffee-beans', 'brew-gear', 'gift-sets' ) as $slug ) {
			if ( empty( $this->cats[ $slug ] ) ) {
				continue;
			}
			$current = (int) get_term_meta( $this->cats[ $slug ], 'thumbnail_id', true );
			if ( $this->replaceable( $current ) ) {
				$image = $this->attach_image( 'photo-cat-' . $slug . '.webp', '' );
				if ( $image ) {
					update_term_meta( $this->cats[ $slug ], 'thumbnail_id', $image );
				}
			}
		}

		if ( $this->replaceable( (int) get_option( 'dane_hero_image_id' ) ) ) {
			$hero = $this->attach_image( 'photo-hero.webp', 'دم‌آوری قهوه با دریپر و کتری گوسنک' );
			if ( $hero ) {
				update_option( 'dane_hero_image_id', $hero );
			}
		}
		if ( $swapped ) {
			$this->log[] = dane_fa_digits( sprintf( 'تصاویر: عکس %d محصول تنظیم شد.', $swapped ) );
		}
	}

	/**
	 * True when an image slot is empty or holds an image this plugin added (old illustration or photo).
	 *
	 * @param int $attachment_id Attachment ID or 0.
	 */
	private function replaceable( $attachment_id ) {
		if ( ! $attachment_id || ! get_post( $attachment_id ) ) {
			return true;
		}
		$source = (string) get_post_meta( $attachment_id, '_dane_source', true );
		return '' !== $source;
	}

	private function base_product( WC_Product $product, array $p ) {
		$product->set_name( $p['name'] );
		$product->set_sku( $p['sku'] );
		$product->set_status( 'publish' );
		$product->set_short_description( $p['short'] );
		$product->set_description( $p['description'] );
		$product->set_category_ids( isset( $this->cats[ $p['category'] ] ) ? array( $this->cats[ $p['category'] ] ) : array() );
		$product->set_featured( ! empty( $p['featured'] ) );
		$product->set_reviews_allowed( false );
		$image = $this->attach_image( 'photo-' . strtolower( $p['sku'] ) . '.webp', $p['name'] );
		if ( $image ) {
			$product->set_image_id( $image );
		}
		if ( ! empty( $p['roast'] ) ) {
			$product->update_meta_data( DANE_META_ROAST, (string) $p['roast'] );
		}
		if ( ! empty( $p['origin'] ) ) {
			$product->update_meta_data( DANE_META_ORIGIN, $p['origin'] );
		}
		if ( ! empty( $p['notes'] ) ) {
			$product->update_meta_data( DANE_META_NOTES, $p['notes'] );
		}
	}

	private function simple_product( array $p ) {
		$product = new WC_Product_Simple();
		$this->base_product( $product, $p );
		$product->set_regular_price( (string) $p['price'] );
		if ( ! empty( $p['sale_price'] ) ) {
			$product->set_sale_price( (string) $p['sale_price'] );
		}
		$product->set_stock_status( isset( $p['stock'] ) ? $p['stock'] : 'instock' );
		return $product->save();
	}

	private function variable_product( array $p ) {
		$product    = new WC_Product_Variable();
		$this->base_product( $product, $p );
		$attributes = array();
		$position   = 0;
		foreach ( $this->data['attributes'] as $slug => $attr ) {
			$taxonomy  = wc_attribute_taxonomy_name( $slug );
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
			$attribute->set_name( $taxonomy );
			$term_ids = array();
			foreach ( $attr['terms'] as $term ) {
				$t = get_term_by( 'slug', $term[0], $taxonomy );
				if ( $t ) {
					$term_ids[] = (int) $t->term_id;
				}
			}
			$attribute->set_options( $term_ids );
			$attribute->set_position( $position++ );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$attributes[] = $attribute;
		}
		$product->set_attributes( $attributes );
		$product->set_default_attributes( array( 'pa_weight' => '250g' ) );
		$id = $product->save();
		if ( ! $id ) {
			return 0;
		}

		$stock = isset( $p['stock'] ) ? $p['stock'] : 'instock';
		foreach ( $this->data['attributes']['weight']['terms'] as $weight ) {
			foreach ( $this->data['attributes']['grind']['terms'] as $grind ) {
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $id );
				$variation->set_attributes( array( 'pa_weight' => $weight[0], 'pa_grind' => $grind[0] ) );
				$regular = (int) ( round( $p['price'] * $weight[2] / 1000 ) * 1000 );
				$variation->set_regular_price( (string) $regular );
				if ( ! empty( $p['sale_ratio'] ) ) {
					$variation->set_sale_price( (string) ( (int) ( round( $regular * $p['sale_ratio'] / 1000 ) * 1000 ) ) );
				}
				$variation->set_sku( $p['sku'] . '-' . strtoupper( $weight[0] ) . '-' . strtoupper( $grind[0] ) );
				$variation->set_stock_status( $stock );
				$variation->set_status( 'publish' );
				$variation->save();
			}
		}
		WC_Product_Variable::sync( $id );
		wc_delete_product_transients( $id );
		return $id;
	}

	/**
	 * Copy an image from the plugin into the media library (once).
	 *
	 * @param string $file  File name in assets/images.
	 * @param string $title Attachment title and alt text.
	 * @return int Attachment ID or 0.
	 */
	private function attach_image( $file, $title ) {
		$existing = get_posts(
			array(
				'post_type'   => 'attachment',
				'meta_key'    => '_dane_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $file, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}
		$path = DANE_STORE_DIR . 'assets/images/' . $file;
		if ( ! file_exists( $path ) ) {
			return 0;
		}
		$upload = wp_upload_bits( $file, null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! empty( $upload['error'] ) ) {
			$this->log[] = 'خطا در بارگذاری تصویر ' . $file . ': ' . $upload['error'];
			return 0;
		}
		$id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/webp',
				'post_title'     => $title,
				'post_status'    => 'inherit',
			),
			$upload['file']
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
		update_post_meta( $id, '_wp_attachment_image_alt', $title );
		update_post_meta( $id, '_dane_source', $file );
		return (int) $id;
	}

	/**
	 * Content pages from includes/pages/*.html.
	 *
	 * @return int[] Page IDs by slug.
	 */
	private function pages() {
		$ids   = array();
		$pages = $this->data['pages'];
		array_unshift( $pages, array( 'slug' => 'home', 'title' => 'خانه', 'file' => '' ) );
		foreach ( $pages as $page ) {
			$found = get_page_by_path( $page['slug'] );
			if ( $found ) {
				$ids[ $page['slug'] ] = (int) $found->ID;
				continue;
			}
			$content = '';
			if ( $page['file'] ) {
				$file    = DANE_STORE_DIR . 'includes/pages/' . $page['file'] . '.html';
				$content = file_exists( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
			$ids[ $page['slug'] ] = (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_content' => $content,
				)
			);
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
		update_option( 'woocommerce_terms_page_id', $ids['terms'] );
		update_option( 'wp_page_for_privacy_policy', $ids['terms'] );
		$this->log[] = 'برگه‌ها آماده است: خانه، درباره ما، تماس، سؤالات متداول، ارسال و بازگشت، قوانین.';
		return $ids;
	}

	/**
	 * Shop, cart, checkout and account pages: Persian titles, classic cart/checkout.
	 *
	 * @param int[] $pages Content page IDs.
	 */
	private function woocommerce_pages( $pages ) {
		WC_Install::create_pages();
		$map = array(
			'shop'      => array( 'فروشگاه', '' ),
			'cart'      => array( 'سبد خرید', "<!-- wp:shortcode -->\n[woocommerce_cart]\n<!-- /wp:shortcode -->" ),
			'checkout'  => array( 'تسویه حساب', "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->" ),
			'myaccount' => array( 'حساب کاربری', "<!-- wp:shortcode -->\n[woocommerce_my_account]\n<!-- /wp:shortcode -->" ),
		);
		foreach ( $map as $key => $settings ) {
			$id = wc_get_page_id( $key );
			if ( $id <= 0 ) {
				continue;
			}
			$update = array( 'ID' => $id, 'post_title' => $settings[0] );
			if ( $settings[1] ) {
				$update['post_content'] = $settings[1];
			}
			wp_update_post( $update );
		}
		$this->log[] = 'برگه‌های فروشگاه، سبد خرید، تسویه حساب و حساب کاربری فارسی شد؛ سبد و تسویه حساب کلاسیک (سازگار با درگاه‌های ایرانی).';
	}

	private function shipping_and_payments() {
		$cfg      = $this->data['shipping'];
		$tehran   = $this->zone( $cfg['tehran']['name'], array( array( $cfg['tehran']['state'], 'state' ) ), 0 );
		$iran     = $this->zone( $cfg['iran']['name'], array( array( 'IR', 'country' ) ), 1 );
		$t_flat   = $this->method( $tehran, 'flat_rate', array( 'title' => $cfg['tehran']['flat_title'], 'tax_status' => 'none', 'cost' => (string) $cfg['tehran']['flat_cost'] ) );
		$t_free   = $this->method( $tehran, 'free_shipping', array( 'title' => 'ارسال رایگان با پیک', 'requires' => 'min_amount', 'min_amount' => (string) $cfg['free_min'], 'ignore_discounts' => 'no' ) );
		$this->method( $iran, 'flat_rate', array( 'title' => $cfg['iran']['flat_title'], 'tax_status' => 'none', 'cost' => (string) $cfg['iran']['flat_cost'] ) );
		$this->method( $iran, 'free_shipping', array( 'title' => 'ارسال رایگان با پست', 'requires' => 'min_amount', 'min_amount' => (string) $cfg['free_min'], 'ignore_discounts' => 'no' ) );
		$this->log[] = 'مناطق ارسال: تهران (پیک) و سایر استان‌ها (پست پیشتاز)، با ارسال رایگان بالای ' . dane_fa_digits( number_format( $cfg['free_min'], 0, '', '٬' ) ) . ' تومان.';

		update_option(
			'woocommerce_cod_settings',
			array(
				'enabled'            => 'yes',
				'title'              => 'پرداخت در محل (فقط تهران)',
				'description'        => 'هزینه را هنگام تحویل به پیک پرداخت کنید.',
				'instructions'       => 'هزینه سفارش را هنگام تحویل به پیک پرداخت کنید.',
				'enable_for_methods' => array_values( array_filter( array( $t_flat ? 'flat_rate:' . $t_flat : '', $t_free ? 'free_shipping:' . $t_free : '' ) ) ),
				'enable_for_virtual' => 'no',
			)
		);
		update_option(
			'woocommerce_bacs_settings',
			array(
				'enabled'      => 'yes',
				'title'        => 'کارت به کارت',
				'description'  => 'پس از ثبت سفارش، اطلاعات کارت برای واریز نمایش داده می‌شود.',
				'instructions' => 'این فروشگاه نمونه است و پرداختی دریافت نمی‌کند. در فروشگاه واقعی، شماره کارت و نام صاحب حساب در این بخش نوشته می‌شود.',
			)
		);
		update_option( 'woocommerce_bacs_accounts', array() );
		update_option( 'woocommerce_cheque_settings', array( 'enabled' => 'no' ) );
		$this->log[] = 'روش‌های پرداخت: «پرداخت در محل» فقط برای پیک تهران و «کارت به کارت». درگاه اینترنتی واقعی در این دمو فعال نیست.';
	}

	/**
	 * Find or create a shipping zone.
	 *
	 * @param string $name      Zone name.
	 * @param array  $locations Pairs of [code, type].
	 * @param int    $order     Zone order.
	 */
	private function zone( $name, $locations, $order ) {
		foreach ( WC_Shipping_Zones::get_zones() as $z ) {
			if ( $z['zone_name'] === $name ) {
				return new WC_Shipping_Zone( $z['id'] );
			}
		}
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );
		foreach ( $locations as $loc ) {
			$zone->add_location( $loc[0], $loc[1] );
		}
		$zone->save();
		return $zone;
	}

	/**
	 * Find or add a shipping method on a zone and save its settings.
	 *
	 * @param WC_Shipping_Zone $zone     Zone.
	 * @param string           $type     Method ID.
	 * @param array            $settings Instance settings.
	 * @return int Instance ID.
	 */
	private function method( $zone, $type, $settings ) {
		$instance = 0;
		foreach ( $zone->get_shipping_methods() as $m ) {
			if ( $m->id === $type ) {
				$instance = (int) $m->instance_id;
				break;
			}
		}
		if ( ! $instance ) {
			$instance = (int) $zone->add_shipping_method( $type );
		}
		if ( $instance ) {
			update_option( 'woocommerce_' . $type . '_' . $instance . '_settings', $settings );
		}
		return $instance;
	}

	private function coupon() {
		$c = $this->data['coupon'];
		if ( wc_get_coupon_id_by_code( $c['code'] ) ) {
			return;
		}
		$coupon = new WC_Coupon();
		$coupon->set_code( $c['code'] );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( $c['percent'] );
		$coupon->set_individual_use( true );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->set_description( 'کد خوش‌آمد خرید اول (دمو)' );
		$coupon->save();
		$this->log[] = 'کد تخفیف ' . $c['code'] . ' (۱۰ درصد، یک بار برای هر مشتری) ساخته شد.';
	}

	private function navigation() {
		$existing = get_posts( array( 'post_type' => 'wp_navigation', 'title' => 'منوی اصلی دانه', 'post_status' => 'publish', 'numberposts' => 1 ) );
		$links    = array(
			array( 'فروشگاه', get_permalink( wc_get_page_id( 'shop' ) ) ),
			array( 'قهوه', get_term_link( $this->cats['coffee-beans'] ?? 0, 'product_cat' ) ),
			array( 'دمی‌افزار', get_term_link( $this->cats['brew-gear'] ?? 0, 'product_cat' ) ),
			array( 'درباره ما', get_permalink( get_page_by_path( 'about' ) ) ),
			array( 'سؤالات متداول', get_permalink( get_page_by_path( 'faq' ) ) ),
			array( 'تماس', get_permalink( get_page_by_path( 'contact' ) ) ),
		);
		$content = '';
		foreach ( $links as $link ) {
			if ( is_wp_error( $link[1] ) || ! $link[1] ) {
				continue;
			}
			$content .= '<!-- wp:navigation-link ' . wp_json_encode( array( 'label' => $link[0], 'url' => $link[1], 'kind' => 'custom' ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->';
		}
		$post = array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => 'منوی اصلی دانه',
			'post_content' => $content,
		);
		if ( $existing ) {
			$post['ID'] = $existing[0]->ID;
			wp_update_post( $post );
		} else {
			wp_insert_post( $post );
		}
		$this->log[] = 'منوی اصلی ساخته شد.';
	}

	private function cleanup_defaults() {
		foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
			$post = get_page_by_path( $slug, OBJECT, $type );
			if ( $post && 'publish' === $post->post_status ) {
				wp_trash_post( $post->ID );
			}
		}
	}
}

/* ---------- Admin screen: Tools → دموی فروشگاه دانه ---------- */

add_action(
	'admin_menu',
	static function () {
		add_management_page( 'دموی فروشگاه دانه', 'دموی فروشگاه دانه', 'manage_woocommerce', 'dane-setup', 'dane_setup_screen' );
	}
);

/**
 * Render the setup screen and handle the button.
 */
function dane_setup_screen() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}
	$log = array();
	if ( isset( $_POST['dane_run'] ) && check_admin_referer( 'dane_setup' ) ) {
		$log = ( new Dane_Setup() )->run( ! empty( $_POST['dane_theme'] ) );
	}
	$done = get_option( 'dane_demo_version' );
	?>
	<div class="wrap" style="max-width:760px">
		<h1>دموی فروشگاه دانه</h1>
		<p>این ابزار یک فروشگاه نمونه کامل می‌سازد: ۳ دسته، ۱۲ محصول (۶ قهوه با گزینه وزن و آسیاب)، برگه‌های قوانین و سؤالات متداول، مناطق و هزینه ارسال، روش‌های پرداخت، کد تخفیف و منو.</p>
		<p><strong>فقط روی سایت آزمایشی اجرا کنید.</strong> تنظیمات فروشگاه (واحد پول، کشور، روش‌های پرداخت) بازنویسی می‌شود. اجرای دوباره، محصولات تکراری نمی‌سازد.</p>
		<?php if ( $done && ! $log ) : ?>
			<div class="notice notice-info inline"><p>دمو قبلاً یک بار راه‌اندازی شده است (نسخه <?php echo esc_html( $done ); ?>).</p></div>
		<?php endif; ?>
		<?php if ( $log ) : ?>
			<div class="notice notice-success inline"><ul style="list-style:disc;padding-inline-start:20px">
				<?php foreach ( $log as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">مشاهده فروشگاه</a></p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'dane_setup' ); ?>
			<p><label><input type="checkbox" name="dane_theme" value="1" checked> پوسته «دانه» را هم فعال کن</label></p>
			<p><button class="button button-primary button-hero" name="dane_run" value="1">ساخت فروشگاه نمونه</button></p>
		</form>
	</div>
	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'dane setup',
		static function ( $args, $assoc ) {
			$log = ( new Dane_Setup() )->run( ! isset( $assoc['skip-theme'] ) );
			foreach ( $log as $line ) {
				WP_CLI::log( $line );
			}
			WP_CLI::success( 'Dane demo store is ready.' );
		},
		array( 'shortdesc' => 'Build the Dane demo store. Use --skip-theme to keep the current theme.' )
	);
}
