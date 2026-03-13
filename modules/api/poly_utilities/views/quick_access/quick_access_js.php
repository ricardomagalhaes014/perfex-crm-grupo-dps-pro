<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Included in modules/poly_utilities/views/quick_access/manage.php
 */
echo '<script src="' . base_url('modules/poly_utilities/dist/assets/js/sortable/1.15.0/sortable.min.js') . '"></script>';
$this->load->view('poly_utilities/head_js');
?>
<script>
    (function($) {
        "use strict";
        //Validation
        var vRules = {};
        vRules = {
            poly_utilities_quick_access_title: 'required',
            poly_utilities_quick_access_shortcut_key: 'required',
            poly_utilities_quick_access_link: 'required',
        }
        appValidateForm($('.quick_access-form'), vRules);

        //Bind Shortcut key
        polyBindHotKey(['poly_utilities_quick_access_shortcut_key_pre', 'poly_utilities_quick_access_shortcut_key_last'], 'poly_utilities_quick_access_shortcut_key');

        $('.poly-hotkey').on('change', function() {
            "use strict";
            let shortcutKey = 'poly_utilities_quick_access_shortcut_key';
            let shortcutKeyPre = 'poly_utilities_quick_access_shortcut_key_pre';
            let shortcutKeyLast = 'poly_utilities_quick_access_shortcut_key_last';

            let dataId = $(this).data('id');
            if (dataId) {
                shortcutKey = `${shortcutKey}_${dataId}`;
                shortcutKeyPre = `${shortcutKeyPre}_${dataId}`;
                shortcutKeyLast = `${shortcutKeyLast}_${dataId}`;
            }

            polyBindHotKey([shortcutKeyPre, shortcutKeyLast], shortcutKey);
        });
        //Bind Shortcut key

        //Submit Add Quick Access Menu Item
        $('.btn-submit-poly-utilities').on('click', function() {
            "use strict";
            var form = $('.quick_access-form');
            if (form.valid()) {
                $.post(admin_url + 'poly_utilities/save_quick_access', {
                    icon: $('#poly_utilities_quick_access_icon').val(),
                    title: $('#poly_utilities_quick_access_title').val(),
                    link: $('#poly_utilities_quick_access_link').val(),
                    shortcut_key: $('#poly_utilities_quick_access_shortcut_key').val(),
                }).done(function(response) {
                    let dataResponse = JSON.parse(response);
                    dataResponse.title = "Alert";
                    PolyPopup.popup(dataResponse, true);
                });
            }
        });

        //Save manage
        $('.btn-submit-manage-poly-utilities').on('click', function() {
            "use strict";
            initListQuickAccessInformation();

            $.post(admin_url + 'poly_utilities/update_quick_access_menu', {
                data: poly_quick_access_menu,
            }).done(function(response) {
                PolyMessage.displayNotification('success', 'Successfully');
            });
        });

        //Sortable
        if (document.getElementById('myListItem')) {
            Sortable.create(myListItem, {
                handle: '.poly-handle',
                multiDrag: true,
                selectedClass: 'selected',
                filter: '.layer-locked',
                fallbackTolerance: 3,
                animation: 150,
                onEnd: function(evt) {
                    initListQuickAccessInformation();
                }
            });
        }
        //Sortable

        //Remove
        $('.poly-quick-access-menu-delete').on('click', function() {
            "use strict";
            var data = {};
            data.link = $(this).data('link');

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
                    $.post(admin_url + 'poly_utilities/delete_quick_access', data).done(function(response) {
                        window.location.reload();
                    });

                }
            });
        });

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

        //Function
        function polyBindHotKey(idsArray, idBindValue) {
            let shortcutKey = '';
            idsArray.forEach(function(id) {
                shortcutKey += $(`#${id}`).val() + '+';
            });
            shortcutKey = shortcutKey.slice(0, -1);
            $('#' + idBindValue).val(shortcutKey);
        }

        function addToListAccessMenu(obj) {
            poly_quick_access_menu.push({
                icon: obj.icon,
                index: obj.index,
                title: obj.title,
                link: obj.link,
                shortcut_key: obj.shortcut_key
            });
        }

        function initListQuickAccessInformation() {
            poly_quick_access_menu = [];
            var items = myListItem.querySelectorAll('[data-icon][data-index][data-title][data-link][data-shortcut_key]');
            items.forEach(function(item) {
                let dataId = $(item).data('index');
                item.dataset.shortcut_key = $(`#poly_utilities_quick_access_shortcut_key_${dataId}`).val();
                item.dataset.title = $(`#poly_utilities_quick_access_title_${dataId}`).val();
                item.dataset.link = $(`#poly_utilities_quick_access_link_${dataId}`).val();
                item.dataset.icon = $(`#mn_icon-${dataId}`).val();
                addToListAccessMenu(item.dataset);
            });
        }
    })(jQuery);
</script>