(($) => {

    /**
     * Update Form Tag Generator Button
     *
     * Add class to DTX buttons they can be styled via CSS
     */
    $('#tag-generator-list button[data-target*="tag-generator-panel-dynamic_"]').each(function() {
        $(this).addClass('dtx-form-tag');
    });

    $('.tag-generator-dialog button[data-taggen="close-dialog"]').addClass('button button-secondary');

    let wpcf7dtx_taggen = {};

    /**
     * Replace All Helper Function
     *
     * @param {} input
     * @param {*} f
     * @param {*} r
     * @param {*} no_escape
     * @returns {string}
     */
    wpcf7dtx_taggen.replaceAll = (input, f, r, no_escape) => {
        if (input !== undefined && input !== null && typeof(input) == 'string' && input.trim() !== '' && input.indexOf(f) > -1) {
            var rexp = new RegExp(f.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"), 'g');
            if (no_escape) { rexp = new RegExp(f, 'g'); }
            return input.replace(rexp, r);
        }
        return input;
    };

    /**
     * Update form-tag preview with encoded attribute value
     *
     * Encode user input to form-tag dynamic attribute values.
     *
     * @param {object} e Event object. Event types include change, keyup, and click.
     */
    wpcf7dtx_taggen.updateOption = (e) => {
        let $this = $(e.currentTarget),
            value = encodeURIComponent(wpcf7dtx_taggen.replaceAll($this.val(), "'", '&#39;'));
        $this.siblings('input[type="hidden"][data-tag-part="option"]').val(value).trigger('change'); // Set the value on the real option and trigger the change event
    };

    // Update form tag preview with encoded value
    $('form.tag-generator-panel input[type="hidden"][data-tag-part="option"]+.dtx-option').on('change keyup click', wpcf7dtx_taggen.updateOption);

})(jQuery);