/* Sofia Widget - disabled. Remove pill button, show native ElevenLabs widget. */
(function() {
  function cleanup() {
    var ids = ['dps-sofia-btn', 'dps-sofia-container', 'dps-btn', 'dps-tip'];
    ids.forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.remove();
    });
    var native = document.querySelector('elevenlabs-convai:not(#dps-sofia-el-widget)');
    if (native) {
      native.style.cssText = '';
      native.removeAttribute('style');
    }
    var injected = document.getElementById('dps-sofia-el-widget');
    if (injected) injected.remove();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cleanup);
  } else {
    cleanup();
  }
  setTimeout(cleanup, 300);
  setTimeout(cleanup, 800);
  setTimeout(cleanup, 2000);
})();
