<?php



/**

 * Load Parent Theme's style.css

 */

add_action( 'wp_enqueue_scripts', 'et_ct_load_parent_styles' );

function et_ct_load_parent_styles() {

    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

}





/**

 * Load Child Theme's files

 */

add_action( 'init', 'et_ct_load_child_theme_files' );

function et_ct_load_child_theme_files(){

    require_once ( get_stylesheet_directory() . '/lib/init.php' );

}
/**
 * --------------------
 * ADD CUSTOM CODE HERE
 * --------------------
 */

//*********  plucking extra fields to single product page (Admin)  *********//
 function rosed_load_extras($field){

    // reset choices
    $field['choices'] = array();

    // getting products of extras category
    $args = array(
        'post_type'             => 'product',
        'post_status'           => 'publish',
        'ignore_sticky_posts'   => 1,
        'posts_per_page'        => '12',
        'tax_query'             => array(
            array(
                'taxonomy'      => 'product_cat',
                'field'         => 'term_id', //This is optional, as it defaults to 'term_id'
                'terms'         => 22,//cat id of extras(category)
                'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
            ),
            array(
                'taxonomy'      => 'product_visibility',
                'field'         => 'slug',
                'terms'         => 'exclude-from-catalog', // Possibly 'exclude-from-search' too
                'operator'      => 'NOT IN'
            )
        )
    );
    $products = new WP_Query($args);
    $products = $products->posts;
        foreach ($products as $key => $product) {
            $value = $product->ID;
            $label = $product->post_title;
            $field['choices'][ $value ] = $label;            
        }//end foreach

    return $field;
    
 }

 add_filter('acf/load_field/name=select_extras', 'rosed_load_extras',10);




//*********  plucking divi library layouts to single product page (Admin)  *********//
function rosed_load_how_it_works_layouts($field){

    // reset choices
    $field['choices'] = array();

    // getting divi library layouts of how_it_works category
    $args = array(
        'post_type'             => 'et_pb_layout',
        'post_status'           => 'publish',
        'numberposts'        => '-1',
        'tax_query'             => array(
            array(
                'taxonomy'      => 'layout_category',
                'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                'terms'         => 25,//cat id 
                'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
            ),
        )
    );
    $posts = get_posts($args);
    // var_dump($products);
        foreach ($posts as $key => $post) {
            $value = $post->ID;
            $label = $post->post_title;
            $field['choices'][ $value ] = $label;            
        }//end foreach

    return $field;
    
 }

 add_filter('acf/load_field/name=how_it_works_layout', 'rosed_load_how_it_works_layouts',10);



//*********  plucking extra fields to single product page (Clinet) *********//
 //***********************************************************************//
function rosed_before_add_to_cart_button() { 
        global $post;

        // ***for extras       
        $enable_extras = get_post_meta( $post->ID,'enable_extras');
        $extras = get_post_meta( $post->ID,'select_extras');
        if(sizeof($extras)>0 && $enable_extras){
            $extras = $extras[0]; 
        
        // var_dump($extras[0]);
        if(is_array($extras) && $enable_extras){
            $checkbox = '';

            foreach ($extras as $key => $extra) {
                $extra = (int)$extra;//basically product id
                $product = wc_get_product( $extra );
                $product_name = $product->get_name();
                $product_price = $product->get_price();
                $checkbox.= '<div class = "rosed_extra_checkbox">
                <label class="container_check_box">
                <input type = "checkbox" name = "rosed_product_extras[]" value="'.$extra.'" >
                <span class="checkmark"></span>
                </label>
                <div class = "rosed_extra_checkbox_wrap">
                    <h4> '.$product_name.'</h4>
                    <p>No tracking available</p>                
                </div>
                <div class = "rosed_extra_points">
                    <label>'.$product_price.' points</label>
                </div>
                </div>
                ';
            }//end foreach

            $checkboxes = '<div class = "rosed_extra_checkboxes_container">
                            <div class = "checkboxes_header_wrap">
                                <h4> See extras</h4>
                            </div>
                            <div class = "rosed_extra_checkboxes active">';
            $checkboxes.=$checkbox;
            $checkboxes.=' </div>
                        </div>';
            echo $checkboxes;
    
        }//if is array
      }//size of array
    }//function end 
          
add_action( 'woocommerce_before_add_to_cart_button', 'rosed_before_add_to_cart_button', 10, 0 ); 


//*********  add to cart extra(which is actually product) of product  *********//
 //**************************************************************************//
 function rosed_after_added_to_card( $cart_item_data,  $product_id,  $variation_id){
   
    $extras = isset($_POST['rosed_product_extras'])?$_POST['rosed_product_extras']:"";

    if(is_array($extras)){
        foreach ($extras as $key => $extra) {

            $product_idd = (int)$extra;//basically product id
            remove_action( 'woocommerce_add_cart_item_data', 'rosed_after_added_to_card', 10 );
            WC()->cart->add_to_cart( $product_idd );
           
        }//foreach    
    }//if

}
add_filter('woocommerce_add_cart_item_data', 'rosed_after_added_to_card', 10, 3);


//*********  adding tabs to single product  *********//
 //************************************************//
function rosed_single_product_tabs($tabs){
    // Adds the new tab
    global $post;
    $tabs = array();
    
    $enable_extras = get_post_meta( $post->ID,'enable_extras',true);
    $enable_timeline_and_revision_tab = get_post_meta( $post->ID,'enable_timeline_and_revision_tab',true);
    $enable_how_it_works_tab = get_post_meta( $post->ID,'enable_how_it_works_tab',true);

    // **how it works tab
    if($enable_how_it_works_tab){
        $tab = array(
                    'title'     => __( 'How it works', 'child-theme' ),
                    'priority'  => 50,
                    'callback'  => 'rosed_how_it_works_tab'    
            );
            $tabs['how_it_works'] = $tab;     
    }//if


    // **timeline and revisions tab
    if($enable_timeline_and_revision_tab){
        $tab = array(
                'title'     => __( 'Timeline & revision steps', 'child-theme' ),
                'priority'  => 50,
                'callback'  => 'rosed_timeline_and_revision_tab'
            );
            $tabs['timeline'] = $tab;     
    }//if

    // **About extras tab    
    if($enable_extras){
        $tab = array(
                'title'     => __( 'About extras', 'child-theme' ),
                'priority'  => 50,
                'callback'  => 'rosed_about_extras_tab'
            );
            $tabs['about_extras'] = $tab;     
    }//if
    // var_dump($tabs);
    return $tabs;

}

add_filter( 'woocommerce_product_tabs', 'rosed_single_product_tabs',99);

//**how it works tab body */
function rosed_how_it_works_tab(){
    global $post;
    $divi_lib_layout_id = get_post_meta( $post->ID,'how_it_works_layout',true);    
    echo do_shortcode('[et_pb_section global_module="'.$divi_lib_layout_id.'"][/et_pb_section]');
}

//**Timeline & revision tab body */
function rosed_timeline_and_revision_tab(){
    global $post;
    $timeline_and_revision_outer_steps = get_field( 'timeline_and_revision_steps' , $post->ID );    


    if(is_array($timeline_and_revision_outer_steps)){
        
        // if(sizeof($timeline_and_revision_outer_steps>0)){
            $timeline_wrap = "";
            $container = "";
            foreach ($timeline_and_revision_outer_steps as $key0 => $timeline_and_revision_outer_step) {
                
                $outer_step_heading = $timeline_and_revision_outer_step['steps_heading'];
                
                $timeline_and_revision_inner_steps =  $timeline_and_revision_outer_step['steps'];

                $timeline_wrap.='<div class = "rosed_timeline_wrap"><h4>'.$outer_step_heading.'</h4>';

                if(is_array($timeline_and_revision_inner_steps)){
        
            
                        foreach ($timeline_and_revision_inner_steps as $key1 => $timeline_and_revision_inner_step) {
                            
                            $label = $timeline_and_revision_inner_step['label'];
                            $description = $timeline_and_revision_inner_step['description'];
                            $timeline_wrap.='<div class = "rosed_timeline_step">
                            <p class = "rosed_timeline_step_label">'.$label.'</p>
                            <p class = "rosed_timeline_step_description">'.$description.'</p>
                            <span class = "rosed_timeline_step_dot"></span>
                            </div>';

                        }//foreach for inner steps OR inner loop


                }//if is inner array

                $timeline_wrap.='</div>';

            }//foreach for outer steps OR outer loop


    }//if is outer array

    $container = '<div id = "rosed_single_product_timline_container">';
    $container.=$timeline_wrap;
    $container.='</div>';
    echo $container;
    // var_dump($timeline_and_revision_steps);
}

//**About Extras tab body */
function rosed_about_extras_tab(){
    global $post;

        $enable_extras = get_post_meta( $post->ID,'enable_extras');
        $extras = get_post_meta( $post->ID,'select_extras');
        $extras = $extras[0]; 
        
        if(is_array($extras) && $enable_extras){
            $layout_container = '';
            $layout = '';
            foreach ($extras as $key => $extra) {
                $extra = (int)$extra;//basically product id
                $product = wc_get_product( $extra );
                $product_name = $product->get_name();
                $featured_image = ( wp_get_attachment_url($product->get_image_id()) ) ? wp_get_attachment_url($product->get_image_id()) : wc_placeholder_img_src();
                // $featured_image = $product->get_featured();
                
                $description = $product->get_description(); 

                
                $layout.= '<div class = "rosed_extra_wrap">
                <div class = "rosed_extra_left">
                    <img src = "'.$featured_image.'" />
                </div>
                <div class = "rosed_extra_right">
                    <div class = "rosed_extra_right_content_wrap">
                        <h4>'.$product_name.'</h4>
                        <p>'.$description.'</p>
                    </div>
                </div>
                </div>
                ';
            }//end foreach

            $layout_container = '<div class = "rosed_single_product_extras_tab_container">';
            $layout_container.=$layout;
            $layout_container.='</div>';
            echo $layout_container;
    
        }//if is array
}



/**
 * Add ACF Option Page
 */
if( function_exists('acf_add_options_page') ) {

    acf_add_options_page(array(
        'page_title' 	=> 'Site Settings',
        'menu_title'	=> 'Site Settings',
        'menu_slug' 	=> 'rd_site_sttings',
        'capability'	=> 'edit_posts',
        'redirect'		=> false
    ));
    
}


/**
 * header menu short-code for points and cart icon
 */
function rosed_header_menu_points_cart(){ 
    $template = "";
    if(is_user_logged_in()){
        global $woocommerce;
        $template =  '<div class="rosed-points-cart-container">
        <div class="rosed-points-wrap">
            <img src="/wp-content/uploads/2021/06/user.png" alt=""> <span>'.do_shortcode("[rs_my_reward_points]").'</span> points
        </div>
        <a href = "/cart/">
        <div class="rosed-cart-wrap">
            <img src="/wp-content/uploads/2021/06/cart.png" alt="">
            <span id = "rosed-cart-count">'.WC()->cart->get_cart_contents_count().'</span>
        </div>
        </a>
    </div>';
    }

    return $template;

}
add_shortcode('rosed_header_menu_points_cart','rosed_header_menu_points_cart');


/**
 * product attribute extra fields
 */
function rosed_custom_attribute_label( $label, $name, $product ) {

    if (is_product()) {
    
        global $post;
        $taxonomy = 'pa_'.$name;
        $tooltip_for_every_variations = get_field( 'tooltip_for_every_variation' , $post->ID ); 

        // var_dump($tooltip_for_every_variation);
        if(is_array($tooltip_for_every_variations)){
            foreach ($tooltip_for_every_variations as $key => $tooltip_for_every_variation) {
                $compare_taxenomy = 'pa_'.$tooltip_for_every_variation['variation_name'];    
                if($compare_taxenomy == $taxonomy){
                    $label = '<div class="rosed-custom-label-wrap"><div class = "label-btn-wrap"><span>'.$tooltip_for_every_variation["variation_name"].'</span><button class = "rosed-tool-tip-qm">?</button></div> <div class = "rosed-tool-tip-desc"><div class = "rosed-tool-tip-triangle"></div>'.$tooltip_for_every_variation["tooltip_text"].'</div> </div>';
                }
            }
        }
        // if( $taxonomy == 'pa_inclusions' ){
        //     $label .= '<div class="custom-label">' . __('MY TEXT', 'woocommerce') . '</div>';
        // }
        return $label;
    }
    return $label;
}
add_filter( 'woocommerce_attribute_label', 'rosed_custom_attribute_label', 10, 3 );



/**
 * checkout page gate way enable
 */

add_filter( 'woocommerce_available_payment_gateways', 'rosed_unhook_payment_methods' ); 
function rosed_unhook_payment_methods( $available_gateways ){


    // global $woocommerce;
    // $cart_total = WC()->cart->total;


    // if( isset($_GET['lsg']) && $_GET['lsg'] == 'yes' && isset($_GET['key']) ){
    //     $order_id                = wc_get_order_id_by_order_key( $_GET['key'] );
    //     $order                   = wc_get_order($order_id);
    //     $order_payment_method_id = $order->get_payment_method();
    //     foreach( $available_gateways as $gateway_id => $gateway ){
    //         if( $gateway_id !== $order_payment_method_id ){
    //             unset( $available_gateways[ $gateway_id ] );
    //         }
    //     }
    // }

    // var_dump($available_gateways);
    return $available_gateways;
}


