var $ = jQuery.noConflict(),
    dtx = {
        queue: [],
        init: function() {
            var $inputs = $('input.dtx-pageload[data-dtx-value]');
            if ($inputs.length) {
                // If this is any of our built-in shortcodes, see if there's any that can be duplicated via client side
                $inputs.each(function(i, el) {
                    var $input = $(el),
                        raw_value = $input.attr('data-dtx-value'),
                        v = decodeURIComponent(raw_value).split(' ');
                    if (v.length) {
                        var tag = v[0],
                            atts = {};
                        if (v.length > 1) {
                            for (var x = 1; x < v.length; x++) {
                                var att = v[x].split('=');
                                if (att.length === 2) {
                                    var key = att[0];
                                    atts[key] = att[1].split("'").join('');
                                }
                            }
                        }
                        var value = '';
                        switch (tag) {
                            case 'CF7_GET':
                                value = dtx.get(atts);
                                break;
                            case 'CF7_referrer':
                                value = dtx.referrer(atts);
                                break;
                            case 'CF7_URL':
                                value = dtx.current_url(atts);
                                break;
                            case 'CF7_get_cookie':
                                value = dtx.get_cookie(atts);
                                break;
                            case 'CF7_guid':
                                value = dtx.guid();
                                break;
                            case 'CF7_get_current_var':
                                if (dtx.validKey(atts, 'key') && atts.key == 'url') {
                                    value = dtx.current_url(atts);
                                } else {
                                    return; // Do nothing, current page variables are safe to cache, just use the value that was calculated by server
                                }
                                break;
                            case 'CF7_get_post_var': // Current post variables are safe to cache
                            case 'CF7_get_custom_field': // Meta data is safe to cache
                            case 'CF7_get_taxonomy': // Terms are safe to cache
                            case 'CF7_get_attachment': // Media attachment info is safe to cache
                            case 'CF7_bloginfo': // Site info is safe to cache
                            case 'CF7_get_theme_option': // Theme options are safe to cache
                                return; // Do nothing, just use the value that was calculated by server
                            default:
                                if (tag) {
                                    // Queue the requests for an AJAX call at the end of init
                                    dtx.queue.push({ 'value': raw_value, 'multiline': $input.is('textarea') });
                                }
                                return; // Don't continue after queuing it for AJAX
                        }
                        dtx.set($input, value);
                    }
                });
                if (dtx.queue.length) {
                    setTimeout(function() { // Set timeout to force it async
                        $.ajax({
                            type: 'POST',
                            url: dtx_obj.ajax_url,
                            dataType: 'json', // only accept strict JSON objects
                            data: {
                                'action': 'wpcf7dtx',
                                'shortcodes': dtx.queue
                            },
                            cache: false,
                            error: function(xhr, status, error) {
                                console.error('[CF7 DTX AJAX ERROR]', error, status, xhr);
                            },
                            success: function(data, status, xhr) {
                                if (typeof(data) == 'object' && data.length) {
                                    $.each(data, function(i, obj) {
                                        var $inputs = $('.wpcf7 form input.dtx-pageload[data-dtx-value="' + obj.raw_value + '"]');
                                        if ($inputs.length) {
                                            dtx.set($inputs, obj.value);
                                            $inputs.addClass('dtx-ajax-loaded');
                                        }
                                    });
                                }
                            }
                        });
                    }, 10);
                }
            }
        },
        /**
         * Check if Key Exists in Object
         */
        validKey: function(obj, key) {
            return obj.hasOwnProperty(key) && typeof(obj[key]) == 'string' && obj[key].trim();
        },
        /**
         * Maybe Obfuscate Value
         *
         * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-obfuscate/
         */
        obfuscate: function(value, atts) {
            value = value.trim();
            if (dtx.validKey(atts, 'obfuscate') && atts.obfuscate) {
                var o = '';
                for (var i = 0; i < value.length; i++) {
                    o += '&#' + value.codePointAt(i) + ';';
                }
                return o;
            }
            return value;
        },
        /**
         * Set Value for Form Field
         */
        set: function($input, value) {
            $input.attr('value', value).addClass('dtx-loaded');
        },
        /**
         * Get Value form URL Query by Key
         *
         * @see @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-php-get-variables/
         */
        get: function(atts) {
            if (dtx.validKey(atts, 'key')) {
                var query = window.location.search;
                if (query) {
                    query = new URLSearchParams(query);
                    return dtx.obfuscate(query.get(atts.key).trim(), atts);
                }
            }
            return '';
        },
        /**
         * Get Referrering URL
         *
         * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-referrer-url/
         */
        referrer: function(atts) {
            return dtx.obfuscate(document.referrer, atts);
        },
        /**
         * Get Current URL or Part
         *
         * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-url/
         */
        current_url: function(atts) {
            if (atts.hasOwnProperty('part')) {
                var parts = [
                    'scheme', // e.g. `http`
                    'host',
                    'port',
                    'path',
                    'query', // after the question mark ?
                    'fragment' // after the pound sign #
                ];
                if (parts.includes(atts.part)) {
                    // return part of the url
                    switch (atts.part) {
                        case 'scheme':
                            return dtx.obfuscate(window.location.protocol.replace(':', ''), atts);
                        case 'host':
                            return dtx.obfuscate(window.location.host, atts);
                        case 'port':
                            return dtx.obfuscate(window.location.port, atts);
                        case 'path':
                            return dtx.obfuscate(window.location.pathname, atts);
                        case 'query':
                            return dtx.obfuscate(window.location.search.replace('?', ''), atts);
                        case 'fragment':
                            return dtx.obfuscate(window.location.hash.replace('#', ''), atts);
                        default:
                            break;
                    }
                }
            } else {
                return dtx.obfuscate(window.location.href, atts); // Return the full url
            }
            return '';
        },
        /**
         * Get Cookie Value
         *
         * @since 3.3.0
         *
         * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-cookie/
         */
        get_cookie: function(atts) {
            if (atts.hasOwnProperty('key') && typeof(atts.key) == 'string' && atts.key.trim() != '') {
                var keyValue = document.cookie.match('(^|;) ?' + atts.key.trim() + '=([^;]*)(;|$)');
                return keyValue ? dtx.obfuscate(keyValue[2], atts) : '';
            }
            return '';
        },
        /**
         * Generate a random GUID (globally unique identifier)
         *
         * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-guid/
         */
        guid: function() {
            if (typeof(window.crypto) != 'undefined' && typeof(window.crypto.getRandomValues) != 'undefined') {
                return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
                    (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
                ).toUpperCase();
            }
            console.warn('[CF7 DTX] Cryptographically secure PRNG is not available for generating GUID value');
            var d = new Date().getTime(), //Timestamp
                d2 = ((typeof performance !== 'undefined') && performance.now && (performance.now() * 1000)) || 0; //Time in microseconds since page-load or 0 if unsupported
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16; //random number between 0 and 16
                if (d > 0) { //Use timestamp until depleted
                    r = (d + r) % 16 | 0;
                    d = Math.floor(d / 16);
                } else { //Use microseconds since page-load if supported
                    r = (d2 + r) % 16 | 0;
                    d2 = Math.floor(d2 / 16);
                }
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16).toUpperCase();
            }).toUpperCase();;
        }
    };
$(document).ready(dtx.init);