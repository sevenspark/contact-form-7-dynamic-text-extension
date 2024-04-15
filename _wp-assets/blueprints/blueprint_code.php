<?php
require_once('/wordpress/wp-load.php');

update_option('cf7dtx_settings', array('user_data_allow_keys' => 'user_email'));

wp_insert_post(array(
    'ID' => 2,
    'post_author' => 1,
    'post_type' => 'page',
    'post_status' => 'publish',
    'post_name' => 'example-page-with-dynamic-form',
    'post_title' => 'Example Page with Dynamic Form',
    'post_content' => '<!-- wp:contact-form-7/contact-form-selector {"id":4,"hash":"d3m0For","title":"Demo Form"} -->
<div class="wp-block-contact-form-7-contact-form-selector">[contact-form-7 id="d3m0For" title="Demo Form"]</div>
<!-- /wp:contact-form-7/contact-form-selector -->',
    'comment_status' => 'closed',
    'ping_status' => 'closed'
));

wp_insert_post(array(
    'ID' => 4,
    'post_type' => 'wpcf7_contact_form',
    'post_status' => 'publish',
    'post_title' => 'Demo Form',
    'post_author' => 1,
    'comment_status' => 'closed',
    'ping_status' => 'closed',
    'meta_input' => array(
        '_form' => '<p>
        <label>
            <span class="label">Post Title</span><br />
            [dynamic_text dtx_post_title size:60 "CF7_get_current_var key=\'post_title\'"]
        </label>
    </p>

    <p>
        <label>
            <span class="label">Dynamic Placeholder</span><br />
            [dynamic_text dtx_placeholder placeholder:CF7_GET%20key%3D%26%2339%3Bfoo%26%2339%3B]
        </label>
    </p>

    <p>
        <label>
            <span class="label">Your Email</span><br />
            [dynamic_email* dtx_email "CF7_get_current_user key=\'user_email\'"]
        </label>
    </p>

    <p>
        <label>
            <span class="label">The value of <code>?bar</code></span> (readonly)<br />
            [dynamic_text dtx_foobar readonly "CF7_GET key=\'bar\'"]
        </label>
    </p>

    <p>
        <label>
            <span class="label">Your message (optional)<br />
            [dynamic_textarea dtx_textarea maxlength:50 "Maximum character length is 50!"]
        </label>
    </p>

    <p>[dynamic_submit "Submit"]</p>',
        '_mail' => array(),
        '_mail2' => array(),
        '_messages' => array(),
        '_additional_settings' => 'demo_mode: on' . PHP_EOL . 'skip_mail: on',
        '_locale' => 'en_US',
        '_hash' => 'd3m0Form'
    )
));
