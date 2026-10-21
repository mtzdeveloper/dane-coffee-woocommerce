<?php
/**
 * Title: صفحه پیدا نشد
 * Slug: dane/not-found
 * Inserter: no
 *
 * @package Dane
 */

?>
<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">این صفحه پیدا نشد</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>ممکن است نشانی اشتباه باشد یا محصول حذف شده باشد. از فروشگاه یا جست‌وجو استفاده کنید.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">رفتن به فروشگاه</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></main>
<!-- /wp:group -->
