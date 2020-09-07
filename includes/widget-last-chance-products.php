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
			)
		);

		add_action( 'woocommerce_product_query', [ $this, 'last_chance_filter' ], 99, 2 );

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
				<input type="checkbox" name="last_chance_products" id="last_chance_products" value="1" <?php checked( $is_checked, true, true ) ?> >
			</div>
			<button class="button btn" type="submit"  style="width: 100%; margin-top:30px;">Filter</button>
		</form>

		<?php
		$this->widget_end( $args );
	}
	
	protected function get_current_page_url(){

		$link = parent::get_current_page_url();

		if ( isset( $_GET['last_chance_products'] ) ) {
			$link = add_query_arg( 'last_chance_products', (bool)$_GET['last_chance_products'] == 'on' ? true : false , $link );
		}

		return $link;
	}


	public function last_chance_filter( $q, $instance ){
		
		if( empty( $_GET['last_chance_products'] ) ) {
			return;
		}

		$users = get_users(
			array(
				'role__in'   => array( 'seller', 'administrator' ),
				'number'     => -1,
				'status'     => 'approved',
				'fields'  	 => 'ID',
				'meta_query' => array(
					'relationship' => 'AND',
					array(
						'key' 	  => 'product_pack_enddate',
						'compare' => 'BETWEEN',
						'type' 	  => 'DATE',
						'value'   => [ date('Y-m-d 00:00:00'), date( 'Y-m-d H:i:s', strtotime( "+3 days" ) ) ]
					)
				),
			)
		);

		if( count( $users ) ) {
			$q->set( 'author__in', $users );
		}else{
			$q->set( 'author__in', array( 0 ) );
		}
	}

}