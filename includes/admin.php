<?php

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
function wpcf7dtx_enqueue_admin_assets($hook)
{
    // Only load on CF7 Form pages (both editing forms and creating new forms)
    if ($hook === 'toplevel_page_wpcf7' || $hook === 'contact_page_wpcf7-new') {
        $prefix = 'wpcf7dtx-';
        $debug = defined('WP_DEBUG_SCRIPTS') && constant('WP_DEBUG_SCRIPTS');
        $url = plugin_dir_url(WPCF7DTX_FILE);
        $path = plugin_dir_path(WPCF7DTX_FILE);

        wp_enqueue_style(
            $prefix . 'admin', //Handle
            $url . 'assets/styles/tag-generator' . ($debug ? '' : '.min') . '.css', //Source
            array('contact-form-7-admin'), //Dependencies
            $debug ? @filemtime($path . 'assets/styles/tag-generator.css') : WPCF7DTX_VERSION //Version
        );

        //Plugin Scripts
        wp_enqueue_script(
            $prefix . 'taggenerator', //Handle
            $url . 'assets/scripts/tag-generator' . ($debug ? '' : '.min') . '.js', //Source
            array('jquery', 'wpcf7-admin-taggenerator'), //Dependencies
            $debug ? @filemtime($path . 'assets/scripts/tag-generator.js') : WPCF7DTX_VERSION, //Version
            array('in_footer' => true, 'strategy' => 'defer') // Defer loading in footer
        );
    }
}
add_action('admin_enqueue_scripts', 'wpcf7dtx_enqueue_admin_assets'); //Enqueue styles/scripts for admin page

/**
 * Create Tag Generators
 *
 * @return void
 */
function wpcf7dtx_add_tag_generators()
{
    if (!class_exists('WPCF7_TagGenerator')) {
        return;
    }

    // Custom dynamic fields to add
    global $wpcf7_dynamic_fields_config;

    // Loop fields to add them
    $tag_generator = WPCF7_TagGenerator::get_instance();
    foreach ($wpcf7_dynamic_fields_config as $id => $field) {
        $tag_generator->add($id, $field['title'], 'wpcf7dtx_tag_generator', array_merge(array('name-attr', 'dtx_pageload'), $field['options']));
    }
}
add_action('wpcf7_admin_init', 'wpcf7dtx_add_tag_generators', 100);

/**
 * Echo HTML for Dynamic Tag Generator
 *
 * @param WPCF7_ContactForm $contact_form
 * @param array $options
 *
 * @return void
 */
function wpcf7dtx_tag_generator($contact_form, $options = '')
{
    $options = wp_parse_args($options);
    global $wpcf7_dynamic_fields_config;
    $type = $options['id'];
    $input_type = str_replace('dynamic_', '', $type);
    $utm_source = urlencode(home_url());
    $description = sprintf(
        __('Generate a form-tag for %s with a dynamic default value. For more details, see %s fields in the %s.', 'contact-form-7-dynamic-text-extension'),
        esc_html($wpcf7_dynamic_fields_config[$type]['description']), // dynamic description
        // Link to specific form-tag documentation
        sprintf(
            '<a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/form-tags/%s?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" title="%s" target="_blank" rel="noopener">%s</a>',
            esc_attr(str_replace('_', '-', $type)), // URL component
            esc_attr($utm_source), //UTM source
            esc_attr($type), //UTM content
            esc_attr__('View this form-tag on the DTX Documentation website', 'contact-form-7-dynamic-text-extension'), // Link title
            esc_html(ucwords(str_replace('_', ' ', $type))) // Link label
        ),
        // Link to general DTX documentation
        sprintf(
            '<a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" title="%s" target="_blank" rel="noopener">%s</a>',
            esc_attr($utm_source), //UTM source
            esc_attr($type), //UTM content
            esc_attr__('Go to DTX Documentation website', 'contact-form-7-dynamic-text-extension'),
            esc_html__('DTX knowledge base', 'contact-form-7-dynamic-text-extension')
        )
    );

    // Open Form-Tag Generator
    printf(
        '<div class="control-box dtx-taggen"><fieldset><legend>%s</legend><table class="form-table"><tbody>',
        wp_kses($description, array('a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'title' => array()))) //Tag generator description
    );

    // Input field - Required checkbox (not available for some fields)
    if (!in_array($input_type, array('hidden', 'quiz', 'submit', 'reset'))) {
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
            esc_attr($options['content'] . '-required'), // field id
            esc_html__('Field type', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'required',
                'id' => $options['content'] . '-required'
            )),
            esc_html__('Required field', 'contact-form-7-dynamic-text-extension') // checkbox label
        );
    }

    // Input field - Field Name (not available for some fields)
    if (!in_array($input_type, array('submit', 'reset'))) {
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s /></td></tr>',
            esc_attr($options['content'] . '-name'), // field id
            esc_html__('Name', 'contact-form-7-dynamic-text-extension'), // field label
            wpcf7_format_atts(array(
                'type' => 'text',
                'name' => 'name',
                'id' => $options['content'] . '-name',
                'class' => 'tg-name oneline',
                'autocomplete' => 'off'
            ))
        );
    }

    // Input field - Dynamic value/options
    $value_name = __('Dynamic value', 'contact-form-7-dynamic-text-extension');
    $value_description = __('Can be static text or a shortcode.', 'contact-form-7-dynamic-text-extension');
    $value_placeholder = "CF7_GET key='foo'";
    $value_input_type = '<input %s />';
    switch ($input_type) {
        case 'textarea':
            $value_placeholder = "CF7_get_post_var key='post_excerpt'";
            $value_input_type = '<textarea %s></textarea>';
            break;
        case 'select':
            $value_name = __('Dynamic options', 'contact-form-7-dynamic-text-extension');
            $value_description .= ' ' . __('If static text, use one option per line. Can define static key/value pairs using pipes.', 'contact-form-7-dynamic-text-extension');
            $value_description .= ' ' . __('If shortcode, it must return only option or optgroup HTML and can override the first option and select default settings here.', 'contact-form-7-dynamic-text-extension');
            $value_placeholder = "hello-world | Hello World" . PHP_EOL . "Foo";
            $value_input_type = '<textarea %s></textarea>';
            break;
        case 'checkbox':
        case 'radio':
            $value_name = __('Dynamic options', 'contact-form-7-dynamic-text-extension');
            $value_description .= ' ' . __('If static text, use one option per line. Can define static key/value pairs using pipes.', 'contact-form-7-dynamic-text-extension');
            $value_description .= ' ' . __('If shortcode, it must return only option or optgroup HTML.', 'contact-form-7-dynamic-text-extension');
            $value_placeholder = "hello-world | Hello World" . PHP_EOL . "Foo";
            $value_input_type = '<textarea %s></textarea>';
            break;
        default: // All other text fields
            break;
    }
    printf(
        '<tr><th scope="row"><label for="%s">%s</label></th><td>' . $value_input_type . '<br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
        esc_attr($options['content'] . '-values'), // field id
        esc_html($value_name), // field label
        wpcf7_format_atts(array(
            'name' => 'values',
            'id' => $options['content'] . '-values',
            'class' => 'multiline',
            'placeholder' => $value_placeholder,
            'list' => 'dtx-shortcodes'
        )),
        esc_html($value_description),
        esc_attr($utm_source), // UTM source
        esc_attr($type), // UTM content
        esc_html__('View DTX shortcode syntax documentation', 'contact-form-7-dynamic-text-extension') // Link label
    );

    if ($input_type == 'select') {
        // Input field - Multiple selections checkbox
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
            esc_attr($options['content'] . '-multiple'), // field id
            esc_html__('Multiple Options', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'multiple',
                'id' => $options['content'] . '-multiple',
                'class' => 'option'
            )),
            esc_html__('Allow user to select multiple options', 'contact-form-7-dynamic-text-extension') // checkbox label
        );

        // Input field - Include blank checkbox
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
            esc_attr($options['content'] . '-include_blank'), // field id
            esc_html__('First Option', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'include_blank',
                'id' => $options['content'] . '-include_blank',
                'class' => 'include_blankvalue option'
            )),
            esc_html__('Insert a blank item as the first option', 'contact-form-7-dynamic-text-extension') // checkbox label
        );
    }

    // Input field - Dynamic placeholder (not available for some fields)
    if (!in_array($input_type, array('hidden', 'radio', 'checkbox', 'quiz', 'submit', 'reset'))) {
        $placeholder_description = '';
        if (in_array($input_type, array('select', 'checkbox', 'radio'))) {
            $placeholder_label = __('First Option Label', 'contact-form-7-dynamic-text-extension');
            $placeholder_description .= __('Optionally define a label for the first option.', 'contact-form-7-dynamic-text-extension') . ' ';
        } else {
            $placeholder_label = __('Dynamic placeholder', 'contact-form-7-dynamic-text-extension');
        }
        $placeholder_description .= __('Can be static text or a shortcode.', 'contact-form-7-dynamic-text-extension');
        $placeholder_input_type = $input_type == 'textarea' ? $value_input_type : '<input %s />';
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s />' . $placeholder_input_type . '<br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-placeholder/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
            esc_attr($options['content'] . '-placeholder'), // field id
            esc_html($placeholder_label), // field label
            wpcf7_format_atts(array(
                'type' => 'hidden',
                'name' => 'placeholder',
                'class' => 'option'
            )),
            wpcf7_format_atts(array(
                'name' => 'dtx-placeholder',
                'id' => $options['content'] . '-placeholder', // field id
                'class' => 'multiline dtx-option',
                'placeholder' => "CF7_get_post_var key='post_title'",
                'list' => 'dtx-shortcodes'
            )),
            esc_html($placeholder_description), // Small note below input
            esc_attr($utm_source), //UTM source
            esc_attr($type), //UTM content
            esc_html__('View DTX placeholder documentation', 'contact-form-7-dynamic-text-extension') //Link label
        );
    }

    // Additional fields for select regarding placeholder options
    if ($input_type == 'select') {

        // Input field - Hide Blank Option
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label><br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/form-tags/dynamic-select/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
            esc_attr($options['content'] . '-dtx_hide_blank'), // field id
            esc_html__('Hide First Option', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'dtx_hide_blank',
                'id' => $options['content'] . '-dtx_hide_blank',
                'class' => 'option'
            )),
            esc_html__('Hide the first blank option from being visible in the drop-down', 'contact-form-7-dynamic-text-extension'), // checkbox label
            esc_html__('Optional. Only works if "First Option" is checked.', 'contact-form-7-dynamic-text-extension'), // Small note below input
            esc_attr($utm_source), //UTM source
            esc_attr($type), //UTM content
            esc_html__('View Dynamic Select documentation', 'contact-form-7-dynamic-text-extension') //Link label
        );

        // Input field - Disable Blank Option
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label><br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/form-tags/dynamic-select/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
            esc_attr($options['content'] . '-dtx_disable_blank'), // field id
            esc_html__('Disable First Option', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'dtx_disable_blank',
                'id' => $options['content'] . '-dtx_disable_blank',
                'class' => 'option'
            )),
            esc_html__('Disable the first blank option from being selectable in the drop-down', 'contact-form-7-dynamic-text-extension'), // checkbox label
            esc_html__('Optional. Only works if "First Option" is checked.', 'contact-form-7-dynamic-text-extension'), // Small note below input
            esc_attr($utm_source), //UTM source
            esc_attr($type), //UTM content
            esc_html__('View Dynamic Select documentation', 'contact-form-7-dynamic-text-extension') //Link label

        );
    } elseif (in_array($input_type, array('checkbox', 'radio'))) {
        // Additional fields for checkboxes and radio buttons

        // Input field - Checkbox Layout Reverse Option
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
            esc_attr($options['content'] . '-label_first'), // field id
            esc_html__('Reverse', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'label_first',
                'id' => $options['content'] . '-label_first',
                'class' => 'option'
            )),
            esc_html__('Put a label first, an input last', 'contact-form-7-dynamic-text-extension') // checkbox label
        );

        // Input field - Label UI
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
            esc_attr($options['content'] . '-use_label_element'), // field id
            esc_html__('Label', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'use_label_element',
                'id' => $options['content'] . '-use_label_element',
                'class' => 'option'
            )),
            esc_html__('Wrap each item with label element', 'contact-form-7-dynamic-text-extension') // checkbox label
        );

        // Input field - Exclusive Checkbox
        if ($input_type == 'checkbox') {
            printf(
                '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
                esc_attr($options['content'] . '-exclusive'), // field id
                esc_html__('Exclusive', 'contact-form-7-dynamic-text-extension'), // field Label
                wpcf7_format_atts(array(
                    'type' => 'checkbox',
                    'name' => 'exclusive',
                    'id' => $options['content'] . '-exclusive',
                    'class' => 'option'
                )),
                esc_html__('Make checkboxes exclusive', 'contact-form-7-dynamic-text-extension') // checkbox label
            );
        }
    }

    // Input field - Dynamic default value (not available for some fields)
    if (in_array($input_type, array('select'))) {
        $default_input_type = '<input %s />';
        $default_placeholder = '';
        if ($input_type == 'checkbox') {
            $default_input_type = '<textarea %s></textarea>';
            $default_description =  __('Optionally define the default on/off status of the checkboxes by putting a 1 (checked) or 0 (not checked) on each line that corresponds with the options.', 'contact-form-7-dynamic-text-extension') . ' ';
            $default_placeholder = '0' . PHP_EOL . '1';
        } else {
            $default_description =  __('Optionally define the option that is selected by default. This can be different than the first [blank] option. If options use key/value pairs, only define the key here.', 'contact-form-7-dynamic-text-extension') . ' ';
        }
        $default_description .= __('Can be static text or a shortcode.', 'contact-form-7-dynamic-text-extension');
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s />' . $default_input_type . '<br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/form-tags/dynamic-select/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
            esc_attr($options['content'] . '-default'), // field id
            esc_html__('Selected Default'), // field label
            wpcf7_format_atts(array(
                'type' => 'hidden',
                'name' => 'default',
                'class' => 'option'
            )),
            wpcf7_format_atts(array(
                'name' => 'dtx-default',
                'id' => $options['content'] . '-default', // field id
                'class' => 'oneline dtx-option',
                'placeholder' => $default_placeholder,
                'list' => 'dtx-shortcodes'
            )),
            esc_html($default_description), // Small note below input
            esc_attr($utm_source), //UTM source
            esc_attr($type), //UTM content
            esc_html__('View Dynamic Select documentation', 'contact-form-7-dynamic-text-extension') //Link label
        );
    }

    //Input field - ID attribute
    printf(
        '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s /></td></tr>',
        esc_attr($options['content'] . '-id'), // field id
        esc_html__('Id attribute', 'contact-form-7-dynamic-text-extension'), // field label
        wpcf7_format_atts(array(
            'type' => 'text',
            'name' => 'id',
            'id' => $options['content'] . '-id', // field id
            'class' => 'idvalue oneline option'
        ))
    );

    //Input field - Class attribute
    printf(
        '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s /></td></tr>',
        esc_attr($options['content'] . '-class'), // field id
        esc_html__('Class attribute', 'contact-form-7-dynamic-text-extension'), // field label
        wpcf7_format_atts(array(
            'type' => 'text',
            'name' => 'class',
            'id' => $options['content'] . '-class', // field id
            'class' => 'classvalue oneline option'
        ))
    );

    //Input field - Readonly attribute (not available for hidden, submit, or quiz fields)
    if (!in_array($input_type, array('hidden', 'submit', 'quiz'))) {
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
            esc_attr($options['content'] . '-readonly'), // field id
            esc_html__('Read only attribute', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'readonly',
                'id' => $options['content'] . '-readonly',
                'class' => 'readonlyvalue option'
            )),
            esc_html__('Do not let users edit this field', 'contact-form-7-dynamic-text-extension') // checkbox label
        );
    }

    // Input field - Page load data attribute (triggers the loading of a frontend script)
    printf(
        '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label><br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/form-tag-attribute-after-page-load/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
        esc_attr($options['content'] . '-dtx_pageload'), // field id
        esc_html__('Cache Compatible', 'contact-form-7-dynamic-text-extension'), // field Label
        wpcf7_format_atts(array(
            'type' => 'checkbox',
            'name' => 'dtx_pageload',
            'id' => $options['content'] . '-dtx_pageload',
            'class' => 'option'
        )),
        esc_html__('Get the dynamic value after the page has loaded', 'contact-form-7-dynamic-text-extension'), // checkbox label
        esc_html__('May impact page performance.', 'contact-form-7-dynamic-text-extension'), // Small note below input
        esc_attr($utm_source), //UTM source
        esc_attr($type), //UTM content
        esc_html__('View DTX page load documentation', 'contact-form-7-dynamic-text-extension') //Link label

    );

    // Input field - Akismet module (only available for text, email, and url fields)
    if (in_array($input_type, array('text', 'email', 'url'))) {
        switch ($input_type) {
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
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><label><input %s />%s</label></td></tr>',
            esc_attr($options['content'] . '-readonly'), // field id
            esc_html__('Akismet', 'contact-form-7-dynamic-text-extension'), // field Label
            wpcf7_format_atts(array(
                'type' => 'checkbox',
                'name' => 'akismet:' . $akismet_name,
                'id' => $options['content'] . '-akismet-' . $akismet_name,
                'class' => 'akismetvalue option'
            )),
            esc_html($akismet_desc) // checkbox label
        );
    }

    //Close Form-Tag Generator
    printf(
        '</tbody></table></fieldset></div><div class="insert-box"><input type="text" name="%s" class="tag code" readonly="readonly" onfocus="this.select()" /><div class="submitbox"><input type="button" class="button button-primary insert-tag" value="%s" /></div><br class="clear" /></div>',
        esc_attr($type),
        esc_html__('Insert Tag', 'contact-form-7-dynamic-text-extension')
    );
}
