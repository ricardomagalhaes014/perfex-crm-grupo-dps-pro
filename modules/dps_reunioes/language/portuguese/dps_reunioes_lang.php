<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * O Perfex carrega SEMPRE o inglês, mesmo com o CRM em português. Um módulo
 * sem esta pasta derruba o backoffice inteiro com "Unable to load the
 * requested language file" — aconteceu a 01/08/2026 e o CRM esteve em baixo.
 */
$lang['dps_reunioes']            = 'Reuniões online';
$lang['dps_reunioes_marcar']     = 'Marcar reunião';
$lang['dps_reunioes_agendada']   = 'Agendada';
$lang['dps_reunioes_realizada']  = 'Realizada';
$lang['dps_reunioes_faltou']     = 'Não compareceu';
$lang['dps_reunioes_cancelada']  = 'Cancelada';
