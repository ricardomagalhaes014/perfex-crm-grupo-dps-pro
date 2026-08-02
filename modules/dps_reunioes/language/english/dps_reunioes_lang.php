<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * O Perfex carrega SEMPRE o inglês, mesmo com o CRM em português. Um módulo
 * sem esta pasta derruba o backoffice inteiro com "Unable to load the
 * requested language file" — aconteceu a 01/08/2026 e o CRM esteve em baixo.
 */
$lang['dps_reunioes']            = 'Online meetings';
$lang['dps_reunioes_marcar']     = 'Schedule meeting';
$lang['dps_reunioes_agendada']   = 'Scheduled';
$lang['dps_reunioes_realizada']  = 'Held';
$lang['dps_reunioes_faltou']     = 'No-show';
$lang['dps_reunioes_cancelada']  = 'Cancelled';
