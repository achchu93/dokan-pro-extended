<?php
/**
 * The template for displaying product widget entries
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-widget-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	return;
}

?>
<li>
	<?php do_action( 'woocommerce_widget_product_item_start', $args ); ?>
	<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<?php echo wp_kses_post($product->get_image( 'shop_catalog' )); ?>
		<span class="product-title"><?php echo wp_kses_post($product->get_name()); ?></span>
	</a>
	<?php if ( ! empty( $show_rating ) ) : ?>
		<?php echo wp_kses_post( wc_get_rating_html( $product->get_average_rating() ) ); ?>
	<?php endif; ?>
	<span class="price">
		<?php echo wp_kses_post($product->get_price_html()); ?>
	</span>
	<?php if( get_field('color') || get_field('size') ): ?>
	<div class="custom-fields-widget">
		<?php if( get_field('color') ): ?>
		<strong>Color:</strong><?php the_field('color'); ?>
		<?php endif; ?>
		<?php if( get_field('size') ): ?>
		<strong>Size:</strong><?php the_field('size'); ?>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php do_action( 'woocommerce_widget_product_item_end', $args ); ?>
</li>
