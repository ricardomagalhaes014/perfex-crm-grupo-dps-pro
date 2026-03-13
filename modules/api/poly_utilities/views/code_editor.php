<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Included in views/scripts/create.php, views/styles/create.php
 */
// ==== Code editor ==== //
echo render_textarea('poly_utilities_resource_content', '', $contents, [], [], 'poly-utilities-code-editor');
?>
<!-- Tutorials -->
<div>
    <div><strong>Ctr-S</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_save') ?></div>
    <div><strong>Ctr-Space</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_autocomplete') ?></div>
    <div><strong>Ctrl-F / Cmd-F</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_search') ?></div>
    <div><strong>Ctrl-G / Cmd-G</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_find_next') ?></div>
    <div><strong>Shift-Ctrl-G / Shift-Cmd-G</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_find_previous') ?></div>
    <div><strong>Shift-Ctrl-F / Cmd-Option-F</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_find_replace') ?></div>
    <div><strong>Shift-Ctrl-R / Shift-Cmd-Option-F</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_find_replace_all') ?></div>
    <div><strong>Alt-F</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_search_continue') ?></div>
    <div><strong>Alt-G</strong>: <?php echo _l('poly_utilities_code_editor_hotkey_jump_to_line') ?></div>
</div>
<!-- Tutorials -->