<?php

/**
 * Admin Scripts and Styles
 *
 * Enqueue scripts and styles to be used on the admin pages
 *
 * @since 3.1.0
 *
 * @param string $hook Hook suffix for the current admin page
 */
function wpcf7dtx_enqueue_admin_assets($hook)
{
    //Only load on CF7 Form pages
    if ($hook == 'toplevel_page_wpcf7') {
        $prefix = 'wpcf7dtx-';
        $url = plugin_dir_url(WPCF7DTX_FILE);
        $path = plugin_dir_path(WPCF7DTX_FILE);

        wp_enqueue_style(
            $prefix . 'admin', //Handle
            $url . 'assets/styles/tag-generator.css', //Source
            array('contact-form-7-admin'), //Dependencies
            @filemtime($path . 'assets/styles/tag-generator.css') //Version
        );

        //Plugin Scripts
        wp_enqueue_script(
            $prefix . 'taggenerator', //Handle
            $url . 'assets/scripts/tag-generator.js', //Source
            array('jquery', 'wpcf7-admin-taggenerator'), //Dependencies
            @filemtime($path . 'assets/scripts/tag-generator.js'), //Version
            true //In footer
        );
    }
}
add_action('admin_enqueue_scripts', 'wpcf7dtx_enqueue_admin_assets'); //Enqueue styles/scripts for admin page

/**
 * Create Tag Generators
 *
 * @return void
 */
function wpcf7dtx_add_tag_generator_dynamictext()
{
    if (!class_exists('WPCF7_TagGenerator')) {
        return;
    }
    $tag_generator = WPCF7_TagGenerator::get_instance();

    //Dynamic Text Field
    $tag_generator->add(
        'dynamictext', //id
        __('dynamic text', 'contact-form-7-dynamic-text-extension'), //title
        'wpcf7dtx_tag_generator_dynamictext', //callback
        array('placeholder', 'readonly') //options
    );

    //Dynamic Hidden Field
    $tag_generator->add(
        'dynamichidden', //id
        __('dynamic hidden', 'contact-form-7-dynamic-text-extension'), //title
        'wpcf7dtx_tag_generator_dynamictext' //callback
    );
}
add_action('wpcf7_admin_init', 'wpcf7dtx_add_tag_generator_dynamictext', 100);

/**
 * Echo HTML for Dynamic Tag Generator
 *
 * @param WPCF7_ContactForm $contact_form
 * @param array $options
 * @return void
 */
function wpcf7dtx_tag_generator_dynamictext($contact_form, $options = '')
{
    $options = wp_parse_args($options);
    $type = $options['id'];
    switch ($type) {
        case 'dynamichidden': //hiden
            $description = __('Generate a form-tag for a hidden input field, with a dynamically generated default value.', 'contact-form-7-dynamic-text-extension');
            break;
        default:
            $description = __('Generate a form-tag for a single-line plain text input field, with a dynamically generated default value.', 'contact-form-7-dynamic-text-extension');
            break;
    }
    $utm_source = urlencode(home_url());
    $description .= sprintf(
        ' %s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a>.',
        __('For more details, see', 'contact-form-7-dynamic-text-extension'),
        esc_attr($utm_source), //UTM source
        esc_attr($type), //UTM content
        __('DTX knowledge base', 'contact-form-7-dynamic-text-extension')
    );

    //Open Form-Tag Generator
    printf(
        '<div class="control-box"><fieldset><legend>%s</legend><table class="form-table"><tbody>',
        wp_kses($description, 'a') //Tag generator description
    );

    //Input field - Required checkbox (not available for hidden fields)
    if ($type != 'dynamichidden') {
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

    //Input field - Field Name
    printf(
        '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s /></td></tr>',
        esc_attr($options['content'] . '-name'), // field id
        esc_html__('Name', 'contact-form-7-dynamic-text-extension'), // field label
        wpcf7_format_atts(array(
            'type' => 'text',
            'name' => 'name',
            'id' => $options['content'] . '-name',
            'class' => 'tg-name oneline'
        ))
    );

    //Input field - Dynamic value
    printf(
        '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s /><br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
        esc_attr($options['content'] . '-values'), // field id
        esc_html__('Dynamic value', 'contact-form-7-dynamic-text-extension'), // field label
        wpcf7_format_atts(array(
            'type' => 'text',
            'name' => 'values',
            'id' => $options['content'] . '-values',
            'class' => 'oneline',
            'placeholder' => "CF7_GET key='foo'"
        )),
        esc_html__('Can be static text or a shortcode.', 'contact-form-7-dynamic-text-extension'),
        esc_attr($utm_source), //UTM source
        esc_attr($type), //UTM content
        esc_html__('View DTX shortcode syntax documentation', 'contact-form-7-dynamic-text-extension') //Link label
    );

    //Input field - Dynamic placeholder (not available for hidden fields)
    if ($type != 'dynamichidden') {
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td><input %s /><input %s /><br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-placeholder/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
            esc_attr($options['content'] . '-placeholder'), // field id
            esc_html__('Dynamic placeholder', 'contact-form-7-dynamic-text-extension'), // field label
            wpcf7_format_atts(array(
                'type' => 'hidden',
                'name' => 'placeholder',
                'class' => 'option'
            )),
            wpcf7_format_atts(array(
                'type' => 'text',
                'name' => 'dtx-placeholder',
                'id' => $options['content'] . '-placeholder', // field id
                'class' => 'oneline dtx-option',
                'placeholder' => 'CF7_get_post_var key=\'post_title\''
            )),
            esc_html__('Can be static text or a shortcode.', 'contact-form-7-dynamic-text-extension'),
            esc_attr($utm_source), //UTM source
            esc_attr($type), //UTM content
            esc_html__('View DTX placeholder documentation', 'contact-form-7-dynamic-text-extension') //Link label
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

    //Input field - Readonly attribute (not available for hidden fields)
    if ($type != 'dynamichidden') {
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

    //Close Form-Tag Generator
    printf(
        '</tbody></table></fieldset></div><div class="insert-box"><input type="text" name="%s" class="tag code" readonly="readonly" onfocus="this.select()" /><div class="submitbox"><input type="button" class="button button-primary insert-tag" value="%s" /></div><br class="clear" /></div>',
        esc_attr($type),
        esc_html__('Insert Tag', 'contact-form-7-dynamic-text-extension')
    );
}
