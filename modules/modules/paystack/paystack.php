<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Paystack | Pilha de pagamento
Description: Módulo paystack para pagamento de faturas.
Author: Boxvibe Technologies | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
Version: 1.0.0
Requires at least: 2.3.*
*/
require(__DIR__.'/vendor/autoload.php');
register_payment_gateway('paystack_gateway', 'paystack');
