(function ($) {
    'use strict';

    $(function () {

        // ------------------------------------------------ mapeamento de colunas
        var $table = $('#src_table');

        function loadColumns(autoSuggest) {
            var table = $table.val();

            if (!table || typeof DPS_MOLONI_COLUMNS_URL === 'undefined') {
                return;
            }

            $.getJSON(DPS_MOLONI_COLUMNS_URL, {table: table}, function (data) {
                var columns = data.columns || [];
                var suggested = data.suggested || {};

                $('.dps-col-select').each(function () {
                    var $select = $(this);
                    var field = $select.attr('id');
                    var current = $select.val() || $select.data('selected') || '';

                    $select.empty().append($('<option>').val('').text('—'));

                    columns.forEach(function (column) {
                        $select.append($('<option>').val(column).text(column));
                    });

                    if (columns.indexOf(current) !== -1) {
                        $select.val(current);
                    } else if (autoSuggest && suggested[field]) {
                        $select.val(suggested[field]);
                    }
                });

                if ($.fn.selectpicker) {
                    $('.dps-col-select').selectpicker('refresh');
                }
            });
        }

        $table.on('change', function () {
            loadColumns(true);
        });

        // ------------------------------------------ colunas da sobreposta
        var $overlay = $('#src_overlay_table');

        function loadOverlayColumns() {
            var table = $overlay.val();

            if (typeof DPS_MOLONI_COLUMNS_URL === 'undefined') {
                return;
            }

            if (!table) {
                $('.dps-ov-select').empty().append($('<option>').val('').text('—'));
                if ($.fn.selectpicker) { $('.dps-ov-select').selectpicker('refresh'); }
                return;
            }

            $.getJSON(DPS_MOLONI_COLUMNS_URL, {table: table}, function (data) {
                var columns = data.columns || [];

                $('.dps-ov-select').each(function () {
                    var $select = $(this);
                    var current = $select.val() || $select.data('selected') || '';

                    $select.empty().append($('<option>').val('').text('—'));

                    columns.forEach(function (column) {
                        $select.append($('<option>').val(column).text(column));
                    });

                    if (columns.indexOf(current) !== -1) {
                        $select.val(current);
                    }
                });

                if ($.fn.selectpicker) {
                    $('.dps-ov-select').selectpicker('refresh');
                }
            });
        }

        $overlay.on('change', loadOverlayColumns);

        $('#dps-suggest').on('click', function (e) {
            e.preventDefault();
            loadColumns(true);
        });

        // ------------------------------------------------------- conciliacao
        $('#dps-check-all').on('change', function () {
            $('.dps-apply').prop('checked', $(this).is(':checked'));
        });

        // -------------------------------------------------------------- logs
        $(document).on('click', '.dps-toggle-detail', function (e) {
            e.preventDefault();
            $($(this).data('target')).toggleClass('hide');
        });

    });
})(jQuery);
