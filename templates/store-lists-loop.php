<div id="dokan-seller-listing-wrap" class="grid-view">
    <div class="seller-listing-content">
        <?php if ( $sellers['users'] ) : ?>
            <ul class="dokan-seller-wrap">
                <?php
                foreach ( $sellers['users'] as $seller ) {
                    $vendor            = dokan()->vendor->get( $seller->ID );
                    $store_banner_id   = $vendor->get_banner_id();
                    $store_name        = $vendor->get_shop_name();
                    $store_url         = $vendor->get_shop_url();
                    $store_rating      = $vendor->get_rating();
                    $is_store_featured = $vendor->is_featured();
                    $store_phone       = $vendor->get_phone();
                    $store_info        = dokan_get_store_info( $seller->ID );
                    $store_address     = dokan_get_seller_short_address( $seller->ID );
                    $store_banner_url  = $store_banner_id ? wp_get_attachment_image_src( $store_banner_id, $image_size ) : DOKAN_PLUGIN_ASSEST . '/images/default-store-banner.png';
                    //Start Code for gettings vendor related Products in loop
                    $product_per_page = 3;
                    $args = array(
                     'author'  => $seller->ID,
                     'post_type' => 'product', 
                     'posts_per_page' => $product_per_page,
                     'orderby' => 'publish_date',
                     'order' => 'ASC',
                    );
                    $total_product = count_user_posts( $seller->ID , "product"  );
                     $loop = new WP_Query( $args );
                     //End Code for gettings vendor related Products in loop
                    ?>

                    <li class="dokan-single-seller woocommerce coloum-<?php echo esc_attr( $per_row ); ?> <?php echo ( ! $store_banner_id ) ? 'no-banner-img' : ''; ?>">
                        <div class="store-wrapper">
                            <div class="store-header">
                                <div class="store-banner">
                                    <!-- Commented to show products-->
                                    <!-- <a href="<?php echo esc_url( $store_url ); ?>">
                                        <img src="<?php echo is_array( $store_banner_url ) ? esc_attr( $store_banner_url[0] ) : esc_attr( $store_banner_url ); ?>">
                                    </a> -->
                                </div>
                            </div> 

                            <div class="store-content <?php echo ! $store_banner_id ? esc_attr( 'default-store-banner' ) : '' ?>">
                                <div class="store-data-container">
                                    <!-- Commented to show products-->
                                    <!-- <div class="featured-favourite">
                                        <?php if ( $is_store_featured ) : ?>
                                            <div class="featured-label"><?php esc_html_e( 'Featured', 'dokan-lite' ); ?></div>
                                        <?php endif ?>

                                        <?php do_action( 'dokan_seller_listing_after_featured', $seller, $store_info ); ?>
                                    </div> -->

                                    <div class="store-data">
                                        <h2 style="font-size: 20px !important; font-weight: 600 !important;color: #333 !important;">Bás nr. <a style="font-size:20px; font-weight:500;" href="<?php echo esc_attr( $store_url ); ?>"><?php if($store_name!=""){ echo esc_html( $store_name );} else{echo esc_html("No Name" );} ?></a></h2>
                                         <!-- Commented to show products-->
                                        <!-- <?php if ( !empty( $store_rating['count'] ) ): ?>
                                            <div class="dokan-seller-rating" title="<?php echo sprintf( esc_attr__( 'Rated %s out of 5', 'dokan-lite' ), esc_attr( $store_rating['rating'] ) ) ?>">
                                                <?php echo wp_kses_post( dokan_generate_ratings( $store_rating['rating'], 5 ) ); ?>
                                                <p class="rating">
                                                    <?php echo esc_html( sprintf( __( '%s out of 5', 'dokan-lite' ), $store_rating['rating'] ) ); ?>
                                                </p>
                                            </div>
                                        <?php endif ?>

                                        <?php if ( ! dokan_is_vendor_info_hidden( 'address' ) && $store_address ): ?>
                                            <?php
                                                $allowed_tags = array(
                                                    'span' => array(
                                                        'class' => array(),
                                                    ),
                                                    'br' => array()
                                                );
                                            ?>
                                            <p class="store-address"><?php echo wp_kses( $store_address, $allowed_tags ); ?></p>
                                        <?php endif ?>

                                        <?php if ( ! dokan_is_vendor_info_hidden( 'phone' ) && $store_phone ) { ?>
                                            <p class="store-phone">
                                                <i class="fa fa-phone" aria-hidden="true"></i> <?php echo esc_html( $store_phone ); ?>
                                            </p>
                                        <?php } ?>

                                        <?php do_action( 'dokan_seller_listing_after_store_data', $seller, $store_info ); ?> -->
                                        <div class="store-product-grid">
                                <!--Start Code to show products from Loop-->
                                <ul>
                                   <?php while ( $loop->have_posts() ) : $loop->the_post(); 
                                   //wc_get_template_part( 'content','product' );
                                    $product_id = get_the_ID();
                                    $product_price = get_post_meta($product_id , '_price', true);
                                    
                                    $product_thumbnail = get_the_post_thumbnail_url($product_id);
                                    if(!$product_thumbnail){
                                    $product_thumbnail = wc_placeholder_img_src( $size );
                                    }
                                    $product_Regularprice = get_post_meta($product_id , '_regular_price', true);
                                    $product_Saleprice = get_post_meta($product_id , '_sale_price', true);
                                    ?>
                                    <li class="product type-product">
                                        <a href="<?php the_permalink(); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
                                        <img width="200" height="150" src="<?php echo $product_thumbnail; ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt=""><?php 
                                        $string = esc_html(get_the_title($product_id)); 
                                        if (strlen($string) > 8) {
                                            // truncate string
                                            $stringCut = substr($string, 0, 8);
                                            $endPoint = strrpos($stringCut, ' ');

                                            //if the string doesn't contain any space then it will cut without word basis.
                                            $string = $endPoint? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
                                            $string = $string.'..';
                                        }
                                        echo "<span>".esc_html($string)."</span>";
                                        ?>
                                        <span class="price"><?php if($product_Saleprice){ ?><ins><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol(); ?></span><?php echo $product_Saleprice; ?></span></ins><?php } ?>
                                        <?php if($product_Saleprice){ echo "<del>"; } ?><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol(); ?></span><?php echo $product_Regularprice; ?></span><?php if($product_Saleprice){ echo "</del>"; } ?>
                                        </a>
                                    </li>
                                    
                                     <?php endwhile;

                                 wp_reset_postdata();
                                 if($total_product>$product_per_page){
                                 ?>
                                    <a href="<?php echo esc_attr( $store_url ); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link"><span class="count-products"><li class="product type-product"><br/><?php echo "+".$total_product." Products"; ?></span></li></a>
                                    
                                <?php } ?>
                                 </ul>
                                <!--End Code to show products from Loop-->
                                </div>
                                    </div>
                                </div>
                                
                            </div>

                            <div class="store-footer">
                                <!-- Commented to show products -->
                                <!-- <div class="seller-avatar">
                                    <?php echo get_avatar( $seller->ID, 150 ); ?>
                                </div> -->
                                <a href="<?php echo esc_url( $store_url ); ?>" title="<?php esc_attr_e( 'Visit Store', 'dokan-lite' );?>" class="btn-products">

                                    <?php _e( 'See all products', 'Dokan' ); ?>

                                </a>
                                <!-- Commented to show products -->
                                <?php //do_action( 'dokan_seller_listing_footer_content', $seller, $store_info ); ?>
                            </div> 
<?php if( $sale_price = get_user_meta( $seller->ID, 'store_discount_rate', true ) ): ?>
                            <div class="store-sale-price"><?php echo "-{$sale_price}%"; ?></div>
                            <?php endif; ?>
                            
                        </div>
                    </li>

                <?php  } ?>
                <div class="dokan-clearfix"></div>
            </ul> <!-- .dokan-seller-wrap -->

            <?php
            $user_count   = $sellers['count'];
            $num_of_pages = ceil( $user_count / $limit );

            if ( $num_of_pages > 1 ) {
                echo '<div class="pagination-container clearfix">';

                $pagination_args = array(
                    'current'   => $paged,
                    'total'     => $num_of_pages,
                    'base'      => $pagination_base,
                    'type'      => 'array',
                    'prev_text' => __( '&larr; Previous', 'dokan-lite' ),
                    'next_text' => __( 'Next &rarr;', 'dokan-lite' ),
                );

                if ( ! empty( $search_query ) ) {
                    $pagination_args['add_args'] = array(
                        'dokan_seller_search' => $search_query,
                    );
                }

                $page_links = paginate_links( $pagination_args );

                if ( $page_links ) {
                    $pagination_links  = '<div class="pagination-wrap">';
                    $pagination_links .= '<ul class="pagination"><li>';
                    $pagination_links .= join( "</li>\n\t<li>", $page_links );
                    $pagination_links .= "</li>\n</ul>\n";
                    $pagination_links .= '</div>';

                    echo $pagination_links; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
                }

                echo '</div>';
            }
            ?>

        <?php else:  ?>
            <p class="dokan-error"><?php esc_html_e( 'No vendor found!', 'dokan-lite' ); ?></p>
        <?php endif; ?>
    </div>
</div>