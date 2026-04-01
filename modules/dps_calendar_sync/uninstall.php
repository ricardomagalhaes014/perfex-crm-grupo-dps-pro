<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Script de desinstalação para o módulo DPS Calendar Sync.
 * Remove tabelas e opções criadas pelo módulo.
 */
$CI = &get_instance();

// Remover tabelas
$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'dps_calendar_tokens`');
$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'dps_calendar_events`');

// Remover opções
delete_option('dps_calendar_sync_client_id');
delete_option('dps_calendar_sync_client_secret');
delete_option('dps_calendar_sync_redirect_uri');
delete_option('dps_calendar_sync_calendar_id');
