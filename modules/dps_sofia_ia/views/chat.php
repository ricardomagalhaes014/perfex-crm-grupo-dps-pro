<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-9">
        <div class="panel_s">
          <div class="panel-body" style="padding:0;">
            <div style="padding:14px 16px; border-bottom:1px solid #e2e5ea; display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h4 class="no-margin">A Sofia responde</h4>
                <span class="text-muted" style="font-size:12px;">
                  <?php if ($modo === 'local') { ?>
                  Modo procura: mostra o que está escrito na base de conhecimento. Quando não encontra, pergunta.
                  <?php } else { ?>
                  Responde a partir do que a administração lhe ensinou. Quando não sabe, pergunta.
                  <?php } ?>
                </span>
              </div>
              <a href="<?= admin_url('dps_sofia_ia/nova_conversa'); ?>" class="btn btn-default btn-sm">Nova conversa</a>
            </div>

            <?php if (!$pronta) { ?>
            <div class="alert alert-warning" style="margin:14px;">
              A Sofia ainda não tem chave de API configurada, por isso não consegue responder.
              <?php if (is_admin()) { ?>
              Escreva-a em <a href="<?= admin_url('dps_sofia_ia/definicoes'); ?>">Definições</a>.
              <?php } else { ?>
              Fale com um administrador.
              <?php } ?>
            </div>
            <?php } ?>

            <div style="height:calc(100vh - 300px); min-height:420px;">
              <?php $this->load->view('dps_sofia_ia/partials/caixa', [
                  'mensagens' => $mensagens,
                  'pronta'    => $pronta,
              ]); ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-margin">Como tirar mais partido</h5>
            <hr>
            <?php if ($modo === 'local') { ?>
            <p class="text-muted" style="font-size:13px;">
              Neste modo a Sofia procura pelas <strong>palavras</strong> que escrever, e mostra
              os textos onde elas aparecem. Use os termos que estarão escritos na ficha —
              <em>"preço T2 Boavista"</em> funciona melhor do que <em>"o cliente acha caro"</em>.
            </p>
            <?php } else { ?>
            <p class="text-muted" style="font-size:13px;">
              Pergunte como falaria com um colega: <em>"quanto custa o T2 no Boavista?"</em>,
              <em>"o cliente diz que está caro, o que respondo?"</em>,
              <em>"que documentos preciso para o CPCV?"</em>
            </p>
            <?php } ?>
            <p class="text-muted" style="font-size:13px;">
              Se a resposta estiver errada, use o <strong>"Esta resposta está errada"</strong> por baixo dela.
              Vai directo à administração para ser corrigido — e a Sofia deixa de repetir o erro.
            </p>
            <p class="text-muted" style="font-size:13px;">
              Se a Sofia não souber, a pergunta segue sozinha para a administração e é avisado
              quando houver resposta.
            </p>
          </div>
        </div>

        <?php if (dps_sofia_ia_pode_gerir()) {
            $por_responder = dps_sofia_ia_contar_pendentes(); ?>
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-margin">Administração</h5>
            <hr>
            <a href="<?= admin_url('dps_sofia_ia/pendentes'); ?>" class="btn btn-<?= $por_responder > 0 ? 'warning' : 'default'; ?> btn-block btn-sm">
              Por responder<?= $por_responder > 0 ? ' (' . $por_responder . ')' : ''; ?>
            </a>
            <a href="<?= admin_url('dps_sofia_ia/conhecimento'); ?>" class="btn btn-default btn-block btn-sm">
              Base de conhecimento
            </a>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('dps_sofia_ia/partials/assets'); ?>
<?php init_tail(); ?>
