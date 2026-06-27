/* Sofia Widget v3 - popup + native ElevenLabs widget */
(function() {
  var AGENTS = {
    '/': 'agent_0901kv03vzc4eqnvzt5758mms6t8',
    '/raizes/': 'agent_7501kv0dj084fmbahfdafsfmgcfv',
    '/belohorizonte/': 'agent_1901kv0dj4m0fxnr5pxqdhqzjf26',
    '/boavistatowers/': 'agent_0901kv03vzc4eqnvzt5758mms6t8'
  };

  var MESSAGES = {
    '/': 'Olá! Sou a Sofia, assistente virtual da DPS Imobiliário. Posso ajudá-lo a encontrar o investimento imobiliário certo para si.',
    '/raizes/': 'Olá! Sou a Sofia. Quer saber mais sobre o Raízes Fânzeres? Estou aqui para ajudar!',
    '/belohorizonte/': 'Olá! Sou a Sofia. Posso apresentar-lhe o Belo Horizonte Residences e responder a todas as suas questões.',
    '/boavistatowers/': 'Olá! Sou a Sofia. Quer saber mais sobre o Boavista Tower? Estou disponível para o ajudar!'
  };

  var POPUP_SHOWN_KEY = 'dps_sofia_popup_shown';

  function getPath() {
    var path = window.location.pathname;
    if (!path.endsWith('/')) path += '/';
    return path;
  }

  function getAgentId() {
    var path = getPath();
    return AGENTS[path] || AGENTS['/'];
  }

  function getMessage() {
    var path = getPath();
    return MESSAGES[path] || MESSAGES['/'];
  }

  function cleanup() {
    var ids = ['dps-sofia-btn', 'dps-sofia-container', 'dps-btn', 'dps-tip'];
    ids.forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.remove();
    });
    var injected = document.getElementById('dps-sofia-el-widget');
    if (injected) injected.remove();
    var native = document.querySelector('elevenlabs-convai:not(#dps-sofia-el-widget)');
    if (native) {
      native.style.cssText = '';
      native.removeAttribute('style');
    }
  }

  function injectWidget() {
    if (document.querySelector('elevenlabs-convai')) return;
    var agentId = getAgentId();
    var el = document.createElement('elevenlabs-convai');
    el.setAttribute('agent-id', agentId);
    document.body.appendChild(el);
    if (!document.querySelector('script[src*="elevenlabs.io/convai-widget"]')) {
      var script = document.createElement('script');
      script.src = 'https://elevenlabs.io/convai-widget/index.js';
      script.async = true;
      script.type = 'text/javascript';
      document.body.appendChild(script);
    }
  }

  function openWidget() {
    var widget = document.querySelector('elevenlabs-convai');
    if (widget) {
      var btn = widget.shadowRoot && widget.shadowRoot.querySelector('button');
      if (btn) btn.click();
    }
  }

  function closePopup() {
    var popup = document.getElementById('dps-sofia-popup');
    if (popup) {
      popup.style.opacity = '0';
      popup.style.transform = 'translateY(20px)';
      setTimeout(function() { if (popup) popup.remove(); }, 400);
    }
  }

  function showPopup() {
    if (document.getElementById('dps-sofia-popup')) return;

    var msg = getMessage();

    var popup = document.createElement('div');
    popup.id = 'dps-sofia-popup';
    popup.style.cssText = [
      'position:fixed',
      'bottom:90px',
      'right:24px',
      'z-index:99999',
      'width:300px',
      'background:#1a1a1a',
      'border:1px solid rgba(197,165,90,0.35)',
      'border-radius:16px',
      'box-shadow:0 8px 40px rgba(0,0,0,0.45)',
      'padding:20px 18px 16px',
      'font-family:Inter,sans-serif',
      'opacity:0',
      'transform:translateY(20px)',
      'transition:opacity 0.4s ease, transform 0.4s ease',
      'cursor:default'
    ].join(';');

    popup.innerHTML = [
      '<button id="dps-sofia-close" style="position:absolute;top:10px;right:12px;background:none;border:none;color:rgba(255,255,255,0.4);font-size:18px;cursor:pointer;line-height:1;padding:0;" aria-label="Fechar">&times;</button>',
      '<div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">',
        '<div style="position:relative;flex-shrink:0;">',
          '<img src="https://dpsimobiliario.pt/sofia_avatar.jpg" alt="Sofia" style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid #C5A55A;">',
          '<span style="position:absolute;bottom:2px;right:2px;width:10px;height:10px;background:#22c55e;border-radius:50%;border:2px solid #1a1a1a;"></span>',
        '</div>',
        '<div>',
          '<div style="font-weight:600;color:#fff;font-size:0.9rem;">Sofia</div>',
          '<div style="font-size:0.72rem;color:#C5A55A;letter-spacing:0.05em;">Assistente DPS &bull; Online</div>',
        '</div>',
      '</div>',
      '<div style="font-size:0.82rem;color:rgba(255,255,255,0.8);line-height:1.55;margin-bottom:14px;">' + msg + '</div>',
      '<button id="dps-sofia-start" style="width:100%;background:linear-gradient(135deg,#C5A55A,#a8893d);color:#fff;border:none;border-radius:8px;padding:10px 0;font-size:0.82rem;font-weight:600;letter-spacing:0.06em;cursor:pointer;transition:opacity 0.2s;">',
        '&#127908; Falar com a Sofia',
      '</button>'
    ].join('');

    document.body.appendChild(popup);

    // Trigger animation
    setTimeout(function() {
      popup.style.opacity = '1';
      popup.style.transform = 'translateY(0)';
    }, 50);

    // Close button
    document.getElementById('dps-sofia-close').addEventListener('click', function(e) {
      e.stopPropagation();
      closePopup();
    });

    // Start button — open ElevenLabs widget
    document.getElementById('dps-sofia-start').addEventListener('click', function() {
      closePopup();
      setTimeout(openWidget, 300);
    });

    // Auto-close after 12 seconds
    setTimeout(closePopup, 12000);
  }

  function init() {
    cleanup();
    injectWidget();
    // Show popup after 3 seconds
    setTimeout(showPopup, 3000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  setTimeout(cleanup, 300);
  setTimeout(cleanup, 800);
  setTimeout(cleanup, 2000);
})();
