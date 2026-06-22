/* Sofia Widget v2 - inject native ElevenLabs widget */
(function() {
  var AGENTS = {
    '/': 'agent_0901kv03vzc4eqnvzt5758mms6t8',
    '/raizes/': 'agent_7501kv0dj084fmbahfdafsfmgcfv',
    '/belohorizonte/': 'agent_1901kv0dj4m0fxnr5pxqdhqzjf26'
  };

  function getAgentId() {
    var path = window.location.pathname;
    // Normalize path
    if (!path.endsWith('/')) path += '/';
    return AGENTS[path] || AGENTS['/'];
  }

  function cleanup() {
    // Remove pill button elements
    var ids = ['dps-sofia-btn', 'dps-sofia-container', 'dps-btn', 'dps-tip'];
    ids.forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.remove();
    });
    // Remove injected widget by old sofia-widget
    var injected = document.getElementById('dps-sofia-el-widget');
    if (injected) injected.remove();
    // Show native widget if exists
    var native = document.querySelector('elevenlabs-convai:not(#dps-sofia-el-widget)');
    if (native) {
      native.style.cssText = '';
      native.removeAttribute('style');
    }
  }

  function injectWidget() {
    // Check if native widget already exists
    if (document.querySelector('elevenlabs-convai')) return;
    
    var agentId = getAgentId();
    
    // Inject elevenlabs-convai element
    var el = document.createElement('elevenlabs-convai');
    el.setAttribute('agent-id', agentId);
    document.body.appendChild(el);
    
    // Inject script if not already loaded
    if (!document.querySelector('script[src*="elevenlabs.io/convai-widget"]')) {
      var script = document.createElement('script');
      script.src = 'https://elevenlabs.io/convai-widget/index.js';
      script.async = true;
      script.type = 'text/javascript';
      document.body.appendChild(script);
    }
  }

  function init() {
    cleanup();
    injectWidget();
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
