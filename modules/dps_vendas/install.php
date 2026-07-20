<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$vendas     = db_prefix() . 'simulador_vendas';
$docs       = db_prefix() . 'vendas_docs';
$historico  = db_prefix() . 'vendas_historico';
$regras     = db_prefix() . 'comissao_regras';

/*
 * -------------------------------------------------------------------------
 * 1. Estender a tabela de vendas que já existe (do módulo simulador_comissoes)
 * -------------------------------------------------------------------------
 * Só acrescentamos colunas — nada é removido nem alterado, para o simulador
 * continuar a funcionar exactamente como antes.
 *
 * NOTA sobre `estado`: assume 'pendente' por omissão. A tabela estava vazia
 * quando o módulo foi instalado, por isso não há registos antigos a proteger —
 * e assim qualquer venda criada pelo simulador entra automaticamente no
 * circuito, em vez de ficar de fora dele. O código continua a tolerar NULL,
 * caso alguma linha apareça sem estado.
 */
if ($CI->db->table_exists($vendas)) {
    $existing = array_map(function ($f) {
        return $f->name;
    }, $CI->db->field_data($vendas));

    $novas_colunas = [
        'estado'           => "ENUM('pendente','para_assinatura','assinado','pago','recebido') NULL DEFAULT 'pendente'",
        'origem'           => "ENUM('formulario','simulador','manual') NOT NULL DEFAULT 'manual'",
        'lead_id'          => 'INT NULL DEFAULT NULL',
        'cliente_morada'   => 'VARCHAR(255) NULL DEFAULT NULL',
        'cliente_telefone' => 'VARCHAR(50) NULL DEFAULT NULL',
        'cliente_email'    => 'VARCHAR(191) NULL DEFAULT NULL',
        'regime_civil'     => 'VARCHAR(100) NULL DEFAULT NULL',
        'comissao_estado'  => "ENUM('na','a_receber','recebida') NOT NULL DEFAULT 'na'",
        'dateupdated'      => 'DATETIME NULL DEFAULT NULL',
        'created_by'       => 'INT NULL DEFAULT NULL',
    ];

    foreach ($novas_colunas as $coluna => $definicao) {
        if (!in_array($coluna, $existing, true)) {
            $CI->db->query("ALTER TABLE `{$vendas}` ADD `{$coluna}` {$definicao}");
        }
    }

    // Índices só se ainda não existirem (evita erro em reinstalação)
    $indices = $CI->db->query("SHOW INDEX FROM `{$vendas}`")->result_array();
    $nomes   = array_column($indices, 'Key_name');

    if (!in_array('estado', $nomes, true)) {
        $CI->db->query("ALTER TABLE `{$vendas}` ADD INDEX `estado` (`estado`)");
    }
    if (!in_array('lead_id', $nomes, true)) {
        $CI->db->query("ALTER TABLE `{$vendas}` ADD INDEX `lead_id` (`lead_id`)");
    }
}

/*
 * -------------------------------------------------------------------------
 * 2. Documentos da venda (CC frente/verso obrigatórios + outros)
 * -------------------------------------------------------------------------
 */
if (!$CI->db->table_exists($docs)) {
    $CI->db->query('CREATE TABLE `' . $docs . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `venda_id` INT NOT NULL,
        `tipo` ENUM('cc_frente','cc_verso','outro') NOT NULL DEFAULT 'outro',
        `filename` VARCHAR(255) NOT NULL,
        `original_name` VARCHAR(255) NULL,
        `uploaded_by` INT NULL,
        `dateadded` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `venda_id` (`venda_id`),
        KEY `tipo` (`tipo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

/*
 * -------------------------------------------------------------------------
 * 3. Histórico de transições de estado
 * -------------------------------------------------------------------------
 */
if (!$CI->db->table_exists($historico)) {
    $CI->db->query('CREATE TABLE `' . $historico . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `venda_id` INT NOT NULL,
        `estado_de` VARCHAR(30) NULL,
        `estado_para` VARCHAR(30) NOT NULL,
        `staff_id` INT NULL,
        `nota` TEXT NULL,
        `dateadded` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `venda_id` (`venda_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

/*
 * -------------------------------------------------------------------------
 * 4. Regras de comissão por empreendimento
 * -------------------------------------------------------------------------
 * Até aqui a taxa era escrita à mão em cada venda. Esta tabela passa a ser a
 * fonte da verdade; a venda continua a guardar a taxa aplicada (snapshot),
 * para que alterar uma regra no futuro não reescreva o histórico.
 */
if (!$CI->db->table_exists($regras)) {
    $CI->db->query('CREATE TABLE `' . $regras . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `empreendimento` VARCHAR(191) NOT NULL,
        `taxa` DECIMAL(8,4) NOT NULL DEFAULT '0.0000',
        `cpcv_taxa` DECIMAL(8,4) NULL DEFAULT NULL,
        `escritura_taxa` DECIMAL(8,4) NULL DEFAULT NULL,
        `ativo` TINYINT(1) NOT NULL DEFAULT '1',
        `notas` TEXT NULL,
        `dateadded` DATETIME NOT NULL,
        `updated_by` INT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `empreendimento` (`empreendimento`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    /*
     * Semeadura em dois tempos.
     *
     * 1) Se houver vendas, adoptamos a taxa mais recente praticada em cada
     *    empreendimento — é o valor real, melhor ponto de partida que qualquer
     *    palpite.
     * 2) Acrescentamos os empreendimentos do simulador que ainda não tenham
     *    regra, com taxa 0 e marcados como POR DEFINIR. Servem só para o
     *    utilizador não ter de os escrever à mão; enquanto a taxa for 0 o
     *    módulo avisa que a comissão não está configurada.
     */
    if ($CI->db->table_exists($vendas)) {
        $seed = $CI->db->query(
            "SELECT v.empreendimento, v.taxa, v.cpcv_taxa, v.escritura_taxa
             FROM `{$vendas}` v
             INNER JOIN (
                 SELECT empreendimento, MAX(id) AS max_id
                 FROM `{$vendas}`
                 WHERE empreendimento IS NOT NULL AND empreendimento <> ''
                 GROUP BY empreendimento
             ) ult ON ult.max_id = v.id"
        )->result_array();

        foreach ($seed as $linha) {
            $CI->db->insert($regras, [
                'empreendimento' => $linha['empreendimento'],
                'taxa'           => $linha['taxa'] ?: 0,
                'cpcv_taxa'      => $linha['cpcv_taxa'],
                'escritura_taxa' => $linha['escritura_taxa'],
                'ativo'          => 1,
                'notas'          => 'Semeado a partir da venda mais recente deste empreendimento. Confirmar.',
                'dateadded'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // Empreendimentos actualmente no simulador de vendas (dpsimobiliario.pt)
    $do_simulador = [
        'Boavista Towers',
        'Raízes Fanzeres',
        'Belo Horizonte',
        'Lake Towers',
        'Gaia Premium',
    ];

    foreach ($do_simulador as $nome) {
        $ja_existe = $CI->db->where('LOWER(TRIM(empreendimento))', strtolower($nome))
            ->count_all_results($regras);

        if (!$ja_existe) {
            $CI->db->insert($regras, [
                'empreendimento' => $nome,
                'taxa'           => 0,
                'ativo'          => 1,
                'notas'          => 'TAXA POR DEFINIR — nenhuma comissão será calculada enquanto estiver a 0.',
                'dateadded'      => date('Y-m-d H:i:s'),
            ]);
        }
    }
}

/*
 * -------------------------------------------------------------------------
 * 5. Pasta de uploads protegida
 * -------------------------------------------------------------------------
 * Os documentos são Cartões de Cidadão. Ficam fora do alcance directo do
 * browser: o .htaccess bloqueia o acesso e o download passa obrigatoriamente
 * pelo controller, que verifica permissões.
 */
$upload_path = FCPATH . 'uploads/dps_vendas/';
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0755, true);
}

$htaccess = $upload_path . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

$index_html = $upload_path . 'index.html';
if (!file_exists($index_html)) {
    file_put_contents($index_html, '');
}
