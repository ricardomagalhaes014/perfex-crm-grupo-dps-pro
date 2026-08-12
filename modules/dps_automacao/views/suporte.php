<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <h4 class="no-margin">Suporte</h4>
                        <p class="text-muted">
                            <?php if ($tudo) { ?>
                                <strong>Vista de direcção:</strong> todos os pedidos da equipa, de quem quer que
                                sejam. Só pode responder aos que lhe foram dirigidos.
                            <?php } elseif ($manda) { ?>
                                Os pedidos de apoio que lhe foram dirigidos, e os que fez. Responda aqui — o
                                desfecho aparece na ficha da lead de quem pediu e chega-lhe pelo sino.
                            <?php } else { ?>
                                Os seus pedidos de apoio à direcção e as respostas que já receberam.
                            <?php } ?>
                        </p>
                        <hr>

                        <?php $modo = $tudo ? '&tudo=1' : ''; ?>
                        <p>
                            <a href="<?php echo admin_url('dps_automacao/suporte' . ($tudo ? '?tudo=1' : '')); ?>"
                               class="btn btn-<?php echo $filtro === '' ? 'info' : 'default'; ?> btn-sm">
                                Todos
                            </a>
                            <?php foreach ($estados as $chave => $e) { ?>
                                <a href="<?php echo admin_url('dps_automacao/suporte?estado=' . $chave . $modo); ?>"
                                   class="btn btn-<?php echo $filtro === $chave ? 'info' : 'default'; ?> btn-sm">
                                    <?php echo $e[0]; ?>
                                    <span class="badge"><?php echo (int) $contagem[$chave]; ?></span>
                                </a>
                            <?php } ?>

                            <?php if ($e_admin) { ?>
                                <a href="<?php echo admin_url('dps_automacao/suporte' . ($tudo ? '' : '?tudo=1')); ?>"
                                   class="btn btn-<?php echo $tudo ? 'warning' : 'default'; ?> btn-sm pull-right">
                                    <i class="fa fa-eye"></i>
                                    <?php echo $tudo ? 'A ver tudo — voltar aos meus' : 'Vista de direcção'; ?>
                                </a>
                            <?php } ?>
                        </p>

                        <?php if (empty($pedidos)) { ?>
                            <div class="alert alert-info">
                                Não há pedidos <?php echo $filtro !== '' ? 'neste estado' : 'de suporte'; ?>.
                            </div>
                        <?php } ?>

                        <?php foreach ($pedidos as $p) {
                            $e   = $estados[$p['estado']] ?? ['?', 'default'];
                            $qde = $p['pedinte'] ? get_staff_full_name($p['pedinte']) : '—';
                            // Responder é por pedido, não por perfil: a mesma pessoa
                            // pode receber uns e ter pedido outros.
                            $meu_para_responder = ((int) $p['destino'] === (int) get_staff_user_id());
                        ?>
                        <div class="panel_s" style="border-left:4px solid <?php
                            echo $p['estado'] === 'novo' ? '#e74c3c' : ($p['estado'] === 'resolvido' ? '#27ae60' : '#f0ad4e'); ?>;">
                            <div class="panel-body">

                                <div class="clearfix">
                                    <span class="label label-<?php echo $e[1]; ?>" style="font-size:12px;">
                                        <?php echo $e[0]; ?>
                                    </span>
                                    <strong style="margin-left:8px;font-size:15px;">
                                        <a href="<?php echo admin_url('leads/index/' . (int) $p['lead_id']); ?>" target="_blank">
                                            <?php echo htmlspecialchars((string) $p['lead_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </strong>
                                    <span class="text-muted" style="margin-left:8px;">
                                        <?php echo htmlspecialchars((string) $p['lead_tel'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (! empty($p['lead_email'])) { ?>
                                            · <?php echo htmlspecialchars((string) $p['lead_email'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php } ?>
                                    </span>
                                    <span class="pull-right text-muted">
                                        <?php if ($tudo) { ?>
                                            <strong><?php echo htmlspecialchars($qde, ENT_QUOTES, 'UTF-8'); ?></strong>
                                            &rarr; <strong><?php echo htmlspecialchars(get_staff_full_name($p['destino']), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php } elseif ($meu_para_responder) { ?>
                                            pedido por <strong><?php echo htmlspecialchars($qde, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php } else { ?>
                                            enviado a <strong><?php echo htmlspecialchars(get_staff_full_name($p['destino']), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php } ?>
                                        · <?php echo _dt($p['criado_em']); ?>
                                    </span>
                                </div>

                                <div style="margin-top:12px;padding:10px 14px;background:#f7f7f7;border-radius:4px;white-space:pre-wrap;">
<?php echo htmlspecialchars((string) $p['contexto'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>

                                <?php if (trim((string) $p['resposta']) !== '') { ?>
                                    <div style="margin-top:10px;padding:10px 14px;background:#eaf6ea;border-left:3px solid #27ae60;white-space:pre-wrap;">
<?php echo htmlspecialchars((string) $p['resposta'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php } ?>

                                <?php if ($meu_para_responder) { ?>
                                    <div style="margin-top:12px;">
                                        <textarea id="resp-<?php echo (int) $p['id']; ?>" class="form-control" rows="2"
                                                  placeholder="O que fez, e o que o comercial deve saber…"></textarea>
                                        <div style="margin-top:8px;">
                                            <select id="est-<?php echo (int) $p['id']; ?>" class="form-control"
                                                    style="width:auto;display:inline-block;">
                                                <?php foreach ($estados as $chave => $lbl) { ?>
                                                    <option value="<?php echo $chave; ?>"
                                                        <?php echo $p['estado'] === $chave ? 'selected' : ''; ?>>
                                                        <?php echo $lbl[0]; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <button class="btn btn-info btn-sm"
                                                    onclick="dpsSuporteResponder(<?php echo (int) $p['id']; ?>)">
                                                Responder
                                            </button>
                                            <button class="btn btn-default btn-sm"
                                                    onclick="dpsSuporteEstado(<?php echo (int) $p['id']; ?>)">
                                                Fechar com este desfecho
                                            </button>
                                            <?php if (! empty($p['tarefa_id'])) { ?>
                                                <a href="<?php echo admin_url('tasks/view/' . (int) $p['tarefa_id']); ?>"
                                                   class="btn btn-default btn-sm" target="_blank">Abrir tarefa</a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>

                            </div>
                        </div>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
function dpsSuporteResponder(id) {
    var txt = document.getElementById('resp-' + id).value.trim();
    if (txt === '') { alert_float('warning', 'Escreva a resposta.'); return; }

    $.post('<?php echo admin_url('dps_automacao/suporte_responder'); ?>', {
        id: id,
        resposta: txt,
        estado: document.getElementById('est-' + id).value
    }, function (r) {
        try { r = JSON.parse(r); } catch (e) { alert_float('danger', 'Resposta inesperada do servidor.'); return; }
        alert_float(r.sucesso ? 'success' : 'danger', r.mensagem);
        // Recarregar: a resposta passa a fazer parte do histórico do pedido e
        // reescrevê-la à mão no ecrã dava-lhe um formato diferente do real.
        if (r.sucesso) { setTimeout(function () { location.reload(); }, 900); }
    });
}

function dpsSuporteEstado(id) {
    $.post('<?php echo admin_url('dps_automacao/suporte_estado'); ?>', {
        id: id,
        estado: document.getElementById('est-' + id).value
    }, function (r) {
        try { r = JSON.parse(r); } catch (e) { alert_float('danger', 'Resposta inesperada do servidor.'); return; }
        alert_float(r.sucesso ? 'success' : 'danger', r.mensagem);
        if (r.sucesso) { setTimeout(function () { location.reload(); }, 700); }
    });
}
</script>
