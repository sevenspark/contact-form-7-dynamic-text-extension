(function($) {
    'use strict';
    if (typeof wpcf7 === 'undefined' || wpcf7 === null) {
        return;
    }
    window.wpcf7dtx = window.wpcf7dtx || {};
    wpcf7dtx.taggen = {};

    $('input.dtx-insert-tag').click(function() {
        var $form = $(this).closest('form.tag-generator-panel');
        var tag = $form.find('.insert-box .tag').val();
        wpcf7.taggen.insert(tag);
        tb_remove(); // close thickbox
        return false;
    });

    wpcf7dtx.taggen.escapeRegExp = function(str) {
        return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
    };

    wpcf7dtx.taggen.replaceAll = function(input, f, r, no_escape) {
        if (input !== undefined && input !== null && typeof(input) == 'string' && input.trim() !== '' && input.indexOf(f) > -1) {
            var rexp = new RegExp(wpcf7dtx.taggen.escapeRegExp(f), 'g');
            if (no_escape) { rexp = new RegExp(f, 'g'); }
            return input.replace(rexp, r);
        }
        return input;
    };

    // Overwrite update function to allow the textarea display for advanced tags
    wpcf7.taggen.update = function($form) {
        var id = $form.attr('data-id');
        var name = '';
        var name_fields = $form.find('input[name="name"]');

        if (name_fields.length) {
            name = name_fields.val();
            if ('' === name) {
                name = id + '-' + Math.floor(Math.random() * 1000);
                name_fields.val(name);
            }
        }

        if ($.isFunction(wpcf7.taggen.update[id])) {
            return wpcf7.taggen.update[id].call(this, $form);
        }

        let $display = $form.find('.insert-box .tag');
        // if (!$display.length) {
        //     $display = $form.find('textarea.tag');
        // }

        $display.each(function() {
            var tag_type = $(this).attr('name');

            if ($form.find(':input[name="tagtype"]').length) {
                tag_type = $form.find(':input[name="tagtype"]').val();
            }

            if ($form.find(':input[name="required"]').is(':checked')) {
                tag_type += '*';
            }

            var components = wpcf7.taggen.compose(tag_type, $form);
            $(this).val(components);
        });

        $form.find('span.mail-tag').text('[' + name + ']');
        $form.find('input.mail-tag').each(function() {
            $(this).val('[' + name + ']');
        });
    };

    wpcf7dtx.taggen.updateOption = function(e) {
        var $this = $(e.currentTarget),
            value = encodeURIComponent(wpcf7dtx.taggen.replaceAll($this.val(), "'", '&#39;')),
            $option = $this.siblings('input[type="hidden"].option');
        $option.val(value);
        if (e.type != 'change') {
            // DTX only listens for "change" so force the tag to rebuild for our other listeners
            $option.trigger('change');
        }
    };

    $(function() {
        $('form.tag-generator-panel .dtx-option').on('change keyup click', wpcf7dtx.taggen.updateOption);
        $('.contact-form-editor-panel #tag-generator-list a.thickbox.button[href*="inlineId=tag-generator-panel-dynamic_"]').each(function() {
            var $btn = $(this),
                name = $btn.text();
            $btn.addClass('dtx-form-tag');
            if (
                name.indexOf('dynamic ') === 0 && // Set size of tag generator to be larger for dynamic fields
                name.indexOf('hidden') < 0 && // Except for this one because it doesn't have a lot of options
                name.indexOf('label') < 0 && // Except for this one because it doesn't have a lot of options
                name.indexOf('submit') < 0 // Except for this one because it doesn't have a lot of options
            ) {
                $btn.attr('href', $btn.attr('href').replace('height=500', 'height=785'));
            }
        });
    });
})(jQuery);