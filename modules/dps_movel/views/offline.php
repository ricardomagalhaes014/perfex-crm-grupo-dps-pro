<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('_topo', ['titulo' => $titulo]); ?>

<div class="vazio" style="padding-top:60px;">
  <div style="font-size:44px;margin-bottom:14px;">◌</div>
  <p style="color:var(--texto);font-weight:600;font-size:17px;margin:0 0 8px;">
    Sem ligação à rede
  </p>
  <p style="margin:0 0 24px;">
    A app não guarda leads no telemóvel de propósito — mais valia nada do que
    mostrar-lhe números de ontem.
  </p>
  <button class="btn btn-a" onclick="location.reload()" style="max-width:240px;margin:0 auto;">
    Tentar outra vez
  </button>
</div>

<?php $this->load->view('_fundo', ['aba' => $aba]); ?>
