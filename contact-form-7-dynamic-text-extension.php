<?php

/*
Plugin Name: Contact Form 7 - Dynamic Text Extension
Plugin URI: http://sevenspark.com/wordpress-plugins/contact-form-7-dynamic-text-extension
Description: Provides a dynamic text field that accepts any shortcode to generate the content.  Requires Contact Form 7
Version: 2.0.2.1
Author: Chris Mavricos, SevenSpark
Author URI: http://sevenspark.com
License: GPL2
*/

/*  Copyright 2010-2017  Chris Mavricos, SevenSpark http://sevenspark.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


add_action( 'plugins_loaded', 'wpcf7dtx_init' , 20 );
function wpcf7dtx_init(){
	add_action( 'wpcf7_init', 'wpcf7dtx_add_shortcode_dynamictext' );
	add_filter( 'wpcf7_validate_dynamictext*', 'wpcf7dtx_dynamictext_validation_filter', 10, 2 );
}



function wpcf7dtx_add_shortcode_dynamictext() {
	wpcf7_add_form_tag(
		array( 'dynamictext' , 'dynamictext*' , 'dynamichidden' ),
		'wpcf7dtx_dynamictext_shortcode_handler', true );
}
function wpcf7dtx_dynamictext_shortcode_handler( $tag ) {
	$tag = new WPCF7_FormTag( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type, 'wpcf7dtx-dynamictext' );


	if ( $validation_error )
		$class .= ' wpcf7-not-valid';

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->has_option( 'readonly' ) )
		$atts['readonly'] = 'readonly';

	if ( $tag->is_required() )
		$atts['aria-required'] = 'true';

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = wpcf7_get_hangover( $tag->name, $value );

	$scval = do_shortcode('['.$value.']');
	if( $scval != '['.$value.']' ){
		$value = esc_attr( $scval );
	}

	$atts['value'] = $value;
	
//echo '<pre>'; print_r( $tag ); echo '</pre>';
	switch( $tag->basetype ){
		case 'dynamictext':
			$atts['type'] = 'text';
			break;
		case 'dynamichidden':
			$atts['type'] = 'hidden';
			break;
		default:
			$atts['type'] = 'text';
			break;
	}

	$atts['name'] = $tag->name;

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}

//add_filter( 'wpcf7_validate_text', 'wpcf7_text_validation_filter', 10, 2 );  // in init
function wpcf7dtx_dynamictext_validation_filter( $result, $tag ) {
	$tag = new WPCF7_FormTag( $tag );

	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
		: '';

	if ( 'dynamictext' == $tag->basetype ) {
		if ( $tag->is_required() && '' == $value ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		}
	}

	if ( ! empty( $value ) ) {
		$maxlength = $tag->get_maxlength_option();
		$minlength = $tag->get_minlength_option();

		if ( $maxlength && $minlength && $maxlength < $minlength ) {
			$maxlength = $minlength = null;
		}

		$code_units = wpcf7_count_code_units( $value );

		if ( false !== $code_units ) {
			if ( $maxlength && $maxlength < $code_units ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_long' ) );
			} elseif ( $minlength && $code_units < $minlength ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_short' ) );
			}
		}
	}

	return $result;
}




if ( is_admin() ) {
	//add_action( 'admin_init', 'wpcf7dtx_add_tag_generator_dynamictext', 25 );
	add_action( 'wpcf7_admin_init' , 'wpcf7dtx_add_tag_generator_dynamictext' , 100 );
}

function wpcf7dtx_add_tag_generator_dynamictext() {

	if ( ! class_exists( 'WPCF7_TagGenerator' ) ) return;

	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'dynamictext', __( 'dynamic text', 'contact-form-7' ),
		'wpcf7dtx_tag_generator_dynamictext' );

	$tag_generator->add( 'dynamichidden', __( 'dynamic hidden', 'contact-form-7' ),
		'wpcf7dtx_tag_generator_dynamictext' );
}


function wpcf7dtx_tag_generator_dynamictext( $contact_form , $args = '' ){
	$args = wp_parse_args( $args, array() );
	$type = $args['id'];

	$description;


	switch( $type ){
		case 'dynamictext':
			$description = __( "Generate a form-tag for a single-line plain text input field, with a dynamically generated default value.", 'contact-form-7' );
			//$type = 'text';
			break;
		case 'dynamichidden':
			$description = __( "Generate a form-tag for a hidden input field, with a dynamically generated default value.", 'contact-form-7' );
			//$type = 'hidden';
			break;
		default:
			//$type = 'text';
			break;
	}
	

	


?>
<div class="control-box">
<fieldset>
<legend><?php echo $description; ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Dynamic value', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
	<?php _e( 'You can enter any short code.  Just leave out the square brackets (<code>[]</code>) and only use single quotes (<code>\'</code> not <code>"</code>).  <br/>So <code>[shortcode attribute="value"]</code> becomes <code>shortcode attribute=\'value\'</code>', 'contact-form-7' ); ?></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>

	<br class="clear" />

</div>
<?php
}






/*****************************************************
 * CF7 DTX Included Shortcodes
 * 
 * Used like this:
 * 
 * CF7_GET val='value'
 * 
 * No [] and single quotes ' rather than double "
 * 
 *****************************************************/

/* Insert a $_GET variable */
function cf7_get($atts){
	extract(shortcode_atts(array(
		'key' => 0,
	), $atts));
	$value = '';
	if( isset( $_GET[$key] ) ){
		$value = urldecode($_GET[$key]);
	}
	return $value;
}
add_shortcode('CF7_GET', 'cf7_get');

/* See http://codex.wordpress.org/Function_Reference/get_bloginfo */
function cf7_bloginfo($atts){
	extract(shortcode_atts(array(
		'show' => 'name'
	), $atts));
	
	return get_bloginfo($show);
}
add_shortcode('CF7_bloginfo', 'cf7_bloginfo');

/* Insert a $_POST variable (submitted form value)*/
function cf7_post($atts){
	extract(shortcode_atts(array(
		'key' => -1,
	), $atts));
	if($key == -1) return '';
	$val = '';
	if( isset( $_POST[$key] ) ){
		$val = $_POST[$key];
	}
	return $val;
}
add_shortcode('CF7_POST', 'cf7_post');

/* Insert a $post (Blog Post) Variable */
function cf7_get_post_var($atts){
	extract(shortcode_atts(array(
		'key' => 'post_title',
	), $atts));
	
	switch($key){
		case 'slug':
			$key = 'post_name';
			break;
		case 'title':
			$key = 'post_title';
			break;
	}
	
	global $post;
	//echo '<pre>'; print_r($post); echo '</pre>';
	$val = $post->$key;
	return $val;
}
add_shortcode('CF7_get_post_var', 'cf7_get_post_var');

/* Insert the current URL */
function cf7_url(){
	$pageURL = 'http';
 	if( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on"){ $pageURL .= "s"; }
 	
 	$pageURL .= "://";
 	
 	if( isset( $_SERVER["SERVER_PORT"] ) && $_SERVER["SERVER_PORT"] != "80" ){
  		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 	} else {
  		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 	}
 	return $pageURL;
}
add_shortcode('CF7_URL', 'cf7_url');

/* Insert a Custom Post Field
 * New in 1.0.4 
 */
function cf7_get_custom_field($atts){
	extract(shortcode_atts(array(
		'key' => '',
		'post_id' => -1,
		'obfuscate'	=> 'off'
	), $atts));
	
	if($post_id < 0){
		global $post;
		if(isset($post)) $post_id = $post->ID;
	}
	
	if($post_id < 0 || empty($key)) return '';
		
	$val = get_post_meta($post_id, $key, true);
	
	if($obfuscate == 'on'){
		$val = cf7dtx_obfuscate($val);
	}
	
	return $val;
	
}
add_shortcode('CF7_get_custom_field', 'cf7_get_custom_field');

/* Insert information about the current user
 * New in 1.0.4 
 * See https://codex.wordpress.org/Function_Reference/wp_get_current_user 
 */
function cf7_get_current_user($atts){
	extract(shortcode_atts(array(
		'key' => 'user_login',
	), $atts));
	$val = '';
	if( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$val = $current_user->$key;
	}
	return $val;
}
add_shortcode('CF7_get_current_user', 'cf7_get_current_user');



function cf7_get_referrer( $atts ){
	return isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
}
add_shortcode( 'CF7_referrer' , 'cf7_get_referrer' );


function cf7dtx_obfuscate($val){
	$link = '';
	foreach(str_split($val) as $letter)
		$link .= '&#'.ord($letter).';';
	return $link;
}

function cf7dtx_cf7com_links() {
	$links = '<div class="cf7com-links">'
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'Contactform7.com', 'wpcf7' ) ) . '</a>&ensp;'
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/docs/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'Docs', 'wpcf7' ) ) . '</a> - '
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/faq/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'FAQ', 'wpcf7' ) ) . '</a> - '
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/support/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'Support', 'wpcf7' ) ) . '</a>'
		. ' - <a href="'. esc_url_raw( __( 'http://sevenspark.com/wordpress-plugins/contact-form-7-dynamic-text-extension', 'wpcf7') )
		. '" target="_blank">'.__( 'Dynamic Text Extension' , 'wpcf7').'</a> by <a href="' . esc_url_raw( __( 'http://sevenspark.com', 'wpcf7') ).'" target="_blank">'
		. esc_html( __( 'SevenSpark', 'wpcf7' ) ). '</a> <a href="'.esc_url_raw( __('http://bit.ly/bVogDN')).'" target="_blank">'
		. esc_html( __( '[Donate]')).'</a>'
		. '</div>';
	return $links;
}
add_filter('wpcf7_cf7com_links', 'cf7dtx_cf7com_links');

/*function obf($atts){
	extract(shortcode_atts(array(
		'val' => ''
	), $atts));
	return $val.' : '. cf7dtx_obfuscate($val);
}
add_shortcode('obf', 'obf');*/

