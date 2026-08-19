<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <h4 class="no-margin">
              Propostas de crédito
              <small class="text-muted" style="font-weight:normal;">
                <?php echo count($propostas); ?> lead<?php echo count($propostas) === 1 ? '' : 's'; ?>
              </small>
            </h4>
            <p class="text-muted" style="font-size:13px;margin:6px 0 0;">
              Leads em que o comercial respondeu <strong>sim</strong> à pergunta do crédito.
              Cada uma segue por email para <strong><?php echo html_escape($parceiro); ?></strong>.
            </p>
            <hr>

            <?php if (empty($propostas)) { ?>
              <div class="alert alert-info" style="margin:0;">
                Ainda não há nenhuma. Aparecem aqui assim que um comercial responder
                &laquo;sim&raquo; na pergunta do crédito, na ficha da lead.
              </div>
            <?php } else { ?>

            <div class="table-responsive">
              <table class="table table-striped" style="font-size:13px;">
                <thead>
                  <tr>
                    <th>Lead</th>
                    <th>Contacto</th>
                    <th>Estado da lead</th>
                    <th>Comercial</th>
                    <th>Montante</th>
                    <th>Enviada ao parceiro</th>
                    <th>Respondido em</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($propostas as $p) { ?>
                  <tr>
                    <td>
                      <a href="<?php echo admin_url('leads/index/' . (int) $p->lead_id); ?>">
                        <?php echo html_escape($p->lead_nome ?: ('#' . (int) $p->lead_id)); ?>
                      </a>
                    </td>
                    <td style="white-space:nowrap;">
                      <?php
                      $tel = trim((string) ($p->lead_tel ?? ''));
                      if ($tel !== '') {
                          $wa = preg_replace('/\D+/', '', $tel);
                          if (strlen($wa) === 9 && $wa[0] === '9') { $wa = '351' . $wa; }
                          echo '<a href="tel:' . html_escape($tel) . '">' . html_escape($tel) . '</a>';
                          echo ' <a href="https://wa.me/' . html_escape($wa) . '" target="_blank" rel="noopener"'
                             . ' style="color:#25d366;margin-left:4px;" title="WhatsApp">'
                             . '<i class="fa fa-whatsapp"></i></a>';
                      }
                      if (trim((string) ($p->lead_email ?? '')) !== '') {
                          echo '<br><small class="text-muted">' . html_escape($p->lead_email) . '</small>';
                      }
                      if ($tel === '' && trim((string) ($p->lead_email ?? '')) === '') { echo '—'; }
                      ?>
                    </td>
                    <td><?php echo html_escape($p->estado_lead ?: '—'); ?></td>
                    <td><?php echo html_escape($p->quem_respondeu ?: '—'); ?></td>
                    <td style="white-space:nowrap;">
                      <?php echo $p->montante ? number_format((float) $p->montante, 0, ',', '.') . ' €' : '—'; ?>
                    </td>
                    <td style="white-space:nowrap;">
                      <?php if (!empty($p->enviado_parceiro_em)) { ?>
                        <span class="label label-success">Enviada</span>
                        <br><small class="text-muted"><?php echo html_escape($p->enviado_parceiro_em); ?></small>
                      <?php } else { ?>
                        <?php
                        /*
                         * Uma resposta "sim" anterior a este circuito nunca foi
                         * enviada — dizer "por enviar" é mais honesto do que
                         * deixar a coluna vazia, que se lê como se estivesse.
                         */
                        ?>
                        <span class="label label-default">Por enviar</span>
                      <?php } ?>
                    </td>
                    <td class="text-muted" style="white-space:nowrap;">
                      <?php echo html_escape($p->dateupdated ?: $p->dateadded); ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>

            <?php } ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
