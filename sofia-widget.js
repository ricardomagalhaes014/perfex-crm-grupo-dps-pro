/**
 * Sofia DPS Widget — Chamada Direta ElevenLabs
 * Versão: 3.0
 * Sem formulário — clique no botão abre o widget diretamente
 * Suporte: dpsimobiliario.pt, laketowers.grupo-dps.com, brasil.grupo-dps.com, skymarine.grupo-dps.com
 */
(function() {
  'use strict';

  var hostname = window.location.hostname;

  // Mapa de agentes por domínio
  var DOMAIN_AGENTS = {
    'laketowers.grupo-dps.com': 'agent_2901kv39h680esb9wtrx6yk291sw',
    'brasil.grupo-dps.com':     'agent_9501kv39wjr4etjre7118p0ejncp',
    'skymarine.grupo-dps.com':  'agent_9201kv3brmpcehrtyhahfq214bq8',
    'dpsbrasil.grupo-dps.com':  'agent_9501kv39wjr4etjre7118p0ejncp',
  };

  // Mapa de agentes por página (para dpsimobiliario.pt)
  var PAGE_AGENTS = {
    '/raizes':         'agent_4301kv1pv8g8e259bbdyfk7mrefb',
    '/raizes/':        'agent_4301kv1pv8g8e259bbdyfk7mrefb',
    '/belohorizonte':  'agent_1901kv0dj4m0fxnr5pxqdhqzjf26',
    '/belohorizonte/': 'agent_1901kv0dj4m0fxnr5pxqdhqzjf26',
  };

  var path = window.location.pathname;
  var AGENT_ID = DOMAIN_AGENTS[hostname] || PAGE_AGENTS[path] || 'agent_0901kv03vzc4eqnvzt5758mms6t8';
  var AVATAR_URL = 'https://storage.googleapis.com/eleven-public-prod/DJNwVMnFnDMoqZmJlWBBTKIAHe23/voices/agent_avatar/sofia_dps_avatar.jpg';

  function hideNativeWidget() {
    var existing = document.querySelector('elevenlabs-convai');
    if (existing) {
      existing.style.display = 'none';
      existing.style.visibility = 'hidden';
      existing.style.pointerEvents = 'none';
    }
  }

  function loadElevenLabsScript(callback) {
    if (document.querySelector('script[src*="elevenlabs.io/convai-widget"]')) {
      if (callback) callback();
      return;
    }
    var s = document.createElement('script');
    s.src = 'https://elevenlabs.io/convai-widget/index.js';
    s.async = true;
    s.onload = function() { if (callback) callback(); };
    document.head.appendChild(s);
  }

  function injectWidget() {
    hideNativeWidget();
    var old = document.getElementById('dps-sofia-container');
    if (old) old.remove();

    var container = document.createElement('div');
    container.id = 'dps-sofia-container';

    // Widget ElevenLabs oculto
    var elWidget = document.createElement('elevenlabs-convai');
    elWidget.id = 'dps-sofia-el-widget';
    elWidget.setAttribute('agent-id', AGENT_ID);
    elWidget.style.cssText = 'position:fixed;bottom:102px;right:28px;z-index:99990;display:none;';
    container.appendChild(elWidget);

    // Botão pill flutuante
    var btn = document.createElement('div');
    btn.id = 'dps-sofia-btn';
    btn.style.cssText = 'position:fixed;bottom:102px;right:18px;z-index:99995;'
      + 'display:flex;align-items:center;gap:12px;'
      + 'background:#fff;border-radius:50px;'
      + 'padding:8px 16px 8px 8px;'
      + 'box-shadow:0 4px 20px rgba(0,0,0,0.18);'
      + 'cursor:pointer;'
      + 'transition:box-shadow 0.2s,transform 0.2s;'
      + 'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;';
    btn.onmouseover = function() {
      this.style.boxShadow = '0 6px 28px rgba(0,0,0,0.26)';
      this.style.transform = 'translateY(-2px)';
    };
    btn.onmouseout = function() {
      this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.18)';
      this.style.transform = 'translateY(0)';
    };
    btn.onclick = function() { window._dpsSofiaOpen(); };

    // Avatar
    var avatarDiv = document.createElement('div');
    avatarDiv.style.cssText = 'width:46px;height:46px;border-radius:50%;overflow:hidden;flex-shrink:0;background:#C5A55A;';
    var avatarImg = document.createElement('img');
    avatarImg.src = AVATAR_URL;
    avatarImg.alt = 'Sofia';
    avatarImg.style.cssText = 'width:100%;height:100%;object-fit:cover;';
    avatarImg.onerror = function() {
      this.parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:22px;">👩</div>';
    };
    avatarDiv.appendChild(avatarImg);
    btn.appendChild(avatarDiv);

    // Texto
    var textDiv = document.createElement('div');
    textDiv.style.cssText = 'display:flex;flex-direction:column;gap:5px;';
    textDiv.innerHTML = '<span style="color:#222;font-size:13px;font-weight:600;white-space:nowrap;line-height:1.2;">Sou a Sofia, posso ajudar?</span>'
      + '<div style="display:flex;align-items:center;gap:6px;background:#111;border-radius:30px;padding:5px 12px;color:#fff;font-size:12px;font-weight:700;white-space:nowrap;">'
      + '<svg width="13" height="13" viewBox="0 0 24 24" fill="white" style="flex-shrink:0;"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>'
      + 'Pressione aqui</div>';
    btn.appendChild(textDiv);
    container.appendChild(btn);

    document.body.appendChild(container);

    // Pré-carregar script ElevenLabs
    loadElevenLabsScript(null);
  }

  // Abrir widget diretamente ao clicar
  window._dpsSofiaOpen = function() {
    var widget = document.getElementById('dps-sofia-el-widget');
    if (!widget) return;
    loadElevenLabsScript(function() {
      widget.style.display = 'block';
      setTimeout(function() {
        try {
          var shadow = widget.shadowRoot;
          if (shadow) {
            var btns = shadow.querySelectorAll('button');
            if (btns.length > 0) btns[0].click();
          }
        } catch(err) {}
      }, 400);
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      hideNativeWidget();
      injectWidget();
    });
  } else {
    hideNativeWidget();
    injectWidget();
    window.addEventListener('load', hideNativeWidget);
  }

  var observer = new MutationObserver(function() {
    var native = document.querySelector('elevenlabs-convai:not(#dps-sofia-el-widget)');
    if (native) {
      native.style.display = 'none';
      native.style.visibility = 'hidden';
    }
  });
  observer.observe(document.body || document.documentElement, { childList: true, subtree: true });

})();

// Remover botões antigos de outros widgets Sofia que possam existir na página
(function() {
  function removeOldWidgets() {
    // Remover botão antigo #dps-btn (belohorizonte)
    var oldBtn = document.getElementById('dps-btn');
    if (oldBtn) oldBtn.remove();
    var oldTip = document.getElementById('dps-tip');
    if (oldTip) oldTip.remove();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', removeOldWidgets);
  } else {
    removeOldWidgets();
  }
})();
