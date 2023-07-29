<?php

/**
 * Plugin Name: Contact Form 7 - Dynamic Text Extension
 * Plugin URI: https://sevenspark.com/goods/contact-form-7-dynamic-text-extension
 * Description: This plugin extends Contact Form 7 by adding dynamic form fields that accept any shortcode to generate default values and placeholder text. Requires Contact Form 7.
 * Version: 3.4.0
 * Author: SevenSpark, AuRise Creative
 * Author URI: https://sevenspark.com
 * License: GPL2
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * Text Domain: contact-form-7-dynamic-text-extension
 */

/*
    Copyright 2010-2023 Chris Mavricos, SevenSpark <https://sevenspark.com>
    Copyright 2022-2023 Tessa Watkins, AuRise Creative <https://aurisecreative.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Define current version
define('WPCF7DTX_VERSION', '3.4.0');

// Define root directory
defined('WPCF7DTX_DIR') || define('WPCF7DTX_DIR', __DIR__);

// Define root file
defined('WPCF7DTX_FILE') || define('WPCF7DTX_FILE', __FILE__);

/**
 * Initialise Plugin
 *
 * @return void
 */
function wpcf7dtx_init()
{
    add_action('wpcf7_init', 'wpcf7dtx_add_shortcode_dynamictext'); // Add custom form tags to CF7
    add_filter('wpcf7_validate_dynamictext*', 'wpcf7dtx_dynamictext_validation_filter', 20, 2); // Validate custom form tags
}
add_action('plugins_loaded', 'wpcf7dtx_init', 20);


/**
 * Add Custom Shortcodes to Contact Form 7
 *
 * @return void
 */
function wpcf7dtx_add_shortcode_dynamictext()
{
    //Add the dynamic text and hidden form fields
    wpcf7_add_form_tag(
        array(
            'dynamictext', 'dynamictext*',
            'dynamichidden', 'dynamichidden*' //Required hidden fields do nothing
        ),
        'wpcf7dtx_dynamictext_shortcode_handler', //Callback
        array( //Features
            'name-attr' => true,
            'dtx_pageload' => true
        )
    );
}

/**
 * Register Frontend Script
 *
 * Register the frontend script to be optionally loaded later.
 *
 * @since 3.5.0
 *
 * @param string $hook Hook suffix for the current page
 *
 * @return void
 */
function wpcf7dtx_enqueue_frontend_assets($hook = '')
{
    $debug = defined('WP_DEBUG') && constant('WP_DEBUG');
    $url = plugin_dir_url(WPCF7DTX_FILE);
    $path = plugin_dir_path(WPCF7DTX_FILE);
    wp_register_script(
        'wpcf7dtx', // Handle
        $url . 'assets/scripts/dtx' . ($debug ? '' : '.min') . '.js', // Source
        array('jquery-core'), // Dependencies
        $debug ? @filemtime($path . 'assets/scripts/dtx.js') : WPCF7DTX_VERSION, // Version
        array('in_footer' => true, 'strategy' => 'defer') // Defer loading in footer

    );
    wp_localize_script(
        'wpcf7dtx', // Handle
        'dtx_obj', // Object
        array('ajax_url' => admin_url('admin-ajax.php')) // Data
    );
}
add_action('wp_enqueue_scripts', 'wpcf7dtx_enqueue_frontend_assets');

/**
 * Include Utility Functions
 */
include_once(WPCF7DTX_DIR . '/includes/utilities.php');

/**
 * Get Dynamic Value
 *
 * @since 3.2.2
 *
 * @param string $value The form tag value.
 * @param WPCF7_FormTag|false $tag Optional. Use to look up default value.
 *
 * @return string The dynamic output or the original value, not escaped or sanitized.
 */
function wpcf7dtx_get_dynamic($value, $tag = false)
{
    if ($tag !== false) {
        $default = $tag->get_option('defaultvalue', '', true);
        if (!$default) {
            $default = $tag->get_default_option(strval(reset($tag->values)));
        }
        $value = wpcf7_get_hangover($tag->name, $default);
    }
    $value = apply_filters('wpcf7dtx_sanitize', $value);
    if (is_string($value) && !empty($value)) {
        // If a shortcode was passed as the options, evaluate it and use the result
        $shortcode_tag = '[' . $value . ']';
        $shortcode_output = do_shortcode($shortcode_tag); //Shortcode value
        if (is_string($shortcode_output) && $shortcode_output != $shortcode_tag) {
            return apply_filters('wpcf7dtx_sanitize', $shortcode_output);
        }
    }
    return $value;
}

/**
 * Form Tag Handler
 *
 * @param WPCF7_FormTag $tag Current Contact Form 7 tag object
 *
 * @return string HTML output of the shortcode
 */
function wpcf7dtx_dynamictext_shortcode_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    //Validate
    $validation_error = wpcf7_get_validation_error($tag->name);

    //Configure classes
    $class = wpcf7_form_controls_class($tag->type, 'wpcf7dtx-dynamictext');
    if ($validation_error) {
        $class .= ' wpcf7-not-valid';
    }

    //Configure input attributes
    $atts = array();
    $atts['name'] = $tag->name;
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['size'] = $tag->get_size_option('40');
    $atts['maxlength'] = $tag->get_maxlength_option();
    $atts['minlength'] = $tag->get_minlength_option();
    $atts['aria-invalid'] = $validation_error ? 'true' : 'false';
    switch ($tag->basetype) {
        case 'dynamichidden':
            $atts['type'] = 'hidden'; //Override type as hidden
            break;
        default: // Includes `dynamictext`
            $atts['type'] = 'text'; //Override type as text
            break;
    }
    if ($atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength']) {
        unset($atts['maxlength'], $atts['minlength']);
    }
    if ($tag->has_option('readonly')) {
        $atts['readonly'] = 'readonly';
    }
    if ($tag->is_required() && $atts['type'] !== 'hidden') {
        $atts['aria-required'] = 'true';
        $atts['required'] = 'required';
    }

    // Evaluate the dynamic value
    $value = wpcf7dtx_get_dynamic(false, $tag);

    // Identify placeholder
    if ($tag->has_option('placeholder') || $tag->has_option('watermark')) {
        //Reverse engineer what JS did (converted quotes to HTML entities --> URL encode) then sanitize
        $placeholder = html_entity_decode(urldecode(implode('', (array)$tag->get_option('placeholder'))), ENT_QUOTES);
        if ($placeholder) {
            //If a different placeholder text has been specified, set both attributes
            $placeholder = wpcf7dtx_get_dynamic($placeholder);
            $atts['placeholder'] = $placeholder;
            $atts['value'] = $value;
        } else {
            //Default behavior of using the value as the placeholder
            $atts['placeholder'] = $value;
        }
    } else {
        $atts['value'] = $value;
    }

    if ($atts['type'] == 'hidden') {
        // Always disable for hidden fields
        $atts['autocomplete'] = 'off';
    } else {
        // Disable autocomplete for this field if a value has been specified
        $atts['autocomplete'] = $atts['value'] ? 'off' : $tag->get_option('autocomplete', '[-0-9a-zA-Z]+', true);
    }

    // Page load attribute
    if ($tag->has_option('dtx_pageload')) {
        $atts['data-dtx-value'] = rawurlencode(sanitize_text_field(implode('', (array)$tag->raw_values)));
        $class .= ' dtx-pageload';
        if (wp_style_is('wpcf7dtx', 'registered') && !wp_script_is('wpcf7dtx', 'queue')) {
            // If already registered, just enqueue it
            wp_enqueue_script('wpcf7dtx');
        } elseif (!wp_style_is('wpcf7dtx', 'registered')) {
            // If not registered, do that first, then enqueue it
            wpcf7dtx_enqueue_frontend_assets();
            wp_enqueue_script('wpcf7dtx');
        }
    }

    // Wrap up class attribute
    $atts['class'] = $tag->get_class_option($class);

    if ($atts['name'] == 'url_scheme') {
        au_debug_log($atts, '[DTX] Input Attributes');
    }

    //Output the HTML
    return sprintf(
        '<span class="wpcf7-form-control-wrap %s" data-name="%s"><input %s />%s</span>',
        sanitize_html_class($tag->name),
        esc_attr($tag->name),
        wpcf7_format_atts($atts), //This function already escapes attribute values
        $validation_error
    );
}

/**
 *  Validate Required Dynamic Text Field
 *
 * @param WPCF7_Validation $result the current validation result object
 * @param WPCF7_FormTag $tag the current form tag being filtered for validation
 *
 * @return WPCF7_Validation a possibly modified validation result object
 */
function wpcf7dtx_dynamictext_validation_filter($result, $tag)
{
    //Sanitize value
    $value = empty($_POST[$tag->name]) ? '' : sanitize_text_field(strval($_POST[$tag->name]));

    //Validate
    if ('dynamictext' == $tag->basetype) {
        if ($tag->is_required() && '' == $value) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        }
    }
    if (!empty($value)) {
        $maxlength = $tag->get_maxlength_option();
        $minlength = $tag->get_minlength_option();
        if ($maxlength && $minlength && $maxlength < $minlength) {
            $maxlength = $minlength = null;
        }
        $code_units = wpcf7_count_code_units($value);
        if (false !== $code_units) {
            if ($maxlength && $maxlength < $code_units) {
                $result->invalidate($tag, wpcf7_get_message('invalid_too_long'));
            } elseif ($minlength && $code_units < $minlength) {
                $result->invalidate($tag, wpcf7_get_message('invalid_too_short'));
            }
        }
    }
    return $result;
}

/**
 * Set Session Variables on Page Load
 *
 * These are needed for AJAX calls.
 *
 * @since 3.5.0
 *
 * @return void
 */
function wpcf7dtx_start_session()
{
    if (wpcf7dtx_use_session()) {
        $obj = get_queried_object(); // Get the current WordPress queried object
        if (!is_null($obj)) {
            $_SESSION['dtx_obj'] = $obj;
            if ($obj instanceof WP_User) {
                // This is an author page
                $_SESSION['dtx_obj_type'] = 'user';
                $_SESSION['dtx_obj_id'] = esc_html($obj->ID);
            } elseif (property_exists($obj, 'ID')) {
                // This is a post object
                $_SESSION['dtx_obj_type'] = 'post';
                $_SESSION['dtx_obj_id'] = esc_html($obj->ID);
            } elseif (property_exists($obj, 'term_id')) {
                // This is a taxonomy with a term ID
                $_SESSION['dtx_obj_type'] = 'term';
                $_SESSION['dtx_obj_id'] = esc_html($obj->term_id);
            }
        } elseif (is_archive()) {
            // Possibly a date or formats archive because the queried object is null
            $_SESSION['dtx_obj_type'] = 'archive';
            $_SESSION['dtx_archive_title'] = esc_html(get_the_archive_title());
        }
    }
}
add_action('get_header', 'wpcf7dtx_start_session');

/**
 * AJAX Request Handler for After Page Loading
 *
 * @since 3.5.0
 *
 * @param array $_POST A sequential array of url encoded shortcode values to evaluate
 *
 * @return array A sequential array of objects with `raw_value` and `value` keys
 */
function wpcf7dtx_js_handler()
{
    $return = array();
    $shortcodes = wpcf7dtx_array_has_key('shortcodes', $_POST);
    if (is_array($shortcodes) && count($shortcodes)) {
        foreach ($shortcodes as $raw_value) {
            $value = sanitize_text_field(rawurldecode($raw_value));
            if (!empty($value)) {
                $value = wpcf7dtx_get_dynamic($value);
            }
            $return[] = array(
                'raw_value' => esc_attr($raw_value),
                'value' => esc_attr($value)
            );
        }
    }
    wp_die(json_encode($return));
}
add_action('wp_ajax_wpcf7dtx', 'wpcf7dtx_js_handler'); // Add AJAX call for logged in users
add_action('wp_ajax_nopriv_wpcf7dtx', 'wpcf7dtx_js_handler'); // Add AJAX call for anonymous users

if (is_admin()) {
    /**
     * Include the Admin Stuff
     */
    include_once(WPCF7DTX_DIR . '/includes/admin.php');
}

/**
 * Included Shortcodes
 */
include_once(WPCF7DTX_DIR . '/includes/shortcodes.php');
