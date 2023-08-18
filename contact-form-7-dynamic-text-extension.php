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
                'description' => __('single-line plain text hidden input field', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'display-hidden' => true // Generates an HTML element that is not visible
                )
            ),
            'dynamic_email' => array(
                'title' => __('dynamic email', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'dtx_pageload'),
                'description' => __('single-line email address input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_url' => array(
                'title' => __('dynamic URL', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'dtx_pageload'),
                'description' => __('single-line URL input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_tel' => array(
                'title' => __('dynamic tel', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'pattern'),
                'description' => __('single-line telephone number input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_number' => array(
                'title' => __('dynamic number', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'min', 'max', 'step', 'pattern'),
                'description' =>  __('numeric input field (displayed as either a spinbox or a slider within a range)', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_range' => array(
                'title' => __('dynamic range', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'min', 'max', 'step', 'pattern'),
                'description' =>  __('numeric input field (displayed as either a spinbox or a slider within a range)', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_textarea' => array(
                'title' => __('dynamic textarea', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'dtx_pageload'),
                'description' => __('multi-line plain text input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_select' => array(
                'title' => __('dynamic drop-down menu', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'multiple', 'include_blank'),
                'description' => __('drop-down menu (i.e select input field)', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'selectable-values' => true // Generates an option (or group of options) from which you can select one or more options
                )
            ),
            'dynamic_checkbox' => array(
                'title' => __('dynamic checkboxes', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('readonly', 'label_first', 'use_label_element', 'exclusive'),
                'description' => __('group of checkboxes', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'multiple-controls-container' => true, // Generates an HTML element that can contain multiple form controls
                    'selectable-values' => true // Generates an option (or group of options) from which you can select one or more options
                )
            ),
            'dynamic_radio' => array(
                'title' => __('dynamic radio buttons', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('readonly', 'label_first', 'use_label_element'),
                'description' => __('group of radio buttons', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'multiple-controls-container' => true, // Generates an HTML element that can contain multiple form controls
                    'selectable-values' => true // Generates an option (or group of options) from which you can select one or more options
                )
            ),
            'dynamic_date' => array(
                'title' => __('dynamic date', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'min', 'max'),
                'description' =>  __('date input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_submit' => array(
                'title' => __('dynamic submit', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('dtx_pageload'),
                'description' =>  __('submit button', 'contact-form-7-dynamic-text-extension')
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
        $features = array_merge(array('name-attr' => true), wpcf7dtx_array_has_key('features', $field, array()));
        switch ($input_type) {
            case 'text':
            case 'hidden':
                // Add deprecated tags
                $dep_tag = str_replace('_', '', $form_tag);
                $tag_types[] = $dep_tag;
                $tag_types[] = "$dep_tag*";
                add_filter("wpcf7_validate_$dep_tag*", 'wpcf7dtx_validation_filter', 20, 2); // Validate required deprecated form tags
                break;
            case 'submit':
            case 'reset':
                $callback = 'wpcf7dtx_button_shortcode_handler';
                $features['name-attr'] = false;
                break;
            default:
                break;
        }
        add_filter("wpcf7_validate_$form_tag*", 'wpcf7dtx_validation_filter', 20, 2); // Validate required custom form tags
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
 * Include Validation Functions
 */
include_once(WPCF7DTX_DIR . '/includes/validation.php');

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
    $atts['id'] = strval($tag->get_id_option());
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['size'] = $tag->get_size_option('40');
    $atts['class'] = explode(' ', wpcf7_form_controls_class($atts['type']));
    $atts['class'][] = 'wpcf7dtx';
    $atts['class'][] = sanitize_html_class('wpcf7dtx-' . $atts['type']);
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

    // Additional configuration based on form field type
    if (in_array($atts['type'], array('select', 'checkbox', 'radio'))) {
        /**
         * Configuration for selection-based fields
         */
        if ($tag->has_option('default')) {
            $atts['dtx-default'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('default', '', true)), ENT_QUOTES));
        }

        // Get options for selection-based fields
        $options = array();
        $pipes = $tag->pipes->to_array();
        if (count($pipes)) {
            foreach ($pipes as $pipe) {
                $key = trim(strval($pipe[0]));
                $value = trim(strval($pipe[1]));
                if ($key && $value) {
                    $options[$key] = $value;
                }
            }
        }
        if ($atts['type'] == 'select' && $tag->has_option('include_blank')) {
            $atts['placeholder'] = wpcf7dtx_array_has_key('placeholder', $atts, __('&#8212;Please choose an option&#8212;', 'contact-form-7-dynamic-text-extension'));
        }
    } else {
        /**
         * Configuration for text-based fields
         */

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

        switch ($atts['type']) {
            case 'email':
            case 'url':
            case 'tel':
            case 'number':
            case 'date':
                // Client-side validation by type
                $atts['class'][] =  sanitize_html_class('wpcf7-validates-as-' . $atts['type']);
                break;
            case 'range':
                // Client-side validation by type
                $atts['class'][] =  'wpcf7-validates-as-number';
                break;
            case 'textarea':
                // Attributes unique to textareas
                $atts['cols'] = $tag->get_cols_option('40');
                $atts['rows'] = $tag->get_rows_option('10');
                break;
        }
    }

    // Wrap up class attribute
    $atts['class'] = $tag->get_class_option($atts['class']);

    // Output the form field HTML
    $wrapper = '<span class="wpcf7-form-control-wrap %1$s" data-name="%1$s">%2$s%3$s</span>';
    $allowed_html = array('br' => array(), 'span' => array('id' => array(), 'class' => array(), 'data-name' => array(), 'aria-hidden' => array()));
    switch ($atts['type']) {
        case 'checkbox':
        case 'radio':
            $atts['id'] = strval($tag->get_option('id', 'id', true));
            $wrapper = str_replace('<span class=', '<span%4$s class=', $wrapper); // Insert a 4th parameter for wrapper
            $group_html = '';
            if ($count = count($options)) {
                $reverse = in_array('label_first', $tag->options);
                $label_ui = in_array('use_label_element', $tag->options);
                $exclusive = in_array('exclusive', $tag->options);
                // Loop all the options
                $group_html = array();
                $id_prefix = ($atts['id'] ? $atts['id'] : uniqid($atts['name'] . '_')) . '_';
                $i = 1;
                foreach ($options as $value => $label) {
                    $class = array('wpcf7-list-item');
                    $class[] = sanitize_html_class('wpcf7-list-item-' . $i);
                    if ($i === 1) {
                        $class[] = 'first';
                    }
                    if ($i === $count) {
                        $class[] = 'last';
                    }
                    if ($exclusive) {
                        $class[] = 'wpcf7-exclusive-checkbox';
                    }
                    $group_html[] = sprintf(
                        '<span class="%s">%s</span>',
                        esc_attr(implode(' ', $class)),
                        wpcf7dtx_checkbox_html(
                            // Overwrite name attribute
                            array_merge($atts, array(
                                'id' => sanitize_html_class($id_prefix . $i), // Always have unique IDs for group items
                                'name' => $atts['type'] == 'radio' || $exclusive || $count === 1 ? $atts['name'] : $atts['name'] . '[]', // if there are multiple checkboxes and they aren't exclusive, names are an array
                                //'value' => $atts['value'] && $value == $atts['value'] // Send true/false value
                            )),
                            $label,
                            $label_ui,
                            $reverse
                        )
                    );
                    $i++;
                }
                $group_html = implode('', $group_html);
            }
            return wp_kses(sprintf(
                $wrapper,
                esc_attr($tag->name),
                $group_html,
                $validation_error,
                $atts['id'] ? sprintf(' id="%s"', esc_attr($atts['id'])) : '',
            ), array_merge($allowed_html, array(
                'label' => array('for' => array()),
                'input' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            )));
        case 'select':
            $allowed_html = array_merge($allowed_html, wpcf7dtx_get_allowed_field_properties('option'), array(
                'select' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            ));
            return wp_kses(sprintf(
                $wrapper,
                esc_attr($tag->name),
                wpcf7dtx_select_html(
                    $atts,
                    $options,
                    $tag->has_option('dtx_hide_blank'),
                    $tag->has_option('dtx_disable_blank')
                ),
                $validation_error
            ), array_merge($allowed_html, wpcf7dtx_get_allowed_field_properties('option'), array(
                'select' => wpcf7dtx_get_allowed_field_properties($atts['type']),
            )));
        case 'textarea':
            return wp_kses(sprintf(
                $wrapper,
                esc_attr($tag->name),
                wpcf7dtx_textarea_html($atts),
                $validation_error
            ), array_merge($allowed_html, array(
                'textarea' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            )));
        default:
            return wp_kses(sprintf(
                $wrapper,
                esc_attr($tag->name),
                wpcf7dtx_input_html($atts),
                $validation_error
            ), array_merge($allowed_html, array(
                'input' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            )));
    }
}

/**
 * Form Tag Handler for Dynamic Submit
 *
 * @param WPCF7_FormTag $tag Current Contact Form 7 tag object
 *
 * @return string HTML output of the shortcode
 */
function wpcf7dtx_button_shortcode_handler($tag)
{
    //Configure input attributes
    $atts = array();
    $atts['type'] = sanitize_key(str_replace('dynamic_', '', $tag->basetype));
    $atts['id'] = strval($tag->get_id_option());
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['value'] = wpcf7dtx_get_dynamic(false, $tag); // Evaluate the dynamic value
    $atts['class'] = explode(' ', wpcf7_form_controls_class($atts['type']));
    $atts['class'][] = 'wpcf7dtx';
    $atts['class'][] = sanitize_html_class('wpcf7dtx-' . $atts['type']);
    if ($atts['type'] == 'submit') {
        $atts['class'][] = 'has-spinner';
    }

    // Default value if empty
    if (empty($atts['value'])) {
        switch ($atts['type']) {
            case 'reset':
                $atts['value'] = __('Clear', 'contact-form-7-dynamic-text-extension');
                break;
            default:
                $atts['value'] = __('Send', 'contact-form-7-dynamic-text-extension');
                break;
        }
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

    // Wrap up class attribute
    $atts['class'] = $tag->get_class_option($atts['class']);

    // Output the form field HTML
    return wp_kses(
        wpcf7dtx_input_html($atts),
        array('input' => wpcf7dtx_get_allowed_field_properties($atts['type']))
    );
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
    $queue = wpcf7dtx_array_has_key('shortcodes', $_POST);
    if (is_array($queue) && count($queue)) {
        foreach ($queue as $field) {
            $multiline = wpcf7dtx_array_has_key('multiline', $field, false);
            $raw_value = sanitize_text_field(rawurldecode(wpcf7dtx_array_has_key('value', $field)));
            $return[] = array(
                'raw_value' => esc_attr($raw_value),
                'value' => esc_attr(wpcf7dtx_get_dynamic($raw_value, false, $multiline ? 'textarea' : 'auto'))
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
