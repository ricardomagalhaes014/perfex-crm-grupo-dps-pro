function property_listing_status_mark_as(status, task_id, type) {
    url = 'realestate/property_listing_status_mark_as/' + status + '/' + task_id + '/' + type;
    var taskModalVisible = $('#task-modal').is(':visible');
    url += '?single_task=' + taskModalVisible;
    $("body").append('<div class="dt-loader"></div>');

    requestGetJSON(url).done(function (response) {
        $("body").find('.dt-loader').remove();
        if (response.success === true || response.success == 'true') {
            
          var av_tasks_tables = ['.table-table_manage_delivery', '.table-table_manage_packing_list'];
          $.each(av_tasks_tables, function (i, selector) {
            if ($.fn.DataTable.isDataTable(selector)) {
              $(selector).DataTable().ajax.reload(null, false);
            }
          });
          alert_float('success', response.message);
          location.reload();
        }
    });
}

function rel_init_roles_permissions(roleid, user_changed) {
  roleid =
    typeof roleid == "undefined" ? $('select[name="role"]').val() : roleid;
  var isedit = $('.member > input[name="isedit"]');

  // Check if user is edit view and user has changed the dropdown permission if not only return
  if (
    isedit.length > 0 &&
    typeof roleid !== "undefined" &&
    typeof user_changed == "undefined"
  ) {
    return;
  }

  // Administrators does not have permissions
  if ($('input[name="administrator"]').prop("checked") === true) {
    return;
  }

  // Last if the roleid is blank return
  if (roleid === "") {
    return;
  }

  // Get all permissions
  var permissions = $("table.roles").find("tr");
  requestGetJSON("realestate/role_changed/" + roleid).done(function (response) {
    permissions
      .find(".capability")
      .not('[data-not-applicable="true"]')
      .prop("checked", false)
      .trigger("change");

    $.each(permissions, function () {
      var row = $(this);
      $.each(response, function (feature, obj) {
        if (row.data("name") == feature) {
          $.each(obj, function (i, capability) {
            row
              .find('input[id="' + feature + "_" + capability + '"]')
              .prop("checked", true);
              console.log(capability);
            if (capability == "view") {
              row.find("[data-can-view]").change();
            } else if (capability == "view_own") {
              row.find("[data-can-view-own]").change();
            }
          });
        }
      });
    });
  });
}

$('#rel_property_listing_create_10_on_month, #rel_property_listing_create').on('change', function() {

  if ($('#rel_property_listing_create').prop('checked') == true) {
     $(this).parents('tr').find('td input[id="rel_property_listing_create_10_on_month"]').prop('checked', false);
     $(this).parents('tr').find('td input[id="rel_property_listing_create_10_on_month"]').prop('disabled', $(this).prop('checked') === true);

  }else if($('#rel_property_listing_create_10_on_month').prop('checked') == true){
    $(this).parents('tr').find('td input[id="rel_property_listing_create"]').prop('checked', false);
     $(this).parents('tr').find('td input[id="rel_property_listing_create"]').prop('disabled', $(this).prop('checked') === true);
  }else if ($('#rel_property_listing_create').prop('checked') == false) {
     $(this).parents('tr').find('td input[id="rel_property_listing_create_10_on_month"]').prop('checked', false);
     $(this).parents('tr').find('td input[id="rel_property_listing_create_10_on_month"]').prop('disabled', false);
     $(this).parents('tr').find('td input[id="rel_property_listing_create"]').prop('checked', false);
     $(this).parents('tr').find('td input[id="rel_property_listing_create"]').prop('disabled', false);
  }else if ($('#rel_property_listing_create_10_on_month').prop('checked') == false) {

  }
});

$('select[name="search_template"]').on('change', function() {  
  "use strict";

  var search_templates_filter = $('select[name="search_template"]').val();
  open_change_serial_number_modal(search_templates_filter, true);
  
});

function open_change_serial_number_modal(search_templates_filter, show_criteria) {
  "use strict";

  $("#search_modal_wrapper").load(admin_url+"realestate/realestate/load_search_modal", {
    search_templates_filter: search_templates_filter,
    show_criteria: show_criteria,
  }, function() {
    $("body").find('#searchModal').modal({ show: true, backdrop: 'static' });
  });
}


$("body").on("change", ".onoffswitch1 input", function (event, state) {
  var switch_url = $(this).data("switch-url");
  if (!switch_url) {
    return;
  }
  switch_field1(this);
});

function switch_field1(field) {
  var status, url, id;
  status = 0;
  if ($(field).prop("checked") === true) {
    status = 1;
  }
  url = $(field).data("switch-url");
  id = $(field).data("id");
  rel_type = $(field).data("rel_type");
  requestGet(url + "/" + id + "/" + rel_type + "/" + status);
  $('.table-address_book_company_table').DataTable().ajax.reload();
  $('.table-address_book_agent_table').DataTable().ajax.reload();
}
$("body").on("change", ".onoffswitch input", function (event, state) {

// $('.add_to_compare_list').on('change', function() {  
  "use strict";

  var id = $(this).data("id");
  var type = $(this).data("type");

  if(type == 'add_to_compare'){
    if ($(this).prop("checked") === true) {
      console.log('id==========', id);
      console.log('type==========', type);
      add_to_compare_modal(id);
    }
  }
  
});

function add_to_compare_modal(listing_id) {
  "use strict";

  $("#add_to_compare_modal_wrapper").load(admin_url+"realestate/realestate/load_add_to_compare_modal", {
    listing_id: listing_id,
  }, function() {
    $("body").find('#addtocompareModal').modal({ show: true, backdrop: 'static' });
  });
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