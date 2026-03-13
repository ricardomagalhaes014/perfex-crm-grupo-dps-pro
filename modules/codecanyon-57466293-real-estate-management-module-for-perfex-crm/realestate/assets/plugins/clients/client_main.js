var side_bar = $('#side-menu');
var setup_menu = $('#setup-menu-wrapper');
var content_wrapper = $('#wrapper');
var notifications_wrapper = $('li.notifications-wrapper');
var doc_initial_title = document.title;
var
billingAndShippingFields = ['billing_street', 'billing_city', 'billing_state', 'billing_zip', 'billing_country',
    'shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'
    ]
;


(function($) {
    "use strict";
    
    // Predefined global variables
    $("body").on('change', '#mass_select_all', function() {
        var to, rows, checked;
        to = $(this).data('to-table');

        rows = $('.table-' + to).find('tbody tr');
        checked = $(this).prop('checked');
        $.each(rows, function() {
            var input = $($($(this).find('td').eq(0)).find('input'));
            if(!input.is(':disabled')){
                input.prop('checked', checked);
            }
        });
    });
    // Check for active class in sidebar links
    
// On render select remove the placeholder
    $("body").on("rendered.bs.select", "select", function () {
      $(this).parents().removeClass("select-placeholder");
      $(this)
      .parents(".form-group")
      .find(".select-placeholder")
      .removeClass("select-placeholder");
  });

    $("body").on("loaded.bs.select", "select", function () {
      if ($(this).data("toggle") == 1) {
        $(this).selectpicker("toggle");
    }
});

// Init bootstrap selectpicker
    $("body").on("loaded.bs.select", "._select_input_group", function (e) {
      $(this)
      .parents(".form-group")
      .find(".input-group-select .input-group-addon")
      .css("opacity", "1");
  });

})(jQuery);

  // Custom option to show setup menu item only on hover, not applied on mobile
if (app.options.show_setup_menu_item_only_on_hover == 1 && !is_mobile()) {
    side_bar.hover(
      function () {
        setTimeout(function () {
          setup_menu_item.css("display", "block");
      }, 200);
    },
    function () {
        setTimeout(function () {
          setup_menu_item.css("display", "none");
      }, 1000);
    }
    );
}

    // Check for active class in sidebar links
var $linkSidebarActive = side_bar.find('li > a[href="' + location + '"]');
if ($linkSidebarActive.length) {
    $linkSidebarActive.parents("li").not(".quick-links").addClass("active");
    // Set aria expanded to true
    $linkSidebarActive.prop("aria-expanded", true);
    $linkSidebarActive
    .parents("ul.nav-second-level")
    .prop("aria-expanded", true);
    $linkSidebarActive
    .parents("li")
    .find("a:first-child")
    .prop("aria-expanded", true);
}



// Handle minimalize sidebar menu
$(".hide-menu").on("click", function (e) {
    e.preventDefault();
    if ($("body").hasClass("hide-sidebar")) {
      $("body").removeClass("hide-sidebar").addClass("show-sidebar");
  } else {
      $("body").removeClass("show-sidebar").addClass("hide-sidebar");
  }
    // $("body").toggleClass(
    //   $(window).width() < 769 ? "show-sidebar" : "hide-sidebar"
    // );
  if (setup_menu.hasClass("display-block")) {
      $(".close-customizer").click();
  }
});

  // Hide sidebar on content click on mobile
if (is_mobile()) {
    content_wrapper.on("click", function () {
      if ($("body").hasClass("show-sidebar")) {
        $(".hide-menu").click();
    }
    if (setup_menu.hasClass("display-block")) {
        $(".close-customizer").click();
    }
});
}

// Check for desktop notifications permissions
if (("Notification" in window) && app.options.desktop_notifications == '1') {
    Notification.requestPermission();
}

// General helper function for $.get ajax requests
function requestGet(uri, params) {
    "use strict";
    
    params = typeof(params) == 'undefined' ? {} : params;
    var options = {
        type: 'GET',
        url: uri.indexOf(site_url) > -1 ? uri : site_url + uri
    };
    return $.ajax($.extend({}, options, params));
}




// Check if field is empty
function empty(data) {
    "use strict";
    if (typeof(data) == 'number' || typeof(data) == 'boolean') {
        return false;
    }
    if (typeof(data) == 'undefined' || data === null) {
        return true;
    }
    if (typeof(data.length) != 'undefined') {
        return data.length === 0;
    }
    var count = 0;
    for (var i in data) {
        if (data.hasOwnProperty(i)) {
            count++;
        }
    }
    return count === 0;
}

// Returns datatbles export button array based on settings
// Admin area only
function get_datatable_buttons(table) {
    // pdfmake arabic fonts support
    "use strict";
    var formatExport = {
        body: function(data, row, column, node) {

            // Fix for notes inline datatables
            // Causing issues because of the hidden textarea for edit and the content is duplicating
            // This logic may be extended in future for other similar fixes
            var newTmpRow = $('<div></div>', data);
            newTmpRow.append(data);

            if (newTmpRow.find('[data-note-edit-textarea]').length > 0) {
                newTmpRow.find('[data-note-edit-textarea]').remove();
                data = newTmpRow.html().trim();
            }
            // Convert e.q. two months ago to actual date
            var exportTextHasActionDate = newTmpRow.find('.text-has-action.is-date');

            if(exportTextHasActionDate.length) {
             data = exportTextHasActionDate.attr('data-title');
         }

         if (newTmpRow.find('.row-options').length > 0) {
            newTmpRow.find('.row-options').remove();
            data = newTmpRow.html().trim();
        }

        if (newTmpRow.find('.table-export-exclude').length > 0) {
            newTmpRow.find('.table-export-exclude').remove();
            data = newTmpRow.html().trim();
        }

        if (data) {

        }

            // Datatables use the same implementation to strip the html.
        var div = document.createElement("div");
        div.innerHTML = data;
        var text = div.textContent || div.innerText || "";

        return text.trim();
    }
};
var table_buttons_options = [];

if (typeof(table_export_button_is_hidden) != 'function' || !table_export_button_is_hidden()) {
    table_buttons_options.push({
        extend: 'collection',
        text: app.lang.dt_button_export,
        className: 'btn btn-default-dt-options',
        buttons: [{
            extend: 'excel',
            text: app.lang.dt_button_excel,
            footer: true,
            exportOptions: {
                columns: [':not(.not-export)'],
                rows: function(index) {
                    return _dt_maybe_export_only_selected_rows(index, table);
                },
                format: formatExport,
            },
        }, {
            extend: 'csvHtml5',
            text: app.lang.dt_button_csv,
            footer: true,
            exportOptions: {
                columns: [':not(.not-export)'],
                rows: function(index) {
                    return _dt_maybe_export_only_selected_rows(index, table);
                },
                format: formatExport,
            }
        }, {
            extend: 'pdfHtml5',
            text: app.lang.dt_button_pdf,
            footer: true,
            exportOptions: {
                columns: [':not(.not-export)'],
                rows: function(index) {
                    return _dt_maybe_export_only_selected_rows(index, table);
                },
                format: formatExport,
            },
            orientation: 'landscape',
            customize: function(doc) {
                    // Fix for column widths
                var table_api = $(table).DataTable();
                var columns = table_api.columns().visible();
                var columns_total = columns.length;
                var total_visible_columns = 0;

                for (i = 0; i < columns_total; i++) {
                        // Is only visible column
                    if (columns[i] == true) {
                        total_visible_columns++;
                    }
                }

                setTimeout(function() {
                    if (total_visible_columns <= 5) {
                        var pdf_widths = [];
                        for (i = 0; i < total_visible_columns; i++) {
                            pdf_widths.push((735 / total_visible_columns));
                        }

                        doc.content[1].table.widths = pdf_widths;
                    }
                }, 10);

                if (app.user_language.toLowerCase() == 'persian' || app.user_language.toLowerCase() == 'arabic') {
                    doc.defaultStyle.font = Object.keys(pdfMake.fonts)[0];
                }

                doc.styles.tableHeader.alignment = 'left';
                doc.defaultStyle.fontSize = 10;

                doc.styles.tableHeader.fontSize = 10;
                doc.styles.tableHeader.margin = [3, 3, 3, 3];

                doc.styles.tableFooter.fontSize = 10;
                doc.styles.tableFooter.margin = [3, 0, 0, 0];

                doc.pageMargins = [2, 20, 2, 20];
            }
        }, {
            extend: 'print',
            text: app.lang.dt_button_print,
            footer: true,
            exportOptions: {
                columns: [':not(.not-export)'],
                rows: function(index) {
                    return _dt_maybe_export_only_selected_rows(index, table);
                },
                format: formatExport,
            }
        }],
    });
}
var tableButtons = $("body").find('.table-btn');

$.each(tableButtons, function() {
    var b = $(this);
    if (b.length && b.attr('data-table')) {
        if ($(table).is(b.attr('data-table'))) {
            table_buttons_options.push({
                text: b.text().trim(),
                className: 'btn btn-default-dt-options',
                action: function(e, dt, node, config) {
                    b.click();
                }
            });
        }
    }
});

if (!$(table).hasClass('dt-inline')) {
    table_buttons_options.push({
        text: '<i class="fa fa-refresh"></i>',
        className: 'btn btn-default-dt-options btn-dt-reload',
        action: function(e, dt, node, config) {
            dt.ajax.reload();
        }
    });
}


return table_buttons_options;
}

// Fix for height on the wrapper
function mainWrapperHeightFix() {
    "use strict";
    // Get and set current height
    var headerH = 63;
    var navigationH = side_bar.height();
    var contentH = $("#wrapper").find('.content').height();
    setup_menu.css('min-height', ($(document).outerHeight(true) - (headerH * 2)) + 'px');

    content_wrapper.css('min-height', $(document).outerHeight(true) - headerH + 'px');
    // Set new height when content height is less then navigation
    if (contentH < navigationH) {
        content_wrapper.css("min-height", navigationH + 'px');
    }

    // Set new height when content height is less then navigation and navigation is less then window
    if (contentH < navigationH && navigationH < $(window).height()) {
        content_wrapper.css("min-height", $(window).height() - headerH + 'px');
    }
    // Set new height when content is higher then navigation but less then window
    if (contentH > navigationH && contentH < $(window).height()) {
        content_wrapper.css("min-height", $(window).height() - headerH + 'px');
    }
    // Fix for RTL main admin menu height
    if (is_mobile() && isRTL == 'true') {
        side_bar.css('min-height', $(document).outerHeight(true) - headerH + 'px');
    }
}

function init_selectpicker() {
    "use strict";
    appSelectPicker();
}

function appSelectPicker(element) {
    "use strict";
    if (typeof(element) == 'undefined') {
        element = $("body").find('select.selectpicker');
    }

    if (element.length) {
        element.selectpicker({
            showSubtext: true
        });
    }
}

function requestGetJSON(uri, params) {
    "use strict";
    params = typeof (params) == 'undefined' ? {} : params;
    params.dataType = 'json';
    return requestGet(uri, params);
}

// Format money function
function format_money(total, excludeSymbol) {
    "use strict";
    if (typeof (excludeSymbol) != 'undefined' && excludeSymbol) {
        return accounting.formatMoney(total, {
            symbol: ''
        });
    }

    return accounting.formatMoney(total);
}

function hidden_input(name, val) {
    "use strict";
    return '<input type="hidden" name="' + name + '" value="' + val + '">';
}

function slugify(string) {
    "use strict";
    return string
    .toString()
    .trim()
    .toLowerCase()
    .replace(/\s+/g, "-")
    .replace(/[^\w\-]+/g, "")
    .replace(/\-\-+/g, "-")
    .replace(/^-+/, "")
    .replace(/-+$/, "");
}

function _table_jump_to_page(table, oSettings) {
    "use strict";
    var paginationData = table.DataTable().page.info();
    var previousDtPageJump = $("body").find('#dt-page-jump-' + oSettings.sTableId);

    if (previousDtPageJump.length) {
        previousDtPageJump.remove();
    }

    if (paginationData.pages > 1) {

        var jumpToPageSelect = $("<select></select>", {
            "data-id": oSettings.sTableId,
            "class": "dt-page-jump-select form-control",
            'id': 'dt-page-jump-' + oSettings.sTableId
        });

        var paginationHtml = '';

        for (var i = 1; i <= paginationData.pages; i++) {
            var selectedCurrentPage = ((paginationData.page + 1) === i) ? 'selected' : '';
            paginationHtml += "<option value='" + i + "'" + selectedCurrentPage + ">" + i + "</option>";
        }

        if (paginationHtml != '') {
            jumpToPageSelect.append(paginationHtml);
        }

        $("#" + oSettings.sTableId + "_wrapper .dt-page-jump").append(jumpToPageSelect);
    }
}

function is_mobile() {
    "use strict";
    if (typeof(app) != 'undefined' && typeof(app.is_mobile) != 'undefined') {
        return app.is_mobile;
    }

    try { document.createEvent("TouchEvent"); return true; } catch (e) { return false; }
}
function init_datepicker(element_date, element_time) {
    appDatepicker({
        element_date: element_date,
        element_time: element_time,
    });
}

// Fetches notifications
function candidate_fetch_notifications(callback) {
    requestGetJSON('realestate/broker/notifications_check').done(function (response) {
        var nw = notifications_wrapper;
        nw.html(response.html);
        var total = nw.find('ul.notifications').attr('data-total-unread');
        document.title = total > 0 ? ('(' + total + ') ' + doc_initial_title) : doc_initial_title;
        var nIds = response.notificationsIds;
        if (app.browser == 'firefox' && nIds.length > 1) {
            var lastNotification = nIds[0];
            nIds = [];
            nIds.push(lastNotification);
        }
        setTimeout(function () {
            if (nIds.length > 0) {
                $.each(nIds, function (i, notId) {
                    var nSelector = 'li[data-notification-id="' + notId + '"]';
                    var $not = nw.find(nSelector);
                    $.notify("", {
                        'title': app.lang.new_notification,
                        'body': $not.find('.notification-title').text(),
                        'requireInteraction': true,
                        'icon': $not.find('.notification-image').attr('src'),
                        'tag': notId,
                        'closeTime': app.options.dismiss_desktop_not_after != "0" ? app.options.dismiss_desktop_not_after * 1000 : null
                    }).close(function () {
                        requestGet('realestate/broker/set_desktop_notification_read/' + notId).done(function (response) {
                            var $totalIndicator = nw.find('.icon-total-indicator');
                            nw.find('li[data-notification-id="' + notId + '"] .notification-box').removeClass('unread');
                            var currentTotalNotifications = $totalIndicator.text();
                            currentTotalNotifications = currentTotalNotifications.trim();
                            currentTotalNotifications = (currentTotalNotifications - 1);
                            if (currentTotalNotifications > 0) {
                                document.title = '(' + currentTotalNotifications + ') ' + doc_initial_title;
                                $totalIndicator.html(currentTotalNotifications);
                            } else {
                                document.title = doc_initial_title;
                                $totalIndicator.addClass('hide');
                            }
                        });
                    }).on("click", function (e) {
                        parent.focus();
                        window.focus();
                        setTimeout(function () {
                            nw.find(nSelector + ' .notification-link').addClass('desktopClick').click();
                            e.target.close();
                        }, 70);
                    });
                });
            }
        }, 10);
    });
}

// Notification profile link click
$("body").on('click', '.notification_link', function () {
    var link = $(this).data('link');
    var not_href;
    not_href = link.split('#');
    if (!not_href[1]) {
        window.location.href = link;
    }
});

/* Custom notifications links, NOTE: touchstart listener is for iOS davices */
$("body").on('click' + ('ontouchstart' in window ? ' touchstart' : ''),
    '.notifications a.notification-top, .notification_link',
    function (e) {
        e.preventDefault();
        var $notLink = $(this);
        var not_href_id;

        var not_href = $notLink.hasClass('notification_link') ? $notLink.data('link') : e.currentTarget.href;

        var not_href_array = not_href.split('#');
        var notRedirect = true;
        if (not_href_array[1] && not_href_array[1].indexOf('=') > -1) {
            notRedirect = false;
            not_href_id = not_href_array[1].split('=')[1];
            if (not_href_array[1].indexOf('postid') > -1) {
                postid = not_href_id;
                if ($(window).width() > 769) {
                    $('.open_newsfeed.desktop').click();
                } else {
                    $('.open_newsfeed.mobile').click();
                }
            } else if (not_href_array[1].indexOf('taskid') > -1) {

                var comment_id = undefined;
                if (not_href.indexOf('#comment_') > -1) {
                    var task_comment_id = not_href.split('#comment_');
                    comment_id = task_comment_id[task_comment_id.length - 1];
                }
                init_task_modal(not_href_id, comment_id);
            } else if (not_href_array[1].indexOf('leadid') > -1) {
                init_lead(not_href_id);
            } else if (not_href_array[1].indexOf('eventid') > -1) {
                view_event(not_href_id);
            }
        }
        if (!$notLink.hasClass('desktopClick')) {
            $notLink.parent('li').find('.not-mark-as-read-inline').click();
        }
        if (notRedirect) {
            setTimeout(function () {
                window.location.href = not_href_array;
            }, 50);
        }
    });

    // Set notifications to read when notifictions dropdown is opened
$('.notifications-wrapper').on('show.bs.dropdown', function () {
    var total = notifications_wrapper.find('.notifications').attr('data-total-unread');
    if (total > 0) {
        var data ={};
        data.csrf_token_name = $('input[name="csrf_token_name"]').val();

        $.post(site_url + 'realestate/broker/set_notifications_read', data).done(function (response) {
            response = JSON.parse(response);
            if (response.success === true || response.success == 'true') {
                document.title = doc_initial_title;
                $(".icon-notifications").addClass('hide');
            }
        });
    }
});

    // Set single notification as read INLINE
function set_notification_read_inline(id) {
    requestGet('realestate/broker/set_notification_read_inline/' + id).done(function () {
        var notification = $("body").find('.notification-wrapper[data-notification-id="' + id + '"]');
        notification.find('.notification-box,.notification-box-all').removeClass('unread');
        notification.find('.not-mark-as-read-inline').tooltip('destroy').remove();
    });
}

// Marks all notifications as read INLINE
function mark_all_notifications_as_read_inline() {
    requestGet('realestate/broker/mark_all_notifications_as_read_inline/').done(function () {
        var notification = $("body").find('.notification-wrapper');
        notification.find('.notification-box,.notification-box-all').removeClass('unread');
        notification.find('.not-mark-as-read-inline').tooltip('destroy').remove();
    });
}


// Main logout function check if timers found to show the warning
function logout() {
  var started_timers = $(".started-timers-top").find("li.timer").length;
  if (started_timers > 0) {
    var warning = $("#timers-logout-template-warning").html();
    var $p = system_popup({
      message: " ",
      content: warning,
  });
    $p.find(".popup-message").addClass("hide");
    return false;
} else {
    // No timer logout free
    window.location.href = site_url + "realestate/authentication_broker/logout";
}
}

function real_init_ajax_search(type, selector, server_data, url) {
  var ajaxSelector = $("body").find(selector);

  if (ajaxSelector.length) {
    var options = {
      ajax: {
        url:
          typeof url == "undefined"
            ? site_url + "realestate/client/get_relation_data"
            : url,
        data: function () {
          var data = {};
          data.type = type;
          data.rel_id = "";
          data.q = "{{{q}}}";
          if (typeof server_data != "undefined") {
            jQuery.extend(data, server_data);
          }
          return data;
        },
      },
      locale: {
        emptyTitle: app.lang.search_ajax_empty,
        statusInitialized: app.lang.search_ajax_initialized,
        statusSearching: app.lang.search_ajax_searching,
        statusNoResults: app.lang.not_results_found,
        searchPlaceholder: app.lang.search_ajax_placeholder,
        currentlySelected: app.lang.currently_selected,
      },
      requestDelay: 500,
      cache: false,
      preprocessData: function (processData) {
        var bs_data = [];
        var len = processData.length;
        for (var i = 0; i < len; i++) {
          var tmp_data = {
            value: processData[i].id,
            text: processData[i].name,
          };
          if (processData[i].subtext) {
            tmp_data.data = {
              subtext: processData[i].subtext,
            };
          }
          bs_data.push(tmp_data);
        }
        return bs_data;
      },
      preserveSelectedPosition: "after",
      preserveSelected: true,
    };
    if (ajaxSelector.data("empty-title")) {
      options.locale.emptyTitle = ajaxSelector.data("empty-title");
    }
    ajaxSelector.selectpicker().ajaxSelectPicker(options);
  }
}

// Clear billing and shipping inputs for invoice,estimate etc...
function clear_billing_and_shipping_details() {
  for (var f in billingAndShippingFields) {
    if (billingAndShippingFields[f].indexOf("country") > -1) {
      $('select[name="' + billingAndShippingFields[f] + '"]').selectpicker(
        "val",
        ""
      );
    } else {
      $('input[name="' + billingAndShippingFields[f] + '"]').val("");
      $('textarea[name="' + billingAndShippingFields[f] + '"]').val("");
    }
    if (billingAndShippingFields[f] == "billing_country") {
      $('input[name="include_shipping"]').prop("checked", false);
      $('input[name="include_shipping"]').change();
    }
  }

  init_billing_and_shipping_details();
}

// Init billing and shipping details for invoice, estimate etc...
function init_billing_and_shipping_details() {
  var _f;
  var include_shipping = $('input[name="include_shipping"]').prop("checked");

  for (var f in billingAndShippingFields) {
    _f = "";
    if (billingAndShippingFields[f].indexOf("country") > -1) {
      _f = $("#" + billingAndShippingFields[f] + " option:selected").data(
        "subtext"
      );
    } else if (
      billingAndShippingFields[f].indexOf("shipping_street") > -1 ||
      billingAndShippingFields[f].indexOf("billing_street") > -1
    ) {
      if ($('textarea[name="' + billingAndShippingFields[f] + '"]').length) {
        _f = $('textarea[name="' + billingAndShippingFields[f] + '"]')
          .val()
          .replace(/(?:\r\n|\r|\n)/g, "<br />");
      }
    } else {
      _f = $('input[name="' + billingAndShippingFields[f] + '"]').val();
    }
    if (billingAndShippingFields[f].indexOf("shipping") > -1) {
      if (!include_shipping) {
        _f = "";
      }
    }
    if (typeof _f == "undefined") {
      _f = "";
    }
    _f = _f !== "" ? _f : "--";
    $("." + billingAndShippingFields[f]).html(_f);
  }
  $("#billing_and_shipping_details").modal("hide");
}

// Set the currency for accounting
function init_currency(id, callback) {
  var $accountingTemplate = $("body").find(".accounting-template");

  if ($accountingTemplate.length || id) {
    var selectedCurrencyId = !id
      ? $accountingTemplate.find('select[name="currency"]').val()
      : id;

    requestGetJSON("realestate/client/get_currency/" + selectedCurrencyId).done(function (
      currency
    ) {
      // Used for formatting money
      accounting.settings.currency.decimal = currency.decimal_separator;
      accounting.settings.currency.thousand = currency.thousand_separator;
      accounting.settings.currency.symbol = currency.symbol;
      accounting.settings.currency.format =
        currency.placement == "after" ? "%v %s" : "%s%v";
      calculate_total();

      if (callback) {
        callback();
      }
    });
  }
}