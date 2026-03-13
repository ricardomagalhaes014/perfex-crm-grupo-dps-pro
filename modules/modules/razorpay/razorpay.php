<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Razorpay Gateway
Description: A maneira mais fácil de aceitar pagamentos para empresas na Índia
Version: 2.3.0
Author: Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
Requires at least: 2.3.4
*/

require_once(__DIR__ . '/vendor/autoload.php');

register_payment_gateway('razor_pay_gateway', 'razorpay');
