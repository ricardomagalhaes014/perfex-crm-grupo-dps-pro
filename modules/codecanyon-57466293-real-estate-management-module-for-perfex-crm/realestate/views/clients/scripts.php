<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php hooks()->do_action('before_js_scripts_render'); ?>

<?php echo compile_theme_scripts();

/**
 * Global function for custom field of type hyperlink
 */
echo get_custom_fields_hyperlink_js_function(); ?>
<?php
/**
 * Check for any alerts stored in session
 */
app_js_alerts();
?>
<script type="text/javascript" id="pusher-js" src="https://js.pusher.com/5.0/pusher.min.js"></script>

<?php real_customers_portal_footer(); ?>
