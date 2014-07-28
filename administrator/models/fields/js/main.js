/*
 * jQuery File Upload Plugin JS Example 8.9.1
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/* global $, window */
var $j = jQuery.noConflict();

function init() {
    'use strict';

    // Initialize the jQuery File Upload widget:
    jQuery('#issue-form').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},

        //url: 'server/php/'
        url: 'http://localhost/joomla3/administrator/components/com_imc/models/fields/server/php/'
    });

    // Enable iframe cross-domain access via redirect option:
    jQuery('#issue-form').fileupload(
        'option',
        'redirect',
        window.location.href.replace(
            /\/[^\/]*$/,
            '/cors/result.html?%s'
        )
    );


    // Load existing files:
/*    if (jQuery('.fileupload-buttonbar').length){
        alert('Found with Length');
    }   else {alert ('not found');}*/

    jQuery('#issue-form').addClass('fileupload-processing');
    jQuery.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: jQuery('#issue-form').fileupload('option', 'url'),
        dataType: 'json',
        context: jQuery('#issue-form')[0]
    }).always(function () {
        jQuery(this).removeClass('fileupload-processing');
    }).done(function (result) {
        jQuery(this).fileupload('option', 'done')
            .call(this, jQuery.Event('done'), {result: result});
    });
    

};
