<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Corre na activação do módulo E na migração automática (admin_init), por isso
 * tudo aqui tem de ser idempotente: CREATE TABLE IF NOT EXISTS e add_option,
 * que não sobrepõe uma opção já existente.
 */

$CI = &get_instance();

/*
 * Uma ficha de conhecimento: um PDF importado, um texto colado pelo admin, o
 * prompt da Sofia das chamadas, ou a resposta que o admin deu a uma pergunta
 * que a Sofia não soube.
 */
$CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "dps_sofia_conhecimento` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `titulo` varchar(255) NOT NULL,
    `categoria` varchar(100) DEFAULT NULL,
    `conteudo` longtext NOT NULL,
    `fonte` varchar(40) NOT NULL DEFAULT 'manual',
    `ficheiro` varchar(255) DEFAULT NULL,
    `sempre_incluir` tinyint(1) NOT NULL DEFAULT 0,
    `ativo` tinyint(1) NOT NULL DEFAULT 1,
    `criado_por` int(11) DEFAULT NULL,
    `dateadded` datetime NOT NULL,
    `dateupdated` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ativo` (`ativo`),
    KEY `sempre_incluir` (`sempre_incluir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/*
 * Cada ficha é partida em trechos. É sobre os trechos que se procura: mandar a
 * base inteira ao modelo a cada pergunta seria caro e, passados uns quantos
 * PDFs, deixaria de caber.
 *
 * `texto_norm` é o mesmo texto sem acentos e em minúsculas. Guarda-se em vez de
 * se normalizar na consulta porque a collation da base não trata "ç" e "c" como
 * iguais de forma fiável, e uma procura por "preços" tem de encontrar "precos".
 */
$CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'dps_sofia_trechos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `conhecimento_id` int(11) NOT NULL,
    `ordem` int(11) NOT NULL DEFAULT 0,
    `texto` text NOT NULL,
    `texto_norm` text NOT NULL,
    PRIMARY KEY (`id`),
    KEY `conhecimento_id` (`conhecimento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

$CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'dps_sofia_conversas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `titulo` varchar(255) DEFAULT NULL,
    `dateadded` datetime NOT NULL,
    `dateupdated` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

$CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "dps_sofia_mensagens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `conversa_id` int(11) NOT NULL,
    `papel` enum('comercial','sofia') NOT NULL,
    `texto` longtext NOT NULL,
    `fontes` text DEFAULT NULL,
    `sem_resposta` tinyint(1) NOT NULL DEFAULT 0,
    `tokens_entrada` int(11) DEFAULT NULL,
    `tokens_saida` int(11) DEFAULT NULL,
    `dateadded` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `conversa_id` (`conversa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/*
 * A caixa de entrada da administração. Entram aqui dois casos, distinguidos
 * por `tipo`:
 *
 *   sem_resposta — a Sofia disse que o conhecimento carregado não chegava;
 *   reporte      — a Sofia respondeu, mas o comercial diz que está errado.
 *
 * O segundo caso é o mais importante dos dois: uma resposta errada que ninguém
 * reporta fica na base a ser repetida. Por isso guarda-se também o que a Sofia
 * respondeu (`resposta_sofia`) e o que o comercial diz que está mal (`nota`) —
 * sem isso o admin estaria a corrigir às cegas.
 */
$CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "dps_sofia_pendentes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tipo` enum('sem_resposta','reporte') NOT NULL DEFAULT 'sem_resposta',
    `conversa_id` int(11) DEFAULT NULL,
    `mensagem_id` int(11) DEFAULT NULL,
    `staff_id` int(11) NOT NULL,
    `pergunta` text NOT NULL,
    `resposta_sofia` longtext DEFAULT NULL,
    `nota` text DEFAULT NULL,
    `estado` enum('aberta','respondida','ignorada') NOT NULL DEFAULT 'aberta',
    `resposta` longtext DEFAULT NULL,
    `conhecimento_id` int(11) DEFAULT NULL,
    `respondido_por` int(11) DEFAULT NULL,
    `respondido_em` datetime DEFAULT NULL,
    `dateadded` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `estado` (`estado`),
    KEY `tipo` (`tipo`),
    KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/*
 * As chaves nascem vazias e escrevem-se no ecrã de Definições, que as guarda na
 * base de dados. Nunca no código — foi o erro que já se corrigiu na chave da
 * ElevenLabs, que viajava no repositório.
 */
/*
 * Nasce em modo "local" (procura interna) de propósito: assim o módulo funciona
 * no minuto a seguir a ser instalado, sem chave nem conta em lado nenhum. Passar
 * a Claude é trocar uma opção no ecrã de Definições — a base de conhecimento é
 * a mesma nos dois modos, nada do que for carregado se perde na troca.
 */
add_option('dps_sofia_ia_fornecedor', 'local');
add_option('dps_sofia_ia_api_key_claude', '');
add_option('dps_sofia_ia_api_key_openai', '');
add_option('dps_sofia_ia_modelo', 'claude-opus-5');
add_option('dps_sofia_ia_modelo_openai', 'gpt-4o');
add_option('dps_sofia_ia_notificar_staff', '');
add_option('dps_sofia_ia_limite_hora', '40');
add_option('dps_sofia_ia_persona', dps_sofia_ia_persona_por_omissao());

// Pasta dos PDFs/documentos originais, fora do alcance da web.
$pasta = FCPATH . DPS_SOFIA_IA_UPLOAD_PATH;
if (!is_dir($pasta)) {
    @mkdir($pasta, 0755, true);
}
if (!file_exists($pasta . '.htaccess')) {
    @file_put_contents($pasta . '.htaccess', "Deny from all\n");
}
if (!file_exists($pasta . 'index.html')) {
    @file_put_contents($pasta . 'index.html', '');
}
