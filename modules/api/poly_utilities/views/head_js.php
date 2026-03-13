<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Utilize on pages where disabling page reload requests is required.
 */
?>
<script>
    // ==== Disable confirm reload ==== //
    window.addEventListener('beforeunload', e => {
        window.onbeforeunload = null;
        e.stopImmediatePropagation();
    });

    addEventListener('beforeunload', event => {});
    onbeforeunload = event => {};
</script>