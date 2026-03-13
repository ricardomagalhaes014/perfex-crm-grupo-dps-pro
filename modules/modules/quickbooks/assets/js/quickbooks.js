"use strict";

var qb_progress_bar = '<div class="row mtop10 hide"><div class="col-md-12" id="process"><div class="progress progress-bar-mini"> <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:20%"></div></div></div></div>';
   
//==========================================================
// Chart of Accounts Table
//==========================================================
var table_ledger = $('#qb_ledgertable');
appDataTableInline(table_ledger, {
    supportsButtons: true,
    supportsLoading: true,
    autoWidth: false,
    paging: false,
    ordering: false,
    scrollResponsive: app.options.scroll_responsive_tables,
});

$(function(){
    "use strict";
    $('#chat_of_acc').find('.dt-buttons').append('<button class="btn btn-default" tabindex="0" aria-controls="ledgertable" type="button" onclick="location.reload();"><i class="fa fa-refresh"></i> <span>Reload</span></button>');
});

$('body').on('click', '#synch_chart_of_accounts', function() {
    "use strict";
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');  

    var $qb_synch_area = btn_synch.data('qb_synch_area');  

    //ajax call
    ajax_synch_chart_of_accounts(btn_synch, btn_label, $qb_synch_area);
});

function ajax_synch_chart_of_accounts(btn_synch, btn_label, qb_synch_area = false){
    if(qb_synch_area){
       btn_synch.parent().append(qb_progress_bar);
   }

   $.get(admin_url + 'quickbooks/synchallaccounts', function(response) {
    if (response.success == true) {
        $('#process').parent().removeClass('hide');
        var percentage = 0;
        var timer = setInterval(function() {
            percentage = percentage + 20;
            progress_bar_process(percentage, timer, response.message);
        }, 1000);

        setTimeout(function() {
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
            if(qb_synch_area){
                $('#process').parent().remove();
            }
            
            if ($.fn.DataTable.isDataTable('#qb_ledgertable')) {
                    //$('#ledgertable').DataTable().ajax.reload();
                }
            }, 5000);

    } else {
        alert_float('warning', response.message);
        btn_synch.html(btn_label);
        btn_synch.prop('disabled', false);
        if(qb_synch_area){
            $('#process').parent().remove();
        }
    }

}, 'json');
}

//==========================================================
//  TAXES
//==========================================================
if (qb_sales_tax_model ==  'non_us_locales') {
     /**
     * Tax modal Update
     */
     $('#tax_modal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget)
        var id = button.data('id');

        var working_area = $(this).find('.modal-body .row .col-md-12');
        working_area.find('div:eq(4)').remove()
        //ajax call
        $.get(admin_url + 'quickbooks/tax_agency/get_agency_select_field/' + id, function(data) {

            working_area.append(data).find('select[name="agency_id"]').selectpicker('refresh');
        }, 'html');

        if (typeof(id) === 'undefined') {
            working_area.find('select[name="agency_id"]').selectpicker('refresh').selectpicker('val', '');
        }

    });

     $(function() {
        "use strict";
        if ($('body').hasClass('taxes')) {
            add_qb_comp_taxes();

            $('body').find('.panel-body').prepend('<div class="alert alert-info">Now that you have the QuickBooks® Online module active, make sure that all Tax Rates belong to a Tax Agency. <br>Note: <ul><li>1. Only Taxes within a Tax Agency will synchronize with QuickBooks® Online.</li><li>2. For Tax Rate and Tax Name updates, kindly do the same manually in your remote QuickBooks® Online account (sorry quickbooks has not activated \"tax rate updates\" over API).</li><li>3. <div class="badge bg-success">QB</div> Means it has already been synchronized.</li></ul></div>');
            $('body').find('.panel-body ._buttons').addClass('row').append('<button type="button" class="btn btn-success pull-right" id="synch_taxes"><i class="fa fa-refresh"></i> QuickBooks® Online</button>');
            var originalContents = $('body').find('.panel-body ._buttons').html();
            $('body').find('.panel-body ._buttons').empty().html('<div class="col-md-12">' + originalContents + '</div>');
            $(qb_progress_bar).insertAfter('.panel-body ._buttons');
        }
    });


     $('body').on('click', '#synch_taxes', function() {
        "use strict";
        var btn_synch = $(this);
        var btn_label = btn_synch.html();
        btn_synch.prop('disabled', true);
        btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');  

        var $qb_synch_area = btn_synch.data('qb_synch_area');  

        //ajax call
        ajax_synch_taxes(btn_synch, btn_label, $qb_synch_area);
    });

     function ajax_synch_taxes(btn_synch, btn_label, qb_synch_area = false){
        if(qb_synch_area){
           btn_synch.parent().append(qb_progress_bar);
       }

       $.get(admin_url + 'quickbooks/tax_agency/synchalltaxes', function(response) {
        if (response.success == true) {
            $('#process').parent().removeClass('hide');
            var percentage = 0;
            var timer = setInterval(function() {
                percentage = percentage + 20;
                progress_bar_process(percentage, timer, response.message);
            }, 1000);

            setTimeout(function() {
                btn_synch.html(btn_label);
                btn_synch.prop('disabled', false);
                if(qb_synch_area){
                    $('#process').parent().remove();
                }
                if ($.fn.DataTable.isDataTable('.table-taxes')) {
                    $('.table-taxes').DataTable().ajax.reload();
                    add_qb_comp_taxes();
                }
            }, 5000);

        } else {
            alert_float('warning', response.message);
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
            if(qb_synch_area){
                $('#process').parent().remove();
            }
                if ($.fn.DataTable.isDataTable('.table-taxes')) {
                    $('.table-taxes').DataTable().ajax.reload();
                    add_qb_comp_taxes();
                }
        }

    }, 'json');
   }


   function add_qb_comp_taxes() {
    $.get(admin_url + 'quickbooks/tax_agency/get_qb_taxes', function(response) {
        if (response.length > 0) {
            var qb_tax_r = [];
            jQuery.each(response, function(i, val) {
                qb_tax_r.push(val['id']);
            });
                var table = $('.table-taxes').DataTable();
                if ($.fn.DataTable.isDataTable('.table-taxes')) {
                    table.on('order.dt search.dt', function() {
                        table.column(0, {
                            search: 'applied',
                            order: 'applied'
                        }).nodes().each(function(cell, i) {

                            if ($.inArray(cell.innerHTML, qb_tax_r) >= 0) {
                                cell.innerHTML = cell.innerHTML + ' <div class="badge-xs badge bg-success">QB</div>';
                            }
                        });
                    }).draw()
                }

            }
        }, 'json');
}
}


//==========================================================
//  CUSTOMER
//==========================================================
$(function() {
    "use strict";
    if ($('body').hasClass('clients') && !$('body').hasClass('customer-profile')) {
        add_qb_comp_clients();

        $('body').find('.panel-body').prepend('<div class="alert alert-info">Now that you have the QuickBooks® Online module active. Note: <ul><li>1. For customer deleting, kindly do the same manually in your remote QuickBooks® Online account (sorry quickbooks has not activated \"customer deleting\" over API).</li><li>2. <div class="badge bg-success">QB</div> Means it has already been synchronized.</li></ul></div>');
        $('body').find('.panel-body ._buttons').addClass('row').append('<button type="button" class="btn btn-success pull-right mright10" id="synch_clients"><i class="fa fa-refresh"></i> QuickBooks® Online</button>');
        var originalContents = $('body').find('.panel-body ._buttons').html();
        $('body').find('.panel-body ._buttons').empty().html('<div class="col-md-12">' + originalContents + '</div>');
        $(qb_progress_bar).insertAfter('.panel-body ._buttons');
    }
});

$('body').on('click', '#synch_clients', function() {
    "use strict";
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');

    var $qb_synch_area = btn_synch.data('qb_synch_area');

    //ajax call
    ajax_synch_clients(btn_synch, btn_label, $qb_synch_area);

});

function ajax_synch_clients(btn_synch, btn_label, qb_synch_area = false){
    if(qb_synch_area){
       btn_synch.parent().append(qb_progress_bar);
   }

   $.get(admin_url + 'quickbooks/synchallclients', function(response) {
    if (response.success == true) {
        $('#process').parent().removeClass('hide');
        var percentage = 0;
        var timer = setInterval(function() {
            percentage = percentage + 20;
            progress_bar_process(percentage, timer, response.message);
        }, 1000);

        setTimeout(function() {
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
            if(qb_synch_area){
                $('#process').parent().remove();
            }

            if ($.fn.DataTable.isDataTable('.table-clients')) {
                $('.table-clients').DataTable().ajax.reload();
                add_qb_comp_clients();
            }
        }, 5000);

    } else {
        alert_float('warning', response.message);
        btn_synch.html(btn_label);
        btn_synch.prop('disabled', false);
        if(qb_synch_area){
            $('#process').parent().remove();
        }

    }

}, 'json');
}


function add_qb_comp_clients() {
    $.get(admin_url + 'quickbooks/get_qb_clients/userid', function(response) {
        if (response.length > 0) {
            var qb_client_r = [];
            jQuery.each(response, function(i, val) {
                qb_client_r.push(val['userid']);
            });
            var table = $('.table-clients').DataTable();
            if ($.fn.DataTable.isDataTable('.table-clients')) {
                table.on('order.dt search.dt', function() {
                    table.column(1, {
                        search: 'applied',
                        order: 'applied'
                    }).nodes().each(function(cell, i) {

                        if ($.inArray(cell.innerHTML, qb_client_r) >= 0) {
                            cell.innerHTML = cell.innerHTML + ' <div class="badge-xs badge bg-success">QB</div>';
                        }
                    });
                }).draw()
            }

        }
    }, 'json');
}


//==========================================================
//  ITEMS
//==========================================================
/**
 * Item modal Update
 */
 $('#sales_item_modal').on('show.bs.modal', function(event) {
    "use strict";
    var button = $(event.relatedTarget)
    var id = button.data('id');

    var working_area = $(this).find('.modal-body .row .col-md-12');
    working_area.find('.inc_acc').remove()
    //ajax call
    $.get(admin_url + 'quickbooks/get_item_income_acc_select_field/' + id, function(data) {
        $(data).insertAfter(working_area.find('div:eq(3)'));
        working_area.find('select[name="qb_incomeAccountRef"]').selectpicker('refresh');
    }, 'html');

    if (typeof(id) === 'undefined') {
        working_area.find('select[name="qb_incomeAccountRef"]').selectpicker('refresh').selectpicker('val', '');
    }

});

/**
 * Synch All invoice items
 */
 $(function() {
    "use strict";
    if ($('body').hasClass('invoice_items')) {
        add_qb_comp_items();

        $('body').find('.panel-body').prepend('<div class="alert alert-info">Now that you have the QuickBooks® Online module active. Note: <ul><li>1. <div class="badge bg-success">QB</div> Means it has already been synchronized.</li></ul></div>');
        $('body').find('.panel-body ._buttons').addClass('row').append('<button type="button" class="btn btn-success pull-right mright10" id="synch_items"><i class="fa fa-refresh"></i> QuickBooks® Online</button>');
        var originalContents = $('body').find('.panel-body ._buttons').html();
        $('body').find('.panel-body ._buttons').empty().html('<div class="col-md-12">' + originalContents + '</div>');
        $(qb_progress_bar).insertAfter('.panel-body ._buttons');
    }
});

 $('body').on('click', '#synch_items', function() {
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');

    var $qb_synch_area = btn_synch.data('qb_synch_area');

    //ajax call
    ajax_synch_items(btn_synch, btn_label, $qb_synch_area);

});

 function ajax_synch_items(btn_synch, btn_label, qb_synch_area = false){
    if(qb_synch_area){
       btn_synch.parent().append(qb_progress_bar);
   }

   $.get(admin_url + 'quickbooks/synchallitems', function(response) {
    if (response.success == true) {
        $('#process').parent().removeClass('hide');
        var percentage = 0;
        var timer = setInterval(function() {
            percentage = percentage + 20;
            progress_bar_process(percentage, timer, response.message);
        }, 1000);

        setTimeout(function() {
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
            if(qb_synch_area){
                $('#process').parent().remove();
            }

            if ($.fn.DataTable.isDataTable('.table-invoice-items')) {
                $('.table-invoice-items').DataTable().ajax.reload();
                add_qb_comp_items();
            }
        }, 5000);

    } else {
        alert_float('warning', response.message);
        btn_synch.html(btn_label);
        btn_synch.prop('disabled', false);
        if(qb_synch_area){
            $('#process').parent().remove();
        }

    }

}, 'json');
}


function add_qb_comp_items() {
    $.get(admin_url + 'quickbooks/get_qb_items/item_id', function(response) {
        if (response.length > 0) {
            var qb_item_r = [];
            jQuery.each(response, function(i, val) {
                qb_item_r.push(val['item_id']);
            });
            var table = $('.table-invoice-items').DataTable();
            if ($.fn.DataTable.isDataTable('.table-invoice-items')) {
                table.on('order.dt search.dt', function() {
                $('.qb_item_r').remove(); //avoid duplication of qb badges
                table.column(0, {
                    search: 'applied',
                    order: 'applied'
                }).nodes().each(function(cell, i) {

                    var str = cell.innerHTML; 
                    var matchedString = str.match(/value=\"(.*?)\"/);


                    if ($.inArray(matchedString[1], qb_item_r) >= 0) {
                        cell.innerHTML = cell.innerHTML + ' <div class="qb_item_r badge-xs badge bg-success">QB</div>';
                    }
                });
            }).draw()
            }

        }
    }, 'json');
}

//==========================================================
//  INVOICES
//==========================================================
/**
 * Synch all invoices
 */
 $('body').on('click', '#synch_invoices', function() {
    "use strict";
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');

    var $qb_synch_area = btn_synch.data('qb_synch_area');

    //ajax call
    ajax_synch_invoices(btn_synch, btn_label, $qb_synch_area);

});

 function ajax_synch_invoices(btn_synch, btn_label, qb_synch_area = false){
    if(qb_synch_area){
       btn_synch.parent().append(qb_progress_bar);
   }

   $.get(admin_url + 'quickbooks/synchallinvoices', function(response) {
    if (response.success == true) {
        $('#process').parent().removeClass('hide');
        var percentage = 0;
        var timer = setInterval(function() {
            percentage = percentage + 20;
            progress_bar_process(percentage, timer, response.message);
        }, 1000);

        setTimeout(function() {
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
            if(qb_synch_area){
                $('#process').parent().remove();
            }

            if ($.fn.DataTable.isDataTable('.table-invoices')) {
                $('.table-invoices').DataTable().ajax.reload();
                add_qb_comp_invoices();
                }
            }, 5000);

    } else {
        alert_float('warning', response.message);
        btn_synch.html(btn_label);
        btn_synch.prop('disabled', false);
        if(qb_synch_area){
            $('#process').parent().remove();
        }

    }

}, 'json');
}

 $(function() {
    if ($('body').hasClass('invoices')) {
        add_qb_comp_invoices();

        $('body').find('._buttons').prepend('<div class="alert alert-info"><div class="row"><div class="col-md-7">Now that you have the QuickBooks® Online module active. Note: <ul><li>1. <div class="badge bg-success">QB</div> Means it has already been synchronized.</li></ul></div> <div class="col-md-5"><button type="button" class="btn btn-info pull-right mright10" id="synch_invoices"><i class="fa fa-refresh"></i> Quickbooks® Online</button></div></div>');

        var originalContents = $('body').find('._buttons').html();
        $('body').find('._buttons').empty().html('<div class="col-md-12">' + originalContents + '</div>');
        $(qb_progress_bar).insertAfter('._buttons .display-block');
    }
});

 function add_qb_comp_invoices() {
        $.get(admin_url + 'quickbooks/get_qb_invoice/id', function(response) {
            if (response.length > 0) {
                var xero_client_r = [];
                jQuery.each(response, function(i, val) {
                    xero_client_r.push(val['id']);
                });
                //console.log(xero_client_r);
                var table = $('.table-invoices').DataTable();
                if ($.fn.DataTable.isDataTable('.table-invoices')) {
                    table.on('order.dt search.dt', function() {
                        table.column(0, {
                            search: 'applied',
                            order: 'applied'
                        }).nodes().each(function(cell, i) {
                            var str = cell.innerHTML; 
                            var matchedString = str.match(/onclick=\"init_invoice\((.*?)\); return false;\"/);
                            
                            if ($.inArray(matchedString[1], xero_client_r) >= 0) {
                                var checkstr = cell.innerHTML;
                                var check = checkstr.match(/class=\"badge-xs(.*?)\"/);
                                cell.innerHTML = check ? cell.innerHTML : ' <div class="badge-xs badge bg-success">QB</div> ' + cell.innerHTML;
                            }
                        });
                    }).draw()
                }

            }
        }, 'json');
 }

//==========================================================
//  PAYMENTS
//==========================================================
/**
 * Synch all payments
 */

$(function() {
    "use strict";
    if ($('body').hasClass('payments')) {
        add_qb_comp_payments();

        $('body').find('.panel-body').prepend('<div class="alert alert-info">Now that you have the QuickBooks® Online module active. Note: <ul><li>1. <div class="badge bg-success">QB</div> Means it has already been synchronized.</li></ul></div><div class="_buttons"></div><hr>');
        $('body').find('.panel-body ._buttons').addClass('row').append('<button type="button" class="btn btn-success pull-right" id="synch_payments"><i class="fa fa-refresh"></i> QuickBooks® Online</button>');
        var originalContents = $('body').find('.panel-body ._buttons').html();
        $('body').find('.panel-body ._buttons').empty().html('<div class="col-md-12">' + originalContents + '</div>');
        $(qb_progress_bar).insertAfter('.panel-body ._buttons');
    }
});

function add_qb_comp_payments() {
        $.get(admin_url + 'quickbooks/get_qb_payments/id', function(response) {
            if (response.length > 0) {
                var xero_payment_r = [];
                jQuery.each(response, function(i, val) {
                    xero_payment_r.push(val['id']);
                });
                console.log(xero_payment_r);
                var table = $('.table-payments').DataTable();
                if ($.fn.DataTable.isDataTable('.table-payments')) {
                    table.on('order.dt search.dt', function() {
                        table.column(0, {
                            search: 'applied',
                            order: 'applied'
                        }).nodes().each(function(cell, i) {
                            
                            var str = cell.innerHTML;
                            var matchedString = str.match(/\>(.*?)\<\/a\>\<div class\="row-options"\>/);


                            if ($.inArray(matchedString[1], xero_payment_r) >= 0) {
                                var checkstr = cell.innerHTML;
                                var check = checkstr.match(/class=\"badge-xs(.*?)\"/);
                                cell.innerHTML = check ? cell.innerHTML : ' <div class="badge-xs badge bg-success">QB</div> ' + cell.innerHTML;
                            }
                        });
                    }).draw()
                }

            }
        }, 'json');
    }

 $('body').on('click', '#synch_payments', function() {
    "use strict";
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');

    var $qb_synch_area = btn_synch.data('qb_synch_area');

    //ajax call
    ajax_synch_payments(btn_synch, btn_label, $qb_synch_area);

});

 function ajax_synch_payments(btn_synch, btn_label, qb_synch_area = false){
    if(qb_synch_area){
       btn_synch.parent().append(qb_progress_bar);
   }

   $.get(admin_url + 'quickbooks/synchallpayments', function(response) {
    if (response.success == true) {
        $('#process').parent().removeClass('hide');
        var percentage = 0;
        var timer = setInterval(function() {
            percentage = percentage + 20;
            progress_bar_process(percentage, timer, response.message);
        }, 1000);

        setTimeout(function() {
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
            if(qb_synch_area){
                $('#process').parent().remove();
            }

            if ($.fn.DataTable.isDataTable('.table-payments')) {
                $('.table-payments').DataTable().ajax.reload();
                    //add_qb_comp_items();
                }
            }, 5000);

    } else {
        alert_float('warning', response.message);
        btn_synch.html(btn_label);
        btn_synch.prop('disabled', false);
        if(qb_synch_area){
            $('#process').parent().remove();
        }

    }

}, 'json');
}

//==========================================================
//  EXPENSES
//==========================================================
/**
 * Synch all expenses
 */
 $('body').on('click', '#synch_expenses', function() {
    "use strict";
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');

    var $qb_synch_area = btn_synch.data('qb_synch_area');

    //ajax call
    ajax_synch_expenses(btn_synch, btn_label, $qb_synch_area);

});

 function ajax_synch_expenses(btn_synch, btn_label, qb_synch_area = false){    
    if(qb_synch_area){
       btn_synch.parent().append(qb_progress_bar);
   }

   $.get(admin_url + 'quickbooks/synchallexpenses', function(response) {
    if (response.success == true) {
        $('#process').parent().removeClass('hide');
        var percentage = 0;
        var timer = setInterval(function() {
            percentage = percentage + 20;
            progress_bar_process(percentage, timer, response.message);
        }, 1000);

        setTimeout(function() {
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
            if(qb_synch_area){
                $('#process').parent().remove();
            }

            if ($.fn.DataTable.isDataTable('.table-expenses')) {
                $('.table-expenses').DataTable().ajax.reload();
                        //add_qb_comp_items();
                    }
                }, 5000);

    } else {
        alert_float('warning', response.message);
        btn_synch.html(btn_label);
        btn_synch.prop('disabled', false);
        if(qb_synch_area){
            $('#process').parent().remove();
        }

    }

}, 'json');
}


//==========================================================
//  DISCONNECT FROM QUICKBOOKS ONLINE
//==========================================================
$(function() {
    "use strict";
    $('#qb_disconnect_btn').css('padding', '5px');
});

$('body').on('click', '#qb_disconnect_btn', function() {
    "use strict";
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');
    //ajax call
    $.get(admin_url + 'quickbooks/quickbooks_auth/disconnect', function(response) {
        if (response.success == true) {
            $('#process').parent().removeClass('hide');
            var percentage = 0;
            var timer = setInterval(function() {
                percentage = percentage + 20;
                progress_bar_process(percentage, timer, response.message);
            }, 1000);

            setTimeout(function() {
                btn_synch.html(btn_label);
                //btn_synch.prop('disabled', false);
                //refresh page
                location.reload(true);
            }, 5000);

        } else {
            alert_float('warning', response.message);
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
        }
    }, 'json');

});

//==========================================================
//  CONFIG PAGE
//==========================================================
console.log(qb_manual_cron_sync_enabled);

$(function() {
    if(qb_manual_cron_sync_enabled === '1') {
        $('#auto_synch_area').addClass('hide');
     } else {
        $('#auto_synch_area').removeClass('hide');
      }

    $("[name='settings[qb_manual_cron_sync_enabled]']").on('change',  function(e) {
       if(this.value === '1') {
            $('#auto_synch_area').addClass('hide');
        } else {
            $('#auto_synch_area').removeClass('hide');
        }
    });
});




//==========================================================
//  GENERAL
//==========================================================
function progress_bar_process(percentage, timer, message) {
    $('.progress-bar').css('width', percentage + '%');
    if (percentage > 100) {
        clearInterval(timer);
        $('#process').parent().addClass('hide');
        $('.progress-bar').css('width', '0%');
        alert_float('success', message);
    }
}


//==========================================================
//  TAX AGENCY
//==========================================================
$(function(){
    "use strict";
    initDataTable('.table-tax_agency', window.location.href, [2], [2], undefined, [1,'asc']);

    appValidateForm($('#tax_agency_form'),{
        agency_name:{
            required: true,
            remote: {
                url: admin_url + "quickbooks/tax_agency/tax_agency_name_exists",
                type: 'post',
                data: {
                    tax_agencyid:function(){
                        return $('input[name="tax_agencyid"]').val();
                    }
                }
            }
        }}, manage_tax_agency);

                // don't allow | charachter in tax name
                // is used for tax name and tax rate separations!
                $('#tax_agency_modal input[name="agency_name"]').on('change',function(){
                    var val = $(this).val();
                    if(val.indexOf('|') > -1){
                        val = val.replace('|','');
                        // Clean extra spaces in case this char is in the middle with space
                        val = val.replace( / +/g, ' ' );
                        $(this).val(val);
                    }
                });

                $('#tax_agency_modal').on('show.bs.modal', function(event) {
                    var button = $(event.relatedTarget)
                    var id = button.data('id');
                    $(this).find('button[type="submit"]').prop('disabled',false);
                    $('#tax_agency_modal input[name="agency_name"]').val('').prop('disabled',false);
                    $('#tax_agency_modal input[name="tax_agencyid"]').val('')
                    $('#tax_agency_modal .add-title').removeClass('hide');
                    $('#tax_agency_modal .edit-title').addClass('hide');

                    if (typeof(id) !== 'undefined') {
                        $('input[name="tax_agencyid"]').val(id);
                        var name = $(button).parents('tr').find('td').eq(1).text();

                        $('#tax_agency_modal .add-title').addClass('hide');
                        $('#tax_agency_modal .edit-title').removeClass('hide');
                        $('#tax_agency_modal input[name="agency_name"]').val(name);

                    }
                });
            });

function manage_tax_agency(form) {
    var data = $(form).serialize();
    var url = form.action;
    $.post(url, data).done(function(response) {
        response = JSON.parse(response);
        if (response.success == true) {
            $('.table-tax_agency').DataTable().ajax.reload();
            alert_float('success', response.message);
        } else {
            if(response.message != ''){
                alert_float('warning', response.message);
            }
        }
        $('#tax_agency_modal').modal('hide');
    });
    return false;
}

//==========================================================
//  RESET
//==========================================================

$('body').on('click', '#qb_reset_all_sync', function() {
    "use strict";
    var btn_synch = $(this);
    var btn_label = btn_synch.html();
    btn_synch.prop('disabled', true);
    btn_synch.html('<i class="fa fa-refresh fa-spin"></i> Loading...');
    //ajax call
    $.get(admin_url + 'quickbooks/reset_all_sync', function(response) {
        if (response.success == true) {
            $('#qb_reset_progress_area').removeClass('hide');
            var percentage = 0;
            var timer = setInterval(function() {
                percentage = percentage + 20;
                progress_bar_process(percentage, timer, response.message);
            }, 1000);

            setTimeout(function() {
                btn_synch.html(btn_label);
                btn_synch.prop('disabled', false);
                $('#qb_reset_progress_area').addClass('hide');
                //refresh page
                //location.reload(true);
            }, 5000);

        } else {
            alert_float('warning', response.message);
            btn_synch.html(btn_label);
            btn_synch.prop('disabled', false);
        }
    }, 'json');

});


