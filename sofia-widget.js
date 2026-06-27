/* Sofia Widget v4 - popup 50% iframe sofiadps + native ElevenLabs widget */
(function() {
  var AGENTS = {
    '/': 'agent_0901kv03vzc4eqnvzt5758mms6t8',
    '/raizes/': 'agent_7501kv0dj084fmbahfdafsfmgcfv',
    '/belohorizonte/': 'agent_1901kv0dj4m0fxnr5pxqdhqzjf26',
    '/boavistatowers/': 'agent_0901kv03vzc4eqnvzt5758mms6t8'
  };

  function getAgentId() {
    var path = window.location.pathname;
    if (!path.endsWith('/')) path += '/';
    return AGENTS[path] || AGENTS['/'];
  }

  /* ── limpar elementos antigos ── */
  function cleanup() {
    ['dps-sofia-btn','dps-sofia-container','dps-btn','dps-tip',
     'dps-sofia-popup','dps-sofia-overlay'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.remove();
    });
  }

  /* ── injectar widget ElevenLabs nativo ── */
  function injectWidget() {
    if (document.querySelector('elevenlabs-convai')) return;
    var el = document.createElement('elevenlabs-convai');
    el.setAttribute('agent-id', getAgentId());
    document.body.appendChild(el);
    if (!document.querySelector('script[src*="elevenlabs.io/convai-widget"]')) {
      var s = document.createElement('script');
      s.src = 'https://elevenlabs.io/convai-widget/index.js';
      s.async = true;
      document.body.appendChild(s);
    }
  }

  /* ── fechar popup ── */
  function closePopup() {
    var overlay = document.getElementById('dps-sofia-overlay');
    if (overlay) {
      overlay.style.opacity = '0';
      setTimeout(function() { if (overlay) overlay.remove(); }, 350);
    }
  }

  /* ── mostrar popup 50% com iframe ── */
  function showPopup() {
    if (document.getElementById('dps-sofia-overlay')) return;

    /* overlay escuro */
    var overlay = document.createElement('div');
    overlay.id = 'dps-sofia-overlay';
    overlay.style.cssText = [
      'position:fixed','inset:0','z-index:999998',
      'background:rgba(0,0,0,0.65)',
      'display:flex','align-items:center','justify-content:center',
      'opacity:0','transition:opacity 0.4s ease'
    ].join(';');

    /* modal 50% largura, 85% altura */
    var modal = document.createElement('div');
    modal.style.cssText = [
      'position:relative',
      'width:min(50vw,700px)',
      'height:85vh',
      'border-radius:16px',
      'overflow:hidden',
      'box-shadow:0 20px 60px rgba(0,0,0,0.6)',
      'transform:translateY(30px)',
      'transition:transform 0.4s ease'
    ].join(';');

    /* botão fechar */
    var closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.setAttribute('aria-label', 'Fechar');
    closeBtn.style.cssText = [
      'position:absolute','top:12px','right:14px',
      'z-index:10','background:rgba(0,0,0,0.55)',
      'border:none','color:#fff','font-size:22px',
      'width:36px','height:36px','border-radius:50%',
      'cursor:pointer','line-height:1','display:flex',
      'align-items:center','justify-content:center'
    ].join(';');
    closeBtn.addEventListener('click', closePopup);

    /* iframe */
    var iframe = document.createElement('iframe');
    iframe.src = 'https://dpsimobiliario.pt/sofiadps/';
    iframe.style.cssText = 'width:100%;height:100%;border:none;display:block;';
    iframe.setAttribute('loading', 'lazy');

    modal.appendChild(closeBtn);
    modal.appendChild(iframe);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    /* fechar ao clicar fora do modal */
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closePopup();
    });

    /* animação de entrada */
    requestAnimationFrame(function() {
      overlay.style.opacity = '1';
      modal.style.transform = 'translateY(0)';
    });
  }

  /* ── init ── */
  function init() {
    cleanup();
    injectWidget();
    setTimeout(showPopup, 3000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
