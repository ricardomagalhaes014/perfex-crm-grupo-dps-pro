<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Painel do Negócio
Description: Painel administrativo (só Ricardo): comissões recebidas/pagas, recibos, despesas, resultado e ligação Moloni.
Version: 1.1.1
Author: DPS
Requires at least: 2.3.*
*/

define('DPS_PAINEL_MODULE', 'dps_painel');
define('DPS_PAINEL_VERSION', '1.2.0');
define('DPS_PAINEL_UPLOAD', 'uploads/dps_painel/');

/**
 * Só o Ricardo (staff 1) vê o painel INTEIRO.
 */
function dps_painel_is_owner()
{
    return function_exists('get_staff_user_id') && (int) get_staff_user_id() === 1;
}

/**
 * Quem vê apenas a secção "O que sai" — o que se paga aos comerciais e à
 * direcção.
 *
 * A Samara e o Cláudio precisam de acompanhar o que a casa deve, mas o resto
 * do painel — quanto a DPS recebe do promotor, a tesouraria, o resultado — é
 * do dono e continua fechado. É por isso que não basta dar-lhes acesso ao
 * módulo: entram, mas vêem só esta parte.
 *
 * Para acrescentar alguém, junta-se o número a esta lista.
 */
function dps_painel_ve_o_que_sai()
{
    if (!function_exists('get_staff_user_id')) {
        return false;
    }

    return in_array((int) get_staff_user_id(), [17, 46], true);   // Samara, Cláudio
}

/** Entra no módulo: o dono (tudo) ou quem só vê "O que sai". */
function dps_painel_pode_entrar()
{
    return dps_painel_is_owner() || dps_painel_ve_o_que_sai();
}

/**
 * Garante a pasta de uploads das despesas com o .htaccess de bloqueio, e
 * devolve o caminho absoluto (com barra final).
 *
 * As facturas de despesa são contabilidade da casa. Sem .htaccess, o
 * `RewriteCond %{REQUEST_FILENAME} !-f` do .htaccess da raiz faz o Apache
 * servir qualquer ficheiro que exista em disco sem passar pelo index.php — ou
 * seja, sem AdminController, sem dps_painel_is_owner(), sem sessão nenhuma —, e
 * o gate do controlador em despesa_doc() tornava-se irrelevante. O index.html
 * vazio tapa ainda a listagem de directório em alojamentos com indexação
 * ligada, que anularia a aleatoriedade do nome do ficheiro.
 *
 * É a mesma protecção que o dps_vendas já faz em install.php (uploads/dps_vendas)
 * e em Dps_vendas::arquivo_upload (uploads/dps_arquivo).
 */
function dps_painel_pasta_uploads()
{
    $base = FCPATH . DPS_PAINEL_UPLOAD;

    if (!is_dir($base)) {
        @mkdir($base, 0755, true);
    }

    if (is_dir($base) && !file_exists($base . '.htaccess')) {
        // As duas sintaxes: Apache 2.4 (Require) e 2.2/LiteSpeed (Order).
        @file_put_contents(
            $base . '.htaccess',
            "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\nOrder Deny,Allow\nDeny from all\n</IfModule>\n"
            . "Options -Indexes\n"
        );
    }
    if (is_dir($base) && !file_exists($base . 'index.html')) {
        @file_put_contents($base . 'index.html', '');
    }

    return $base;
}

register_activation_hook(DPS_PAINEL_MODULE, 'dps_painel_activate');
function dps_painel_activate()
{
    dps_painel_ensure_schema();
    dps_painel_pasta_uploads();
}

hooks()->add_action('admin_init', 'dps_painel_ensure_schema');
hooks()->add_action('admin_init', 'dps_painel_menu');

function dps_painel_menu()
{
    if (!dps_painel_pode_entrar()) {
        return;
    }
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_painel', [
        'slug'     => 'dps_painel',
        'name'     => 'Painel do Negócio',
        /*
         * O ?v=2 é um quebra-cache, não um parâmetro com significado.
         *
         * Enquanto o controlador usou access_denied(), o Perfex redireccionava
         * para admin/access_denied — rota que não existe — e devolvia 404. O
         * Safari guardou esse 404 para este endereço e continuava a servi-lo da
         * memória mesmo depois de corrigido. Mudar o endereço obriga o browser
         * a perguntar outra vez ao servidor.
         *
         * Pode ser retirado quando já ninguém tiver o 404 em cache; se voltar a
         * acontecer o mesmo, basta subir o número.
         */
        'href'     => admin_url('dps_painel') . '?v=2',
        'icon'     => 'fa fa-briefcase',
        'position' => 6,
    ]);
}

/*
 * Migração do esquema.
 *
 * O portão é a option `dps_painel_schema`: enquanto não igualar a versão do
 * módulo, isto corre em cada pedido admin. Por isso tudo aqui dentro tem de ser
 * idempotente — e por isso a tabela nova usa CREATE TABLE IF NOT EXISTS, que
 * dispensa o table_exists() e não rebenta se a option se perder.
 *
 * ATENÇÃO para o futuro: acrescentar COLUNAS a uma tabela já criada exige
 * bumpar DPS_PAINEL_VERSION *e* escrever o ALTER TABLE explícito aqui — o
 * CREATE só apanha tabelas que ainda não existem.
 */
function dps_painel_ensure_schema()
{
    if (get_option('dps_painel_schema') === DPS_PAINEL_VERSION) {
        return;
    }
    $CI = &get_instance();

    $ov = db_prefix() . 'dps_painel_vendas';
    if (!$CI->db->table_exists($ov)) {
        $CI->db->query("CREATE TABLE `{$ov}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `venda_id` INT(11) NOT NULL,
            `comissao_recebida` DECIMAL(15,2) NULL DEFAULT NULL,
            `recibo_emitido` TINYINT(1) NOT NULL DEFAULT 0,
            `recibo_numero` VARCHAR(60) NULL DEFAULT NULL,
            `recibo_data` DATE NULL DEFAULT NULL,
            `moloni_doc_id` INT(11) NULL DEFAULT NULL,
            `notas` TEXT NULL,
            `dateupdated` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `venda_id` (`venda_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    $dp = db_prefix() . 'dps_painel_despesas';
    if (!$CI->db->table_exists($dp)) {
        $CI->db->query("CREATE TABLE `{$dp}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `data` DATE NOT NULL,
            `categoria` VARCHAR(80) NULL DEFAULT NULL,
            `descricao` VARCHAR(255) NULL DEFAULT NULL,
            `valor` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `fatura_numero` VARCHAR(80) NULL DEFAULT NULL,
            `doc` VARCHAR(255) NULL DEFAULT NULL,
            `dateadded` DATETIME NULL DEFAULT NULL,
            `created_by` INT(11) NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    /*
     * O que a DPS RECEBE de cada empreendimento (comissão do promotor).
     *
     * Vive aqui, no módulo privado, e não em tblcomissao_regras: as Regras de
     * Comissão do dps_vendas são visíveis aos comerciais e não podem mostrar o
     * que a casa recebe. É a mesma ideia (uma taxa por empreendimento), mas do
     * lado de dentro.
     */
    $rc = db_prefix() . 'dps_painel_recebimento';
    $CI->db->query("CREATE TABLE IF NOT EXISTS `{$rc}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `empreendimento` VARCHAR(191) NOT NULL,
        `taxa_recebida` DECIMAL(8,4) NOT NULL DEFAULT 0,
        `cpcv_pct` DECIMAL(8,4) NULL DEFAULT NULL,
        `escritura_pct` DECIMAL(8,4) NULL DEFAULT NULL,
        `notas` TEXT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `updated_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `empreendimento` (`empreendimento`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    /*
     * 1.2.0 — o que recebemos também entra em duas tranches (CPCV e
     * escritura), tal como a comissão que pagamos. As percentagens são DA
     * VERBA RECEBIDA (ex.: 66% no CPCV, 34% na escritura); os MESES vêm das
     * Regras de Comissão, para não haver dois sítios a dizer prazos
     * diferentes para o mesmo empreendimento.
     *
     * CREATE TABLE IF NOT EXISTS não acrescenta colunas a uma tabela que já
     * exista — daí o ALTER explícito, feito só quando a coluna falta.
     */
    $colunas_rc = [];
    foreach ($CI->db->field_data($rc) as $f) {
        $colunas_rc[] = $f->name;
    }
    foreach ([
        'cpcv_pct'      => 'DECIMAL(8,4) NULL DEFAULT NULL',
        'escritura_pct' => 'DECIMAL(8,4) NULL DEFAULT NULL',
    ] as $coluna => $def) {
        if (!in_array($coluna, $colunas_rc, true)) {
            $CI->db->query("ALTER TABLE `{$rc}` ADD `{$coluna}` {$def}");
        }
    }

    /*
     * 1.1.1 — blindar uploads/dps_painel/. As instalações que já existiam
     * criaram a pasta sem .htaccess (as facturas de despesa ficavam servidas
     * directamente pelo Apache); o bump da versão faz isto correr uma vez.
     */
    dps_painel_pasta_uploads();

    update_option('dps_painel_schema', DPS_PAINEL_VERSION);
}

/**
 * As três categorias de despesa. Lista fechada de propósito: em campo livre
 * a mesma despesa aparecia como "Marketing", "marketing" e "MKT", e no fim
 * do ano não somava nada.
 */
if (!function_exists('dps_painel_categorias_despesa')) {
    function dps_painel_categorias_despesa()
    {
        return ['Representação', 'Marketing', 'Outros'];
    }
}

/** "2026-08" -> "Agosto de 2026". */
if (!function_exists('dps_painel_mes_extenso')) {
    function dps_painel_mes_extenso($mes)
    {
        $nomes = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                  'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $p = explode('-', (string) $mes);

        return isset($p[1], $nomes[(int) $p[1]]) ? $nomes[(int) $p[1]] . ' de ' . $p[0] : (string) $mes;
    }
}
