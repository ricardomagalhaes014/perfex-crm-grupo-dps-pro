<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Included in modules/poly_utilities/views/scripts/manage.php
 */
$this->load->view('poly_utilities/head_js');
?>
<script>
     (function($) {
        "use strict";
        //Toggle
        $('.toggle-menu-options').on('click', function(e) {
            "use strict";
            e.preventDefault();
            let menu_id = $(this).parents('li').data('id');
            if ($(this).hasClass('main-item-options')) {
                $(this).parents('li').find('.main-item-options[data-menu-options="' + menu_id + '"]')
                    .slideToggle();
            } else {
                $(this).parents('li').find('.sub-item-options[data-menu-options="' + menu_id + '"]')
                    .slideToggle();
            }
        });

        //Remove
        $('.poly-resource-delete').on('click', function() {
            "use strict";
            var data = {};
            data.id = $(this).data('id');
            data.resource = 'js';

            Swal.fire({
                title: '<?php echo _l('poly_utilities_delete_header_text') ?>',
                text: '<?php echo _l('poly_utilities_delete_message') ?>',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#2364EB",
                cancelButtonColor: "#d33",
                cancelButtonText: '<?php echo _l('poly_utilities_cancel_button_text') ?>',
                confirmButtonText: '<?php echo _l('poly_utilities_confirm_button_text') ?>',
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(admin_url + 'poly_utilities/delete_resource', data).done(function(response) {
                        let dataResponse = JSON.parse(response);
                        dataResponse.title = "Alert";
                        PolyPopup.popup(dataResponse, true);
                    });

                }
            });
        });
    })(jQuery);
</script>