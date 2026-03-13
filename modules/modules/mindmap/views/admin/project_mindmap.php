<?php
defined('BASEPATH') or exit('No direct script access allowed');
 defined('BASEPATH') or exit('No direct script access allowed'); 
$isGridView = 0;

?>


 <div class="row" id="mindmap-table">
<div class="col-md-12">
 <?php render_datatable(array(
                                _l('mindmap_title'),
                                _l('mindmap_desc'),
                                _l('mindmap_staff'),
                                _l('mindmap_group'),
                                _l('mindmap_created_at')
                            ),'mindmap', array('customizable-table'),
                              array(
                                  'id'=>'table-mindmap',
                                  'data-last-order-identifier'=>'mindmap',
                                  'data-default-order'=>get_table_last_order('mindmap'),
                              )); ?>
                        <?php ?>
                        </div>
                        </div>
                        <?php init_tail(); ?>
 <script>
    var _lnth = 12;
$(function(){
    var id = $('input[name=project_id]').val();
    var TblServerParams = {
        "assigned": "[name='view_assigned']",
        "group": "[name='view_group']",
        "project_id": id,
    };

    // if(<?php echo $isGridView ?> == 0) {
        var tAPI = initDataTable('.table-mindmap', admin_url+'mindmap/project_table?project_id='+id, [2, 3], [2, 3], TblServerParams);
        console.log(tAPI);
        $.each(TblServerParams, function(i, obj) {
            $('select' + obj).on('change', function() {
                $('table.table-mindmap').DataTable().ajax.reload()
                    .columns.adjust()
                    .responsive.recalc();
            });
        });

    // }else{
    //     loadGridView();

    //     $(document).off().on('click','a.paginate',function(e){
    //         e.preventDefault();
    //         console.log("$(this)", $(this).data('ci-pagination-page'))
    //         var pageno = $(this).data('ci-pagination-page');
    //         var formData = {
    //             search: $("input#search").val(),
    //             start: (pageno-1),
    //             length: _lnth,
    //             draw: 1
    //         }
    //         gridViewDataCall(formData, function (resposne) {
    //             $('div#grid-tab').html(resposne)
    //         })
    //     });
    // }
});
</script>