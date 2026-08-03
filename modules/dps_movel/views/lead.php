<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$this->load->view('_topo', [
    'titulo' => $titulo,
    'voltar' => admin_url('dps_movel/leads'),
]);

$wa = dps_movel_numero_wa($lead['phonenumber']);
?>

<div class="cartao">
  <div class="nome" style="font-size:19px;"><?php echo html_escape($lead['name']); ?></div>
  <div class="sub">
    <?php echo html_escape($lead['company'] ?: 'Particular'); ?>
    <?php if (!empty($lead['email'])) { ?><br><?php echo html_escape($lead['email']); ?><?php } ?>
    <?php if (!empty($lead['phonenumber'])) { ?><br><?php echo html_escape($lead['phonenumber']); ?><?php } ?>
  </div>
</div>

<div class="dois">
  <?php if (!empty($lead['phonenumber'])) { ?>
    <a class="btn" href="tel:<?php echo html_escape(preg_replace('/[^0-9+]/', '', $lead['phonenumber'])); ?>">Ligar</a>
  <?php } else { ?>
    <span class="btn" style="opacity:.4;">Sem telefone</span>
  <?php } ?>

  <?php if ($wa !== '') { ?>
    <a class="btn btn-ok" href="https://wa.me/<?php echo $wa; ?>" target="_blank" rel="noopener">WhatsApp</a>
  <?php } else { ?>
    <span class="btn" style="opacity:.4;">Sem WhatsApp</span>
  <?php } ?>
</div>

<div class="titulo-seccao">Estado</div>
<form method="post" action="<?php echo admin_url('dps_movel/estado/' . (int) $lead['id']); ?>">
  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
  <select name="status" onchange="this.form.submit()">
    <?php foreach ($estados as $e) { ?>
      <option value="<?php echo (int) $e['id']; ?>"
        <?php echo (int) $e['id'] === (int) $lead['status'] ? 'selected' : ''; ?>>
        <?php echo html_escape($e['name']); ?>
      </option>
    <?php } ?>
  </select>
  <noscript><button class="btn" type="submit">Guardar estado</button></noscript>
</form>

<?php if (!empty($empreendimentos)) { ?>
  <div class="titulo-seccao">Enviar ao cliente</div>
  <div class="cartao">
    <select id="emp">
      <?php foreach ($empreendimentos as $slug => $e) { ?>
        <option value="<?php echo html_escape($slug); ?>"><?php echo html_escape($e['nome']); ?></option>
      <?php } ?>
    </select>
    <div class="dois" style="margin-bottom:0;">
      <button class="btn btn-ok" type="button" onclick="enviar('whatsapp')">Por WhatsApp</button>
      <button class="btn" type="button" onclick="enviar('email')">Por email</button>
    </div>
    <p class="sub" id="resultado" style="margin:12px 0 0;">
      Vai o dossier e a lista de unidades disponíveis, lida no momento.
    </p>
  </div>
<?php } ?>

<div class="titulo-seccao">Nota</div>
<form method="post" action="<?php echo admin_url('dps_movel/nota/' . (int) $lead['id']); ?>">
  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
  <textarea name="nota" placeholder="O que ficou combinado?" required></textarea>
  <button class="btn btn-a" type="submit">Guardar nota</button>
</form>

<?php if (!empty($notas)) { ?>
  <div class="titulo-seccao">Histórico</div>
  <?php foreach ($notas as $n) { ?>
    <div class="cartao" style="padding:12px 14px;">
      <div style="font-size:14.5px;"><?php echo html_escape(mb_substr($n['description'], 0, 220)); ?></div>
      <div class="sub"><?php echo date('d/m/Y H:i', strtotime($n['date'])); ?></div>
    </div>
  <?php } ?>
<?php } ?>

<script>
function enviar(canal) {
    var saida = document.getElementById('resultado');
    var botoes = document.querySelectorAll('.dois .btn');

    saida.textContent = 'A enviar...';
    botoes.forEach(function (b) { b.disabled = true; });

    var dados = new FormData();
    dados.append('lead_id', '<?php echo (int) $lead['id']; ?>');
    dados.append('empreendimento', document.getElementById('emp').value);
    dados.append('canal', canal);
    dados.append('<?php echo $this->security->get_csrf_token_name(); ?>',
                 '<?php echo $this->security->get_csrf_hash(); ?>');

    fetch('<?php echo admin_url('dps_propostas/enviar_info'); ?>', {
        method: 'POST', body: dados, credentials: 'same-origin'
    })
    .then(function (r) { return r.json(); })
    .then(function (r) {
        saida.textContent = r.message || (r.success ? 'Enviado.' : 'Não foi possível enviar.');
        saida.style.color = r.success ? 'var(--ok)' : 'var(--mau)';
    })
    .catch(function () {
        // Sem isto o comercial ficava com "A enviar..." para sempre e não sabia
        // se o cliente tinha recebido ou não.
        saida.textContent = 'Não consegui falar com o servidor. Verifique a rede e tente outra vez.';
        saida.style.color = 'var(--mau)';
    })
    .finally(function () { botoes.forEach(function (b) { b.disabled = false; }); });
}
</script>

<?php $this->load->view('_fundo', ['aba' => $aba]); ?>
