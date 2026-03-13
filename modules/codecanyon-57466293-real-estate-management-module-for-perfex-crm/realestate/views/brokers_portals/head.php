<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?php echo new_html_entity_decode($locale); ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<title><?php if (isset($title)){ echo new_html_entity_decode($title); } ?></title>

	<?php if(isset($meta_description)){ ?>
		<meta name="description" content="<?php echo new_html_entity_decode($meta_description); ?>">
	<?php } ?>

	<?php echo compile_theme_css(); ?>
	<script src="<?php echo base_url('assets/plugins/jquery/jquery.min.js'); ?>"></script>
	<script>
    var
        billingAndShippingFields = ['billing_street', 'billing_city', 'billing_state', 'billing_zip', 'billing_country',
            'shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'
        ]
        ;
    </script>
	<?php app_broker_portal_head(); ?>
</head>
<body class="customers<?php if(is_mobile()){echo ' mobile';}?><?php if(isset($bodyclass)){echo ' ' . $bodyclass; } ?>" >
	<?php hooks()->do_action('after_broker_body_start'); ?>
