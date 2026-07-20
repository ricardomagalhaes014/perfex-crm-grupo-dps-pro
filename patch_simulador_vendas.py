#!/usr/bin/env python3
"""
Injecta no simulador o fluxo de importação de vendas para o CRM.

O simulador é um único ficheiro de ~8 MB com tudo inline. Nunca se reescreve à
mão: este script faz backup, verifica se o bloco já lá está, e insere-o antes
de </body>. Correr duas vezes não duplica nada.

Uso:
    python3 patch_simulador_vendas.py [caminho/para/index.html]

Por omissão actua em simuladorportugal/index.html a partir da raiz do repo.
"""

import shutil
import sys
from datetime import datetime
from pathlib import Path

MARCADOR = "DPS_VENDA_IMPORT_INJECT"

BLOCO = """
<!-- DPS_VENDA_IMPORT_INJECT -->
<script>
(function () {
  'use strict';

  // Preencher antes de usar: URL do endpoint e token do .dps_venda_secret
  var CRM_ENDPOINT = 'https://crm.grupo-dps.com/dps_venda_receber.php';
  var CRM_TOKEN = '__TOKEN_AQUI__';

  // O nome tem de bater certo com o das Regras de Comissão no CRM, senão a
  // taxa não é encontrada e a venda fica sem comissão calculada.
  var EMPREENDIMENTOS = {
    bh: 'Belo Horizonte',
    raizes: 'Raízes Fanzeres',
    gp: 'Gaia Premium',
    boavista: 'Boavista Towers',
    lake: 'Lake Towers'
  };

  var comerciaisCache = null;

  function encontrarUnidade(table, key) {
    try {
      if (table === 'bh') return BH_UNITS.find(function (u) { return u.fraccao === key; });
      if (table === 'raizes') return raizesData.find(function (u) { return u.fraccao === key; });
      if (table === 'gp') return gpData.find(function (u) { return u.fraccao === key; });
      if (table === 'boavista') return BOAVISTA_UNITS.find(function (u) { return u.fraccao === key; });
      var partes = key.split('_');
      return LAKE_UNITS.find(function (u) { return u.torre === partes[0] && u.fraccao === partes[1]; });
    } catch (e) {
      return null;
    }
  }

  function carregarComerciais() {
    if (comerciaisCache) return Promise.resolve(comerciaisCache);

    return fetch(CRM_ENDPOINT + '?a=comerciais&t=' + encodeURIComponent(CRM_TOKEN))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.success) throw new Error(d.error || 'erro');
        comerciaisCache = d.comerciais;
        return comerciaisCache;
      });
  }

  function construirModal() {
    if (document.getElementById('dpsVendaModal')) return;

    var html =
      '<div id="dpsVendaModal" style="display:none;position:fixed;inset:0;z-index:99999;' +
      'background:rgba(0,0,0,.7);align-items:center;justify-content:center;font-family:inherit;">' +
      '<div style="background:#0d1b2e;color:#F5F2ED;padding:24px;border-radius:12px;max-width:420px;' +
      'width:92%;border:1px solid rgba(197,165,90,.4);box-shadow:0 10px 40px rgba(0,0,0,.5);">' +
      '<h3 style="margin:0 0 4px;font-size:1.1rem;">Registar venda no CRM</h3>' +
      '<p id="dpsVendaResumo" style="margin:0 0 16px;opacity:.75;font-size:.85rem;"></p>' +
      '<label style="display:block;margin-bottom:6px;font-size:.85rem;">Comercial</label>' +
      '<select id="dpsVendaComercial" style="width:100%;padding:10px;border-radius:8px;' +
      'border:1px solid rgba(197,165,90,.4);background:#081528;color:#F5F2ED;margin-bottom:14px;"></select>' +
      '<label style="display:block;margin-bottom:6px;font-size:.85rem;">Valor da venda (EUR)</label>' +
      '<input id="dpsVendaValor" type="text" style="width:100%;padding:10px;border-radius:8px;' +
      'border:1px solid rgba(197,165,90,.4);background:#081528;color:#F5F2ED;margin-bottom:18px;">' +
      '<div id="dpsVendaErro" style="display:none;color:#ff8a80;font-size:.85rem;margin-bottom:12px;"></div>' +
      '<div style="display:flex;gap:8px;justify-content:flex-end;">' +
      '<button id="dpsVendaCancelar" style="padding:9px 16px;border-radius:8px;border:1px solid rgba(255,255,255,.25);' +
      'background:transparent;color:#F5F2ED;cursor:pointer;">Só marcar vendido</button>' +
      '<button id="dpsVendaConfirmar" style="padding:9px 16px;border-radius:8px;border:none;' +
      'background:#C5A55A;color:#081528;font-weight:600;cursor:pointer;">Registar no CRM</button>' +
      '</div></div></div>';

    document.body.insertAdjacentHTML('beforeend', html);
  }

  function abrirModal(table, key, unidade, aoConcluir) {
    construirModal();

    var modal = document.getElementById('dpsVendaModal');
    var erro = document.getElementById('dpsVendaErro');
    var select = document.getElementById('dpsVendaComercial');
    var campoValor = document.getElementById('dpsVendaValor');

    erro.style.display = 'none';
    document.getElementById('dpsVendaResumo').textContent =
      EMPREENDIMENTOS[table] + ' · Fracção ' + key + (unidade && unidade.tipologia ? ' · ' + unidade.tipologia : '');
    campoValor.value = unidade && unidade.preco ? unidade.preco : '';

    select.innerHTML = '<option>A carregar...</option>';
    modal.style.display = 'flex';

    carregarComerciais().then(function (lista) {
      select.innerHTML = lista.map(function (c) {
        return '<option value="' + c.id + '">' + c.nome + '</option>';
      }).join('');
    }).catch(function () {
      select.innerHTML = '';
      erro.textContent = 'Não foi possível contactar o CRM. Pode marcar como vendido e registar a venda manualmente.';
      erro.style.display = 'block';
    });

    function fechar() {
      modal.style.display = 'none';
      document.getElementById('dpsVendaConfirmar').onclick = null;
      document.getElementById('dpsVendaCancelar').onclick = null;
    }

    // "Só marcar vendido": mantém o comportamento antigo do simulador.
    // Nunca se bloqueia a marcação por causa do CRM estar em baixo.
    document.getElementById('dpsVendaCancelar').onclick = function () {
      fechar();
      aoConcluir();
    };

    document.getElementById('dpsVendaConfirmar').onclick = function () {
      var botao = this;
      var valor = String(campoValor.value).replace(/[^0-9.,]/g, '').replace(',', '.');

      if (!select.value || !valor || parseFloat(valor) <= 0) {
        erro.textContent = 'Escolha o comercial e confirme o valor.';
        erro.style.display = 'block';
        return;
      }

      botao.disabled = true;
      botao.textContent = 'A registar...';

      var corpo = new FormData();
      corpo.append('a', 'importar');
      corpo.append('token', CRM_TOKEN);
      corpo.append('empreendimento', EMPREENDIMENTOS[table]);
      corpo.append('unidade', key);
      corpo.append('valor', valor);
      corpo.append('comercial_id', select.value);
      corpo.append('tipologia', (unidade && unidade.tipologia) || '');

      fetch(CRM_ENDPOINT, { method: 'POST', body: corpo })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          botao.disabled = false;
          botao.textContent = 'Registar no CRM';

          if (!d.success) {
            erro.textContent = d.error || 'Não foi possível registar.';
            erro.style.display = 'block';
            return;
          }

          fechar();
          aoConcluir();
          alert(d.message || 'Venda registada no CRM.');
        })
        .catch(function () {
          botao.disabled = false;
          botao.textContent = 'Registar no CRM';
          erro.textContent = 'Falha de comunicação com o CRM.';
          erro.style.display = 'block';
        });
    };
  }

  // Envolvemos a função original em vez de a reescrever: se o simulador mudar
  // por dentro, isto continua a funcionar.
  function instalar() {
    if (typeof window.changeStatus !== 'function' || window.changeStatus.__dpsEnvolvida) {
      return;
    }

    var original = window.changeStatus;

    window.changeStatus = function (table, key, newStatus) {
      if (newStatus !== 'Vendido') {
        return original.apply(this, arguments);
      }

      var self = this;
      var args = arguments;
      var unidade = encontrarUnidade(table, key);

      abrirModal(table, key, unidade, function () {
        original.apply(self, args);
      });
    };

    window.changeStatus.__dpsEnvolvida = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', instalar);
  } else {
    instalar();
  }
})();
</script>
<!-- /DPS_VENDA_IMPORT_INJECT -->
"""


def main() -> int:
    if len(sys.argv) > 1:
        alvo = Path(sys.argv[1])
    else:
        alvo = Path(__file__).parent / "simuladorportugal" / "index.html"

    if not alvo.is_file():
        print(f"ERRO: não encontrei {alvo}")
        return 1

    conteudo = alvo.read_text(encoding="utf-8", errors="surrogateescape")

    if MARCADOR in conteudo:
        print("O bloco já está injectado. Nada a fazer.")
        return 0

    fecho = conteudo.rfind("</body>")
    if fecho == -1:
        print("ERRO: não encontrei </body> no ficheiro.")
        return 1

    carimbo = datetime.now().strftime("%Y%m%d-%H%M%S")
    backup = alvo.with_name(f"{alvo.stem}-backup-{carimbo}{alvo.suffix}")
    shutil.copy2(alvo, backup)
    print(f"Backup: {backup.name}")

    novo = conteudo[:fecho] + BLOCO + conteudo[fecho:]
    alvo.write_text(novo, encoding="utf-8", errors="surrogateescape")

    print(f"Injectado em {alvo.name} ({len(novo):,} bytes)")
    print()
    print("FALTA: substituir __TOKEN_AQUI__ pelo conteúdo de /home/u172337921/.dps_venda_secret")
    return 0


if __name__ == "__main__":
    sys.exit(main())
