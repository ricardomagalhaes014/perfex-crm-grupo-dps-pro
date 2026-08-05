<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <div class="row mbot15">
            <div class="col-md-8">
                <h4 class="no-margin"><i class="fa fa-paper-plane"></i> Envio Massa Tarefa</h4>
                <small class="text-muted">
                    Escreve a toda a gente que tem tarefas num determinado estado. O email sai
                    pela sua caixa de email, e leva no fim um botão para o seu WhatsApp.
                    Máximo de <strong>100 por dia</strong>: o que passar disso fica agendado
                    e sai sozinho nos dias seguintes.
                </small>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('dps_automacao/registo_envio_tarefa'); ?>" class="btn btn-default">
                    <i class="fa fa-list"></i> Registo de envios
                </a>
                <a href="<?php echo admin_url('dps_automacao/envio_massa'); ?>" class="btn btn-default">
                    <i class="fa fa-users"></i> Envio por estado de lead
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="panel_s"><div class="panel-body">

                    <h5 class="no-margin">1. A quem</h5>
                    <hr>

                    <label class="control-label">Estados da tarefa</label>
                    <?php foreach ($estados as $e) { ?>
                        <div class="checkbox">
                            <input type="checkbox" class="dps-estado" id="est<?php echo (int) $e['id']; ?>"
                                   value="<?php echo (int) $e['id']; ?>">
                            <label for="est<?php echo (int) $e['id']; ?>">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                                             background:<?php echo html_escape($e['color']); ?>;margin-right:6px;"></span>
                                <?php echo html_escape($e['name']); ?>
                            </label>
                        </div>
                    <?php } ?>

                    <?php
                    /*
                     * O filtro de comercial só aparece a quem pode ver os outros.
                     * Para um comercial, o controlador já lhe fixa as próprias
                     * tarefas — não há aqui regra a duplicar.
                     */
                    if (!empty($comerciais)) { ?>
                        <hr>
                        <label class="control-label">Comercial</label>
                        <select id="dps-comercial" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($comerciais as $c) {
                                /*
                                 * O nome vinha vazio: a consulta devolve firstname/lastname
                                 * e aqui lia-se 'nome', que não existia. As opções apareciam
                                 * em branco — via-se o selector, mas não os nomes.
                                 */
                                $rotulo = trim((string) ($c['nome'] ?? ''));
                                if ($rotulo === '') {
                                    $rotulo = trim(($c['firstname'] ?? '') . ' ' . ($c['lastname'] ?? ''));
                                }
                                if ($rotulo === '') {
                                    $rotulo = 'Staff #' . (int) $c['staffid'];
                                }
                                ?>
                                <option value="<?php echo (int) $c['staffid']; ?>">
                                    <?php echo html_escape($rotulo); ?><?php
                                        echo isset($c['n']) ? ' (' . (int) $c['n'] . ')' : ''; ?>
                                </option>
                            <?php } ?>
                        </select>
                    <?php } ?>

                    <hr>
                    <button type="button" class="btn btn-default btn-block" id="dps-ver">
                        <i class="fa fa-search"></i> Ver quantos são
                    </button>

                    <div id="dps-contagem" class="mtop15" style="display:none;"></div>

                </div></div>
            </div>

            <div class="col-md-7">
                <div class="panel_s"><div class="panel-body">

                    <h5 class="no-margin">2. O que se escreve</h5>
                    <hr>

                    <div class="form-group">
                        <label class="control-label">Assunto</label>
                        <input type="text" id="dps-assunto" class="form-control"
                               placeholder="ex.: Novidades sobre a sua procura">
                    </div>

                    <div class="form-group">
                        <label class="control-label">Mensagem</label>
                        <textarea id="dps-mensagem" class="form-control" rows="12"
                                  placeholder="Olá {nome},&#10;&#10;..."></textarea>
                        <small class="text-muted">
                            <strong>{nome}</strong> é substituído pelo nome do cliente e
                            <strong>{comercial}</strong> pelo nome de quem tem a tarefa.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Anexo <small class="text-muted">(opcional)</small></label>
                        <input type="file" id="dps-anexo" class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        <small class="text-muted">
                            Um ficheiro, até 10 MB. PDF, imagem, Word ou Excel — nada que
                            um servidor de email recuse à porta.
                        </small>
                    </div>

                    <hr>
                    <button type="button" class="btn btn-info btn-block" id="dps-enviar" disabled>
                        <i class="fa fa-paper-plane"></i> Enviar
                    </button>
                    <p class="text-muted mtop10" style="font-size:.85em;">
                        O botão só abre depois de ver quantos são. Enviar sem saber para quantos
                        é a maneira mais fácil de escrever a mais gente do que se pensava.
                    </p>

                    <div id="dps-resultado" class="mtop15"></div>

                </div></div>
            </div>
        </div>

    </div>
</div>
<?php init_tail(); ?>
<script>
(function () {
    var CSRF = { nome: '<?php echo $this->security->get_csrf_token_name(); ?>',
                 hash: '<?php echo $this->security->get_csrf_hash(); ?>' };
    var BASE = '<?php echo admin_url('dps_automacao/'); ?>';
    var LOTE = 100;   // tecto do fornecedor de email, por envio

    function estados() {
        return Array.prototype.slice.call(document.querySelectorAll('.dps-estado:checked'))
            .map(function (c) { return c.value; });
    }

    function dados(extra) {
        var d = { comercial_id: (document.getElementById('dps-comercial') || {}).value || '' };
        estados().forEach(function (v, i) { d['estados[' + i + ']'] = v; });
        Object.keys(extra || {}).forEach(function (k) { d[k] = extra[k]; });
        d[CSRF.nome] = CSRF.hash;
        return d;
    }

    var caixa   = document.getElementById('dps-contagem');
    var botao   = document.getElementById('dps-enviar');
    var prontos = 0;   // quantos a contagem disse que recebem

    function fecharBotao() {
        prontos = 0;
        botao.disabled = true;
        botao.innerHTML = '<i class="fa fa-search"></i> Ver quantos são primeiro';
    }
    fecharBotao();

    document.getElementById('dps-ver').onclick = function () {
        if (!estados().length) {
            caixa.style.display = 'block';
            caixa.innerHTML = '<div class="alert alert-warning">Escolha pelo menos um estado.</div>';
            return;
        }
        caixa.style.display = 'block';
        caixa.innerHTML = '<p class="text-muted">A contar…</p>';

        $.post(BASE + 'envio_massa_tarefa_preview', dados(), function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
            if (!r || r.erro) {
                caixa.innerHTML = '<div class="alert alert-danger">' + ((r && r.erro) || 'Erro.') + '</div>';
                return;
            }
            var h = '<table class="table table-condensed">'
                  + '<thead><tr><th>Estado</th><th class="text-right">Tarefas</th>'
                  + '<th class="text-right">Recebem</th></tr></thead><tbody>';
            (r.estados || []).forEach(function (e) {
                h += '<tr><td>' + (e.estado_nome || ('Estado ' + e.estado_id)) + '</td>'
                   + '<td class="text-right text-muted">' + e.total + '</td>'
                   + '<td class="text-right"><strong>' + e.com_contacto + '</strong></td></tr>';
            });
            h += '</tbody></table>';

            h += '<div class="alert alert-' + (r.com_contacto > 0 ? 'info' : 'warning') + '">'
               + '<strong>' + r.com_contacto + '</strong> vão receber email.';
            if (r.excluidas > 0) {
                h += '<br><small>' + r.excluidas + ' ficam de fora — a tarefa não está ligada a '
                   + 'nenhuma lead ou cliente com email.</small>';
            }
            // O tecto do fornecedor não é detalhe: quem carrega em Enviar tem
            // de saber, ANTES, que isto vai levar dias.
            if (r.com_contacto > LOTE) {
                var dias = Math.ceil(r.com_contacto / LOTE);
                h += '<hr style="margin:10px 0"><small><strong>' + LOTE + ' hoje</strong>, '
                   + 'os restantes ' + (r.com_contacto - LOTE) + ' de ' + LOTE + ' em ' + LOTE
                   + ' por dia — ' + dias + ' dias ao todo. É o máximo que o fornecedor '
                   + 'de email deixa passar de uma vez.</small>';
            }
            h += '</div>';
            caixa.innerHTML = h;

            /*
             * Abrir o botão é o passo que interessa: se falhar, o ecrã fica
             * util-inútil — mostra a contagem e não deixa enviar. Por isso a
             * conta é feita com parseInt (o JSON pode trazer texto) e o
             * resultado vai escrito no próprio botão, para se ver de relance
             * se está aberto e para quantos.
             */
            var quantos = parseInt(r.com_contacto, 10);
            if (isNaN(quantos)) { quantos = 0; }

            prontos = quantos;
            botao.disabled = (quantos <= 0);
            botao.innerHTML = quantos > 0
                ? '<i class="fa fa-paper-plane"></i> Enviar a ' + Math.min(quantos, LOTE)
                  + (quantos > LOTE ? ' hoje (de ' + quantos + ')' : '')
                : '<i class="fa fa-paper-plane"></i> Ninguém para enviar';
        }).fail(function () {
            caixa.innerHTML = '<div class="alert alert-danger">Erro de comunicação.</div>';
        });
    };

    // Mudar os critérios fecha o botão outra vez: a contagem que se viu
    // deixou de valer para o que está agora escolhido.
    document.querySelectorAll('.dps-estado').forEach(function (c) {
        c.addEventListener('change', fecharBotao);
    });
    var sel = document.getElementById('dps-comercial');
    if (sel) { sel.addEventListener('change', fecharBotao); }

    botao.onclick = function () {
        var assunto  = document.getElementById('dps-assunto').value.trim();
        var mensagem = document.getElementById('dps-mensagem').value.trim();
        if (!assunto || !mensagem) { alert('Falta o assunto ou a mensagem.'); return; }
        if (!confirm('Pôr em fila? A partir daqui saem sozinhas e não há forma de as recolher.')) { return; }

        botao.disabled = true;
        botao.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A enviar…';
        var res = document.getElementById('dps-resultado');
        res.innerHTML = '';

        /*
         * FormData em vez de $.post simples: um ficheiro não viaja num pedido
         * codificado como formulário normal. O resto dos campos segue no mesmo
         * pacote, incluindo o token do Perfex.
         */
        var fd = new FormData();
        var d  = dados({ assunto: assunto, mensagem: mensagem });
        Object.keys(d).forEach(function (k) { fd.append(k, d[k]); });

        var f = document.getElementById('dps-anexo');
        if (f && f.files && f.files[0]) { fd.append('anexo', f.files[0]); }

        $.ajax({
            url: BASE + 'envio_massa_tarefa_enviar',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false
        }).done(function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
            botao.innerHTML = '<i class="fa fa-paper-plane"></i> Enviar';
            if (!r || r.erro) {
                res.innerHTML = '<div class="alert alert-danger">' + ((r && r.erro) || 'Erro.') + '</div>';
                botao.disabled = false;
                return;
            }
            /*
             * Já não se diz "X enviados": nada sai dentro deste pedido. Tudo
             * fica em fila e é o cron que leva — os primeiros nos minutos
             * seguintes. Dizer "enviados" quando ainda não saíram era prometer
             * o que não se tinha feito.
             */
            var primeiro = Math.min(r.total, LOTE);
            var depois   = Math.max(0, r.total - primeiro);

            var h = '<div class="alert alert-success">'
                  + '<strong>' + r.total + '</strong> mensagens em fila.';
            h += '<br>As primeiras <strong>' + primeiro + '</strong> saem nos próximos minutos.';
            if (depois > 0) {
                h += '<br>As restantes <strong>' + depois + '</strong> seguem '
                   + LOTE + ' por dia — é o máximo que o fornecedor de email deixa passar.';
            }
            if (r.anexo) { h += '<div class="text-muted">com o anexo <strong>' + r.anexo + '</strong></div>'; }
            h += '<hr style="margin:10px 0">'
               + '<small>Saem sozinhas, não é preciso voltar aqui nem carregar outra vez. '
               + 'Pode acompanhar em <em>Registo Envio Tarefa</em>.</small>';
            h += '</div>';
            res.innerHTML = h;
        }).fail(function () {
            botao.innerHTML = '<i class="fa fa-paper-plane"></i> Enviar';
            botao.disabled = false;
            res.innerHTML = '<div class="alert alert-danger">Erro de comunicação.</div>';
        });
    };
})();
</script>
