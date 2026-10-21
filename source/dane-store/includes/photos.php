<?php
/**
 * Product photo replacement: Tools → تصاویر محصولات دانه.
 * Upload several photos at once; each file is matched to a product by its file name:
 *   dn-eth-yrg.jpg      → main image of the product with SKU DN-ETH-YRG
 *   dn-eth-yrg-2.jpg    → added to that product's gallery
 *   hero.jpg            → large image on the home page
 *   cat-coffee-beans.jpg → category image (coffee-beans, brew-gear, gift-sets)
 *
 * @package DaneStore
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_management_page( 'تصاویر محصولات دانه', 'تصاویر محصولات دانه', 'manage_woocommerce', 'dane-photos', 'dane_photos_screen' );
	}
);

/**
 * Match a file name to a target.
 *
 * @param string $filename Uploaded file name.
 * @return array{type:string,id:int,label:string,gallery:bool}|null
 */
function dane_photo_target( $filename ) {
	$base = strtolower( pathinfo( $filename, PATHINFO_FILENAME ) );
	$base = preg_replace( '/[\s_]+/', '-', trim( $base ) );

	if ( 'hero' === $base ) {
		return array( 'type' => 'hero', 'id' => 0, 'label' => 'تصویر بزرگ صفحه اصلی', 'gallery' => false );
	}
	if ( 0 === strpos( $base, 'cat-' ) ) {
		$term = get_term_by( 'slug', substr( $base, 4 ), 'product_cat' );
		return $term ? array( 'type' => 'category', 'id' => (int) $term->term_id, 'label' => 'دسته: ' . $term->name, 'gallery' => false ) : null;
	}
	$gallery = false;
	if ( preg_match( '/^(.+)-(\d)$/', $base, $m ) && ! wc_get_product_id_by_sku( strtoupper( $base ) ) ) {
		$base    = $m[1];
		$gallery = true;
	}
	$product_id = wc_get_product_id_by_sku( strtoupper( $base ) );
	if ( ! $product_id ) {
		return null;
	}
	return array(
		'type'    => 'product',
		'id'      => (int) $product_id,
		'label'   => get_the_title( $product_id ) . ( $gallery ? ' (گالری)' : '' ),
		'gallery' => $gallery,
	);
}

/**
 * Handle the upload form and return result rows.
 *
 * @return array<int,array{file:string,ok:bool,message:string}>
 */
function dane_photos_handle_upload() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$rows = array();
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked by caller; files validated by media_handle_sideload.
	$files = isset( $_FILES['dane_photos'] ) ? $_FILES['dane_photos'] : null;
	if ( ! $files || empty( $files['name'] ) || ! is_array( $files['name'] ) ) {
		return array( array( 'file' => '—', 'ok' => false, 'message' => 'فایلی انتخاب نشده است.' ) );
	}

	foreach ( $files['name'] as $i => $name ) {
		$name = sanitize_file_name( wp_unslash( $name ) );
		if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] ) {
			$rows[] = array( 'file' => $name, 'ok' => false, 'message' => 'بارگذاری ناموفق بود؛ حجم فایل را بررسی کنید (حداکثر ' . size_format( wp_max_upload_size() ) . ').' );
			continue;
		}
		$target = dane_photo_target( $name );
		if ( ! $target ) {
			$rows[] = array( 'file' => $name, 'ok' => false, 'message' => 'نام فایل با هیچ محصولی جور نیست. نام باید شناسه محصول باشد، مثل dn-eth-yrg.jpg' );
			continue;
		}
		$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
			$rows[] = array( 'file' => $name, 'ok' => false, 'message' => 'فقط JPG، PNG یا WebP پذیرفته می‌شود.' );
			continue;
		}
		$size = @getimagesize( $files['tmp_name'][ $i ] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $size ) {
			$rows[] = array( 'file' => $name, 'ok' => false, 'message' => 'فایل تصویر معتبر نیست.' );
			continue;
		}

		$attachment_id = media_handle_sideload(
			array( 'name' => $name, 'tmp_name' => $files['tmp_name'][ $i ] ),
			'product' === $target['type'] ? $target['id'] : 0,
			$target['label']
		);
		if ( is_wp_error( $attachment_id ) ) {
			$rows[] = array( 'file' => $name, 'ok' => false, 'message' => $attachment_id->get_error_message() );
			continue;
		}
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', wp_strip_all_tags( $target['label'] ) );

		if ( 'hero' === $target['type'] ) {
			update_option( 'dane_hero_image_id', $attachment_id );
		} elseif ( 'category' === $target['type'] ) {
			update_term_meta( $target['id'], 'thumbnail_id', $attachment_id );
		} else {
			$product = wc_get_product( $target['id'] );
			if ( $target['gallery'] ) {
				$product->set_gallery_image_ids( array_unique( array_merge( $product->get_gallery_image_ids(), array( $attachment_id ) ) ) );
			} else {
				$product->set_image_id( $attachment_id );
			}
			$product->save();
		}

		$note = '';
		if ( $size[0] < 800 || $size[1] < 800 ) {
			$note = ' — هشدار: ابعاد ' . dane_fa_digits( $size[0] . '×' . $size[1] ) . ' کوچک است؛ حداقل ۸۰۰×۸۰۰ پیشنهاد می‌شود.';
		} elseif ( 'product' === $target['type'] && abs( $size[0] - $size[1] ) > max( $size[0], $size[1] ) * 0.05 ) {
			$note = ' — تصویر مربع نیست؛ در کارت محصول از وسط برش می‌خورد.';
		}
		$rows[] = array( 'file' => $name, 'ok' => true, 'message' => 'جایگزین شد: ' . $target['label'] . $note );
	}
	return $rows;
}

/**
 * Admin screen.
 */
function dane_photos_screen() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}
	$rows = array();
	if ( isset( $_POST['dane_photos_submit'] ) && check_admin_referer( 'dane_photos' ) ) {
		$rows = dane_photos_handle_upload();
	}
	$products = wc_get_products( array( 'limit' => -1, 'orderby' => 'title', 'order' => 'ASC', 'status' => 'publish' ) );
	?>
	<div class="wrap" style="max-width:900px">
		<h1>تصاویر محصولات دانه</h1>
		<p>چند عکس را با هم انتخاب کنید. هر عکس بر اساس <strong>نام فایل</strong> روی محصول خودش قرار می‌گیرد. عکس قبلی حذف نمی‌شود و در کتابخانه رسانه می‌ماند.</p>
		<?php if ( $rows ) : ?>
			<table class="widefat striped" style="margin:16px 0">
				<thead><tr><th>فایل</th><th>نتیجه</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td dir="ltr" style="text-align:right"><?php echo esc_html( $row['file'] ); ?></td>
						<td style="color:<?php echo $row['ok'] ? '#1d6b45' : '#b3261e'; ?>"><?php echo esc_html( $row['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #dcdcde;padding:16px 20px;border-radius:6px">
			<?php wp_nonce_field( 'dane_photos' ); ?>
			<p><input type="file" name="dane_photos[]" accept="image/jpeg,image/png,image/webp" multiple required></p>
			<p class="description">JPG، PNG یا WebP؛ مربع و حداقل ۸۰۰×۸۰۰ پیکسل. حداکثر حجم هر فایل: <?php echo esc_html( size_format( wp_max_upload_size() ) ); ?>.</p>
			<p><button class="button button-primary" name="dane_photos_submit" value="1">بارگذاری و جایگزینی</button></p>
		</form>

		<h2 style="margin-top:28px">نام فایل هر محصول</h2>
		<table class="widefat striped">
			<thead><tr><th>محصول</th><th>نام فایل عکس اصلی</th><th>عکس فعلی</th></tr></thead>
			<tbody>
				<tr><td>تصویر بزرگ صفحه اصلی</td><td dir="ltr" style="text-align:right"><code>hero.jpg</code> (افقی، حداقل ۱۶۰۰ پیکسل عرض)</td><td><?php echo get_option( 'dane_hero_image_id' ) ? wp_get_attachment_image( (int) get_option( 'dane_hero_image_id' ), array( 48, 48 ) ) : '—'; ?></td></tr>
				<?php foreach ( $products as $p ) : ?>
					<tr>
						<td><?php echo esc_html( $p->get_name() ); ?></td>
						<td dir="ltr" style="text-align:right"><code><?php echo esc_html( strtolower( $p->get_sku() ) ); ?>.jpg</code></td>
						<td><?php echo $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), array( 48, 48 ) ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
				<?php foreach ( array( 'coffee-beans', 'brew-gear', 'gift-sets' ) as $slug ) : $t = get_term_by( 'slug', $slug, 'product_cat' ); if ( ! $t ) { continue; } ?>
					<tr><td>دسته: <?php echo esc_html( $t->name ); ?></td><td dir="ltr" style="text-align:right"><code>cat-<?php echo esc_html( $slug ); ?>.jpg</code></td><td><?php $tid = (int) get_term_meta( $t->term_id, 'thumbnail_id', true ); echo $tid ? wp_get_attachment_image( $tid, array( 48, 48 ) ) : '—'; ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">برای عکس دوم و سوم گالری یک محصول، <code dir="ltr">-2</code> و <code dir="ltr">-3</code> به آخر نام اضافه کنید؛ مثل <code dir="ltr">dn-eth-yrg-2.jpg</code>.</p>
	</div>
	<?php
}
