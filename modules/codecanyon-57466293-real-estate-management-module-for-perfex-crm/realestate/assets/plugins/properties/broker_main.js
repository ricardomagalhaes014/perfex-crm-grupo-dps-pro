var side_bar = $('#side-menu');
var setup_menu = $('#setup-menu-wrapper');
var content_wrapper = $('#wrapper');
var notifications_wrapper = $('li.notifications-wrapper');
var doc_initial_title = document.title;
var lastAddedItemKey = null;


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

      // Init the billing and shipping details in the field - estimates and invoices
    $("body").on("click", ".save-shipping-billing", function (e) {
        init_billing_and_shipping_details();
    });

      // When customer_id is passed init the data
    if ($("body").find('input[name="isedit"]').length === 0) {
        $('.f_client_id select[name="clientid"]').change();
    }


    $("body").on("submit", "._transaction_form", function () {
    // On submit re-calculate total and reorder the items for all cases.
    calculate_total();

    $("body").find("#items-warning").remove();
    var $itemsTable = $(this).find("table.items");
    var $previewItem = $itemsTable.find(".main");

    if (
      $previewItem.find('[name="description"]').length &&
      $previewItem.find('[name="description"]').val().trim().length > 0 &&
      $previewItem.find('[name="rate"]').val().trim().length > 0
    ) {
      $itemsTable.before(
        '<div class="alert alert-warning mbot20" id="items-warning">' +
          app.lang.item_forgotten_in_preview +
          '<i class="fa fa-angle-double-down pointer pull-right fa-2x mtop5" onclick="add_item_to_table(\'undefined\',\'undefined\',undefined); return false;"></i></div>'
      );

      $("html,body").animate({
        scrollTop: $("#items-warning").offset().top,
      });

      return false;
    } else {
      if ($itemsTable.length && $itemsTable.find(".item").length === 0) {
        $itemsTable.before(
          '<div class="alert alert-warning mbot20" id="items-warning">' +
            app.lang.no_items_warning +
            "</div>"
        );
        $("html,body").animate({
          scrollTop: $("#items-warning").offset().top,
        });
        return false;
      }
    }

    reorder_items();

    // Remove the disabled attribute from the disabled fields becuase if they are disabled won't be sent with the request.
    $('select[name="currency"]').prop("disabled", false);
    $('select[name="project_id"]').prop("disabled", false);
    $('input[name="date"]').prop("disabled", false);

    // Add disabled to submit buttons
    $(this).find(".transaction-submit").prop("disabled", true);

    return true;
  });

  $("body").on("click", ".transaction-submit", function () {
    var that = $(this);
    var form = that.parents("form._transaction_form");
    if (form.valid()) {
      if (that.hasClass("save-as-draft")) {
        form.append(hidden_input("save_as_draft", "true"));
      } else if (that.hasClass("save-and-send")) {
        form.append(hidden_input("save_and_send", "true"));
      } else if (that.hasClass("save-and-record-payment")) {
        form.append(hidden_input("save_and_record_payment", "true"));
      } else if (that.hasClass("save-and-send-later")) {
        form.append(hidden_input("save_and_send_later", "true"));
      }
    }
    form.submit();
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

    // Init now metisMenu for the main admin sidebar
  side_bar.metisMenu();

   // Init tinymce editors
  init_editor();
  // Init the media elfinder for tinymce browser
  function elFinderBrowser(callback, value, meta) {
      tinymce.activeEditor.windowManager.elfinderCallback = callback
      
      tinymce.activeEditor.windowManager.openUrl({
          url: admin_url + "misc/tinymce_file_browser",
          title: app.lang.media_files,
          width: 900,
          height: 450,
      });
      
      return false;
  }

// Function to init the tinymce editor
  function init_editor(selector, settings) {
      selector = typeof selector == "undefined" ? ".tinymce" : selector;
      var _editor_selector_check = $(selector);

      if (_editor_selector_check.length === 0) {
        return;
    }

    $.each(_editor_selector_check, function () {
        if ($(this).hasClass("tinymce-manual")) {
          $(this).removeClass("tinymce");
      }
  });

  // Original settings
    var _settings = {
        branding: false,
        promotion: false,
        selector: selector,
        browser_spellcheck: true,
        cache_suffix: '?v='+app.version,
        height: 250,
        min_height: 250,
        theme: "silver",
        paste_block_drop: true,
        language: app.tinymce_lang || 'en',
        relative_urls: false,
        entity_encoding: "raw",
        autoresize_bottom_margin: 25,
        valid_elements: "+*[*]",
        valid_children: "+body[style], +style[type]",
        remove_script_host: false,
        removed_menuitems: "newdocument restoredraft",
        forced_root_block: "p",
        autosave_restore_when_empty: false,
        font_size_formats: "8pt 10pt 12pt 14pt 18pt 24pt 36pt",
        table_default_styles: {
          width: "100%",
      },
      plugins: [
          "advlist", "autoresize", "autosave", "lists", "link", "image", "codesample",
          "visualblocks", "code", "fullscreen",
          "media", "save", "table",
          ],
      toolbar: "fontfamily fontsize | forecolor backcolor | bold italic | alignleft aligncenter alignright alignjustify | image link | bullist numlist | restoredraft",
      contextmenu: "link image | paste copy",
      file_picker_callback : elFinderBrowser,
      setup: function (ed) {
      // Default fontsize is 12
          ed.on("init", function () {
            this.getDoc().body.style.fontSize = "12pt";
        });
      },
  };

  // Add the rtl to the settings if is true
  if(isRTL == "true") {
    _settings.directionality = "rtl"
    _settings.plugins.push('directionality')
}

  // Possible settings passed to be overwrited or added
if (typeof settings != "undefined") {
    for (var key in settings) {
      if (key != "append_plugins") {
        _settings[key] = settings[key];
    } else {
        _settings.plugins.push(settings[key]);
    }
}
}

  // Init the editor
var editor = tinymce.init(_settings);

$(document).trigger("app.editor.initialized");

return editor;
}

// Handle minimalize sidebar menu
  $(".hide-menu").on("click", function (e) {
    e.preventDefault();
    if ($("body").hasClass("hide-sidebar")) {
      $("body").removeClass("hide-sidebar").addClass("show-sidebar");
    } else {
      $("body").removeClass("show-sidebar").addClass("hide-sidebar");
    }
    
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

// Bootstrap switch active or inactive global function
$("body").on("change", ".onoffswitch input", function (event, state) {
    var switch_url = $(this).data("switch-url");
    if (!switch_url) {
      return;
  }
  switch_field(this);
});


  // Switch field make request
function switch_field(field) {
  var status, url, id;
  status = 0;
  if ($(field).prop("checked") === true) {
    status = 1;
}
url = $(field).data("switch-url");
id = $(field).data("id");
requestGet(url + "/" + id + "/" + status);
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


// General function for all datatables serverside
function initDataTable(selector, url, notsearchable, notsortable, fnserverparams, defaultorder) {
    "use strict";
    var table = typeof (selector) == 'string' ? $("body").find('table' + selector) : selector;

    if (table.length === 0) {
        return false;
    }

    fnserverparams = (fnserverparams == 'undefined' || typeof (fnserverparams) == 'undefined') ? [] : fnserverparams;

    // If not order is passed order by the first column
    if (typeof (defaultorder) == 'undefined') {
        defaultorder = [
            [0, 'asc']
        ];
    } else {
        if (defaultorder.length === 1) {
            defaultorder = [defaultorder];
        }
    }

    var user_table_default_order = table.attr('data-default-order');

    if (!empty(user_table_default_order)) {
        var tmp_new_default_order = JSON.parse(user_table_default_order);
        var new_defaultorder = [];
        for (var i in tmp_new_default_order) {
            // If the order index do not exists will throw errors
            if (table.find('thead th:eq(' + tmp_new_default_order[i][0] + ')').length > 0) {
                new_defaultorder.push(tmp_new_default_order[i]);
            }
        }
        if (new_defaultorder.length > 0) {
            defaultorder = new_defaultorder;
        }
    }

    var length_options = [10, 25, 50, 100];
    var length_options_names = [10, 25, 50, 100];

    app.options.tables_pagination_limit = parseFloat(app.options.tables_pagination_limit);

    if ($.inArray(app.options.tables_pagination_limit, length_options) == -1) {
        length_options.push(app.options.tables_pagination_limit);
        length_options_names.push(app.options.tables_pagination_limit);
    }

    length_options.sort(function (a, b) {
        return a - b;
    });
    length_options_names.sort(function (a, b) {
        return a - b;
    });

    length_options.push(-1);
    length_options_names.push(app.lang.dt_length_menu_all);

    var dtSettings = {
        "language": app.lang.datatables,
        "processing": true,
        "retrieve": true,
        "serverSide": true,
        'paginate': true,
        'searchDelay': 750,
        "bDeferRender": true,
        "autoWidth": false,
        dom: "<'row'><'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i>><'row'<'#colvis'><'.dt-page-jump'>p>",
        "pageLength": app.options.tables_pagination_limit,
        "lengthMenu": [length_options, length_options_names],
        "columnDefs": [{
            "searchable": false,
            "targets": notsearchable,
        }, {
            "sortable": false,
            "targets": notsortable
        }],
        "fnDrawCallback": function (oSettings) {
            _table_jump_to_page(this, oSettings);
            if (oSettings.aoData.length === 0) {
                $(oSettings.nTableWrapper).addClass('app_dt_empty');
            } else {
                $(oSettings.nTableWrapper).removeClass('app_dt_empty');
            }
        },
        "fnCreatedRow": function (nRow, aData, iDataIndex) {
            // If tooltips found
            $(nRow).attr('data-title', aData.Data_Title);
            $(nRow).attr('data-toggle', aData.Data_Toggle);
        },
        "initComplete": function (settings, json) {
            var t = this;
            var $btnReload = $('.btn-dt-reload');
            $btnReload.attr('data-toggle', 'tooltip');
            $btnReload.attr('title', app.lang.dt_button_reload);

            var $btnColVis = $('.dt-column-visibility');
            $btnColVis.attr('data-toggle', 'tooltip');
            $btnColVis.attr('title', app.lang.dt_button_column_visibility);

            t.wrap('<div class="table-responsive"></div>');

            var dtEmpty = t.find('.dataTables_empty');
            if (dtEmpty.length) {
                dtEmpty.attr('colspan', t.find('thead th').length);
            }

            // Hide mass selection because causing issue on small devices
            if (is_mobile() && $(window).width() < 400 && t.find('tbody td:first-child input[type="checkbox"]').length > 0) {
                t.DataTable().column(0).visible(false, false).columns.adjust();
                $("a[data-target*='bulk_actions']").addClass('hide');
            }

            t.parents('.table-loading').removeClass('table-loading');
            t.removeClass('dt-table-loading');
            var th_last_child = t.find('thead th:last-child');
            var th_first_child = t.find('thead th:first-child');
            if (th_last_child.text().trim() == app.lang.options) {
                th_last_child.addClass('not-export');
            }
            if (th_first_child.find('input[type="checkbox"]').length > 0) {
                th_first_child.addClass('not-export');
            }
            mainWrapperHeightFix();
        },
        "order": defaultorder,
        "ajax": {
            "url": url,
            "type": "POST",
            "data": function (d) {
                if (typeof (csrfData) !== 'undefined') {
                    d[csrfData['token_name']] = csrfData['hash'];
                }
                for (var key in fnserverparams) {
                    d[key] = $(fnserverparams[key]).val();
                }
                if (table.attr('data-last-order-identifier')) {
                    d['last_order_identifier'] = table.attr('data-last-order-identifier');
                }
            }
        },
        buttons: get_datatable_buttons(table),
    };

    table = table.dataTable(dtSettings);
    var tableApi = table.DataTable();

    var hiddenHeadings = table.find('th.not_visible');
    var hiddenIndexes = [];

    $.each(hiddenHeadings, function () {
        hiddenIndexes.push(this.cellIndex);
    });

    setTimeout(function () {
        for (var i in hiddenIndexes) {
            tableApi.columns(hiddenIndexes[i]).visible(false, false).columns.adjust();
        }
    }, 10);

    if (table.hasClass('customizable-table')) {
        var tableToggleAbleHeadings = table.find('th.toggleable');
        var invisible = $('#hidden-columns-' + table.attr('id'));
        try {
            invisible = JSON.parse(invisible.text());
        } catch (err) {
            invisible = [];
        }

        $.each(tableToggleAbleHeadings, function () {
            var cID = $(this).attr('id');
            if ($.inArray(cID, invisible) > -1) {
                tableApi.column('#' + cID).visible(false);
            }
        });

    }

    // Fix for hidden tables colspan not correct if the table is empty
    if (table.is(':hidden')) {
        table.find('.dataTables_empty').attr('colspan', table.find('thead th').length);
    }

    table.on('preXhr.dt', function (e, settings, data) {
        if (settings.jqXHR) settings.jqXHR.abort();
    });

    return tableApi;
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

// Init color pickers
function init_color_pickers() {
  appColorPicker();
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

function appValidateForm(form, form_rules, submithandler, overwriteMessages) {
  $(form).appFormValidator({
    rules: form_rules,
    onSubmit: submithandler,
    messages: overwriteMessages,
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

function load_small_table_item(id, selector, input_name, url, table) {
  var _tmpID = $('input[name="' + input_name + '"]').val();
  // Check if id passed from url, hash is prioritized becuase is last
  if (_tmpID !== "" && !window.location.hash) {
    id = _tmpID;
    // Clear the current id value in case user click on the left sidebar credit_note_ids
    $('input[name="' + input_name + '"]').val("");
  } else {
    // check first if hash exists and not id is passed, becuase id is prioritized
    if (window.location.hash && !id) {
      id = window.location.hash.substring(1); //Puts hash in variable, and removes the # character
    }
  }
  if (typeof id == "undefined" || id === "") {
    return;
  }
  // destroy_dynamic_scripts_in_element($(selector));
  if (!$("body").hasClass("small-table")) {
    toggle_small_view(table, selector);
  }
  $('input[name="' + input_name + '"]').val(id);
  do_hash_helper(id);
  $(selector).load(url + "/" + id)

  $("html, body").animate(
    {
      scrollTop: $(selector).offset().top + (is_mobile() ? 150 : 0),
    },
    600
  );
}

function real_init_ajax_search(type, selector, server_data, url) {
  var ajaxSelector = $("body").find(selector);

  if (ajaxSelector.length) {
    var options = {
      ajax: {
        url:
          typeof url == "undefined"
            ? page_url + "get_relation_data"
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

    requestGetJSON("realestate/broker/get_currency/" + selectedCurrencyId).done(function (
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

// Make items sortable with jquery sort plugin
function init_items_sortable(preview_table) {
  var _items_sortable = $("#wrapper").find(".items tbody");

  if (_items_sortable.length === 0) {
    return;
  }
  _items_sortable.sortable({
    helper: fixHelperTableHelperSortable,
    handle: ".dragger",
    placeholder: "ui-placeholder",
    itemPath: "> tbody",
    itemSelector: "tr.sortable",
    items: "tr.sortable",
    update: function () {
      if (typeof preview_table == "undefined") {
        reorder_items();
      } else {
        // If passed from the admin preview there is other function for re-ordering
        save_ei_items_order();
      }
    },
    sort: function (event, ui) {
      // Firefox fixer when dragging
      var $target = $(event.target);
      if (!/html|body/i.test($target.offsetParent()[0].tagName)) {
        var top =
          event.pageY -
          $target.offsetParent().offset().top -
          ui.helper.outerHeight(true) / 2;
        ui.helper.css({
          top: top + "px",
        });
      }
    },
  });
}

// Show/hide full table
function toggle_small_view(table, main_data) {
  if (
    !is_mobile() &&
    $("#small-table").hasClass("hide") &&
    $(".small-table-right-col").hasClass("col-md-12")
  ) {
    $("#small-table").toggleClass("hide");
    $(".small-table-right-col").toggleClass("col-md-12 col-md-7");
    $(window).trigger("resize");
    return;
  }
  $("body").toggleClass("small-table");
  var tablewrap = $("#small-table");
  if (tablewrap.length === 0) {
    return;
  }
  var _visible = false;
  if (tablewrap.hasClass("col-md-5")) {
    tablewrap.removeClass("col-md-5").addClass("col-md-12");
    _visible = true;
    $(".toggle-small-view")
      .find("i")
      .removeClass("fa fa-angle-double-right")
      .addClass("fa fa-angle-double-left");
  } else {
    tablewrap.addClass("col-md-5").removeClass("col-md-12");
    $(".toggle-small-view")
      .find("i")
      .removeClass("fa fa-angle-double-left")
      .addClass("fa fa-angle-double-right");
  }
  var _table = $(table).DataTable();
  // Show hide hidden columns
  _table.columns(hidden_columns).visible(_visible, false);
  _table.columns.adjust();
  $(main_data).toggleClass("hide");
  $(window).trigger("resize");
}

// Helper hash id for estimates,invoices,proposals,expenses, credit notes
function do_hash_helper(hash) {
  if (typeof history.pushState != "undefined") {
    var url = window.location.href;
    var obj = {
      Url: url,
    };
    history.pushState(obj, "", obj.Url);
    window.location.hash = hash;
  }
}

// Delete estimate note
function delete_sales_note(wrapper, id) {
  if (confirm_delete()) {
    requestGetJSON("misc/delete_note/" + id).done(function (response) {
      if (response.success === true || response.success == "true") {
        $(wrapper).parents(".sales-note-wrapper").remove();
        var salesNotesWrapper = $("#sales-notes-wrapper");
        var totalNotesNow = salesNotesWrapper.attr("data-total") - 1;
        var notesTotal = $(".notes-total");
        salesNotesWrapper.attr("data-total", totalNotesNow);
        if (totalNotesNow <= 0) {
          notesTotal.addClass("hide");
        } else {
          notesTotal.html('<span class="badge">' + totalNotesNow + "</span>");
        }
      }
    });
  }
}

// Get all estimate notes
function get_sales_notes(id, controller) {
  requestGet(controller + "/get_notes/" + id).done(function (response) {
    $("#sales_notes_area").html(response);
    var totalNotesNow = $("#sales-notes-wrapper").attr("data-total");
    if (totalNotesNow > 0) {
      $(".notes-total")
        .html('<span class="badge">' + totalNotesNow + "</span>")
        .removeClass("hide");
    }
  });
}

// Get all estimate notes
function get_invoice_notes(id, controller) {
  requestGet(controller + "/get_invoice_notes/" + id).done(function (response) {
    $("#sales_notes_area").html(response);
    var totalNotesNow = $("#sales-notes-wrapper").attr("data-total");
    if (totalNotesNow > 0) {
      $(".notes-total")
        .html('<span class="badge">' + totalNotesNow + "</span>")
        .removeClass("hide");
    }
  });
}

// Toggle full view for small tables like proposals
function small_table_full_view() {
  $("#small-table").toggleClass("hide");
  $(".small-table-right-col").toggleClass("col-md-12 col-md-7");
  $(window).trigger("resize");
}

  // Init the editor for email templates where changing data is allowed
$("body").on("show.bs.modal", ".modal.email-template", function () {
    init_editor($(this).data("editor-id"), {
      urlconverter_callback: merge_field_format_url,
  });
});

// Used for email template URL
function merge_field_format_url(url, node, on_save, name) {
  // Merge fields url
  if (url && url.indexOf("%7B") > -1 && url.indexOf("%7D") > -1) {
    url = url.replaceAll("%7B", "{").replaceAll("%7D", "}");
  }

  return url;
}

// Ajax project search but only for specific customer
function init_ajax_project_search_by_customer_id(selector) {
  selector =
    typeof selector == "undefined" ? "#project_id.ajax-search" : selector;
  real_init_ajax_search("project", selector, {
    customer_id: function () {
      return $("#clientid").val();
    },
  });
}

  // add invoice/estimate note
$("body").on("submit", "#sales-notes", function () {
    var form = $(this);
    if (form.find('textarea[name="description"]').val() === "") {
      return;
  }

  $.post(form.attr("action"), $(form).serialize()).done(function (rel_id) {
      // Reset the note textarea value
      form.find('textarea[name="description"]').val("");
      // Reload the notes
    if (form.hasClass("invoice-notes-form")) {
        get_invoice_notes(rel_id, "realestate/broker");
    } else if (form.hasClass("contract-notes-form")) {
        get_sales_notes(rel_id, "realestate/broker");
    }
});
  return false;
});


// Datatables custom view will fill input with the value
function dt_custom_view(value, table, custom_input_name, clear_other_filters) {
  var name =
    typeof custom_input_name == "undefined" ? "custom_view" : custom_input_name;
  if (typeof clear_other_filters != "undefined") {
    var filters = $("._filter_data li.active").not(".clear-all-prevent");
    filters.removeClass("active");
    $.each(filters, function () {
      var input_name = $(this).find("a").attr("data-cview");
      $('._filters input[name="' + input_name + '"]').val("");
    });
  }
  var _cinput = do_filter_active(name);
  if (_cinput != name) {
    value = "";
  }
  $('input[name="' + name + '"]').val(value);
  $(table).DataTable().ajax.reload();
}

// Sets table filters dropdown to active
function do_filter_active(value, parent_selector) {
  if (value !== "" && typeof value != "undefined") {
    $('[data-cview="all"]').parents("li").removeClass("active");
    var selector = $('[data-cview="' + value + '"]');
    if (typeof parent_selector != "undefined") {
      selector = $(parent_selector + ' [data-cview="' + value + '"]');
    }
    var parent = selector.parents("li");
    if (parent.hasClass("filter-group")) {
      var group = parent.data("filter-group");
      $('[data-filter-group="' + group + '"]')
        .not(parent)
        .removeClass("active");
      $.each($('[data-filter-group="' + group + '"]').not(parent), function () {
        $('input[name="' + $(this).find("a").attr("data-cview") + '"]').val("");
      });
      //   $('input[name="' + value + '"]').val('');
    }
    if (!parent.not(".dropdown-submenu").hasClass("active")) {
      parent.addClass("active");
    } else {
      parent.not(".dropdown-submenu").removeClass("active");
      parent.find("a").blur();
      // Remove active class from the parent dropdown if nothing selected in the child dropdown
      var parents_sub = selector.parents("li.dropdown-submenu");
      if (parents_sub.length > 0) {
        if (parents_sub.find("li.active").length === 0) {
          parents_sub.removeClass("active");
        }
      }
      value = "";
    }
    return value;
  } else {
    $("._filters input").val("");
    $("._filter_data li.active").removeClass("active");
    $('[data-cview="all"]').parents("li").addClass("active");
    return "";
  }
}
// Set dropzone not auto discover
Dropzone.options.newsFeedDropzone = false;
Dropzone.options.salesUpload = false;

if ($("#sales-upload").length > 0) {
    new Dropzone(
      "#sales-upload",
      appCreateDropzoneOptions({
        sending: function (file, xhr, formData) {
          formData.append(
            "rel_id",
            $("body").find('input[name="_attachment_sale_id"]').val()
          );
          formData.append(
            "type",
            $("body").find('input[name="_attachment_sale_type"]').val()
          );
        },
        success: function (files, response) {
          response = JSON.parse(response);
          var type = $("body")
            .find('input[name="_attachment_sale_type"]')
            .val();
          var dl_url, delete_function;
          dl_url = "download/file/sales_attachment/";
          delete_function = "delete_" + type + "_attachment";
          if (type == "estimate") {
            $("body").hasClass("estimates-pipeline")
              ? estimate_pipeline_open(response.rel_id)
              : init_estimate(response.rel_id);
          } else if (type == "proposal") {
            $("body").hasClass("proposals-pipeline")
              ? proposal_pipeline_open(response.rel_id)
              : init_proposal(response.rel_id);
          } else {
            if (typeof window["init_" + type] == "function") {
              window["init_" + type](response.rel_id);
            }
          }
          var data = "";
          if (response.success === true || response.success == "true") {
            data +=
              '<div class="display-block sales-attach-file-preview" data-attachment-id="' +
              response.attachment_id +
              '">';
            data += '<div class="col-md-10">';
            data +=
              '<div class="pull-left"><i class="attachment-icon-preview fa-regular fa-file"></i></div>';
            data +=
              '<a href="' +
              site_url +
              dl_url +
              response.key +
              '" target="_blank">' +
              response.file_name +
              "</a>";
            data += '<p class="text-muted">' + response.filetype + "</p>";
            data += "</div>";
            data += '<div class="col-md-2 text-right">';
            data +=
              '<a href="#" class="text-danger" onclick="' +
              delete_function +
              "(" +
              response.attachment_id +
              '); return false;"><i class="fa fa-times"></i></a>';
            data += "</div>";
            data += '<div class="clearfix"></div><hr/>';
            data += "</div>";
            $("#sales_uploaded_files_preview").append(data);
          }
        },
      })
    );
  }
