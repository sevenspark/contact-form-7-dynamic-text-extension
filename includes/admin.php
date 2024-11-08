<?php

namespace WPCF7DTX;

defined('ABSPATH') || exit; // Exit if accessed directly

// Include the Settings Page & Update Check
include_once 'admin/settings.php';
include_once 'admin/update-check.php';

use WPCF7_TagGenerator;

/**
 * Tag Generator Class for DTX
 *
 * Compatible with v2 of Contact Form 7's form-tag generator.
 * Important notes between v1 and v2:
 *      - The attribute `data-tag-part` on input elements is the type of part. Possible values include:
 *          tag - tag content to be inserted
 *          name - form tag name
 *          basetype - input type of form field to be inserted
 *          type-suffix - used with required checkbox
 *          option - form tag setting
 *          value - form tag value ("Default value" in CF7)
 *          content - form tag body content ("Default value" for textarea and "Condition" in acceptance) because it has end form tags
 *          mail-tag - mail tag demo text
 *      - The attribute `data-tag-option` on input elements exists when `data-tag-part` is set to `option`. This defines which option. Identified values include:
 *          "autocomplete:name" used for the field name checkbox that states name is expected
 *          "class:" used for class attribute text field
 *      - The attribute `data-taggen` on buttons (usually) defines what it does. Possible values include `insert-tag`, `open-dialog`, `close-dialog`
 *
 * @package WPCF7DTX
 *
 * @since 5.0.0
 *
 * @see https://contactform7.com/2024/11/03/contact-form-7-60/
 */
class TagGenerator
{
    private $config;
    private $id;
    private $form_tag;
    private $tag_type;
    private $utm_source;
    private $utm_content;
    private $allowedtags;

    /**
     * The single instance of the class
     *
     * @var TagGenerator
     *
     * @since 5.0.0
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * Ensures only one instance of is loaded or can be loaded.
     *
     * @since 5.0.0
     *
     * @static
     *
     * @return TagGenerator instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'load_assets')); // Enqueue styles/scripts for admin page
        add_action('wpcf7_admin_init', array($this, 'init'), 100); // Add DTX tag generators
    }

    /**
     * Check Dependency
     *
     * @return bool True if Contact Form 7 is version 6.0 or higher. False otherwise.
     */
    private function dependency()
    {
        return wpcf7dtx_dependencies(WPCF7DTX_MINVERSION_TAGGEN);
    }

    /**
     * Admin Scripts and Styles
     *
     * Enqueue scripts and styles to be used on the admin pages
     *
     * @since 3.1.0
     *
     * @param string $hook Hook suffix for the current admin page
     *
     * @return void
     */
    public function load_assets($hook)
    {
        if (!$this->dependency()) {
            return;
        }
        // Only load on CF7 Form pages (both editing forms and creating new forms)
        if ($hook === 'toplevel_page_wpcf7' || $hook === 'contact_page_wpcf7-new') {
            $prefix = 'wpcf7dtx-';
            $minify = defined('WP_DEBUG') && constant('WP_DEBUG') ? '' : '.min';
            $url = plugin_dir_url(WPCF7DTX_FILE);
            $path = plugin_dir_path(WPCF7DTX_FILE);

            wp_enqueue_style(
                $prefix . 'admin', //Handle
                $url . "assets/styles/tag-generator{$minify}.css", //Source
                array('contact-form-7-admin'), // Dependencies
                $minify ? WPCF7DTX_VERSION : @filemtime($path .  'assets/styles/tag-generator.css') //Version
            );
            wp_enqueue_script(
                $prefix . 'taggenerator', //Handle
                $url . "assets/scripts/tag-generator{$minify}.js", //Source
                array('jquery-core', 'wpcf7-admin'), // Dependencies
                $minify ? WPCF7DTX_VERSION : @filemtime($path . 'assets/scripts/tag-generator.js'), //Version
                array('in_footer' => true, 'strategy' => 'defer') // Defer loading in footer
            );
        }
    }

    /**
     * Create Tag Generators
     *
     * @since 3.1.0
     *
     * @return void
     */
    public function init()
    {
        if (!$this->dependency()) {
            return;
        }
        // Loop fields to add them
        $tag_generator = WPCF7_TagGenerator::get_instance();
        foreach (wpcf7dtx_config() as $id => $field) {
            $tag_generator->add(
                $id, // ID
                $field['label'], // Button label
                array($this, 'render'), // Callback
                array_merge(array( // Options
                    'version' => 2, // Use form tag generator version 2 introduced in cf7 6.0
                    'name-attr', // Always include this attribute
                    'dtx_pageload' // Always include our custom attribute
                ), $field['options']) // Include any additional attributes
            );
        }
    }

    private function default_input_atts($default = array(), $atts = array(), $classes = array())
    {
        $atts = array_merge(array(
            'data-dtx' => '',
            'type' => 'text',
            'id' => '',
            'name' => '',
            'class' => array()
        ), $default, $atts);
        $atts['class'] = array_unique(array_filter(array_merge($atts['class'], $classes)));
        return $atts;
    }

    private function allowed_tags($type = 'control-box')
    {
        if (!isset($this->allowedtags)) {
            $this->allowedtags = array();
            $this->allowedtags['header'] = array(
                'header' => array('class' => true),
                'h3' => true,
                'p' => true,
                'a' => array(
                    'href' => true,
                    'target' => array('_blank' => true),
                    'rel' => array('noopener' => true),
                    'title' => true
                ),
                'span' => true
            );
            $footer_input_atts = array(
                'data-dtx' => true,
                'type' => array('text' => true),
                'class' => array('code' => true),
                'readonly' => array('readonly' => true),
                'onfocus' => array('this.select()' => true),
                'data-tag-part' => true,
                'aria-label' => true,
                'rows' => true
            );
            $this->allowedtags['footer'] = array(
                'footer' => array('class' => true),
                'div' => array('class' => true),
                'input' => $footer_input_atts,
                'textarea' => $footer_input_atts,
                'button' => array(
                    'type' => array('button' => true),
                    'class' => array('button-primary' => true),
                    'data-taggen' => array('insert-tag' => true)
                ),
                'p' => array('class' => array('mail-tag-tip' => true)),
                'strong' => array('data-tag-part' => array('mail-tag' => true))
            );
            global $allowedtags; // Access global allowed inline tags
            $input_atts = array(
                //'name' => true,
                'data-dtx' => true,
                'id' => true,
                'data-tag-part' => true,
                'data-tag-option' => true,
                'class' => true,
                'aria-labelledby' => true,
                'placeholder' => true,
                'list' => true,
                'autocomplete' => true,
                'pattern' => true,
                'size' => true,
                'value' => true
            );
            $this->allowedtags['control-box'] = array_merge($allowedtags, array(
                'div' => true,
                'a' => array(
                    // Overwrite link to allow rel and target attributes
                    'href' => true,
                    'title' => true,
                    'target' => array('_blank' => true),
                    'rel' => array('noopener' => true)
                ),
                // Additional tags
                'small' => true,
                'br' => true,
                // Form tags
                'fieldset' => array('class' => true),
                'legend' => array('id' => true),
                'label' => array('for' => true, 'title' => true),
                'textarea' => array_merge($input_atts),
                'input' => array_merge($input_atts, array(
                    'type' => true
                )),
                'span' => array('class' => array('"wpcf7dtx-mini-att' => true)),
                'datalist' => array('id' => true),
                'option' => array('value' => true, 'disabled' => true, 'hidden' => true, 'selected' => true)
            ));
            // Remove these tags from the default
            unset(
                $this->allowedtags['control-box']['i'], // Deprecated, replaced with em
                $this->allowedtags['control-box']['b'], // Deprecated, replaced with strong
                $this->allowedtags['control-box']['q'], // Deprecated, replaced with blockquote
                $this->allowedtags['control-box']['blockquote'], // Unwanted block-level quotes
                $this->allowedtags['control-box']['acronym'], // Deprecated, replaced with abbr
                $this->allowedtags['control-box']['cite'], // Unwanted citations
                $this->allowedtags['control-box']['del'], // Unwanted delete format
                $this->allowedtags['control-box']['s'], // Deprecated, replaced with strike
                $this->allowedtags['control-box']['strike'], // Unwanted strike format
            );
        }
        if (!array_key_exists($type, $this->allowedtags)) {
            $type = 'control-box';
        }
        return $this->allowedtags[$type];
    }

    private function get_legend_id($name)
    {
        return trim(sanitize_key($this->id . '-' . $name . '-legend'));
    }

    /**
     * Input HTML
     *
     * @since 5.0.0
     *
     * @param string $name Form tag name.
     * @param array $atts Optional. Input attributes. Possible customizations include:
     *      `part` => The tag part, if different from the field name.
     *      `atts` => Associative array of input attributes.
     *      `class` => Sequential array of additional classes.
     *      `option` => `dtx` if custom DTX option or boolean true for CF7 option.
     *      `datalist` => Array to create the datalist element or a string id to refer to an existing one.
     *
     * @return string Input HTML
     */
    private function input($name, $atts = array())
    {
        $atts = shortcode_atts(array(
            //$input_atts, $custom_option = false, $datalist = false,
            'part' => $name, // Default to the field name
            'atts' => array(),
            'class' => array(),
            'option' => '',
            'datalist' => false
        ), $atts);
        $input = '';
        $input_atts = $this->default_input_atts(array(
            'data-dtx' => $name, // Always have a reference
            'type' => 'text',
            'data-tag-part' => $atts['part'],
            'id' => $this->id . '-' . $name, // field id
        ), $atts['atts'], $atts['class']);
        if ($input_atts['type'] != 'hidden') {
            $input_atts['aria-labelledby'] = $this->get_legend_id($name);
        }
        if (is_array($atts['datalist'])) {
            // If a datalist was provided, make it and add it
            $dl = sanitize_key("dtx-{$name}-datalist");
            $input .= sprintf(
                '<datalist id="%s">%s</datalist>',
                esc_attr($dl),
                wpcf7dtx_options_html($atts['datalist'])
            );
            $input_atts['list'] = $dl;
        } elseif (is_string($atts['datalist'])) {
            // If a datalist was referenced, add it
            $input_atts['list'] = $atts['datalist'];
        }
        // Do extra stuff for options
        if ($atts['option']) {
            // The ending defines if it is supposed to have a value in the tag or if its just a boolean
            $ending = in_array($input_atts['type'], array('checkbox', 'radio')) ? '' : ':';
            if ($atts['option'] === true || $atts['option'] === 'dtx') {
                $input_atts['data-tag-option'] = $input_atts['data-tag-part'] . $ending; // Move the name to the option
                $input_atts['data-tag-part'] = 'option'; // Set the part to option
            } else {
                $input_atts['data-tag-option'] = trim(sanitize_text_field($atts['option'])) . $ending; // Set the option name
                $input_atts['data-tag-part'] = 'option'; // Set the part to option
            }
            if ($ending) {
                // This requires a value, so it needs to be encoded. We're going to use JS to do that on the fly with a hidden input acting as the real option value
                $input .= sprintf('<input %s>', wpcf7dtx_format_atts(array(
                    'type' => 'hidden',
                    'data-tag-option' => $input_atts['data-tag-option'], // Set the option name
                    'data-tag-part' => 'option', // Set the part to option
                )));
                $input_atts['class'][] = 'dtx-option'; // Set the class for the JS listener
                unset($input_atts['data-tag-part'], $input_atts['data-tag-option']); // Unset these from the dummy input
            }
        }
        switch ($input_atts['type']) {
            case 'textarea':
                unset($input_atts['type']);
                return $input . sprintf('<textarea %s></textarea>', wpcf7dtx_format_atts($input_atts));
            default:
                return $input . sprintf('<input %s>', wpcf7dtx_format_atts($input_atts));
        }
    }

    /**
     * Escape and Echo the Tag Generator Field
     *
     * Generates the wrapper HTML for the fieldset.
     *
     * @since 5.0.0
     *
     * @param string $name Form tag name.
     * @param string $label Field label.
     * @param string $form_controls Form controls for the field.
     * @param array|false $note Optional. Text to display below the form controls as a footnote. Possible customizations include:
     *      `text` => Text to display below the form control(s).
     *      `url` => URL to documentation page.
     *      `label` => Text to display in hyperlink. Default is "Learn more" when there is a URL but no label.
     * @param string $field_note Optional. Additional text to display below the field label.
     *
     * @return void
     */
    private function field($name, $label, $form_controls, $note = false, $field_note = '')
    {
        $helptext = '';
        if (is_array($note)) {
            $helptext = '';
            if (array_key_exists('text', $note)) {
                $helptext .=  wp_kses_data($note['text']);
            }
            if (array_key_exists('url', $note)) {
                $helptext .= ($helptext ? '&nbsp;' : '') . sprintf(
                    '<a href="%s" target="_blank" rel="noopener">%s</a>',
                    esc_url($note['url']),
                    esc_html(array_key_exists('label', $note) ? $note['label'] : __('Learn more', 'contact-form-7-dynamic-text-extension'))
                );
            }
            $helptext = "<small>{$helptext}</small>";
        }
        if ($field_note) {
            $field_note = '<small>' . wp_kses_data($field_note) . '</small>';
        }
        echo wp_kses(sprintf(
            '<fieldset class="%1$s"><legend id="%2$s"><label for="%1$s">%3$s</label>%6$s</legend><div>%4$s%5$s</div></fieldset>',
            esc_attr($this->id . '-' . $name), // 1 - field id
            // esc_attr(sanitize_key("tag-generator-panel-{$form_tag}-{$name}-legend")), // 2 -fieldset legend id
            esc_attr($this->get_legend_id($name)), // 2 -fieldset legend id
            wp_kses_data($label), // 3 - Field label
            $form_controls, // 4 - inputs & their descriptions
            $helptext, // 5 - field documentation (including link)
            $field_note // 6 - info to display below label
        ), $this->allowed_tags(), array('https'));
    }

    /**
     * Standard Text field
     *
     * @since 5.0.0
     *
     * @param string $name Form tag name.
     * @param string $label Field label.
     * @param array $input_atts Optional. Input attributes. Possible customizations include:
     *      `part` => The tag part, if different from the field name.
     *      `atts` => Associative array of input attributes.
     *      `class` => Sequential array of additional classes.
     *      `option` => `dtx` if custom DTX option or boolean true for CF7 option.
     *      `datalist` => Array to create the datalist element or a string id to refer to an existing one.
     * @param array|false $note Optional. Text to display below the form controls as a footnote. Possible customizations include:
     *      `text` => Text to display below the form control(s).
     *      `url` => URL to documentation page.
     *      `label` => Text to display in hyperlink. Default is "Learn more" when there is a URL but no label.
     * @param string $field_note Optional. Additional text to display below the field label.
     *
     * @return void
     */
    private function text($name, $label, $input_atts = array(), $note = false, $field_note = '')
    {
        $this->field(
            $name, // Field name
            $label, // Field label
            $this->input($name, $input_atts), // Form controls
            $note, // Documentation
            $field_note // Field note
        );
    }

    /**
     * Standard Checkbox
     *
     * @since 5.0.0
     *
     * @param string $name Form tag name.
     * @param string $label Field label.
     * @param string $checkbox_label Optional. Label to display to the right of the checkbox.
     * @param array $input_atts Optional. Input attributes. Possible customizations include:
     *      `part` => The tag part, if different from the field name.
     *      `atts` => Associative array of input attributes.
     *      `class` => Sequential array of additional classes.
     *      `option` => `dtx` if custom DTX option or boolean true for CF7 option.
     *      `datalist` => Array to create the datalist element or a string id to refer to an existing one.
     * @param array|false $note Optional. Text to display below the form controls as a footnote. Possible customizations include:
     *      `text` => Text to display below the form control(s).
     *      `url` => URL to documentation page.
     *      `label` => Text to display in hyperlink. Default is "Learn more" when there is a URL but no label.
     * @param string $field_note Optional. Additional text to display below the field label.
     *
     * @return void
     */
    private function checkbox($name, $label, $checkbox_label = '', $input_atts = array(), $note = false, $field_note = '')
    {
        if (array_key_exists('atts', $input_atts)) {
            $input_atts['atts'] = array_merge($input_atts['atts'], array('type' => 'checkbox'));
        } else {
            $input_atts['atts'] = array('type' => 'checkbox');
        }
        $this->field(
            $name, // Field name
            $label, // Field label
            sprintf( // Form controls
                '<label>%s%s</label>',
                $this->input($name, $input_atts),
                esc_html($checkbox_label)
            ),
            $note, // Text to display below form controls
            $field_note // Field note
        );
    }

    /**
     * Get Documentation Link
     *
     * @since 5.0.0
     *
     * @param string $path Optional. The path to the specific page on the documentation website.
     *
     * @return string Absolute URL to the documentation page.
     */
    private function doc_link($path = '')
    {
        return sanitize_url(sprintf(
            'https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/%s?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s',
            $path . ($path ? '/' : ''),
            $this->utm_source,
            $this->utm_content
        ), array('https'));
    }

    /**
     * Echo HTML for Dynamic Tag Generator
     *
     * @since 5.0.0
     *
     * @param WPCF7_ContactForm $contact_form The current form being edited.
     * @param array $options Associative array of tag options.
     *
     * @return void
     */
    public function render($contact_form, $options)
    {
        $options = wp_parse_args($options);
        $config = wpcf7dtx_config();
        $this->id = trim(sanitize_key($options['content']));
        $this->form_tag = trim(sanitize_key($options['id']));
        $this->utm_source = urlencode(home_url());
        $this->utm_content = urlencode(trim(sanitize_text_field($this->form_tag)));
        $tag_type = str_replace('dynamic_', '', $this->form_tag);
        $utm_source = urlencode(home_url());

        $can_be = __('Can be static text or a shortcode.', 'contact-form-7-dynamic-text-extension');
        $each_can_be = __('Each can be static text or a shortcode.', 'contact-form-7-dynamic-text-extension');
        $link_label_atts = __('View documentation for Dynamic Attributes', 'contact-form-7-dynamic-text-extension');

        /**
         * Header
         */
        $description = sprintf(
            __('Generate a form-tag for %s with %s. For more details, see %s in the %s.', 'contact-form-7-dynamic-text-extension'),
            esc_html($config[$this->form_tag]['description']), // dynamic description
            esc_html(in_array($tag_type, array('select', 'checkbox', 'radio')) ? __('dynamic options and a default value') : __('a default value')),
            // Link to specific form-tag documentation
            sprintf(
                '<a href="%s" title="%s" target="_blank" rel="noopener">%s</a>',
                esc_url($this->doc_link('form-tags/' . str_replace('_', '-', $this->form_tag))),
                esc_attr__('View this form-tag on the DTX Documentation website', 'contact-form-7-dynamic-text-extension'), // Link title
                esc_html(strtolower($config[$this->form_tag]['title'])) // Link label
            ),
            // Link to general DTX documentation
            sprintf(
                '<a href="%s" title="%s" target="_blank" rel="noopener">%s</a>',
                esc_url($this->doc_link()),
                esc_attr__('Go to DTX Documentation website', 'contact-form-7-dynamic-text-extension'),
                esc_html__('DTX knowledge base', 'contact-form-7-dynamic-text-extension')
            )
        );
        echo wp_kses(sprintf(
            '<header class="description-box dtx-taggen"><h3>%s</h3><p>%s</p></header>',
            sprintf(
                esc_html_x('%s form-tag generator', 'placeholder represents the name of the form tag', 'contact-form-7-dynamic-text-extension'),
                '<span>' . $config[$this->form_tag]['title'] . '</span>'
            ),
            $description
        ), $this->allowed_tags('header'), array('https'));

        /**
         * Form Controls
         */

        // Open Form-Tag Generator
        echo '<div class="control-box dtx-taggen">';

        // Shortcode datalist
        $shortcode_datalist = '<datalist id="dtx-shortcodes">';
        foreach (wpcf7dtx_builtin_shortcodes_config() as $shortcode) {
            $shortcode_datalist .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($shortcode['tag']),
                esc_html($shortcode['description'])
            );
        }
        $shortcode_datalist .= '</datalist>';
        echo wp_kses($shortcode_datalist, array('datalist' => array('id' => true), 'option' => array('value' => true)));

        // Input field - Field type (making it hidden instead of the dropdown with one option)
        echo wp_kses(
            $this->input(
                'form_tag',
                array(
                    'part' => 'basetype',
                    'atts' => array('type' => 'hidden', 'value' => $this->form_tag)
                )
            ),
            $this->allowed_tags(),
            array('https')
        );

        // Input field - Required checkbox (not available for some fields)
        if (!in_array($tag_type, array('hidden', 'quiz', 'submit', 'reset', 'label'))) {
            $this->checkbox(
                'required', // Field name
                __('Field type', 'contact-form-7-dynamic-text-extension'), // Field label
                __('This is a required field.', 'contact-form-7-dynamic-text-extension'), // Checkbox label
                array(
                    'part' => 'type-suffix', // CF7 Field name
                    'atts' => array('value' => '*')
                )
            );
        }

        // Input field - Field Name (not available for some fields)
        if (!in_array($tag_type, array('submit', 'reset', 'label'))) {
            $this->text(
                'field_name', // Field name
                __('Field name', 'contact-form-7-dynamic-text-extension'), // Field label
                array(
                    'part' => 'name',
                    'atts' => array( // Input attributes
                        'autocomplete' => 'off',
                        'pattern' => '[A-Za-z][A-Za-z0-9_\-]*'
                    )
                )
            );
        }

        // Input field - default value for text-based fields, options/choices for selection-based fields
        $field_label = __('Default value', 'contact-form-7-dynamic-text-extension');
        $note = $can_be;
        $placeholder = "CF7_GET key='foo'";
        $field_type = 'text';
        switch ($tag_type) {
            case 'textarea':
                $field_type = 'textarea';
                $placeholder = "CF7_get_post_var key='post_excerpt'";
                $doc_link =  $this->doc_link('shortcodes');
                $doc_label = __('View DTX shortcode syntax documentation', 'contact-form-7-dynamic-text-extension');
                break;
            case 'select':
                $field_type = 'textarea';
                $field_label = __('Options', 'contact-form-7-dynamic-text-extension');
                $placeholder = "hello-world | Hello World" . PHP_EOL . "Foo";
                $note .= ' ' . __('If static text, use one option per line. Can define static key/value pairs using pipes.', 'contact-form-7-dynamic-text-extension');
                $note .= ' ' . __('If shortcode, it must return only option/optgroup JSON or HTML. If returning HTML, it will override the "First option" and "Default value" settings.', 'contact-form-7-dynamic-text-extension');
                $doc_link =  $this->doc_link('shortcodes/custom-shortcodes/dynamic-options-for-select-checkbox-radio');
                $doc_label = __('View documentation for Dynamic Options and Choices', 'contact-form-7-dynamic-text-extension'); //Link label
                break;
            case 'checkbox':
            case 'radio':
                $field_type = 'textarea';
                $field_label = __('Options', 'contact-form-7-dynamic-text-extension');
                $placeholder = "hello-world | Hello World" . PHP_EOL . "Foo";
                $note .= ' ' . __('If static text, use one option per line. Can define static key/value pairs using pipes.', 'contact-form-7-dynamic-text-extension');
                $note .= ' ' . __('If shortcode, it must return choices as JSON or HTML. If returning HTML, it will override all attribute settings.', 'contact-form-7-dynamic-text-extension');
                $doc_link =  $this->doc_link('shortcodes/custom-shortcodes/dynamic-options-for-select-checkbox-radio');
                $doc_label = __('View documentation for Dynamic Options and Choices', 'contact-form-7-dynamic-text-extension'); //Link label
                break;
            default: // All other text fields
                $doc_link =  $this->doc_link('shortcodes');
                $doc_label = __('View DTX shortcode syntax documentation', 'contact-form-7-dynamic-text-extension');
                break;
        }
        $this->field(
            'value', // Field name
            $field_label, // Field label
            $this->input( // Form controls
                'value', // Field name
                array(
                    'atts' => array( // Additional attributes
                        'type' => $field_type,
                        'placeholder' => $placeholder
                    ),
                    'class' => array('multiline'), // Additional classes
                    'datalist' => 'dtx-shortcodes'
                )
            ),
            array(
                'text' => $note, // Documentation text
                'url' => $doc_link, // Documentation link url
                'label' => $doc_label // Documentation link label
            )
        );

        // Input field - default value for selection-based fields
        if (in_array($tag_type, array('select', 'checkbox', 'radio'))) {
            switch ($tag_type) {
                case 'checkbox':
                    $label = __('Default value(s)', 'contact-form-7-dynamic-text-extension');
                    $note = __('Optionally define which checkboxes are checked by default by defining the checked values using an underscore (_) delimited list.', 'contact-form-7-dynamic-text-extension') . ' ';
                    $placeholder = 'hello-world_Foo';
                    break;
                case 'radio':
                    $label = __('Default value', 'contact-form-7-dynamic-text-extension');
                    $note =  __('Optionally define the button that is selected by default. This can be different than the first [blank] option.', 'contact-form-7-dynamic-text-extension') . ' ';
                    $placeholder = "CF7_get_post_var key='post_title'";
                    break;
                default: // select
                    $label = __('Default value(s)', 'contact-form-7-dynamic-text-extension');
                    $note =  __('Optionally define the button that is selected by default. This can be different than the first [blank] option.', 'contact-form-7-dynamic-text-extension') . ' ';
                    $placeholder = "CF7_get_post_var key='post_title'";
                    break;
            }
            $note .= __('If options use key/value pairs, only use the key here.', 'contact-form-7-dynamic-text-extension') . ' ' . $can_be;
            $this->text(
                'default', // Field name
                $label, // Field label
                array(
                    'option' => true,
                    'atts' => array( // Additional attributes
                        'type' => $tag_type == 'radio' ? 'text' : 'textarea',
                        'placeholder' => $placeholder
                    ),
                    'datalist' => 'dtx-shortcodes'
                ),
                array(
                    'text' => $note, // Documentation text
                    'url' => $this->doc_link('shortcodes/custom-shortcodes/dynamic-options-for-select-checkbox-radio'), // Documentation link url
                    'label' => __('View documentation for Dynamic Options and Choices', 'contact-form-7-dynamic-text-extension') // Documentation link label
                )
            );
        }

        if ($tag_type == 'label') {
            $this->text(
                'for', // Field name
                __('For attribute', 'contact-form-7-dynamic-text-extension'), // Field label
                array(
                    'option' => 'dtx',
                    'atts' =>  array('placeholder' => $placeholder),
                    'datalist' => 'dtx-shortcodes'
                ),
                array(
                    'text' => $can_be, // Documentation text
                    'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                    'label' =>  $link_label_atts // Documentation link label
                )
            );
        } elseif ($tag_type == 'select') {
            // Input field - Multiple selections checkbox
            $this->checkbox(
                'multiple',
                __('Multiple options', 'contact-form-7-dynamic-text-extension'),
                __('Allow user to select multiple options', 'contact-form-7-dynamic-text-extension'),
                array('option' => true)
            );

            // Input field - Include blank checkbox
            $this->checkbox(
                'include_blank',
                __('First Option', 'contact-form-7-dynamic-text-extension'),
                __('Insert a blank item as the first option', 'contact-form-7-dynamic-text-extension'),
                array('option' => true)
            );
        }

        $only_first_option = __('Only has any effect if "First Option" is enabled.', 'contact-form-7-dynamic-text-extension');

        // Input field - Dynamic placeholder (not available for some fields)
        if (!in_array($tag_type, array('hidden', 'radio', 'checkbox', 'range', 'quiz', 'submit', 'reset', 'label'))) {
            $note = '';
            if (in_array($tag_type, array('select', 'checkbox', 'radio'))) {
                $label = __('First Option Label', 'contact-form-7-dynamic-text-extension');
                $note .= __('Optionally define an alternative label for the first option.', 'contact-form-7-dynamic-text-extension');
                $note .= ' ';
                $doc_link =  $this->doc_link('shortcodes/custom-shortcodes/dynamic-options-for-select-checkbox-radio');
                $doc_label = __('View documentation for Dynamic Options and Choices', 'contact-form-7-dynamic-text-extension'); //Link label
                $field_label = $only_first_option;
            } else {
                $label = __('Placeholder', 'contact-form-7-dynamic-text-extension');
                $doc_link =  $this->doc_link('shortcodes/dtx-attribute-placeholder');
                $doc_label = __('View DTX placeholder documentation', 'contact-form-7-dynamic-text-extension'); // Link label
                $field_label = '';
            }
            $note .= $can_be;
            $this->field(
                'placeholder', // Field name
                $label,
                $this->input(
                    'placeholder', // Field name
                    array(
                        'option' => 'dtx',
                        'atts' => array(
                            'type' => $tag_type == 'textarea' ? 'textarea' : 'text',
                            'placeholder' => "CF7_get_post_var key='post_title'",
                            'list' => 'dtx-shortcodes'
                        )
                    )
                ),
                array(
                    'text' => $note, // Documentation text
                    'url' => $doc_link, // Documentation link url
                    'label' => $doc_label // Documentation link label
                )
            );
        }

        // Additional fields for select regarding placeholder options
        if ($tag_type == 'select') {

            // Input field - Hide Blank Option
            $this->checkbox(
                'dtx_hide_blank', // Field name
                __('Hide First Option', 'contact-form-7-dynamic-text-extension'), // Field label
                __('Insert a blank item as the first option', 'contact-form-7-dynamic-text-extension'), // Checkbox label
                array('option' => true),
                array(
                    'url' => $this->doc_link('form-tags/dynamic-select'),
                    'label' => __('View Dynamic Select documentation', 'contact-form-7-dynamic-text-extension')
                ),
                $only_first_option
            );

            // Input field - Disable Blank Option
            $this->checkbox(
                'dtx_disable_blank', // Field name
                __('Disable First Option', 'contact-form-7-dynamic-text-extension'), // Field label
                __('Disable the first blank option from being selectable in the drop-down', 'contact-form-7-dynamic-text-extension'), // Checkbox label
                array('option' => true),
                array(
                    'url' => $this->doc_link('form-tags/dynamic-select'),
                    'label' => __('View Dynamic Select documentation', 'contact-form-7-dynamic-text-extension')
                ),
                $only_first_option
            );
        } elseif (in_array($tag_type, array('checkbox', 'radio'))) {
            // Additional fields for checkboxes and radio buttons

            // Input field - Checkbox Layout Reverse Option
            $this->checkbox(
                'label_first', // Field name
                __('Reverse', 'contact-form-7-dynamic-text-extension'), // Field label
                __('Put a label first, an input last', 'contact-form-7-dynamic-text-extension'), // Checkbox label
                array('option' => true)
            );

            // Input field - Label UI
            $this->checkbox(
                'use_label_element', // Field name
                __('Label', 'contact-form-7-dynamic-text-extension'), // Field label
                __('Wrap each item with label element', 'contact-form-7-dynamic-text-extension'), // Checkbox label
                array('option' => true)
            );

            // Input field - Exclusive Checkbox
            if ($tag_type == 'checkbox') {
                $this->checkbox(
                    'exclusive', // Field name
                    __('Exclusive', 'contact-form-7-dynamic-text-extension'), // Field label
                    __('Make checkboxes exclusive', 'contact-form-7-dynamic-text-extension'), // Checkbox label
                    array('option' => true)
                );
            }
        }

        // Input field - Range attributes (only available for numeric and date fields)
        if (in_array($tag_type, array('number', 'range', 'date'))) {
            $placeholder = 'Foo';
            $step_option = '';
            if ($tag_type == 'date') {
                $min_title = __('Oldest date allowed.', 'contact-form-7-dynamic-text-extension');
                $max_title = __('The most recent date or the date that is farthest in the future allowed.', 'contact-form-7-dynamic-text-extension');
                $note =  __('Optionally define the minimum and/or maximum date values the user may input.', 'contact-form-7-dynamic-text-extension') . ' ';
                $placeholder = 'hello-world_Foo';
            } else {
                $min_title = __('Lowest number allowed.', 'contact-form-7-dynamic-text-extension');
                $max_title = __('Highest number allowed', 'contact-form-7-dynamic-text-extension');
                $note = __('Optionally define the minimum and/or maximum number the user may input.', 'contact-form-7-dynamic-text-extension') . ' ';
                $step_option = sprintf(
                    '&nbsp;&nbsp;&nbsp;<label title="%s">%s <span class="wpcf7dtx-mini-att">%s</span></label>',
                    __('Allow the user to increment or decrement this number using the arrow keys', 'contact-form-7-dynamic-text-extension'),
                    esc_html__('Step'),
                    $this->input(
                        'step',
                        array(
                            'option' => 'dtx',
                            'atts' => array('size' => 10),
                            'datalist' => 'dtx-shortcodes'
                        )
                    ),
                );
                $note =  __('Optionally define the minimum, maximum, and/or step values.', 'contact-form-7-dynamic-text-extension') . ' ';
            }
            $form_controls = sprintf(
                '<label title="%s">%s <span class="wpcf7dtx-mini-att">%s</span></label> &#x27FA; <label title="%s">%s <span class="wpcf7dtx-mini-att">%s</span></label>',
                $min_title,
                esc_html__('Min', 'contact-form-7-dynamic-text-extension'),
                $this->input(
                    'min',
                    array(
                        'option' => 'dtx',
                        'atts' => array('size' => 10),
                        'datalist' => 'dtx-shortcodes'
                    )
                ),
                $max_title,
                esc_html__('Max', 'contact-form-7-dynamic-text-extension'),
                $this->input(
                    'max',
                    array(
                        'option' => 'dtx',
                        'atts' => array('size' => 10),
                        'datalist' => 'dtx-shortcodes'
                    )
                ),
            ) . $step_option;
            $note .= $each_can_be;
            $this->field(
                'min', // Field name
                __('Range', 'contact-form-7-dynamic-text-extension'), // Label
                $form_controls, // Form controls
                array(
                    'text' => $note, // Documentation text
                    'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                    'label' =>  $link_label_atts // Documentation link label
                )
            );
        }

        //Input field - ID attribute
        $this->text(
            'id', // Field name
            __('Id attribute', 'contact-form-7-dynamic-text-extension'), // Field label
            array(
                'option' => true,
                'datalist' => 'dtx-shortcodes'
            ),
            array(
                'text' => $can_be, // Documentation text
                'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                'label' =>  $link_label_atts // Documentation link label
            )
        );

        //Input field - Class attribute
        $this->text(
            'class',
            __('Class attribute', 'contact-form-7-dynamic-text-extension'),
            array(
                'option' => true,
                'datalist' => 'dtx-shortcodes'
            ),
            array(
                'text' => $can_be, // Documentation text
                'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                'label' =>  $link_label_atts // Documentation link label
            )
        );

        // Input field - Maxlenth & Minlength attributes (only available for visible text fields)
        if (!in_array($tag_type, array('hidden', 'quiz', 'submit', 'reset', 'label', 'number', 'range', 'date', 'select', 'checkbox', 'radio'))) {
            $note =  __('Optionally define the minimum and/or maximum character string length. Must be a positive whole number or integer.', 'contact-form-7-dynamic-text-extension') . ' ';
            $note .= $each_can_be;
            $form_controls = sprintf(
                '<label title="%s">%s <span class="wpcf7dtx-mini-att">%s</span></label> &#x27FA; <label title="%s">%s <span class="wpcf7dtx-mini-att">%s</span></label>',
                esc_attr__('minimum character length', 'contact-form-7-dynamic-text-extension'),
                esc_html__('Min', 'contact-form-7-dynamic-text-extension'),
                $this->input(
                    'minlength',
                    array(
                        'option' => 'dtx',
                        'atts' => array('size' => 10),
                        'datalist' => 'dtx-shortcodes'
                    )
                ),
                esc_attr('maximum character length', 'contact-form-7-dynamic-text-extension'),
                esc_html__('Max', 'contact-form-7-dynamic-text-extension'),
                $this->input(
                    'maxlength',
                    array(
                        'option' => 'dtx',
                        'atts' => array('size' => 10),
                        'datalist' => 'dtx-shortcodes'
                    )
                )
            );
            $this->field(
                'minlength', // Field name
                __('Character length', 'contact-form-7-dynamic-text-extension'), // Label
                $form_controls, // Form controls
                array(
                    'text' => $note, // Documentation text
                    'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                    'label' =>  $link_label_atts // Documentation link label
                )
            );
        }

        // Input field - Maxlenth & Minlength attributes (only available for visible text fields)
        if (in_array($tag_type, array('text', 'email', 'phone', 'url', 'password', 'textarea'))) {
            $note =  __('Optionally define an autocomplete setting by the browser.', 'contact-form-7-dynamic-text-extension') . ' ';
            $note .= $can_be;
            $this->text(
                'autocomplete', // Field name
                __('Auto-complete', 'contact-form-7-dynamic-text-extension'), // Field label
                array(
                    'option' => 'dtx',
                    'datalist' => array( // Datalist
                        'off' => __('Off', 'contact-form-7-dynamic-text-extension'),
                        'on' => __('On', 'contact-form-7-dynamic-text-extension'),
                        'name' => __('Full Name', 'contact-form-7-dynamic-text-extension'),
                        'given-name' => __('First Name', 'contact-form-7-dynamic-text-extension'),
                        'family-name' => __('Last Name', 'contact-form-7-dynamic-text-extension'),
                        'honorific-prefix' => __('Honorific prefix or title', 'contact-form-7-dynamic-text-extension'),
                        'honorific-suffix' => __('Honorific suffix or credentials', 'contact-form-7-dynamic-text-extension'),
                        'nickname' => __('Nickname', 'contact-form-7-dynamic-text-extension'),
                        'email' => __('Email Address', 'contact-form-7-dynamic-text-extension'),
                        'new-password' => __('A new password', 'contact-form-7-dynamic-text-extension'),
                        'current-password' => __('The current password', 'contact-form-7-dynamic-text-extension'),
                        'organization-title' => __('Job Title', 'contact-form-7-dynamic-text-extension'),
                        'organization' => __('Company/Organization Name', 'contact-form-7-dynamic-text-extension'),
                        'street-address' => __('Full Street Address', 'contact-form-7-dynamic-text-extension'),
                        'shipping' => __('Full Shipping Address', 'contact-form-7-dynamic-text-extension'),
                        'billing' => __('Full Billing Address', 'contact-form-7-dynamic-text-extension'),
                        'address-line1' => __('Address Line 1', 'contact-form-7-dynamic-text-extension'),
                        'address-line2' => __('Address Line 2', 'contact-form-7-dynamic-text-extension'),
                        'address-line3' => __('Address Line 3', 'contact-form-7-dynamic-text-extension'),
                        'address-level1' => __('State', 'contact-form-7-dynamic-text-extension'),
                        'address-level2' => __('City', 'contact-form-7-dynamic-text-extension'),
                        'country' => __('Country', 'contact-form-7-dynamic-text-extension'),
                        'postal-code' => __('Zip Code', 'contact-form-7-dynamic-text-extension'),
                        'sex' => __('Gender Identity', 'contact-form-7-dynamic-text-extension'),
                        'tel' => __('Phone Number', 'contact-form-7-dynamic-text-extension'),
                        'tel-country-code' => __('Country Code', 'contact-form-7-dynamic-text-extension'),
                        'tel-national' => __('Full phone number without the country code', 'contact-form-7-dynamic-text-extension'),
                        'tel-area-code' => __('Area code, with country code if applicable', 'contact-form-7-dynamic-text-extension'),
                        'tel-local' => __('Phone number without the country code or area code', 'contact-form-7-dynamic-text-extension'),
                        'tel-local-prefix' => __('First part of the local phone number', 'contact-form-7-dynamic-text-extension'),
                        'tel-local-suffix' => __('Last part of the local phone number', 'contact-form-7-dynamic-text-extension'),
                        'url' => __('Website URL', 'contact-form-7-dynamic-text-extension'),
                        'webauthn' => __('Passkeys generated by the Web Authentication API', 'contact-form-7-dynamic-text-extension')
                    )
                ),
                array(
                    'text' => $note, // Documentation text
                    'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                    'label' =>  $link_label_atts // Documentation link label
                )
            );
        }

        // Input field - Autocapitalize attribute (only available for some fields)
        if (in_array($tag_type, array('text', 'textarea'))) {
            $note =  __('Optionally define an autocapitalize attribute for input when entered via non-typing mechanisms such as virtual keyboards on mobile devices or voice-to-text.', 'contact-form-7-dynamic-text-extension') . ' ';
            $note .= $can_be;
            $this->text(
                'autocapitalize', // Field name
                __('Auto-capitalize', 'contact-form-7-dynamic-text-extension'), // Field label
                array(
                    'option' => 'dtx',
                    'datalist' =>  array( // Datalist
                        'none' => __('Do not automatically capitlize any text', 'contact-form-7-dynamic-text-extension'),
                        'off' => __('Do not automatically capitlize any text', 'contact-form-7-dynamic-text-extension'),
                        'on' => __('Automatically capitalize the first character of each sentence', 'contact-form-7-dynamic-text-extension'),
                        'sentences' => __('Automatically capitalize the first character of each sentence', 'contact-form-7-dynamic-text-extension'),
                        'words' => __('Automatically capitalize the first character of each word', 'contact-form-7-dynamic-text-extension'),
                        'characters' => __('Automatically capitalize every character', 'contact-form-7-dynamic-text-extension'),
                    )
                ),
                array(
                    'text' => $note, // Documentation text
                    'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                    'label' =>  $link_label_atts // Documentation link label
                )
            );
        }

        //Input field - Readonly attribute (not available for hidden, submit, or quiz fields)
        if (!in_array($tag_type, array('hidden', 'submit', 'label', 'quiz', 'select'))) {
            $this->checkbox(
                'readonly', // Field name
                __('Read only attribute', 'contact-form-7-dynamic-text-extension'), // Field label
                __('Do not let users edit this field', 'contact-form-7-dynamic-text-extension'), // Checkbox label
                array('option' => true), // CF7 boolean option
                array(
                    'text' => $can_be, // Documentation text
                    'url' => $this->doc_link('dynamic-attributes'), // Documentation link url
                    'label' =>  $link_label_atts // Documentation link label
                )
            );
        }

        // Input field - Page load data attribute (triggers the loading of a frontend script)
        $this->checkbox(
            'dtx_pageload', // Field name
            __('Cache compatible', 'contact-form-7-dynamic-text-extension'), // Field label
            __('Get the dynamic value after the page has loaded', 'contact-form-7-dynamic-text-extension'), // Checkbox label
            array('option' => true,),
            array(
                'text' =>  __('May impact page performance.', 'contact-form-7-dynamic-text-extension'), // Small note below input
                'url' => $this->doc_link('form-tag-attribute-after-page-load'),
                'label' => __('View DTX page load documentation', 'contact-form-7-dynamic-text-extension') //Link label
            )
        );

        // Input field - Akismet module (only available for text, email, and url fields)
        if (wpcf7_akismet_is_available() && in_array($tag_type, array('text', 'email', 'url'))) {
            switch ($tag_type) {
                case 'email':
                    $akismet_name = 'author_email';
                    $akismet_desc = __("This field requires author's email address",  'contact-form-7-dynamic-text-extension');
                    break;
                case 'url':
                    $akismet_name = 'author_url';
                    $akismet_desc = __("This field requires author's URL",  'contact-form-7-dynamic-text-extension');
                    break;
                default:
                    $akismet_name = 'author';
                    $akismet_desc = __("This field requires author's name",  'contact-form-7-dynamic-text-extension');
                    break;
            }
            $this->checkbox(
                'akismet-' . $akismet_name, // Field name
                __('Akismet', 'contact-form-7-dynamic-text-extension'), // Field label
                $akismet_desc, // Checkbox label
                array(
                    'option' => true, // CF7 option
                    'part' => 'akismet:' . $akismet_name // Custom option name
                )
            );
        }

        // Close Form-Tag Generator
        echo '</div>';

        /**
         * Footer
         */
        echo wp_kses(sprintf(
            '<footer class="insert-box dtx-taggen"><div class="flex-container">%s<button type="button" class="button-primary" data-taggen="insert-tag">%s</button></div><p class="mail-tag-tip">%s</p></footer>',
            sprintf(
                in_array($tag_type, array('hidden', 'submit', 'label', 'reset')) ?
                    '<input type="text" class="code" readonly="readonly" onfocus="this.select()" data-tag-part="tag" aria-label="%s">' :
                    '<textarea class="code" readonly="readonly" onfocus="this.select()" rows="3" data-tag-part="tag" aria-label="%s"></textarea>',
                esc_html__('The form-tag to be inserted into the form template', 'contact-form-7-dynamic-text-extension')
            ),
            esc_html__('Insert Tag', 'contact-form-7-dynamic-text-extension'),
            in_array($tag_type, array('submit', 'reset', 'label')) ?
                esc_html__('This value is not submitted in the contact form and should not be used in the mail template.', 'contact-form-7-dynamic-text-extension') :
                sprintf(
                    /* translators: %s: mail-tag corresponding to the form-tag */
                    esc_html__('To use the user input in the email, insert the corresponding mail-tag %s into the email template.', 'contact-form-7'),
                    '<strong data-tag-part="mail-tag"></strong>'
                )
        ), $this->allowed_tags('footer'), array('https'));
    }
}
TagGenerator::instance(); // Initialise self