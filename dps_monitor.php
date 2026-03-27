<?php
/**
 * CI3 × MariaDB Monitor — Wrapper Web
 *
 * Acesso: https://crm.grupo-dps.com/dps_monitor.php?token=dps_monitor_2026
 *
 * Este ficheiro é apenas um ponto de entrada web que inclui o monitor principal.
 * O monitor real está em modules/dps_teams/tools/ci3_mariadb_monitor.php
 *
 * ATENÇÃO: Este ficheiro expõe informações de diagnóstico.
 * Proteger com token e remover em produção quando não necessário.
 */

// Definir o directório raiz do Perfex
define('PERFEX_ROOT', __DIR__);

// Incluir o monitor principal
require_once __DIR__ . '/modules/dps_teams/tools/ci3_mariadb_monitor.php';
