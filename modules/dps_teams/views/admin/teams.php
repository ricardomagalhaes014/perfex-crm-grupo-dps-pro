<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <!-- Cabeçalho -->
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
                    <div>
                        <h3 class="tw-text-2xl tw-font-bold tw-text-gray-800">
                            <i class="fa fa-users tw-mr-2 tw-text-blue-600"></i>
                            Gestão de Equipas DPS
                        </h3>
                        <p class="tw-text-gray-500 tw-mt-1">
                            Organiza os comerciais e gestores pelas 3 áreas de negócio.
                            <strong>Tu (Ricardo Magalhães)</strong> tens visibilidade total sobre todas as equipas.
                        </p>
                    </div>
                </div>

                <!-- Hierarquia de níveis -->
                <div class="panel panel-default tw-mb-6">
                    <div class="panel-body">
                        <div class="tw-flex tw-flex-wrap tw-gap-4">
                            <div class="tw-flex tw-items-center tw-gap-2 tw-bg-purple-50 tw-border tw-border-purple-200 tw-rounded-lg tw-px-4 tw-py-3">
                                <i class="fa fa-crown tw-text-purple-600 tw-text-lg"></i>
                                <div>
                                    <div class="tw-font-bold tw-text-purple-700 tw-text-sm">Super Admin</div>
                                    <div class="tw-text-xs tw-text-gray-500">Vê tudo — todas as equipas e leads</div>
                                </div>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1 tw-text-gray-400 tw-text-xl">→</div>
                            <div class="tw-flex tw-items-center tw-gap-2 tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-px-4 tw-py-3">
                                <i class="fa fa-user-tie tw-text-blue-600 tw-text-lg"></i>
                                <div>
                                    <div class="tw-font-bold tw-text-blue-700 tw-text-sm">Gestor de Equipa</div>
                                    <div class="tw-text-xs tw-text-gray-500">Vê leads de toda a sua equipa</div>
                                </div>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1 tw-text-gray-400 tw-text-xl">→</div>
                            <div class="tw-flex tw-items-center tw-gap-2 tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-px-4 tw-py-3">
                                <i class="fa fa-user tw-text-green-600 tw-text-lg"></i>
                                <div>
                                    <div class="tw-font-bold tw-text-green-700 tw-text-sm">Comercial</div>
                                    <div class="tw-text-xs tw-text-gray-500">Só vê as suas próprias leads</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cards das equipas -->
                <div class="row" id="teams-container">
                    <?php foreach ($teams as $team): ?>
                    <?php
                        $area_colors = [
                            'dental' => ['bg' => 'tw-bg-rose-50',   'border' => 'tw-border-rose-300',   'icon_color' => 'tw-text-rose-600',   'badge' => 'tw-bg-rose-100 tw-text-rose-700',   'icon' => 'fa-tooth'],
                            'imo'    => ['bg' => 'tw-bg-amber-50',  'border' => 'tw-border-amber-300',  'icon_color' => 'tw-text-amber-600',  'badge' => 'tw-bg-amber-100 tw-text-amber-700',  'icon' => 'fa-building'],
                            'media'  => ['bg' => 'tw-bg-sky-50',    'border' => 'tw-border-sky-300',    'icon_color' => 'tw-text-sky-600',    'badge' => 'tw-bg-sky-100 tw-text-sky-700',    'icon' => 'fa-bullhorn'],
                        ];
                        $area_labels = ['dental' => 'Dentária', 'imo' => 'Imobiliário', 'media' => 'Media'];
                        $c = $area_colors[$team['area']] ?? $area_colors['media'];
                        $area_label = $area_labels[$team['area']] ?? $team['area'];
                        $managers    = array_filter($team['members'], fn($m) => $m['role'] === 'manager');
                        $commercials = array_filter($team['members'], fn($m) => $m['role'] === 'commercial');
                    ?>
                    <div class="col-md-4" id="team-card-<?= $team['id'] ?>">
                        <div class="panel panel-default <?= $c['bg'] ?> <?= $c['border'] ?> tw-border tw-rounded-xl tw-shadow-sm">
                            <div class="panel-heading tw-rounded-t-xl tw-border-b <?= $c['border'] ?> tw-bg-transparent">
                                <div class="tw-flex tw-items-center tw-justify-between">
                                    <div class="tw-flex tw-items-center tw-gap-2">
                                        <i class="fa <?= $c['icon'] ?> <?= $c['icon_color'] ?> tw-text-xl"></i>
                                        <h4 class="panel-title tw-font-bold tw-text-gray-800 tw-text-base">
                                            <?= e($team['name']) ?>
                                        </h4>
                                    </div>
                                    <span class="tw-text-xs tw-font-semibold tw-px-2 tw-py-1 tw-rounded-full <?= $c['badge'] ?>">
                                        <?= $area_label ?>
                                    </span>
                                </div>
                            </div>
                            <div class="panel-body">

                                <!-- Gestores -->
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
                                        <p class="tw-text-xs tw-text-gray-400 tw-italic" id="no-managers-<?= $team['id'] ?>">
                                            Nenhum gestor definido
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <hr class="tw-my-3">

                                <!-- Comerciais -->
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
                                    <div id="commercials-<?= $team['id'] ?>">
                                        <?php foreach ($commercials as $m): ?>
                                        <?= render_member_row($m, $team['id']) ?>
                                        <?php endforeach; ?>
                                        <?php if (empty($commercials)): ?>
                                        <p class="tw-text-xs tw-text-gray-400 tw-italic" id="no-commercials-<?= $team['id'] ?>">
                                            Nenhum comercial definido
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

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
                <input type="hidden" id="modal_team_id" value="">
                <input type="hidden" id="modal_role" value="">

                <div class="form-group">
                    <label class="control-label">Seleccionar Utilizador</label>
                    <select class="form-control selectpicker" id="modal_staff_id" data-live-search="true"
                            data-none-selected-text="-- Escolhe um utilizador --">
                    </select>
                </div>

                <div class="form-group">
                    <label class="control-label">Papel na Equipa</label>
                    <select class="form-control" id="modal_role_select">
                        <option value="manager">Gestor de Equipa</option>
                        <option value="commercial">Comercial</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnAddMember">
                    <i class="fa fa-plus tw-mr-1"></i> Adicionar
                </button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
var DPS_TEAMS_URL = '<?= admin_url('dps_teams') ?>';

// Abrir modal de adicionar membro
function openAddMemberModal(teamId, defaultRole) {
    $('#modal_team_id').val(teamId);
    $('#modal_role').val(defaultRole);
    $('#modal_role_select').val(defaultRole);
    $('#modal_staff_id').empty();
    $('#addMemberModalTitle').text(defaultRole === 'manager' ? 'Adicionar Gestor' : 'Adicionar Comercial');

    // Carregar staff disponível via AJAX
    $.get(DPS_TEAMS_URL + '/available_staff', { team_id: teamId }, function(data) {
        var select = $('#modal_staff_id');
        select.empty();
        if (data.length === 0) {
            select.append('<option value="">Todos os utilizadores já estão na equipa</option>');
        } else {
            $.each(data, function(i, s) {
                select.append('<option value="' + s.staffid + '">' + s.full_name + ' (' + s.email + ')</option>');
            });
        }
        select.selectpicker('refresh');
        $('#addMemberModal').modal('show');
    }, 'json');
}

// Confirmar adição
$('#btnAddMember').on('click', function() {
    var teamId   = $('#modal_team_id').val();
    var staffId  = $('#modal_staff_id').val();
    var role     = $('#modal_role_select').val();

    if (!staffId) {
        alert('Por favor selecciona um utilizador.');
        return;
    }

    $.post(DPS_TEAMS_URL + '/add_member', {
        team_id:  teamId,
        staff_id: staffId,
        role:     role
    }, function(resp) {
        if (resp.success) {
            $('#addMemberModal').modal('hide');
            refreshTeamCard(teamId, resp.members);
            alert_float('success', 'Membro adicionado com sucesso!');
        } else {
            alert_float('danger', resp.message || 'Erro ao adicionar membro.');
        }
    }, 'json');
});

// Remover membro
function removeMember(memberId, teamId) {
    if (!confirm('Tens a certeza que queres remover este membro da equipa?')) return;

    $.post(DPS_TEAMS_URL + '/remove_member', {
        member_id: memberId,
        team_id:   teamId
    }, function(resp) {
        if (resp.success) {
            refreshTeamCard(teamId, resp.members);
            alert_float('success', 'Membro removido.');
        } else {
            alert_float('danger', resp.message || 'Erro ao remover membro.');
        }
    }, 'json');
}

// Alterar papel
function changeMemberRole(memberId, teamId, newRole) {
    $.post(DPS_TEAMS_URL + '/change_role', {
        member_id: memberId,
        team_id:   teamId,
        role:      newRole
    }, function(resp) {
        if (resp.success) {
            refreshTeamCard(teamId, resp.members);
            alert_float('success', 'Papel actualizado.');
        } else {
            alert_float('danger', resp.message || 'Erro ao alterar papel.');
        }
    }, 'json');
}

// Re-renderizar os membros de uma equipa após operação AJAX
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
            var otherRole = m.role === 'manager' ? 'commercial' : 'manager';
            var otherLabel = m.role === 'manager' ? 'Tornar Comercial' : 'Tornar Gestor';

            html += '<div class="tw-flex tw-items-center tw-justify-between tw-py-1 tw-border-b tw-border-gray-100 tw-last:border-0">';
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

<?php
// Função auxiliar PHP para renderizar linha de membro
function render_member_row($m, $team_id) {
    $role_label = $m['role'] === 'manager'
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
