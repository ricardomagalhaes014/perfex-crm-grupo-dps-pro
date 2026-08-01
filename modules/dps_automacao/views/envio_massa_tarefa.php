<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <div class="row mbot15">
            <div class="col-md-8">
                <h4 class="no-margin"><i class="fa fa-paper-plane"></i> Envio Massa Tarefa</h4>
                <small class="text-muted">
                    Escreve a toda a gente que tem tarefas num determinado estado. O email sai
                    pela caixa do comercial de cada tarefa — o cliente responde a quem o acompanha.
                </small>
            </div>
            <div class="col-md-4 text-right">
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
                            <?php foreach ($comerciais as $c) { ?>
                                <option value="<?php echo (int) $c['staffid']; ?>">
                                    <?php echo html_escape($c['nome']); ?>
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

    var caixa = document.getElementById('dps-contagem');
    var botao = document.getElementById('dps-enviar');

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
            var h = '<table class="table table-condensed"><tbody>';
            (r.estados || []).forEach(function (e) {
                h += '<tr><td>' + e.estado_nome + '</td><td class="text-right">'
                   + e.com_contacto + ' de ' + e.total + '</td></tr>';
            });
            h += '</tbody></table>';
            h += '<div class="alert alert-' + (r.com_contacto > 0 ? 'info' : 'warning') + '">'
               + '<strong>' + r.com_contacto + '</strong> vão receber email.';
            if (r.excluidas > 0) {
                h += '<br><small>' + r.excluidas + ' ficam de fora — a tarefa não está ligada a '
                   + 'nenhuma lead ou cliente com email.</small>';
            }
            h += '</div>';
            caixa.innerHTML = h;
            botao.disabled = (r.com_contacto === 0);
        }).fail(function () {
            caixa.innerHTML = '<div class="alert alert-danger">Erro de comunicação.</div>';
        });
    };

    // Mudar os critérios fecha o botão outra vez: a contagem que se viu
    // deixou de valer para o que está agora escolhido.
    document.querySelectorAll('.dps-estado').forEach(function (c) {
        c.addEventListener('change', function () { botao.disabled = true; });
    });
    var sel = document.getElementById('dps-comercial');
    if (sel) { sel.addEventListener('change', function () { botao.disabled = true; }); }

    botao.onclick = function () {
        var assunto  = document.getElementById('dps-assunto').value.trim();
        var mensagem = document.getElementById('dps-mensagem').value.trim();
        if (!assunto || !mensagem) { alert('Falta o assunto ou a mensagem.'); return; }
        if (!confirm('Enviar agora? Não há forma de recolher emails já enviados.')) { return; }

        botao.disabled = true;
        botao.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A enviar…';
        var res = document.getElementById('dps-resultado');
        res.innerHTML = '';

        $.post(BASE + 'envio_massa_tarefa_enviar', dados({ assunto: assunto, mensagem: mensagem }),
        function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
            botao.innerHTML = '<i class="fa fa-paper-plane"></i> Enviar';
            if (!r || r.erro) {
                res.innerHTML = '<div class="alert alert-danger">' + ((r && r.erro) || 'Erro.') + '</div>';
                botao.disabled = false;
                return;
            }
            var h = '<div class="alert alert-success"><strong>' + r.enviados + '</strong> enviados';
            if (r.falhas > 0) {
                h += ' · <strong>' + r.falhas + '</strong> falharam';
                if (r.exemplos && r.exemplos.length) { h += '<br><small>' + r.exemplos.join(', ') + '</small>'; }
            }
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
