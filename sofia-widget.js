/**
 * Sofia DPS Widget — Formulário Pré-Chamada ElevenLabs
 * Versão: 2.1
 * Posicionamento: acima do botão WhatsApp (bottom: 102px)
 * Formulário: Nome, Email, Telemóvel, Gestor (opcional)
 * Suporte: dpsimobiliario.pt, laketowers.grupo-dps.com, brasil.grupo-dps.com
 */
(function() {
  'use strict';

  var hostname = window.location.hostname;

  // Mapa de agentes por domínio
  var DOMAIN_AGENTS = {
    'laketowers.grupo-dps.com': 'agent_2901kv39h680esb9wtrx6yk291sw',
    'brasil.grupo-dps.com':     'agent_9501kv39wjr4etjre7118p0ejncp',
    'dpsbrasil.grupo-dps.com':  'agent_9501kv39wjr4etjre7118p0ejncp',
  };

  // Mapa de agentes por página (para dpsimobiliario.pt)
  var AGENTS = {
    '/raizes':        'agent_7501kv0dj084fmbahfdafsfmgcfv',
    '/raizes/':       'agent_7501kv0dj084fmbahfdafsfmgcfv',
    '/belohorizonte': 'agent_1901kv0dj4m0fxnr5pxqdhqzjf26',
    '/belohorizonte/':'agent_1901kv0dj4m0fxnr5pxqdhqzjf26',
  };

  var path = window.location.pathname;
  var AGENT_ID = DOMAIN_AGENTS[hostname] || AGENTS[path] || 'agent_0901kv03vzc4eqnvzt5758mms6t8';
  var WEBHOOK_URL = 'https://dpsimobiliario.pt/elevenlabs_webhook.php?action=register_user';
  var AVATAR_URL = 'https://storage.googleapis.com/eleven-public-prod/DJNwVMnFnDMoqZmJlWBBTKIAHe23/voices/agent_avatar/sofia_dps_avatar.jpg';

  // Esconder o widget nativo do ElevenLabs (se existir)
  function hideNativeWidget() {
    var existing = document.querySelector('elevenlabs-convai');
    if (existing) {
      existing.style.display = 'none';
      existing.style.visibility = 'hidden';
      existing.style.pointerEvents = 'none';
    }
  }

  // Injetar o script do ElevenLabs se não estiver carregado
  function loadElevenLabsScript(callback) {
    if (document.querySelector('script[src*="elevenlabs.io/convai-widget"]')) {
      callback();
      return;
    }
    var s = document.createElement('script');
    s.src = 'https://elevenlabs.io/convai-widget/index.js';
    s.async = true;
    s.onload = callback;
    document.head.appendChild(s);
  }

  function injectWidget() {
    hideNativeWidget();

    // Remover widget anterior se existir
    var old = document.getElementById('dps-sofia-container');
    if (old) old.remove();

    var html = `
      <!-- Widget Sofia DPS -->
      <div id="dps-sofia-container">

        <!-- Widget ElevenLabs oculto -->
        <elevenlabs-convai
          id="dps-sofia-el-widget"
          agent-id="${AGENT_ID}"
          style="position:fixed;bottom:102px;right:28px;z-index:99990;display:none;">
        </elevenlabs-convai>

        <!-- Botão flutuante Sofia — posicionado acima do WhatsApp -->
        <div id="dps-sofia-btn"
          style="position:fixed;bottom:102px;right:28px;z-index:99995;
                 width:60px;height:60px;border-radius:50%;
                 background:#C5A55A;
                 box-shadow:0 4px 18px rgba(0,0,0,0.4);
                 cursor:pointer;display:flex;align-items:center;justify-content:center;
                 overflow:hidden;border:2px solid rgba(255,255,255,0.8);
                 transition:transform 0.2s,box-shadow 0.2s;"
          onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 6px 24px rgba(0,0,0,0.5)'"
          onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 18px rgba(0,0,0,0.4)'"
          onclick="document.getElementById('dps-sofia-modal').style.display='flex'">
          <img src="${AVATAR_URL}" alt="Sofia"
               style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
               onerror="this.style.display='none';document.getElementById('dps-sofia-fallback').style.display='flex'">
          <div id="dps-sofia-fallback" style="display:none;width:100%;height:100%;align-items:center;justify-content:center;position:absolute;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
          </div>
        </div>

        <!-- Tooltip -->
        <div id="dps-sofia-tooltip"
          style="position:fixed;bottom:170px;right:18px;z-index:99994;
                 background:#1a1a1a;color:#C5A55A;
                 padding:8px 14px;border-radius:20px;font-size:13px;font-weight:600;
                 border:1px solid #C5A55A;white-space:nowrap;
                 box-shadow:0 4px 12px rgba(0,0,0,0.3);
                 opacity:0;transition:opacity 0.3s;pointer-events:none;">
          💬 Falar com a Sofia
        </div>

        <!-- Modal do formulário -->
        <div id="dps-sofia-modal"
          style="display:none;position:fixed;inset:0;z-index:999999;
                 background:rgba(0,0,0,0.65);align-items:center;justify-content:center;
                 padding:20px;backdrop-filter:blur(5px);">

          <div style="background:#111;border:1px solid #C5A55A;border-radius:18px;
                      padding:36px 30px;max-width:400px;width:100%;
                      box-shadow:0 24px 64px rgba(0,0,0,0.6);position:relative;">

            <!-- Fechar -->
            <button onclick="document.getElementById('dps-sofia-modal').style.display='none'"
              style="position:absolute;top:14px;right:16px;background:none;border:none;
                     color:#666;font-size:24px;cursor:pointer;line-height:1;padding:0;">✕</button>

            <!-- Cabeçalho -->
            <div style="text-align:center;margin-bottom:26px;">
              <div style="width:76px;height:76px;border-radius:50%;margin:0 auto 12px;
                          overflow:hidden;border:3px solid #C5A55A;background:#C5A55A;">
                <img src="${AVATAR_URL}" alt="Sofia"
                     style="width:100%;height:100%;object-fit:cover;"
                     onerror="this.parentElement.innerHTML='<div style=\\'display:flex;align-items:center;justify-content:center;height:100%;font-size:32px;\\'>👩</div>'">
              </div>
              <h3 style="color:#C5A55A;margin:0 0 6px;font-size:19px;font-weight:700;font-family:inherit;">Sofia — DPS Imobiliário</h3>
              <p style="color:#888;margin:0;font-size:13px;">Preencha os seus dados para iniciar a conversa</p>
            </div>

            <!-- Formulário -->
            <form id="dps-sofia-form" onsubmit="window.dpsSofiaSubmit(event)">

              <div style="margin-bottom:14px;">
                <label style="display:block;color:#C5A55A;font-size:12px;font-weight:700;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.5px;">Nome *</label>
                <input type="text" id="dps-nome" required placeholder="O seu nome completo"
                  style="width:100%;padding:11px 13px;background:#1e1e1e;border:1px solid #333;
                         border-radius:8px;color:#fff;font-size:14px;box-sizing:border-box;outline:none;
                         transition:border-color 0.2s;"
                  onfocus="this.style.borderColor='#C5A55A'" onblur="this.style.borderColor='#333'">
              </div>

              <div style="margin-bottom:14px;">
                <label style="display:block;color:#C5A55A;font-size:12px;font-weight:700;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.5px;">Email *</label>
                <input type="email" id="dps-email" required placeholder="o.seu@email.com"
                  style="width:100%;padding:11px 13px;background:#1e1e1e;border:1px solid #333;
                         border-radius:8px;color:#fff;font-size:14px;box-sizing:border-box;outline:none;
                         transition:border-color 0.2s;"
                  onfocus="this.style.borderColor='#C5A55A'" onblur="this.style.borderColor='#333'">
              </div>

              <div style="margin-bottom:14px;">
                <label style="display:block;color:#C5A55A;font-size:12px;font-weight:700;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.5px;">Telemóvel *</label>
                <input type="tel" id="dps-telefone" required placeholder="+351 9XX XXX XXX"
                  style="width:100%;padding:11px 13px;background:#1e1e1e;border:1px solid #333;
                         border-radius:8px;color:#fff;font-size:14px;box-sizing:border-box;outline:none;
                         transition:border-color 0.2s;"
                  onfocus="this.style.borderColor='#C5A55A'" onblur="this.style.borderColor='#333'">
              </div>

              <div style="margin-bottom:22px;">
                <label style="display:block;color:#666;font-size:12px;font-weight:700;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.5px;">Gestor que o indicou <span style="color:#444;font-weight:400;text-transform:none;">(opcional)</span></label>
                <input type="text" id="dps-gestor" placeholder="Nome do gestor (se aplicável)"
                  style="width:100%;padding:11px 13px;background:#1e1e1e;border:1px solid #333;
                         border-radius:8px;color:#fff;font-size:14px;box-sizing:border-box;outline:none;
                         transition:border-color 0.2s;"
                  onfocus="this.style.borderColor='#C5A55A'" onblur="this.style.borderColor='#333'">
              </div>

              <button type="submit"
                style="width:100%;padding:13px;
                       background:linear-gradient(135deg,#C5A55A 0%,#a8893d 100%);
                       border:none;border-radius:10px;color:#fff;font-size:15px;font-weight:700;
                       cursor:pointer;letter-spacing:0.3px;transition:opacity 0.2s;"
                onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
                🎙️&nbsp; Iniciar Conversa com a Sofia
              </button>

              <p style="color:#444;font-size:11px;text-align:center;margin:12px 0 0;">
                Os seus dados são utilizados apenas para personalizar o atendimento.
              </p>
            </form>

            <!-- Estado: a ligar -->
            <div id="dps-sofia-ligando" style="display:none;text-align:center;padding:16px 0;">
              <div style="font-size:44px;margin-bottom:14px;animation:pulse 1.5s infinite;">🎙️</div>
              <p style="color:#C5A55A;font-size:17px;font-weight:700;margin:0 0 8px;">A ligar à Sofia...</p>
              <p id="dps-sofia-nome-msg" style="color:#888;font-size:14px;margin:0;"></p>
            </div>

          </div>
        </div>

      </div>

      <style>
        @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.1)} }
        #dps-sofia-btn:hover + #dps-sofia-tooltip { opacity: 1 !important; }
      </style>
    `;

    var container = document.createElement('div');
    container.innerHTML = html;
    document.body.appendChild(container);

    // Mostrar tooltip ao hover
    var btn = document.getElementById('dps-sofia-btn');
    var tooltip = document.getElementById('dps-sofia-tooltip');
    if (btn && tooltip) {
      btn.addEventListener('mouseenter', function() { tooltip.style.opacity = '1'; });
      btn.addEventListener('mouseleave', function() { tooltip.style.opacity = '0'; });
    }
  }

  // Função de submissão do formulário
  window.dpsSofiaSubmit = function(e) {
    e.preventDefault();

    var nome     = document.getElementById('dps-nome').value.trim();
    var email    = document.getElementById('dps-email').value.trim();
    var telefone = document.getElementById('dps-telefone').value.trim();
    var gestor   = document.getElementById('dps-gestor').value.trim();

    // Guardar dados globalmente
    window._sofiaUserData = { nome: nome, email: email, telefone: telefone, gestor: gestor, agente: AGENT_ID };

    // Mostrar estado "a ligar"
    document.getElementById('dps-sofia-form').style.display = 'none';
    document.getElementById('dps-sofia-ligando').style.display = 'block';
    document.getElementById('dps-sofia-nome-msg').textContent = 'Olá, ' + nome + '! A Sofia vai atendê-lo(a) já a seguir.';

    // Registar dados no servidor
    fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'user_registered',
        nome: nome,
        email: email,
        telefone: telefone,
        gestor: gestor,
        agente_id: AGENT_ID,
        pagina: window.location.pathname,
        timestamp: new Date().toISOString()
      })
    }).catch(function() {});

    // Ativar o widget ElevenLabs
    setTimeout(function() {
      loadElevenLabsScript(function() {
        var widget = document.getElementById('dps-sofia-el-widget');
        if (widget) {
          widget.setAttribute('dynamic-variables', JSON.stringify({
            nome_cliente: nome,
            email_cliente: email,
            telefone_cliente: telefone
          }));
          widget.style.display = 'block';

          // Fechar modal e abrir widget
          setTimeout(function() {
            document.getElementById('dps-sofia-modal').style.display = 'none';
            // Tentar clicar no botão do widget automaticamente
            try {
              var shadow = widget.shadowRoot;
              if (shadow) {
                var btns = shadow.querySelectorAll('button');
                if (btns.length > 0) btns[0].click();
              }
            } catch(err) {}
          }, 1200);
        }
      });
    }, 600);
  };

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      hideNativeWidget();
      injectWidget();
    });
  } else {
    hideNativeWidget();
    injectWidget();
    // Também ocultar o widget nativo após carregamento completo
    window.addEventListener('load', hideNativeWidget);
  }

  // Observar mudanças no DOM para ocultar o widget nativo se aparecer depois
  var observer = new MutationObserver(function() {
    var native = document.querySelector('elevenlabs-convai:not(#dps-sofia-el-widget)');
    if (native) {
      native.style.display = 'none';
      native.style.visibility = 'hidden';
    }
  });
  observer.observe(document.body || document.documentElement, { childList: true, subtree: true });

})();
