<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Corre na ativação manual do módulo (register_activation_hook).
 *
 * A criação das tabelas e a semeadura das opções vivem em funções partilhadas
 * no bootstrap (dps_automacao.php) — a migração automática (ensure_schema)
 * usa exatamente as mesmas, por isso não há duas cópias do SQL a divergir.
 *
 * Os interruptores nascem a '0': ativar o módulo NÃO liga nenhum envio.
 */

dps_automacao_criar_tabelas();
dps_automacao_semear_opcoes();

// Marcar a versão do esquema para o ensure_schema do admin_init não repetir
// o trabalho no pedido seguinte.
update_option('dps_automacao_schema_version', DPS_AUTOMACAO_VERSION);

// Limpeza do cache de tabelas do CI: sem isto, o table_exists() consultado
// no resto deste mesmo pedido (menus, cron) não vê as tabelas recém-criadas.
// (Armadilha documentada em dps_vendas_ensure_schema.)
$CI = &get_instance();
$CI->db->data_cache = [];
