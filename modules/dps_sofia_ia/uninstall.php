<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Só se apagam as opções. As tabelas ficam: são conhecimento que a empresa
 * construiu ao longo do tempo, e desinstalar um módulo por engano não é razão
 * para o deitar fora.
 */
$CI = &get_instance();

foreach ([
    'dps_sofia_ia_fornecedor',
    'dps_sofia_ia_api_key_claude',
    'dps_sofia_ia_api_key_openai',
    'dps_sofia_ia_modelo',
    'dps_sofia_ia_modelo_openai',
    'dps_sofia_ia_notificar_staff',
    'dps_sofia_ia_limite_hora',
    'dps_sofia_ia_persona',
    'dps_sofia_ia_schema_version',
] as $opcao) {
    delete_option($opcao);
}
