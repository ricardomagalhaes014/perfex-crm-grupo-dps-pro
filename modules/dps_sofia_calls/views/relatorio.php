<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div style="display:flex;align-items:baseline;gap:14px;flex-wrap:wrap;">
              <h4 class="no-margin"><i class="fa fa-phone"></i> Resultados das chamadas</h4>
              <span class="text-muted"><?= (int) $total; ?> chamadas feitas</span>
              <form method="get" action="<?= admin_url('dps_sofia_calls/relatorio'); ?>"
                    style="margin-left:auto;display:flex;align-items:center;gap:8px;">
                <label style="margin:0;font-size:13px;color:#5a6673;">Campanha:</label>
                <select name="campanha" class="selectpicker" data-width="260px" onchange="this.form.submit()">
                  <option value="0">Todas</option>
                  <?php foreach ($campanhas as $c) { ?>
                  <option value="<?= (int) $c['id']; ?>"<?= (int) $campanha === (int) $c['id'] ? ' selected' : ''; ?>>
                    <?= e($c['name']); ?>
                  </option>
                  <?php } ?>
                </select>
              </form>
            </div>
            <hr>

            <?php
            /*
             * Os três números que interessam. "Por responder" são as que ainda
             * não voltaram do ElevenLabs — ficam à parte para não inflacionarem
             * os "não", que é o erro fácil de ler num quadro destes.
             */
            $caixas = [
                ['sim',          'Disseram SIM',    '#1e7e34'],
                ['nao',          'Disseram não',    '#c0392b'],
                ['nao_atendida', 'Não atenderam',   '#6c757d'],
                ['',             'Por responder',   '#b8860b'],
            ];
            ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:22px;">
              <?php foreach ($caixas as [$chave, $rotulo, $cor]) {
                  $n  = (int) ($contagem[$chave] ?? 0);
                  $pc = $total > 0 ? round($n * 100 / $total, 1) : 0;
              ?>
              <div style="border:1px solid #e6eaef;border-radius:10px;padding:14px 16px;background:#fff;">
                <div style="font-size:1.9rem;font-weight:700;color:<?= $cor; ?>;line-height:1;"><?= $n; ?></div>
                <div style="color:#5a6673;font-size:.85rem;margin-top:4px;"><?= $rotulo; ?></div>
                <div style="color:#98a2b3;font-size:.78rem;"><?= $pc; ?>%</div>
              </div>
              <?php } ?>
            </div>

            <?php if (empty($linhas)) { ?>
              <p class="text-muted">Ainda não há chamadas com resultado.</p>
            <?php } else { ?>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Lead</th><th>Telefone</th><th>Campanha</th>
                    <th>Resultado</th><th>Quando</th><th>Resumo da conversa</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($linhas as $l) {
                    $r = (string) $l['resultado'];
                    $etiqueta = [
                        'sim'          => ['Sim',            'label-success'],
                        'nao'          => ['Não',            'label-danger'],
                        'nao_atendida' => ['Não atendeu',    'label-default'],
                    ][$r] ?? ['Por responder', 'label-warning'];
                ?>
                  <tr>
                    <td>
                      <?php if (! empty($l['lead_id'])) { ?>
                        <a href="<?= admin_url('leads/index/' . (int) $l['lead_id']); ?>"><?= e($l['lead_name'] ?: 'Lead #' . $l['lead_id']); ?></a>
                      <?php } else { ?><?= e($l['lead_name'] ?: '—'); ?><?php } ?>
                    </td>
                    <td><?= e($l['phone_number']); ?></td>
                    <td class="text-muted"><?= e($l['campanha'] ?: '—'); ?></td>
                    <td><span class="label <?= $etiqueta[1]; ?>"><?= $etiqueta[0]; ?></span></td>
                    <td class="text-muted" style="font-size:12px;white-space:nowrap;"><?= e($l['started_at'] ?: '—'); ?></td>
                    <td style="font-size:12px;max-width:520px;"><?= e(mb_substr((string) $l['resumo'], 0, 260)); ?></td>
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
