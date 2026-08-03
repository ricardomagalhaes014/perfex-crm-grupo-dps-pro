<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('_topo', ['titulo' => $titulo]); ?>

<p class="sub" style="margin:-4px 0 18px;">
  Olá, <?php echo html_escape(explode(' ', get_staff_full_name())[0]); ?>.
  <?php
  $n = count($tarefas);
  echo $n
      ? 'Tem <strong>' . $n . '</strong> ' . ($n === 1 ? 'tarefa aberta' : 'tarefas abertas') . '.'
      : 'Não tem tarefas abertas.';
  ?>
</p>

<?php if (!empty($reunioes)) { ?>
  <div class="titulo-seccao">A seguir</div>
  <?php foreach ($reunioes as $r) {
      $t     = strtotime($r['data_hora']);
      $hoje  = date('Y-m-d', $t) === date('Y-m-d');
      $agora = $t <= time() + 1800 && $t >= time() - 1800;
      ?>
    <div class="cartao">
      <div class="linha">
        <div style="min-width:0;">
          <div class="nome"><?php echo html_escape($r['assunto'] ?: $r['cliente_nome']); ?></div>
          <div class="sub">
            <?php echo $hoje ? 'Hoje' : date('d/m', $t); ?> às <?php echo date('H:i', $t); ?>
            <?php if ($r['cliente_nome'] && $r['assunto'] !== $r['cliente_nome']) {
                echo ' · ' . html_escape($r['cliente_nome']);
            } ?>
          </div>
        </div>
        <?php if ($agora) { ?>
          <span class="selo" style="background:var(--ok);">agora</span>
        <?php } ?>
      </div>
      <?php if (!empty($r['link'])) { ?>
        <a class="btn btn-a" style="margin-top:12px;" href="<?php echo html_escape($r['link']); ?>" target="_blank" rel="noopener">
          Entrar na sala
        </a>
      <?php } ?>
    </div>
  <?php } ?>
<?php } ?>

<div class="titulo-seccao">Tarefas</div>
<?php if (empty($tarefas)) { ?>
  <div class="cartao"><div class="sub" style="margin:0;">Nada por fazer. Bom sinal.</div></div>
<?php } else { ?>
  <?php foreach ($tarefas as $t) {
      $atraso = $t['duedate'] && strtotime($t['duedate']) < strtotime(date('Y-m-d'));
      ?>
    <a class="cartao" href="<?php echo admin_url('dps_movel/tarefas'); ?>">
      <div class="linha">
        <div style="min-width:0;">
          <div class="nome"><?php echo html_escape($t['name']); ?></div>
          <?php if ($t['duedate']) { ?>
            <div class="sub" style="<?php echo $atraso ? 'color:var(--mau);' : ''; ?>">
              <?php echo $atraso ? 'Atrasada — ' : 'Para '; ?><?php echo date('d/m', strtotime($t['duedate'])); ?>
            </div>
          <?php } ?>
        </div>
      </div>
    </a>
  <?php } ?>
<?php } ?>

<div class="titulo-seccao">As minhas leads</div>
<?php if (empty($estados)) { ?>
  <div class="cartao"><div class="sub" style="margin:0;">Ainda não tem leads.</div></div>
<?php } else { ?>
  <div>
    <?php foreach ($estados as $e) { ?>
      <a class="pilula" href="<?php echo admin_url('dps_movel/leads?estado=' . (int) $e['id']); ?>">
        <span style="width:9px;height:9px;border-radius:50%;background:<?php echo html_escape($e['color'] ?: '#7c8798'); ?>;"></span>
        <?php echo html_escape($e['name']); ?> <b><?php echo (int) $e['n']; ?></b>
      </a>
    <?php } ?>
  </div>
<?php } ?>

<?php $this->load->view('_fundo', ['aba' => $aba]); ?>
