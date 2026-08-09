<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/*
 * A caixa de conversa. Usada tal e qual na página e na gaveta flutuante.
 *
 * $mensagens  — array de mensagens já trocadas (opcional)
 * $pronta     — se false, a Sofia não tem chave e o campo aparece bloqueado
 */
$mensagens = isset($mensagens) ? $mensagens : [];
$pronta    = isset($pronta) ? $pronta : true;
?>
<div class="sofia-caixa" data-sofia-caixa>
    <div class="sofia-mensagens" data-sofia-mensagens>
        <?php if (empty($mensagens)) { ?>
        <div class="sofia-linha sofia-de-sofia">
            <div class="sofia-balao">
                Olá! Sou a Sofia. Pergunte-me o que precisar sobre os empreendimentos,
                preços, condições de pagamento, o processo de venda ou o que dizer a um cliente.
            </div>
        </div>
        <?php } ?>

        <?php foreach ($mensagens as $mensagem) { ?>
        <div class="sofia-linha sofia-de-<?= $mensagem['papel'] === 'comercial' ? 'comercial' : 'sofia'; ?>"
             <?php if ($mensagem['papel'] === 'sofia' && !$mensagem['sem_resposta']) { ?>data-sofia-mensagem-id="<?= (int) $mensagem['id']; ?>"<?php } ?>>
            <div class="sofia-balao">
                <?= nl2br(e($mensagem['texto'])); ?>

                <?php if ($mensagem['papel'] === 'sofia' && !empty($mensagem['fontes'])) { ?>
                <div class="sofia-fontes">Fonte: <?= e(str_replace(' | ', ' · ', $mensagem['fontes'])); ?></div>
                <?php } ?>

                <?php if ($mensagem['papel'] === 'sofia' && $mensagem['sem_resposta']) { ?>
                <div class="sofia-aviso-admin">A administração foi avisada desta pergunta.</div>
                <?php } elseif ($mensagem['papel'] === 'sofia') { ?>
                <div class="sofia-rodape-balao"><a data-sofia-reportar>Esta resposta está errada</a></div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>

    <form class="sofia-form" data-sofia-form onsubmit="return false;">
        <textarea class="form-control" data-sofia-input rows="1"
                  placeholder="<?= $pronta ? 'Escreva a sua pergunta...' : 'A Sofia ainda não está configurada.'; ?>"
                  <?= $pronta ? '' : 'disabled'; ?>></textarea>
        <button type="submit" class="btn btn-primary" <?= $pronta ? '' : 'disabled'; ?>>Perguntar</button>
    </form>
</div>
