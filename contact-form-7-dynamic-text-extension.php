<?php

/**
 * Plugin Name: Contact Form 7 - Dynamic Text Extension
 * Plugin URI: https://sevenspark.com/goods/contact-form-7-dynamic-text-extension
 * Description: This plugin extends Contact Form 7 by adding dynamic form fields that accept any shortcode to generate default values and placeholder text. Requires Contact Form 7.
 * Version: 3.5.4
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
define('WPCF7DTX_VERSION', '3.5.4');

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
    add_action('wpcf7_init', 'wpcf7dtx_add_shortcodes'); // Add custom form tags to CF7
    add_filter('wpcf7_validate_dynamictext*', 'wpcf7dtx_dynamictext_validation_filter', 20, 2); // Validate custom form tags
}
add_action('plugins_loaded', 'wpcf7dtx_init', 20);

/**
 * DTX Formg Tag Configuration
 *
 * @since 3.6.0
 *
 * @return array
 */
function wpcf7dtx_config()
{
    global $wpcf7_dynamic_fields_config;
    if (!isset($wpcf7_dynamic_fields_config)) {
        $wpcf7_dynamic_fields_config = array(
            'dynamic_text' => array(
                'title' => __('dynamic text', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'dtx_pageload'),
                'description' => __('single-line plain text', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_hidden' => array(
                'title' => __('dynamic hidden', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('dtx_pageload'),
                'description' => __('single-line plain text hidden input field', 'contact-form-7-dynamic-text-extension')
            )
        );
    }
    return $wpcf7_dynamic_fields_config;
}

/**
 * Add Custom Shortcodes to Contact Form 7
 *
 * @return void
 */
function wpcf7dtx_add_shortcodes()
{
    //Add the dynamic form fields
    foreach (wpcf7dtx_config() as $form_tag => $field) {
        $input_type = str_replace('dynamic_', '', $form_tag);
        $tag_types = array($form_tag, "$form_tag*");
        $callback = 'wpcf7dtx_shortcode_handler';
        $features = array('name-attr' => true, 'dtx_pageload' => true);
        switch ($input_type) {
            case 'text':
            case 'hidden':
                $dep_tag = str_replace('_', '', $form_tag);
                $tag_types[] = $dep_tag;
                $tag_types[] = "$dep_tag*";
                break;
            default:
                break;
        }
        wpcf7_add_form_tag($tag_types, $callback, $features);
    }
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
 * Form Tag Handler
 *
 * @param WPCF7_FormTag $tag Current Contact Form 7 tag object
 *
 * @return string HTML output of the shortcode
 */
function wpcf7dtx_shortcode_handler($tag)
{
    // Name attribute is required for these form tags
    if (empty($tag->name)) {
        return '';
    }

    // Validate
    $validation_error = wpcf7_get_validation_error($tag->name);

    //Configure input attributes
    $atts = array();
    $atts['type'] = sanitize_key(str_replace(array('dynamic_', 'dynamic'), '', $tag->basetype));
    $atts['name'] = $tag->name;
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['size'] = $tag->get_size_option('40');
    $atts['class'] = explode(' ', wpcf7_form_controls_class($atts['type']));
    $atts['class'][] = 'wpcf7dtx';
    $atts['class'][] = sanitize_html_class('wpcf7dtx-' . str_replace('_', '-', $tag->basetype));
    if ($validation_error) {
        $atts['class'][] = 'wpcf7-not-valid';
        $atts['aria-invalid'] = 'true';
        $atts['aria-describedby'] = wpcf7_get_validation_error_reference($tag->name);
    } else {
        $atts['aria-invalid'] = 'false';
    }

    if ($tag->has_option('readonly')) {
        $atts['readonly'] = 'readonly';
    }
    // Add required attribute to applicable input types
    if ($tag->is_required() && !in_array($atts['type'], array('hidden', 'quiz'))) {
        $atts['aria-required'] = 'true';
        $atts['required'] = 'required';
    }

    // Evaluate the dynamic value
    $sanitize_type = $atts['type'] == 'textarea' ? $atts['type'] : 'auto';
    $value = wpcf7dtx_get_dynamic(false, $tag, $sanitize_type);

    // Identify placeholder
    if ($tag->has_option('placeholder') || $tag->has_option('watermark')) {
        //Reverse engineer what JS did (converted quotes to HTML entities --> URL encode) then sanitize
        $placeholder = html_entity_decode(urldecode($tag->get_option('placeholder', '', true)), ENT_QUOTES);
        if ($placeholder) {
            //If a different placeholder text has been specified, set both attributes
            $placeholder = wpcf7dtx_get_dynamic($placeholder, false, $sanitize_type);
            $atts['placeholder'] = $placeholder;
            $atts['value'] = $value;
        } else {
            //Default behavior of using the value as the placeholder
            $atts['placeholder'] = $value;
        }
    } else {
        $atts['value'] = $value;
    }

    // Page load attribute
    if ($tag->has_option('dtx_pageload') && is_array($tag->raw_values) && count($tag->raw_values)) {
        $atts['data-dtx-value'] = rawurlencode(sanitize_text_field($tag->raw_values[0]));
        $atts['class'][] = 'dtx-pageload';
        if (wp_style_is('wpcf7dtx', 'registered') && !wp_script_is('wpcf7dtx', 'queue')) {
            // If already registered, just enqueue it
            wp_enqueue_script('wpcf7dtx');
        } elseif (!wp_style_is('wpcf7dtx', 'registered')) {
            // If not registered, do that first, then enqueue it
            wpcf7dtx_enqueue_frontend_assets();
            wp_enqueue_script('wpcf7dtx');
        }
    }

    // Attributes
    $atts['maxlength'] = $tag->get_maxlength_option();
    $atts['minlength'] = $tag->get_minlength_option();
    if ($atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength']) {
        unset($atts['maxlength'], $atts['minlength']);
    }

    // Autocomplete attribute
    if ($atts['type'] == 'hidden') {
        $atts['autocomplete'] = 'off'; // Always disable for hidden fields
    } else {
        // Disable autocomplete for this field if a dynamic value has been specified
        $atts['autocomplete'] = $atts['value'] ? 'off' : $tag->get_option('autocomplete', '[-0-9a-zA-Z]+', true);
    }


    // Wrap up class attribute
    $atts['class'] = $tag->get_class_option(implode(' ', array_unique(array_filter($atts['class']))));

    // Output the HTML
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
