<?php

// Include the class
require_once( 'wp-less/wp-less.php' );

// enqueue a .less style sheet
if ( ! is_admin() ){
	wp_enqueue_style( 'animatecss', get_stylesheet_directory_uri() . '/lib/animate/animate.css' );
    wp_enqueue_style( 'lessstyle', get_stylesheet_directory_uri() . '/lessstyle.less' );
	
}

register_sidebar(array(
	'name' => 'Sidebar',
	'id' => 'sidebar',
	'description' => 'Sidebar.',
	'before_title' => '<h2 class="widget-title">',
	'after_title' => '</h2>',
	'before_widget' => '<div id="%1$s" class="well widget sidebar-widget %2$s">',
	'after_widget'  => '</div>'
));

function register_my_menus() {
	register_nav_menus(
		array('header-menu' => __( 'Header Menu' ) )
	);
}
add_action( 'init', 'register_my_menus' );

function register_bootstrap_js() {
	wp_deregister_script( 'jquery' );
    wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js');
    wp_enqueue_script( 'jquery' );
	wp_register_script( 'bootstrap-dropdown', get_template_directory_uri().'/lib/bootstrap/js/bootstrap-dropdown.js', array('jquery'),'2.0');
	wp_enqueue_script( 'bootstrap-dropdown' );
	
	wp_register_script( 'bootpress-script', get_template_directory_uri().'/script.js', array('jquery'),'1.0');
	wp_enqueue_script( 'bootpress-script' );
}
if(!is_admin()){
	add_action( 'init', 'register_bootstrap_js' );
}

function app_template_path() {
	return APP_Wrapping::$main_template;
}

function app_template_base() {
	return APP_Wrapping::$base;
}


class APP_Wrapping {

	/**
	 * Stores the full path to the main template file
	 */
	static $main_template;

	/**
	 * Stores the base name of the template file; e.g. 'page' for 'page.php' etc.
	 */
	static $base;

	static function wrap( $template ) {
		self::$main_template = $template;

		self::$base = substr( basename( self::$main_template ), 0, -4 );

		if ( 'index' == self::$base )
			self::$base = false;

		$templates = array( 'wrapper.php' );

		if ( self::$base )
			array_unshift( $templates, sprintf( 'wrapper-%s.php', self::$base ) );

		return locate_template( $templates );
	}
}

add_filter( 'template_include', array( 'APP_Wrapping', 'wrap' ), 99 );


add_action( 'after_setup_theme', 'bootstrap_setup' );

if ( ! function_exists( 'bootstrap_setup' ) ){

	function bootstrap_setup(){

		class Bootstrap_Walker_Nav_Menu extends Walker_Nav_Menu {

			
			function start_lvl( &$output, $depth ) {

				$indent = str_repeat( "\t", $depth );
				$output	   .= "\n$indent<ul class=\"dropdown-menu\">\n";
				
			}

			function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
				
				$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

				$li_attributes = '';
				$class_names = $value = '';

				$classes = empty( $item->classes ) ? array() : (array) $item->classes;
				if ($args->has_children){
					$classes[] 		= 'dropdown';
					$li_attributes .= 'data-dropdown="dropdown"';
				}
				$classes[] = 'menu-item-' . $item->ID;
				$classes[] = ($item->current) ? 'active' : '';


				$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
				$class_names = ' class="' . esc_attr( $class_names ) . '"';

				$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
				$id = strlen( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

				$output .= $indent . '<li' . $id . $value . $class_names . $li_attributes . '>';

				$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
				$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
				$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
				$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
				$attributes .= ($args->has_children) 				? ' class="dropdown-toggle"' 					   : ''; 

				$item_output = $args->before;
				$item_output .= '<a'. $attributes .'>';
				$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
				if($args->has_children){
					$item_output .= '<b class="caret"></b>';
				}
				$item_output .= '</a>';
				$item_output .= $args->after;

				$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
			}

			function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {
				
				if ( !$element )
					return;
				
				$id_field = $this->db_fields['id'];

				//display this element
				if ( is_array( $args[0] ) ) 
					$args[0]['has_children'] = ! empty( $children_elements[$element->$id_field] );
				else if ( is_object( $args[0] ) ) 
					$args[0]->has_children = ! empty( $children_elements[$element->$id_field] ); 
				$cb_args = array_merge( array(&$output, $element, $depth), $args);
				call_user_func_array(array(&$this, 'start_el'), $cb_args);

				$id = $element->$id_field;

				// descend only when the depth is right and there are childrens for this element
				if ( ($max_depth == 0 || $max_depth > $depth+1 ) && isset( $children_elements[$id]) ) {

					foreach( $children_elements[ $id ] as $child ){

						if ( !isset($newlevel) ) {
							$newlevel = true;
							//start the child delimiter
							$cb_args = array_merge( array(&$output, $depth), $args);
							call_user_func_array(array(&$this, 'start_lvl'), $cb_args);
						}
						$this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
					}
						unset( $children_elements[ $id ] );
				}

				if ( isset($newlevel) && $newlevel ){
					//end the child delimiter
					$cb_args = array_merge( array(&$output, $depth), $args);
					call_user_func_array(array(&$this, 'end_lvl'), $cb_args);
				}

				//end this element
				$cb_args = array_merge( array(&$output, $element, $depth), $args);
				call_user_func_array(array(&$this, 'end_el'), $cb_args);
				
			}
			
		}

	}

}