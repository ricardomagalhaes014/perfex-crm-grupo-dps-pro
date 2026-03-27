<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <!-- Cabeçalho -->
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <a href="<?= admin_url('dps_teams') ?>" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i>
                        </a>
                        <h1 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-m-0">
                            <i class="fa fa-bullseye tw-mr-2 tw-text-blue-600"></i>
                            Objectivos Mensais — <?= e($team['name']) ?>
                        </h1>
                    </div>
                </div>

                <!-- Selector de mês/ano -->
                <div class="panel panel-default tw-mb-4">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-gap-4 tw-flex-wrap">
                            <div class="tw-flex tw-items-center tw-gap-2">
                                <label class="tw-font-semibold tw-text-gray-700 tw-mb-0">Mês:</label>
                                <select id="sel_month" class="form-control tw-w-auto">
                                    <?php
                                    $months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                                               'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                                    for ($m = 1; $m <= 12; $m++):
                                    ?>
                                    <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
                                        <?= $months[$m-1] ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-2">
                                <label class="tw-font-semibold tw-text-gray-700 tw-mb-0">Ano:</label>
                                <select id="sel_year" class="form-control tw-w-auto">
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button id="btn_load_month" class="btn btn-primary btn-sm">
                                <i class="fa fa-refresh tw-mr-1"></i> Carregar
                            </button>
                            <span class="tw-text-sm tw-text-gray-500 tw-ml-2">
                                <i class="fa fa-info-circle"></i>
                                Defina o objectivo de interacções para cada comercial. As interacções contam cada nota gravada numa lead.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabela de objectivos -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title tw-font-bold">
                            <i class="fa fa-table tw-mr-1"></i>
                            Objectivos vs Real —
                            <span id="lbl_period"><?= $months[$month-1] ?> <?= $year ?></span>
                        </h3>
                    </div>
                    <div class="panel-body" id="objectives_container">
                        <?php if (empty($dashboard)): ?>
                        <p class="tw-text-gray-500 tw-italic">Nenhum comercial nesta equipa.</p>
                        <?php else: ?>
                        <?php echo render_objectives_table($dashboard, $team['id'], $year, $month); ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
var DPS_TEAMS_URL = '<?= admin_url('dps_teams') ?>';
var current_team_id = <?= (int)$team['id'] ?>;
var months_pt = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                 'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

// Carregar dados ao mudar mês/ano
$('#btn_load_month').on('click', function() {
    var year  = parseInt($('#sel_year').val());
    var month = parseInt($('#sel_month').val());
    var btn   = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

    var postData = { team_id: current_team_id, year: year, month: month };
    if (typeof csrfData !== 'undefined') { postData[csrfData.token_name] = csrfData.hash; }

    $.post(DPS_TEAMS_URL + '/objectives_data', postData, function(resp) {
        if (resp.success) {
            $('#lbl_period').text(months_pt[month-1] + ' ' + year);
            renderDashboard(resp.dashboard, year, month);
        } else {
            alert_float('danger', 'Erro ao carregar dados.');
        }
        btn.prop('disabled', false).html('<i class="fa fa-refresh tw-mr-1"></i> Carregar');
    }, 'json').fail(function() {
        alert_float('danger', 'Erro de comunicação.');
        btn.prop('disabled', false).html('<i class="fa fa-refresh tw-mr-1"></i> Carregar');
    });
});

// Gravar objectivo ao mudar o input
$(document).on('change', '.obj-input', function() {
    var input    = $(this);
    var staff_id = input.data('staff');
    var year     = parseInt($('#sel_year').val());
    var month    = parseInt($('#sel_month').val());
    var target   = parseInt(input.val()) || 0;

    var postData = {
        team_id:  current_team_id,
        staff_id: staff_id,
        year:     year,
        month:    month,
        target:   target
    };
    if (typeof csrfData !== 'undefined') { postData[csrfData.token_name] = csrfData.hash; }

    input.css('border-color', '#aaa');
    $.post(DPS_TEAMS_URL + '/save_objective', postData, function(resp) {
        if (resp.success) {
            input.css('border-color', '#5cb85c');
            setTimeout(function() { input.css('border-color', ''); }, 1500);
            // Actualizar a barra de progresso
            var real   = parseInt(input.closest('tr').find('.real-count').text()) || 0;
            var pct    = target > 0 ? Math.min(100, Math.round(real / target * 100)) : (real > 0 ? 100 : 0);
            updateProgressBar(input.closest('tr'), real, target, pct);
        } else {
            input.css('border-color', '#d9534f');
            alert_float('danger', 'Erro ao gravar objectivo.');
        }
    }, 'json');
});

function updateProgressBar(row, real, target, pct) {
    var color = pct >= 100 ? 'success' : (pct >= 60 ? 'warning' : 'danger');
    row.find('.progress-bar')
       .removeClass('progress-bar-success progress-bar-warning progress-bar-danger')
       .addClass('progress-bar-' + color)
       .css('width', pct + '%')
       .text(pct + '%');
    row.find('.pct-label').text(pct + '%');
}

function renderDashboard(dashboard, year, month) {
    if (!dashboard || dashboard.length === 0) {
        $('#objectives_container').html('<p class="tw-text-gray-500 tw-italic">Nenhum comercial nesta equipa.</p>');
        return;
    }

    var html = '<div class="table-responsive">';
    html += '<table class="table table-hover">';
    html += '<thead><tr>';
    html += '<th>Comercial</th>';
    html += '<th class="tw-text-center">Objectivo (interacções)</th>';
    html += '<th class="tw-text-center">Real</th>';
    html += '<th style="min-width:200px">Progresso</th>';
    html += '<th class="tw-text-center">%</th>';
    html += '</tr></thead><tbody>';

    $.each(dashboard, function(i, row) {
        var color = row.pct >= 100 ? 'success' : (row.pct >= 60 ? 'warning' : 'danger');
        html += '<tr>';
        html += '<td><strong>' + row.full_name + '</strong></td>';
        html += '<td class="tw-text-center">';
        html += '<input type="number" class="form-control input-sm obj-input tw-text-center" ';
        html += 'data-staff="' + row.staff_id + '" value="' + row.target + '" min="0" style="width:80px;margin:auto;">';
        html += '</td>';
        html += '<td class="tw-text-center"><span class="real-count tw-font-bold">' + row.real + '</span></td>';
        html += '<td>';
        html += '<div class="progress tw-mb-0">';
        var pctVal = row.pct || 0;
        html += '<div class="progress-bar progress-bar-' + color + ' no-percent-text" role="progressbar" ';
        html += 'data-percent="' + pctVal + '" style="width:' + (pctVal > 0 ? pctVal : 2) + '%;min-width:2em">' + pctVal + '%</div>';
        html += '</div>';
        html += '</td>';
        html += '<td class="tw-text-center pct-label tw-font-bold">' + pctVal + '%</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    $('#objectives_container').html(html);
}
</script>

<?php
function render_objectives_table($dashboard, $team_id, $year, $month) {
    if (empty($dashboard)) {
        return '<p class="tw-text-gray-500 tw-italic">Nenhum comercial nesta equipa.</p>';
    }

    $html  = '<div class="table-responsive">';
    $html .= '<table class="table table-hover">';
    $html .= '<thead><tr>';
    $html .= '<th>Comercial</th>';
    $html .= '<th class="text-center">Objectivo (interacções)</th>';
    $html .= '<th class="text-center">Real</th>';
    $html .= '<th style="min-width:200px">Progresso</th>';
    $html .= '<th class="text-center">%</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($dashboard as $row) {
        $pct   = (int)$row['pct'];
        $color = $pct >= 100 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
        $html .= '<tr>';
        $html .= '<td><strong>' . e($row['full_name']) . '</strong></td>';
        $html .= '<td class="text-center">';
        $html .= '<input type="number" class="form-control input-sm obj-input text-center" ';
        $html .= 'data-staff="' . (int)$row['staff_id'] . '" value="' . (int)$row['target'] . '" min="0" style="width:80px;margin:auto;">';
        $html .= '</td>';
        $html .= '<td class="text-center"><span class="real-count" style="font-weight:bold">' . (int)$row['real'] . '</span></td>';
        $html .= '<td>';
    $html .= '<div class="progress" style="margin-bottom:0">';
    $html .= '<div class="progress-bar progress-bar-' . $color . ' no-percent-text" role="progressbar" ';
    $html .= 'data-percent="' . $pct . '" style="width:' . ($pct > 0 ? $pct : 2) . '%;min-width:2em">' . $pct . '%</div>';
    $html .= '</div>';
        $html .= '</td>';
        $html .= '<td class="text-center pct-label" style="font-weight:bold">' . $pct . '%</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';
    return $html;
}
?>
