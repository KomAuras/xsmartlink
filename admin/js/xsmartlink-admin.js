(function ($) {
    'use strict';

    /**
     * Shows spinner on form submit
     * @return {undefined}
     */
    var showSpinner = function () {
        $('form').submit(function () {
            $(this).find('.spinner').addClass('is-active');
        });
    };

    $(document).ready(function () {
        showSpinner();
        $('td.more_posts').hover(function (event) {
            $(this).find('span.more_posts_s').hide();
            $(this).find('span.more_posts_h').show();
        }, function (event) {
            $(this).find('span.more_posts_h').hide();
            $(this).find('span.more_posts_s').show();
        });
    });

})(jQuery);

var $j = jQuery.noConflict();
var records1 = 0;
var current1 = 0;
var records2 = 0;
var current2 = 0;

function xsml_process_relinks_js1() {
    if (records1 === 0) {
        $j.post(ajaxurl, {action: 'process_ajax1', records: records1}, function (response) {
            records1 = response;
            $j('#xlinks_progress').html('');
            $j("#progressbar").show();
            $j('#progressbar .value').width($j('#progressbar').width() / 100 * (current1 / records1 * 100));
            $j("#xl_relink_button").attr("disabled", "disabled");
            $j("#xl_delete404_button").attr("disabled", "disabled");
            window.setTimeout(xsml_process_run_js1, 50);
        });
    }
}

function xsml_process_run_js1() {
    if (current1 <= records1) {
        $j.post(ajaxurl, {action: 'process_ajax1', records: records1, offset: current1}, function (response) {
            current1 = current1 + xsl_per_page;
            if (current1 >= records1) {
                $j("#progressbar").hide();
                $j('#xlinks_progress').html(wma.all_linked);
                records1 = 0;
                current1 = 0;
                $j("#xl_relink_button").removeAttr('disabled');
                $j("#xl_delete404_button").removeAttr('disabled');
            } else {
                $j('#progressbar .value').width($j('#progressbar').width() / 100 * (current1 / records1 * 100));
                window.setTimeout(xsml_process_run_js1, 50);
            }
        });
    }
}

function xsml_process_relinks_js2() {
    if (records2 === 0) {
        $j.post(ajaxurl, {action: 'process_ajax2', records: records2}, function (response) {
            records2 = response;
            $j('#xlinks_progress').html('');
            $j("#progressbar").show();
            $j('#progressbar .value').width($j('#progressbar').width() / 100 * (current2 / records2 * 100));
            $j("#xl_relink_button").attr("disabled", "disabled");
            $j("#xl_delete404_button").attr("disabled", "disabled");
            window.setTimeout(xsml_process_run_js2, 50);
        });
    }
}

function xsml_process_run_js2() {
    if (current2 <= records2) {
        $j.post(ajaxurl, {action: 'process_ajax2', records: records2, offset: current2}, function (response) {
            current2 = current2 + xsl_per_page;
            if (current2 >= records2) {
                $j("#progressbar").hide();
                $j('#xlinks_progress').html(wma.all_links_checked);
                records2 = 0;
                current2 = 0;
                $j("#xl_relink_button").removeAttr('disabled');
                $j("#xl_delete404_button").removeAttr('disabled');
            } else {
                $j('#progressbar .value').width($j('#progressbar').width() / 100 * (current2 / records2 * 100));
                window.setTimeout(xsml_process_run_js2, 50);
            }
        });
    }
}
