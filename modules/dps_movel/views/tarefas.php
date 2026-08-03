<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('_topo', ['titulo' => $titulo]); ?>

<?php if (empty($tarefas)) { ?>
  <div class="vazio">Não tem tarefas por fazer.</div>
<?php } else { ?>
  <?php
  $hoje = strtotime(date('Y-m-d'));
  foreach ($tarefas as $t) {
      $prazo  = $t['duedate'] ? strtotime($t['duedate']) : null;
      $atraso = $prazo && $prazo < $hoje;
      ?>
    <div class="cartao">
      <div class="linha" style="align-items:flex-start;">
        <div style="min-width:0;">
          <div class="nome"><?php echo html_escape($t['name']); ?></div>
          <div class="sub" style="<?php echo $atraso ? 'color:var(--mau);' : ''; ?>">
            <?php
            if (!$prazo) {
                echo 'Sem prazo';
            } elseif ($atraso) {
                echo 'Atrasada desde ' . date('d/m', $prazo);
            } elseif ($prazo === $hoje) {
                echo 'Para hoje';
            } else {
                echo 'Para ' . date('d/m', $prazo);
            }
            ?>
          </div>
        </div>
        <?php if ((int) $t['priority'] === 4) { ?>
          <span class="selo" style="background:var(--mau);">urgente</span>
        <?php } ?>
      </div>

      <form method="post" action="<?php echo admin_url('dps_movel/tarefa_feita/' . (int) $t['id']); ?>"
            style="margin-top:12px;"
            onsubmit="return confirm('Marcar «<?php echo html_escape(addslashes($t['name'])); ?>» como concluída?');">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <button class="btn btn-ok" type="submit">Concluída</button>
      </form>
    </div>
  <?php } ?>
<?php } ?>

<?php $this->load->view('_fundo', ['aba' => $aba]); ?>
