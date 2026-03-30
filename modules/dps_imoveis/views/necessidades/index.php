<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">
    <div class="row">
      <div class="col-md-12">

        <!-- Tabs de navegação do módulo -->
        <ul class="nav nav-tabs tw-mb-4">
          <li>
            <a href="<?php echo admin_url('dps_imoveis'); ?>">
              <i class="fa fa-home"></i> Imóveis
            </a>
          </li>
          <li class="active">
            <a href="<?php echo admin_url('dps_imoveis/necessidades'); ?>">
              <i class="fa fa-search"></i> Necessidades
            </a>
          </li>
        </ul>

        <!-- Cabeçalho com botão Nova Necessidade -->
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
          <div>
            <h4 class="tw-font-semibold tw-mb-1">Necessidades de Clientes</h4>
            <p class="tw-text-neutral-500 tw-text-sm">Registe o que os seus clientes procuram. Uso interno — não publicado no site.</p>
          </div>
          <a href="<?php echo admin_url('dps_imoveis/nova_necessidade'); ?>" class="btn btn-primary">
            <i class="fa fa-plus"></i> Nova Necessidade
          </a>
        </div>

        <!-- Tabela de necessidades -->
        <div class="panel_s">
          <div class="panel-body">
            <?php if (empty($necessidades)): ?>
              <div class="text-center tw-py-12">
                <i class="fa fa-search fa-3x tw-text-neutral-300 tw-mb-3"></i>
                <p class="tw-text-neutral-500">Ainda não existem necessidades registadas.</p>
                <a href="<?php echo admin_url('dps_imoveis/nova_necessidade'); ?>" class="btn btn-primary tw-mt-2">
                  <i class="fa fa-plus"></i> Registar primeira necessidade
                </a>
              </div>
            <?php else: ?>
              <table class="table table-hover dt-table" id="tbl-necessidades">
                <thead>
                  <tr>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Tipo / Tipologia</th>
                    <th>Localização</th>
                    <th>Preço</th>
                    <th>Urgência</th>
                    <th>Mediador</th>
                    <th>Data</th>
                    <th class="not-export">Acções</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($necessidades as $n): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($n['nome_cliente']); ?></strong>
                      <?php if ($n['email_cliente']): ?>
                        <br><small class="tw-text-neutral-500"><?php echo htmlspecialchars($n['email_cliente']); ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?php echo $n['contacto_cliente'] ? htmlspecialchars($n['contacto_cliente']) : '<span class="tw-text-neutral-400">—</span>'; ?></td>
                    <td>
                      <?php if ($n['tipo']): ?>
                        <span class="label label-default"><?php echo htmlspecialchars($n['tipo']); ?></span>
                      <?php endif; ?>
                      <?php if ($n['tipologia']): ?>
                        <span class="label label-info"><?php echo htmlspecialchars($n['tipologia']); ?></span>
                      <?php endif; ?>
                      <?php if (!$n['tipo'] && !$n['tipologia']): ?>
                        <span class="tw-text-neutral-400">Qualquer</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php echo $n['distrito'] ? htmlspecialchars($n['distrito']) : ''; ?>
                      <?php if ($n['cidade']): ?>
                        <br><small><?php echo htmlspecialchars($n['cidade']); ?></small>
                      <?php endif; ?>
                      <?php if (!$n['distrito'] && !$n['cidade']): ?>
                        <span class="tw-text-neutral-400">Qualquer</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($n['preco_min'] || $n['preco_max']): ?>
                        <?php echo $n['preco_min'] ? number_format($n['preco_min'], 0, ',', '.') . ' €' : '0 €'; ?>
                        —
                        <?php echo $n['preco_max'] ? number_format($n['preco_max'], 0, ',', '.') . ' €' : 'Sem limite'; ?>
                      <?php else: ?>
                        <span class="tw-text-neutral-400">Qualquer</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php
                      $urgencia_labels = ['urgente' => 'danger', 'normal' => 'primary', 'sem_pressa' => 'default'];
                      $urgencia_texto = ['urgente' => 'Urgente', 'normal' => 'Normal', 'sem_pressa' => 'Sem pressa'];
                      $urg = $n['urgencia'] ?: 'normal';
                      ?>
                      <span class="label label-<?php echo $urgencia_labels[$urg]; ?>">
                        <?php echo $urgencia_texto[$urg]; ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars($n['staff_nome'] ?? '—'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($n['data_criacao'])); ?></td>
                    <td>
                      <div class="tw-flex tw-gap-1">
                        <a href="<?php echo admin_url('dps_imoveis/editar_necessidade/' . $n['id']); ?>"
                           class="btn btn-xs btn-default" title="Editar">
                          <i class="fa fa-pencil"></i>
                        </a>
                        <?php if (is_admin()): ?>
                        <a href="<?php echo admin_url('dps_imoveis/apagar_necessidade/' . $n['id']); ?>"
                           class="btn btn-xs btn-danger _delete" title="Apagar"
                           data-confirm="Tem a certeza que quer apagar esta necessidade?">
                          <i class="fa fa-trash"></i>
                        </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</div>
</div>
<?php init_tail(); ?>
<script>
$(document).ready(function() {
  if ($('#tbl-necessidades').length) {
    $('#tbl-necessidades').DataTable({
      language: { url: '<?php echo base_url(); ?>assets/plugins/datatables/i18n/Portuguese.json' },
      order: [[7, 'desc']],
      columnDefs: [{ orderable: false, targets: 'not-export' }]
    });
  }

  // Confirmar apagar
  $(document).on('click', '._delete', function(e) {
    e.preventDefault();
    var url = $(this).attr('href');
    var msg = $(this).data('confirm') || 'Tem a certeza?';
    if (confirm(msg)) {
      window.location.href = url;
    }
  });
});
</script>
