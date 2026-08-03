<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('_topo', ['titulo' => $titulo]); ?>

<form method="get" action="<?php echo admin_url('dps_movel/leads'); ?>">
  <input type="search" name="q" placeholder="Nome, telefone ou email"
         value="<?php echo html_escape($procura); ?>" enterkeyhint="search">
  <?php if ($estado) { ?>
    <input type="hidden" name="estado" value="<?php echo (int) $estado; ?>">
  <?php } ?>
</form>

<div style="overflow-x:auto;white-space:nowrap;margin:0 -16px 4px;padding:0 16px;">
  <a class="pilula <?php echo $estado ? '' : 'on'; ?>"
     href="<?php echo admin_url('dps_movel/leads' . ($procura !== '' ? '?q=' . urlencode($procura) : '')); ?>">
    Todas
  </a>
  <?php foreach ($estados as $e) { ?>
    <a class="pilula <?php echo (int) $e['id'] === $estado ? 'on' : ''; ?>"
       href="<?php echo admin_url('dps_movel/leads?estado=' . (int) $e['id']
             . ($procura !== '' ? '&q=' . urlencode($procura) : '')); ?>">
      <?php echo html_escape($e['name']); ?> <b><?php echo (int) $e['n']; ?></b>
    </a>
  <?php } ?>
</div>

<?php if (empty($leads)) { ?>
  <div class="vazio">
    <?php echo $procura !== ''
        ? 'Nada encontrado para «' . html_escape($procura) . '».'
        : 'Nenhuma lead neste estado.'; ?>
  </div>
<?php } else { ?>
  <?php foreach ($leads as $l) { ?>
    <a class="cartao" href="<?php echo admin_url('dps_movel/lead/' . (int) $l['id']); ?>">
      <div class="linha">
        <div style="min-width:0;">
          <div class="nome"><?php echo html_escape($l['name']); ?></div>
          <div class="sub">
            <?php echo html_escape($l['phonenumber'] ?: ($l['email'] ?: 'sem contacto')); ?>
            <?php if ($l['lastcontact']) { ?>
              · último contacto <?php echo date('d/m', strtotime($l['lastcontact'])); ?>
            <?php } ?>
          </div>
        </div>
        <span class="selo" style="background:<?php echo html_escape(dps_movel_cor_estado($l['status'])); ?>;">
          <?php
          $nome_estado = '';
          foreach ($estados as $e) {
              if ((int) $e['id'] === (int) $l['status']) {
                  $nome_estado = $e['name'];
              }
          }
          echo html_escape(mb_substr($nome_estado, 0, 14));
          ?>
        </span>
      </div>
    </a>
  <?php } ?>

  <?php if (count($leads) >= 60) { ?>
    <p class="sub" style="text-align:center;margin-top:14px;">
      A mostrar as 60 mais recentes. Use a pesquisa para chegar às outras.
    </p>
  <?php } ?>
<?php } ?>

<?php $this->load->view('_fundo', ['aba' => $aba]); ?>
