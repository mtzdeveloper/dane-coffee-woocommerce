<?php
/**
 * Title: صفحه اصلی فروشگاه
 * Slug: dane/home
 * Categories: featured
 * Inserter: no
 *
 * @package Dane
 */

// The store setup sets this to the hero photo; a photo uploaded with Tools → تصاویر محصولات دانه (hero.jpg) replaces it.
$dane_hero_id = (int) get_option( 'dane_hero_image_id' );
?>
<!-- wp:group {"tagName":"main","className":"dane-home","layout":{"type":"default"}} -->
<main class="wp-block-group dane-home">

<!-- wp:group {"className":"dane-hero","backgroundColor":"leaf-tint","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group dane-hero has-leaf-tint-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"46%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:46%"><!-- wp:heading {"level":1,"fontSize":"display","style":{"typography":{"fontWeight":"900","lineHeight":"1.25"}}} -->
<h1 class="wp-block-heading has-display-font-size" style="font-weight:900;line-height:1.25">قهوه‌ای که همین هفته برشته شده</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"large","style":{"color":{"text":"#4A443F"}}} -->
<p class="has-text-color has-large-font-size" style="color:#4A443F">هر هفته به اندازه سفارش‌ها برشت می‌کنیم و تاریخ برشت روی بسته نوشته می‌شود. درجه برشت هر قهوه را با پنج نقطه نشان داده‌ایم تا انتخاب ساده باشد.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button {"backgroundColor":"cherry"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-cherry-background-color has-background wp-element-button" href="<?php echo esc_url( home_url( '/product-category/coffee-beans/' ) ); ?>">خرید قهوه</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#roast-guide">کدام برشت برای من؟</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"full","className":"dane-hero-image"} -->
<figure class="wp-block-image size-full dane-hero-image is-photo"><?php
if ( $dane_hero_id ) {
	echo wp_get_attachment_image( $dane_hero_id, 'full', false, array( 'alt' => 'دم‌آوری قهوه با دریپر و کتری گوسنک', 'fetchpriority' => 'high', 'sizes' => '(max-width: 781px) 100vw, 640px' ) );
} else {
	?><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/hero.webp' ) ); ?>" alt="دم‌آوری قهوه با دریپر و کتری گوسنک" width="1400" height="1000"/><?php
}
?></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"dane-facts","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"border":{"bottom":{"color":"var:preset|color|line","width":"1px"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group dane-facts" style="border-bottom-color:var(--wp--preset--color--line);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p><strong>برشت هفتگی</strong><br>تاریخ برشت روی هر بسته</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p><strong>آسیاب به سفارش شما</strong><br>برای اسپرسو، دمی یا دانه کامل</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p><strong>ارسال رایگان</strong><br>برای سفارش‌های بالای ۱٬۵۰۰٬۰۰۰ تومان</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"dane-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group dane-section" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading">قهوه‌های این هفته</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><a href="<?php echo esc_url( home_url( '/product-category/coffee-beans/' ) ); ?>">همه قهوه‌ها</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[products category="coffee-beans" limit="4" columns="4" orderby="date" order="ASC"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"anchor":"roast-guide","className":"dane-roast-guide","backgroundColor":"ink","textColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}},"elements":{"heading":{"color":{"text":"var:preset|color|base"}}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div id="roast-guide" class="wp-block-group dane-roast-guide has-base-color has-ink-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading -->
<h2 class="wp-block-heading">کدام برشت برای شماست؟</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#C9C2BA"}}} -->
<p class="has-text-color" style="color:#C9C2BA">هرچه نقطه‌های پرشده بیشتر باشد، برشت تیره‌تر، بادی سنگین‌تر و اسیدیته کمتر است. همین نقطه‌ها روی کارت هر محصول آمده است.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<ol class="dane-scale">
	<li><span class="dane-roast" aria-hidden="true"><span class="dane-roast__dots"><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot"></span><span class="dane-roast__dot"></span><span class="dane-roast__dot"></span></span></span><h3>روشن</h3><p>میوه‌ای و گلی، اسیدیته روشن. برای V60، کمکس و قهوه‌ساز دمی.</p><a href="<?php echo esc_url( home_url( '/product-category/coffee-beans/' ) ); ?>">اتیوپی یرگاچفه، کنیا AA</a></li>
	<li><span class="dane-roast" aria-hidden="true"><span class="dane-roast__dots"><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot"></span><span class="dane-roast__dot"></span></span></span><h3>متوسط</h3><p>کاراملی و متعادل. هم برای دمی و هم برای اسپرسو.</p><a href="<?php echo esc_url( home_url( '/product-category/coffee-beans/' ) ); ?>">کلمبیا هویلا، گواتمالا آنتیگوا</a></li>
	<li><span class="dane-roast" aria-hidden="true"><span class="dane-roast__dots"><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot is-on"></span><span class="dane-roast__dot is-on"></span></span></span><h3>تیره</h3><p>شکلاتی و آجیلی، بادی سنگین. برای اسپرسو، لاته و موکاپات.</p><a href="<?php echo esc_url( home_url( '/product-category/coffee-beans/' ) ); ?>">برزیل سرادو، ترکیب اسپرسو</a></li>
</ol>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"dane-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group dane-section" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:heading -->
<h2 class="wp-block-heading">خرید بر اساس دسته</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[product_categories columns="3" parent="0" hide_empty="1"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"dane-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group dane-section" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading">برای دم‌آوری در خانه</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><a href="<?php echo esc_url( home_url( '/product-category/brew-gear/' ) ); ?>">همه دمی‌افزارها</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[products category="brew-gear" limit="4" columns="4" orderby="date" order="ASC"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"dane-cta","backgroundColor":"leaf-tint","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group dane-cta has-leaf-tint-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">سؤالی درباره ارسال یا انتخاب قهوه دارید؟</h2>
<!-- /wp:heading -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">سؤالات متداول</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">تماس با ما</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

</main>
<!-- /wp:group -->
