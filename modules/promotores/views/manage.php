<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons mbottom15">
                            <?php if (staff_can('create', 'promotores') || is_admin()) { ?>
                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#promotor_modal">
                                <i class="fa fa-plus"></i> Novo promotor
                            </button>
                            <?php } ?>
                            <?php if (staff_can('create', 'promotores') || staff_can('import', 'promotores') || is_admin()) { ?>
                            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#import_modal">
                                <i class="fa fa-upload"></i> Importar CSV
                            </button>
                            <a href="<?= admin_url('promotores/seed'); ?>" class="btn btn-success">
                                <i class="fa fa-database"></i> Carregar base inicial
                            </a>
                            <?php } ?>
                        </div>

                        <div class="row mtop15">
                            <div class="col-md-3">
                                <label>Filtrar por distrito</label>
                                <select id="filter-district" class="selectpicker" data-width="100%" data-none-selected-text="Todos os distritos">
                                    <option value="">Todos</option>
                                    <?php foreach ($districts as $district) { ?>
                                    <option value="<?= e($district); ?>"><?= e($district); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Filtrar por fase</label>
                                <select id="filter-phase" class="selectpicker" data-width="100%" data-none-selected-text="Todas as fases">
                                    <option value="">Todas</option>
                                    <?php foreach ($contact_phases as $phase) { ?>
                                    <option value="<?= e($phase); ?>"><?= e($phase); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <hr class="hr-panel-heading" />

                        <div class="table-responsive">
                            <table class="table table-promotores dt-table" data-order-col="0" data-order-type="desc">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Empresa</th>
                                        <th>Distrito</th>
                                        <th>Cidade</th>
                                        <th>Fase</th>
                                        <th>Telefone</th>
                                        <th>Email</th>
                                        <th>Website</th>
                                        <th>Atribuído</th>
                                        <th>Último contacto</th>
                                        <th>Opções</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($promotores as $p) { ?>
                                    <tr>
                                        <td><?= (int) $p['id']; ?></td>
                                        <td><?= e($p['name']); ?></td>
                                        <td><?= e($p['company']); ?></td>
                                        <td><?= e($p['state']); ?></td>
                                        <td><?= e($p['city']); ?></td>
                                        <td><span class="label label-default"><?= e($p['phase']); ?></span></td>
                                        <td><?= e($p['phone']); ?></td>
                                        <td><?= e($p['email']); ?></td>
                                        <td>
                                            <?php if (!empty($p['website'])) { ?>
                                            <a href="<?= prep_url($p['website']); ?>" target="_blank"><?= e($p['website']); ?></a>
                                            <?php } ?>
                                        </td>
                                        <td><?= !empty($p['assigned']) ? get_staff_full_name($p['assigned']) : '-'; ?></td>
                                        <td><?= !empty($p['lastcontact']) ? _dt($p['lastcontact']) : '-'; ?></td>
                                        <td>
                                            <?php if (staff_can('edit', 'promotores') || is_admin()) { ?>
                                            <a href="#" onclick='editPromotor(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>); return false;'>Editar</a>
                                            <?php } ?>
                                            <?php if ((staff_can('edit', 'promotores') || is_admin()) && (staff_can('delete', 'promotores') || is_admin())) { ?> |
                                            <?php } ?>
                                            <?php if (staff_can('delete', 'promotores') || is_admin()) { ?>
                                            <a href="<?= admin_url('promotores/delete/' . $p['id']); ?>" class="_delete text-danger">Apagar</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="promotor_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <?= form_open(admin_url('promotores/save'), ['id' => 'promotor-form']); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><span class="add-title">Novo promotor</span><span class="edit-title hide">Editar promotor</span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6"><?php echo render_input('name', 'Nome'); ?></div>
                    <div class="col-md-6"><?php echo render_input('company', 'Empresa'); ?></div>
                    <div class="col-md-4"><?php echo render_select('phase', array_map(function ($s) { return ['id' => $s, 'name' => $s]; }, $contact_phases), ['id', 'name'], 'Fase de contacto'); ?></div>
                    <div class="col-md-4"><?php echo render_input('phone', 'Telefone'); ?></div>
                    <div class="col-md-4"><?php echo render_input('email', 'Email', '', 'email'); ?></div>
                    <div class="col-md-6"><?php echo render_input('website', 'Website'); ?></div>
                    <div class="col-md-6"><?php echo render_select('assigned', $staff_members, ['staffid', ['firstname', 'lastname']], 'Atribuído a'); ?></div>
                    <div class="col-md-6"><?php echo render_input('city', 'Cidade'); ?></div>
                    <div class="col-md-6"><?php echo render_input('state', 'Distrito'); ?></div>
                    <div class="col-md-6"><?php echo render_input('zip', 'Código postal'); ?></div>
                    <div class="col-md-6"><?php echo render_datetime_input('lastcontact', 'Último contacto'); ?></div>
                    <div class="col-md-12"><?php echo render_textarea('address', 'Morada'); ?></div>
                    <div class="col-md-12"><?php echo render_textarea('notes', 'Notas'); ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                <button type="submit" class="btn btn-info">Guardar</button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<div class="modal fade" id="import_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= form_open_multipart(admin_url('promotores/import')); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Importar CSV</h4>
            </div>
            <div class="modal-body">
                <p>Colunas aceites: <code>name/nome, company/empresa, phase/fase, phone/telefone, email, website/site, address/morada, city/cidade, state/distrito, zip/codigo_postal, notes/notas, lastcontact/ultimo_contacto</code></p>
                <input type="file" name="csv_file" accept=".csv" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                <button type="submit" class="btn btn-info">Importar</button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script>
$(function() {
    var table = initDataTable('.table-promotores');
    $('#phase').selectpicker('val', 'Não contactado');

    $.fn.dataTable.ext.search.push(function(settings, data) {
        if (!$(settings.nTable).hasClass('table-promotores')) {
            return true;
        }

        var selectedDistrict = $('#filter-district').val() || '';
        var selectedPhase = $('#filter-phase').val() || '';
        var rowDistrict = data[3] || '';
        var rowPhase = data[5] || '';

        if (selectedDistrict && rowDistrict !== selectedDistrict) {
            return false;
        }
        if (selectedPhase && rowPhase !== selectedPhase) {
            return false;
        }
        return true;
    });

    $('#filter-district, #filter-phase').on('changed.bs.select', function() {
        $('.table-promotores').DataTable().draw();
    });
});

function editPromotor(data) {
    $('#promotor-form').attr('action', admin_url + 'promotores/save/' + data.id);
    $('.add-title').addClass('hide');
    $('.edit-title').removeClass('hide');
    $('#name').val(data.name);
    $('#company').val(data.company);
    $('#phase').selectpicker('val', data.phase);
    $('#phone').val(data.phone);
    $('#email').val(data.email);
    $('#website').val(data.website);
    $('#assigned').selectpicker('val', data.assigned);
    $('#city').val(data.city);
    $('#state').val(data.state);
    $('#zip').val(data.zip);
    $('#address').val(data.address);
    $('#notes').val(data.notes);
    $('#lastcontact').val(data.lastcontact || '');
    $('#promotor_modal').modal('show');
}

$('#promotor_modal').on('hidden.bs.modal', function () {
    $('#promotor-form').attr('action', admin_url + 'promotores/save');
    $('#promotor-form')[0].reset();
    $('.edit-title').addClass('hide');
    $('.add-title').removeClass('hide');
    $('.selectpicker').selectpicker('refresh');
    $('#phase').selectpicker('val', 'Não contactado');
});
</script>
<?php init_tail(); ?>
</body>
</html>
