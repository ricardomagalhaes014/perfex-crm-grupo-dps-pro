<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/*
 * Estilo e comportamento da caixa de conversa.
 *
 * Vive num parcial único porque a mesma caixa aparece em dois sítios — a página
 * "A Sofia responde" e a gaveta flutuante que está em todas as outras páginas.
 * Duplicar isto era garantir que um dos dois ficava para trás na primeira
 * correcção. O JavaScript liga-se por atributos (data-sofia-*) e não por id,
 * exactamente para servir os dois sem saber em qual está.
 */
?>
<style>
.sofia-caixa { display: flex; flex-direction: column; height: 100%; }
.sofia-mensagens { flex: 1 1 auto; overflow-y: auto; padding: 12px; background: #f7f8fa; }
.sofia-linha { margin-bottom: 14px; display: flex; }
.sofia-linha.sofia-de-comercial { justify-content: flex-end; }
.sofia-balao {
    max-width: 82%; padding: 9px 13px; border-radius: 14px;
    line-height: 1.5; font-size: 14px; word-wrap: break-word;
}
.sofia-de-comercial .sofia-balao { background: #2d7ff9; color: #fff; border-bottom-right-radius: 4px; }
.sofia-de-sofia .sofia-balao { background: #fff; border: 1px solid #e2e5ea; border-bottom-left-radius: 4px; }
.sofia-balao.sofia-erro { background: #fdecea; border-color: #f5c2bd; color: #a3271b; }
.sofia-rodape-balao { margin-top: 6px; font-size: 11px; color: #8a9099; }
.sofia-rodape-balao a { color: #8a9099; text-decoration: underline; cursor: pointer; }
.sofia-fontes { margin-top: 6px; font-size: 11px; color: #8a9099; font-style: italic; }
.sofia-aviso-admin {
    margin-top: 6px; font-size: 11px; color: #8a6d1f;
    background: #fdf6e3; border: 1px solid #f0e2b6; border-radius: 6px; padding: 5px 8px;
}
.sofia-form { border-top: 1px solid #e2e5ea; padding: 10px; background: #fff; display: flex; gap: 8px; }
.sofia-form textarea { flex: 1 1 auto; resize: none; height: 44px; max-height: 120px; }
.sofia-reporte { margin-top: 8px; }
.sofia-reporte textarea { width: 100%; height: 60px; font-size: 13px; }
.sofia-a-escrever { color: #8a9099; font-style: italic; }

/* Gaveta flutuante */
#sofia-botao-flutuante {
    position: fixed; right: 22px; bottom: 22px; z-index: 1040;
    border: 0; border-radius: 26px; padding: 12px 20px;
    background: #2d7ff9; color: #fff; font-weight: 600; font-size: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.22); cursor: pointer;
}
#sofia-botao-flutuante:hover { background: #1c6ae0; }
#sofia-gaveta {
    position: fixed; right: 22px; bottom: 22px; z-index: 1041;
    width: 380px; max-width: calc(100vw - 32px);
    height: 520px; max-height: calc(100vh - 90px);
    background: #fff; border-radius: 12px; overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,.28); display: none; flex-direction: column;
}
#sofia-gaveta.sofia-aberta { display: flex; }
#sofia-gaveta .sofia-topo {
    padding: 11px 14px; background: #2d7ff9; color: #fff;
    display: flex; justify-content: space-between; align-items: center; font-weight: 600;
}
#sofia-gaveta .sofia-topo a { color: #fff; opacity: .85; font-size: 13px; text-decoration: none; }
@media (max-width: 480px) {
    #sofia-gaveta { right: 8px; left: 8px; width: auto; bottom: 8px; height: calc(100vh - 80px); }
}
</style>

<script>
(function () {
    if (window.dpsSofia) { return; } // já carregado nesta página

    var urlPerguntar = '<?= admin_url('dps_sofia_ia/perguntar'); ?>';
    var urlReportar  = '<?= admin_url('dps_sofia_ia/reportar'); ?>';

    function dadosCsrf() {
        if (typeof csrfData !== 'undefined' && csrfData['token_name']) {
            var d = {};
            d[csrfData['token_name']] = csrfData['hash'];
            return d;
        }
        return {};
    }

    function escapar(texto) {
        return String(texto)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Formatação mínima: o modelo escreve **negrito** e listas, e o resto do
    // HTML é escapado antes para que nada do que venha da API execute.
    function formatar(texto) {
        return escapar(texto)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function paraBaixo(caixa) {
        var lista = caixa.querySelector('[data-sofia-mensagens]');
        lista.scrollTop = lista.scrollHeight;
    }

    function balao(caixa, quem, html, classes) {
        var lista = caixa.querySelector('[data-sofia-mensagens]');
        var linha = document.createElement('div');
        linha.className = 'sofia-linha sofia-de-' + quem;
        linha.innerHTML = '<div class="sofia-balao ' + (classes || '') + '">' + html + '</div>';
        lista.appendChild(linha);
        paraBaixo(caixa);
        return linha;
    }

    function ligarReporte(linha, mensagemId) {
        var gatilho = linha.querySelector('[data-sofia-reportar]');
        if (!gatilho) { return; }

        gatilho.addEventListener('click', function (e) {
            e.preventDefault();
            if (linha.querySelector('.sofia-reporte')) { return; }

            var bloco = document.createElement('div');
            bloco.className = 'sofia-reporte';
            bloco.innerHTML =
                '<textarea class="form-control" placeholder="O que está errado? (ajuda quem vai corrigir)"></textarea>' +
                '<button type="button" class="btn btn-danger btn-sm" style="margin-top:6px;">Enviar ao administrador</button>';
            linha.querySelector('.sofia-balao').appendChild(bloco);

            bloco.querySelector('button').addEventListener('click', function () {
                var botao = this;
                botao.disabled = true;
                botao.textContent = 'A enviar...';

                var corpo = dadosCsrf();
                corpo.mensagem_id = mensagemId;
                corpo.nota = bloco.querySelector('textarea').value;

                fetch(urlReportar, {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: new URLSearchParams(corpo)
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    bloco.innerHTML = d.ok
                        ? '<div class="sofia-aviso-admin">Reportado. A administração vai corrigir e a Sofia aprende com a correcção.</div>'
                        : '<div class="sofia-aviso-admin">' + escapar(d.erro || 'Não consegui enviar.') + '</div>';
                })
                .catch(function () {
                    bloco.innerHTML = '<div class="sofia-aviso-admin">Não consegui enviar. Tente outra vez.</div>';
                });
            });
        });
    }

    function ligarCaixa(caixa) {
        if (caixa.dataset.sofiaLigada) { return; }
        caixa.dataset.sofiaLigada = '1';

        // Os balões que já vieram do servidor também têm de ter o reporte.
        caixa.querySelectorAll('[data-sofia-mensagem-id]').forEach(function (linha) {
            ligarReporte(linha, linha.dataset.sofiaMensagemId);
        });
        paraBaixo(caixa);

        var form = caixa.querySelector('[data-sofia-form]');
        var campo = caixa.querySelector('[data-sofia-input]');

        // Enter envia; Shift+Enter muda de linha.
        campo.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit', {cancelable: true}));
            }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var pergunta = campo.value.trim();
            if (pergunta === '') { return; }

            campo.value = '';
            campo.disabled = true;
            balao(caixa, 'comercial', formatar(pergunta));

            var espera = balao(caixa, 'sofia', '<span class="sofia-a-escrever">A Sofia está a ver...</span>');

            var corpo = dadosCsrf();
            corpo.pergunta = pergunta;

            fetch(urlPerguntar, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: new URLSearchParams(corpo)
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                campo.disabled = false;
                campo.focus();

                if (!d.ok) {
                    espera.querySelector('.sofia-balao').className = 'sofia-balao sofia-erro';
                    espera.querySelector('.sofia-balao').innerHTML = escapar(d.erro || 'Correu mal.');
                    return;
                }

                var html = formatar(d.resposta);

                if (d.sem_resposta) {
                    html += '<div class="sofia-aviso-admin">A administração foi avisada e vai responder. '
                          + 'Quando responder, fica a saber — e a Sofia passa a saber também.</div>';
                } else {
                    if (d.fontes && d.fontes.length) {
                        html += '<div class="sofia-fontes">Fonte: ' + escapar(d.fontes.join(' · ')) + '</div>';
                    }
                    html += '<div class="sofia-rodape-balao">'
                          + '<a data-sofia-reportar>Esta resposta está errada</a></div>';
                }

                espera.querySelector('.sofia-balao').innerHTML = html;
                ligarReporte(espera, d.mensagem_id);
                paraBaixo(caixa);
            })
            .catch(function () {
                campo.disabled = false;
                espera.querySelector('.sofia-balao').className = 'sofia-balao sofia-erro';
                espera.querySelector('.sofia-balao').innerHTML = 'Não consegui falar com o servidor.';
            });
        });
    }

    window.dpsSofia = {ligar: ligarCaixa};

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-sofia-caixa]').forEach(ligarCaixa);
    });
})();
</script>
