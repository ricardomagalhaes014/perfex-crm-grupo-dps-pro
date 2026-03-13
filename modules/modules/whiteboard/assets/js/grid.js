var _lnth = 12;
$(function(){
    var TblServerParams = {
        "assigned": "[name='view_assigned']",
        "group": "[name='view_group']",
    };

    if(<?php echo $isGridView ?> == 0) {
        var tAPI = initDataTable('.table-whiteboard', admin_url+'whiteboard/table', [2, 3], [2, 3], TblServerParams);

        $.each(TblServerParams, function(i, obj) {
            $('select' + obj).on('change', function() {
                $('table.table-whiteboard').DataTable().ajax.reload()
                    .columns.adjust()
                    .responsive.recalc();
            });
        });

    }else{
        loadGridView();

        $(document).off().on('click','a.paginate',function(e){
            e.preventDefault();
            console.log("$(this)", $(this).data('ci-pagination-page'))
            var pageno = $(this).data('ci-pagination-page');
            var formData = {
                search: $("input#search").val(),
                start: (pageno-1),
                length: _lnth,
                draw: 1
            }
            gridViewDataCall(formData, function (resposne) {
                $('div#grid-tab').html(resposne)
            })
        });
    }
});