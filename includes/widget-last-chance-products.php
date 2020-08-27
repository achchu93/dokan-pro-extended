<?php

defined('ABSPATH') || exit;

class DPE_Last_Chance_Product_Widget extends WC_Widget {

    public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_last_chance_product';
		$this->widget_description = 'Display a checkbox to filter products in your store by vendor subscription end date.';
		$this->widget_id          = 'dpe_last_chance_product';
		$this->widget_name        = 'Last chance products';
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => 'Last chance products',
				'label' => 'Title',
			),
		);
		parent::__construct();
	}


    public function widget( $args, $instance ) {
		global $wp;

        if ( ! is_shop() && ! is_product_taxonomy() ) {
			return;
        }
        
		$this->widget_start( $args, $instance );
		
		if ( '' === get_option( 'permalink_structure' ) ) {
			$form_action = remove_query_arg( array( 'page', 'paged', 'product-page' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		} else {
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
		}

		$is_checked = !empty( $_GET['last_chance_products'] ) ? $_GET['last_chance_products'] : false;
		?>
		
		<form method="get" action="<?php echo esc_url( $form_action ); ?>">
			<div class="last-chance-products-wrapper">
				<label for="">Last chance products</label>
				<input type="checkbox" name="last_chance_products" id="last_chance_products" <?php checked( $is_checked, true, true ) ?> >
			</div>
		</form>

		<?php
		$this->widget_end( $args );
	}
	
	protected function get_current_page_url(){

		return parent::get_current_page_url();
	}

}