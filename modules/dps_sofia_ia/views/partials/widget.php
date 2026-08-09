<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/*
 * Gaveta flutuante, injectada no rodapé de todas as páginas do CRM.
 *
 * Abre vazia de propósito: carregar o histórico aqui era uma consulta à base de
 * dados em cada página do CRM, aberta ou não. As mensagens ficam gravadas na
 * mesma conversa, por isso o histórico completo está a um clique, na página.
 */
$pronta = dps_sofia_ia_esta_pronta();
?>

<?php $this->load->view('dps_sofia_ia/partials/assets'); ?>

<button type="button" id="sofia-botao-flutuante">
    <i class="fa fa-comments-o"></i> &nbsp;A Sofia responde
</button>

<div id="sofia-gaveta">
    <div class="sofia-topo">
        <span>A Sofia responde</span>
        <span>
            <a href="<?= admin_url('dps_sofia_ia'); ?>" title="Abrir a conversa completa">Ver tudo</a>
            &nbsp;&nbsp;<a id="sofia-fechar" title="Fechar">&times;</a>
        </span>
    </div>
    <?php $this->load->view('dps_sofia_ia/partials/caixa', ['mensagens' => [], 'pronta' => $pronta]); ?>
</div>

<script>
(function () {
    var botao  = document.getElementById('sofia-botao-flutuante');
    var gaveta = document.getElementById('sofia-gaveta');
    var fechar = document.getElementById('sofia-fechar');

    function abrir() {
        gaveta.classList.add('sofia-aberta');
        botao.style.display = 'none';
        var campo = gaveta.querySelector('[data-sofia-input]');
        if (campo && !campo.disabled) { campo.focus(); }
    }

    function esconder() {
        gaveta.classList.remove('sofia-aberta');
        botao.style.display = '';
    }

    botao.addEventListener('click', abrir);
    fechar.addEventListener('click', esconder);

    // Escape fecha, como em qualquer outra caixa do CRM.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && gaveta.classList.contains('sofia-aberta')) { esconder(); }
    });
})();
</script>
