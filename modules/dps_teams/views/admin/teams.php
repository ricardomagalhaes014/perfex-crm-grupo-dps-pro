<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
                    <h1 class="tw-text-2xl tw-font-bold tw-text-gray-800">
                        <i class="fa fa-users tw-mr-2 tw-text-blue-600"></i>
                        Gestão de Equipas DPS
                    </h1>
                </div>

                <?php
                // Separar equipas principais das sub-equipas
                $main_teams = array_filter($teams, fn($t) => empty($t['parent_id']));
                $sub_teams  = array_filter($teams, fn($t) => !empty($t['parent_id']));

                // Agrupar sub-equipas por parent_id
                $sub_by_parent = [];
                foreach ($sub_teams as $st) {
                    $sub_by_parent[$st['parent_id']][] = $st;
                }

                $area_colors = [
                    'dental' => ['bg' => 'tw-bg-rose-50',   'border' => 'tw-border-rose-300',   'icon_color' => 'tw-text-rose-600',   'badge' => 'tw-bg-rose-100 tw-text-rose-700',   'icon' => 'fa-heartbeat',   'header' => 'tw-bg-rose-100'],
                    'imo'    => ['bg' => 'tw-bg-amber-50',  'border' => 'tw-border-amber-300',  'icon_color' => 'tw-text-amber-600',  'badge' => 'tw-bg-amber-100 tw-text-amber-700',  'icon' => 'fa-building',    'header' => 'tw-bg-amber-100'],
                    'media'  => ['bg' => 'tw-bg-sky-50',    'border' => 'tw-border-sky-300',    'icon_color' => 'tw-text-sky-600',    'badge' => 'tw-bg-sky-100 tw-text-sky-700',    'icon' => 'fa-bullhorn',    'header' => 'tw-bg-sky-100'],
                ];
                $area_labels = ['dental' => 'Dentária', 'imo' => 'Imobiliário', 'media' => 'Media'];
                ?>

                <?php foreach ($main_teams as $team):
                    $c = $area_colors[$team['area']] ?? $area_colors['media'];
                    $area_label = $area_labels[$team['area']] ?? $team['area'];
                    $managers    = array_filter($team['members'], fn($m) => $m['role'] === 'manager');
                    $commercials = array_filter($team['members'], fn($m) => $m['role'] === 'commercial');
                    $has_subteams = isset($sub_by_parent[$team['id']]);
                ?>
                <!-- ═══ EQUIPA PRINCIPAL ═══ -->
                <div class="panel panel-default tw-border <?= $c['border'] ?> tw-rounded-xl tw-shadow-sm tw-mb-6">
                    <!-- Cabeçalho da equipa principal -->
                    <div class="panel-heading <?= $c['header'] ?> tw-rounded-t-xl tw-border-b <?= $c['border'] ?>">
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <i class="fa <?= $c['icon'] ?> <?= $c['icon_color'] ?> tw-text-2xl"></i>
                                <h3 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-m-0">
                                    <?= e($team['name']) ?>
                                </h3>
                                <span class="tw-text-xs tw-font-semibold tw-px-2 tw-py-1 tw-rounded-full <?= $c['badge'] ?>">
                                    <?= $area_label ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="panel-body">
                        <?php if ($has_subteams): ?>
                        <!-- Equipa com sub-equipas: mostrar gestores no topo e sub-equipas abaixo -->
                        <div class="row tw-mb-4">
                            <div class="col-md-4">
                                <div class="tw-bg-white tw-rounded-lg tw-border tw-border-gray-200 tw-p-3">
                                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                                        <span class="tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-blue-600">
                                            <i class="fa fa-user-tie tw-mr-1"></i> Gestor(es) da Área
                                        </span>
                                        <button class="btn btn-xs btn-default"
                                                onclick="openAddMemberModal(<?= $team['id'] ?>, 'manager')"
                                                title="Adicionar Gestor">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                    <div id="managers-<?= $team['id'] ?>">
                                        <?php foreach ($managers as $m): ?>
                                        <?= render_member_row($m, $team['id']) ?>
                                        <?php endforeach; ?>
                                        <?php if (empty($managers)): ?>
                                        <p class="tw-text-xs tw-text-gray-400 tw-italic">Nenhum gestor definido</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-equipas -->
                        <h5 class="tw-text-sm tw-font-bold tw-uppercase tw-tracking-wide tw-text-gray-500 tw-mb-3">
                            <i class="fa fa-sitemap tw-mr-1"></i> Sub-Equipas
                        </h5>
                        <div class="row">
                            <?php foreach ($sub_by_parent[$team['id']] as $sub):
                                $sub_managers    = array_filter($sub['members'], fn($m) => $m['role'] === 'manager');
                                $sub_commercials = array_filter($sub['members'], fn($m) => $m['role'] === 'commercial');
                            ?>
                            <div class="col-md-4" id="team-card-<?= $sub['id'] ?>">
                                <div class="panel panel-default tw-border <?= $c['border'] ?> tw-rounded-xl tw-shadow-sm <?= $c['bg'] ?>">
                                    <div class="panel-heading tw-rounded-t-xl tw-border-b <?= $c['border'] ?> tw-bg-transparent">
                                        <div class="tw-flex tw-items-center tw-gap-2">
                                            <i class="fa fa-home <?= $c['icon_color'] ?>"></i>
                                            <h4 class="panel-title tw-font-bold tw-text-gray-800 tw-text-sm">
                                                <?= e($sub['name']) ?>
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="panel-body">
                                        <!-- Gestores da sub-equipa -->
                                        <div class="tw-mb-3">
                                            <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                                                <span class="tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-blue-600">
                                                    <i class="fa fa-user-tie tw-mr-1"></i> Gestores
                                                </span>
                                                <button class="btn btn-xs btn-default"
                                                        onclick="openAddMemberModal(<?= $sub['id'] ?>, 'manager')"
                                                        title="Adicionar Gestor">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                            <div id="managers-<?= $sub['id'] ?>">
                                                <?php foreach ($sub_managers as $m): ?>
                                                <?= render_member_row($m, $sub['id']) ?>
                                                <?php endforeach; ?>
                                                <?php if (empty($sub_managers)): ?>
                                                <p class="tw-text-xs tw-text-gray-400 tw-italic">Nenhum gestor</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <hr class="tw-my-2">
                                        <!-- Comerciais da sub-equipa -->
                                        <div>
                                            <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                                                <span class="tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-green-600">
                                                    <i class="fa fa-user tw-mr-1"></i> Comerciais
                                                    <span class="badge tw-ml-1" id="count-<?= $sub['id'] ?>"><?= count($sub_commercials) ?></span>
                                                </span>
                                                <button class="btn btn-xs btn-default"
                                                        onclick="openAddMemberModal(<?= $sub['id'] ?>, 'commercial')"
                                                        title="Adicionar Comercial">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                            <div id="commercials-<?= $sub['id'] ?>">
                                                <?php foreach ($sub_commercials as $m): ?>
                                                <?= render_member_row($m, $sub['id']) ?>
                                                <?php endforeach; ?>
                                                <?php if (empty($sub_commercials)): ?>
                                                <p class="tw-text-xs tw-text-gray-400 tw-italic">Nenhum comercial</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php else: ?>
                        <!-- Equipa simples (sem sub-equipas): Dentária e Media -->
                        <div class="row">
                            <div class="col-md-5">
                                <div class="tw-mb-4">
                                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                                        <span class="tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-blue-600">
                                            <i class="fa fa-user-tie tw-mr-1"></i> Gestores
                                        </span>
                                        <button class="btn btn-xs btn-default"
                                                onclick="openAddMemberModal(<?= $team['id'] ?>, 'manager')"
                                                title="Adicionar Gestor">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                    <div id="managers-<?= $team['id'] ?>">
                                        <?php foreach ($managers as $m): ?>
                                        <?= render_member_row($m, $team['id']) ?>
                                        <?php endforeach; ?>
                                        <?php if (empty($managers)): ?>
                                        <p class="tw-text-xs tw-text-gray-400 tw-italic">Nenhum gestor definido</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div>
                                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                                        <span class="tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-green-600">
                                            <i class="fa fa-user tw-mr-1"></i> Comerciais
                                            <span class="badge tw-ml-1" id="count-<?= $team['id'] ?>"><?= count($commercials) ?></span>
                                        </span>
                                        <button class="btn btn-xs btn-default"
                                                onclick="openAddMemberModal(<?= $team['id'] ?>, 'commercial')"
                                                title="Adicionar Comercial">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                    <div id="commercials-<?= $team['id'] ?>" class="tw-grid tw-grid-cols-2 tw-gap-1">
                                        <?php foreach ($commercials as $m): ?>
                                        <?= render_member_row($m, $team['id']) ?>
                                        <?php endforeach; ?>
                                        <?php if (empty($commercials)): ?>
                                        <p class="tw-text-xs tw-text-gray-400 tw-italic">Nenhum comercial definido</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Membro -->
<div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="addMemberModalTitle">Adicionar Membro</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_team_id">
                <input type="hidden" id="modal_role">
                <div class="form-group">
                    <label>Seleccionar Utilizador</label>
                    <select class="form-control" id="modal_staff_select">
                        <option value="">-- Escolher --</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmAddMember">
                    <i class="fa fa-plus tw-mr-1"></i> Adicionar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var DPS_TEAMS_URL = '<?= admin_url('dps_teams') ?>';

function openAddMemberModal(teamId, role) {
    $('#modal_team_id').val(teamId);
    $('#modal_role').val(role);
    var roleLabel = role === 'manager' ? 'Gestor' : 'Comercial';
    $('#addMemberModalTitle').text('Adicionar ' + roleLabel);
    $('#modal_staff_select').html('<option value="">A carregar...</option>');
    var csrfParam = {};
    if (typeof csrfData !== 'undefined') {
        csrfParam[csrfData.token_name] = csrfData.hash;
    }
    $.ajax({
        url: DPS_TEAMS_URL + '/available_staff',
        type: 'POST',
        data: $.extend({ team_id: teamId }, csrfParam),
        dataType: 'json',
        success: function(staff) {
            var opts = '<option value="">-- Escolher --</option>';
            if (staff && staff.length > 0) {
                $.each(staff, function(i, s) {
                    opts += '<option value="' + s.staffid + '">' + s.full_name + ' (' + s.email + ')</option>';
                });
            } else {
                opts = '<option value="">Todos os utilizadores já estão na equipa</option>';
            }
            $('#modal_staff_select').html(opts);
        },
        error: function() {
            $('#modal_staff_select').html('<option value="">Erro ao carregar utilizadores</option>');
        }
    });
    $('#addMemberModal').modal('show');
}

$(document).on('click', '#btnConfirmAddMember', function() {
    var teamId  = $('#modal_team_id').val();
    var staffId = $('#modal_staff_select').val();
    var role    = $('#modal_role').val();
    if (!staffId) { alert_float('warning', 'Selecciona um utilizador.'); return; }
    var addData = { team_id: teamId, staff_id: staffId, role: role };
    if (typeof csrfData !== 'undefined') { addData[csrfData.token_name] = csrfData.hash; }
    $.post(DPS_TEAMS_URL + '/add_member', addData, function(resp) {
        if (resp.success) {
            $('#addMemberModal').modal('hide');
            refreshTeamCard(teamId, resp.members);
            alert_float('success', 'Membro adicionado com sucesso!');
        } else {
            alert_float('danger', resp.message || 'Erro ao adicionar membro.');
        }
    }, 'json');
});

function removeMember(memberId, teamId) {
    if (!confirm('Tens a certeza que queres remover este membro da equipa?')) return;
    var rmData = { member_id: memberId, team_id: teamId };
    if (typeof csrfData !== 'undefined') { rmData[csrfData.token_name] = csrfData.hash; }
    $.post(DPS_TEAMS_URL + '/remove_member', rmData, function(resp) {
        if (resp.success) {
            refreshTeamCard(teamId, resp.members);
            alert_float('success', 'Membro removido.');
        } else {
            alert_float('danger', resp.message || 'Erro ao remover membro.');
        }
    }, 'json');
}

function changeMemberRole(memberId, teamId, newRole) {
    var crData = { member_id: memberId, team_id: teamId, role: newRole };
    if (typeof csrfData !== 'undefined') { crData[csrfData.token_name] = csrfData.hash; }
    $.post(DPS_TEAMS_URL + '/change_role', crData, function(resp) {
        if (resp.success) {
            refreshTeamCard(teamId, resp.members);
            alert_float('success', 'Papel actualizado.');
        } else {
            alert_float('danger', resp.message || 'Erro ao alterar papel.');
        }
    }, 'json');
}

function refreshTeamCard(teamId, members) {
    var managers    = members.filter(function(m) { return m.role === 'manager'; });
    var commercials = members.filter(function(m) { return m.role === 'commercial'; });
    renderMemberList('#managers-' + teamId, managers, teamId);
    renderMemberList('#commercials-' + teamId, commercials, teamId);
    $('#count-' + teamId).text(commercials.length);
}

function renderMemberList(selector, members, teamId) {
    var html = '';
    if (members.length === 0) {
        html = '<p class="tw-text-xs tw-text-gray-400 tw-italic">Nenhum membro definido</p>';
    } else {
        $.each(members, function(i, m) {
            var roleLabel = m.role === 'manager'
                ? '<span class="label label-info">Gestor</span>'
                : '<span class="label label-success">Comercial</span>';
            var otherRole  = m.role === 'manager' ? 'commercial' : 'manager';
            var otherLabel = m.role === 'manager' ? 'Tornar Comercial' : 'Tornar Gestor';
            html += '<div class="tw-flex tw-items-center tw-justify-between tw-py-1 tw-border-b tw-border-gray-100">';
            html += '  <div class="tw-flex tw-items-center tw-gap-2">';
            html += '    <i class="fa fa-user-circle tw-text-gray-400 tw-text-lg"></i>';
            html += '    <span class="tw-text-sm tw-font-medium tw-text-gray-700">' + m.full_name + '</span>';
            html += '    ' + roleLabel;
            html += '  </div>';
            html += '  <div class="tw-flex tw-gap-1">';
            html += '    <button class="btn btn-xs btn-default" title="' + otherLabel + '" onclick="changeMemberRole(' + m.member_id + ', ' + teamId + ', \'' + otherRole + '\')">';
            html += '      <i class="fa fa-exchange"></i>';
            html += '    </button>';
            html += '    <button class="btn btn-xs btn-danger" title="Remover" onclick="removeMember(' + m.member_id + ', ' + teamId + ')">';
            html += '      <i class="fa fa-times"></i>';
            html += '    </button>';
            html += '  </div>';
            html += '</div>';
        });
    }
    $(selector).html(html);
}
</script>

<?php init_tail(); ?>

<?php
function render_member_row($m, $team_id) {
    $role_label  = $m['role'] === 'manager'
        ? '<span class="label label-info">Gestor</span>'
        : '<span class="label label-success">Comercial</span>';
    $other_role  = $m['role'] === 'manager' ? 'commercial' : 'manager';
    $other_label = $m['role'] === 'manager' ? 'Tornar Comercial' : 'Tornar Gestor';
    return '
    <div class="tw-flex tw-items-center tw-justify-between tw-py-1 tw-border-b tw-border-gray-100">
        <div class="tw-flex tw-items-center tw-gap-2">
            <i class="fa fa-user-circle tw-text-gray-400 tw-text-lg"></i>
            <span class="tw-text-sm tw-font-medium tw-text-gray-700">' . e($m['full_name']) . '</span>
            ' . $role_label . '
        </div>
        <div class="tw-flex tw-gap-1">
            <button class="btn btn-xs btn-default" title="' . $other_label . '"
                    onclick="changeMemberRole(' . $m['member_id'] . ', ' . $team_id . ', \'' . $other_role . '\')">
                <i class="fa fa-exchange"></i>
            </button>
            <button class="btn btn-xs btn-danger" title="Remover"
                    onclick="removeMember(' . $m['member_id'] . ', ' . $team_id . ')">
                <i class="fa fa-times"></i>
            </button>
        </div>
    </div>';
}
?>
