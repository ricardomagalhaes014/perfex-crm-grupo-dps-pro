<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Quem pode perguntar à Sofia. Por omissão qualquer membro do staff — é a
 * ferramenta do comercial, não um painel de gestão.
 */
function dps_sofia_ia_pode_perguntar()
{
    return is_admin() || staff_can('view', DPS_SOFIA_IA_MODULE_NAME);
}

/**
 * Quem gere o conhecimento e responde às perguntas em aberto.
 */
function dps_sofia_ia_pode_gerir()
{
    return is_admin() || staff_can('edit', DPS_SOFIA_IA_MODULE_NAME);
}

/**
 * Se a Sofia consegue responder já. Existe em helper (além do modelo) porque a
 * gaveta flutuante é uma vista carregada num hook, sem modelo à mão.
 */
function dps_sofia_ia_esta_pronta()
{
    if (get_option('dps_sofia_ia_fornecedor') === 'local') {
        return true;
    }

    return trim((string) get_option('dps_sofia_ia_api_key_claude')) !== ''
        || trim((string) get_option('dps_sofia_ia_api_key_openai')) !== '';
}

function dps_sofia_ia_contar_pendentes()
{
    $CI = &get_instance();

    if (!$CI->db->table_exists(db_prefix() . 'dps_sofia_pendentes')) {
        return 0;
    }

    $CI->db->where('estado', 'aberta');

    return (int) $CI->db->count_all_results(db_prefix() . 'dps_sofia_pendentes');
}

/**
 * Empreendimentos, com a chave que identifica cada um no ficheiro de
 * disponibilidade do simulador.
 *
 * Preferimos a lista do módulo dps_propostas: é lá que ela é mantida, e ter
 * uma segunda cópia aqui significava que acrescentar um empreendimento passava
 * a exigir mexer em dois sítios — e que a Sofia ficaria a ignorar o novo até
 * alguém se lembrar do segundo. A cópia abaixo só entra em jogo se esse módulo
 * desaparecer.
 */
function dps_sofia_ia_empreendimentos()
{
    if (function_exists('dps_propostas_empreendimentos')) {
        $lista = [];
        foreach (dps_propostas_empreendimentos() as $chave => $emp) {
            if (empty($emp['states_key'])) {
                continue;
            }
            $lista[$chave] = ['nome' => $emp['nome'], 'states_key' => $emp['states_key']];
        }
        if (!empty($lista)) {
            return $lista;
        }
    }

    return [
        'boavista'      => ['nome' => 'Boavista Towers', 'states_key' => 'boavista_states'],
        'belohorizonte' => ['nome' => 'Belo Horizonte',  'states_key' => 'bh_states'],
        'raizes'        => ['nome' => 'Raízes Fanzeres', 'states_key' => 'raizes_states'],
        'lake'          => ['nome' => 'Lake Towers',     'states_key' => 'lake_states'],
        'gaiadouro'     => ['nome' => 'Douro Mar',      'states_key' => 'gaiadouro_states'],
        'aura'          => ['nome' => 'Aura Residence',  'states_key' => 'aura_states'],
    ];
}

function dps_sofia_ia_categorias()
{
    return [
        'empreendimentos' => 'Empreendimentos e preços',
        'scripts'         => 'Scripts e abordagem',
        'objecoes'        => 'Objecções',
        'processo'        => 'Processo de venda e documentos',
        'credito'         => 'Crédito e financiamento',
        'comissoes'       => 'Comissões',
        'empresa'         => 'Empresa e institucional',
        'outro'           => 'Outro',
    ];
}

/*
 * Só modelos que aceitam pensamento adaptativo e o parâmetro de esforço, que é
 * o que este módulo envia. O Haiku 4.5 recusa os dois com erro 400 — pô-lo na
 * lista era oferecer uma opção que parte o chat assim que fosse escolhida.
 */
function dps_sofia_ia_modelos_claude()
{
    return [
        'claude-opus-5'   => 'Claude Opus 5 (mais capaz)',
        'claude-sonnet-5' => 'Claude Sonnet 5 (mais rápido e barato)',
    ];
}

function dps_sofia_ia_modelos_openai()
{
    return [
        'gpt-4o'      => 'GPT-4o',
        'gpt-4o-mini' => 'GPT-4o mini (mais barato)',
    ];
}

/**
 * As instruções fixas da Sofia. Ficam numa opção editável porque o tom e as
 * regras de negócio mudam mais depressa do que se faz um deploy.
 */
function dps_sofia_ia_persona_por_omissao()
{
    return implode("\n", [
        'És a Sofia, a assistente interna da DPS Imobiliário. Falas com os comerciais da equipa, não com clientes.',
        '',
        'Regras:',
        '- Respondes SEMPRE em português de Portugal.',
        '- Respondes apenas com base no conhecimento que te é dado abaixo. Não inventas preços, tipologias, prazos, condições de pagamento nem percentagens de comissão.',
        '- Se a pergunta for sobre um número concreto (preço, área, comissão, prazo) e esse número não estiver no conhecimento, não estimas: dizes que não sabes.',
        '- Vais direta ao assunto. O comercial está normalmente ao telefone ou em frente ao cliente.',
        '- Quando a resposta vier de uma ficha de conhecimento, refere de onde vem (ex.: "segundo a tabela do Boavista Towers").',
        '- Não incluis etiquetas XML internas nem notas sobre o teu próprio processo na resposta.',
    ]);
}

/**
 * Minúsculas e sem acentos, para que "preços" encontre "precos".
 *
 * Não se usa iconv //TRANSLIT: o resultado depende do locale instalado no
 * servidor e no alojamento partilhado isso não é de confiança.
 */
function dps_sofia_ia_normalizar($texto)
{
    $texto = mb_strtolower((string) $texto, 'UTF-8');

    $acentos = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    $texto = strtr($texto, $acentos);
    $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

    return trim(preg_replace('/\s+/', ' ', $texto));
}

/**
 * Palavras da pergunta que valem a pena procurar.
 *
 * Sem tirar as palavras vazias, "qual é o preço do T2" traz de volta todos os
 * trechos que contenham "o" — ou seja, todos.
 */
function dps_sofia_ia_termos($pergunta)
{
    $vazias = [
        'a', 'o', 'as', 'os', 'um', 'uma', 'uns', 'umas', 'de', 'do', 'da', 'dos', 'das',
        'em', 'no', 'na', 'nos', 'nas', 'por', 'para', 'com', 'sem', 'que', 'qual', 'quais',
        'quanto', 'quantos', 'quanta', 'quantas', 'como', 'quando', 'onde', 'quem', 'porque',
        'e', 'ou', 'se', 'ao', 'aos', 'as', 'me', 'te', 'lhe', 'nos', 'vos', 'eu', 'tu', 'ele',
        'ela', 'nao', 'sim', 'ja', 'so', 'mais', 'menos', 'muito', 'pouco', 'todo', 'toda',
        'este', 'esta', 'esse', 'essa', 'aquele', 'aquela', 'isto', 'isso', 'ser', 'estar',
        'ter', 'haver', 'fazer', 'pode', 'posso', 'podes', 'devo', 'deve', 'qualquer',
    ];

    $palavras = explode(' ', dps_sofia_ia_normalizar($pergunta));
    $termos   = [];

    foreach ($palavras as $palavra) {
        if (mb_strlen($palavra) < 3 || in_array($palavra, $vazias, true)) {
            continue;
        }
        $termos[$palavra] = true;
    }

    /*
     * Só os termos mais longos. São os que distinguem — "boavista" diz mais
     * sobre a pergunta do que "valor", e cada termo extra é mais uma condição
     * LIKE a varrer a tabela.
     */
    $termos = array_keys($termos);
    usort($termos, function ($a, $b) {
        return mb_strlen($b) - mb_strlen($a);
    });

    return array_slice($termos, 0, 8);
}

/**
 * Parte um texto longo em trechos pesquisáveis.
 *
 * Os trechos sobrepõem-se um pouco de propósito: uma frase cortada ao meio
 * entre dois trechos deixaria de ser encontrada por qualquer um dos dois.
 */
function dps_sofia_ia_partir_em_trechos($texto, $tamanho = 1200, $sobreposicao = 150)
{
    $texto = trim(preg_replace('/[ \t]+/', ' ', (string) $texto));

    if ($texto === '') {
        return [];
    }

    if (mb_strlen($texto) <= $tamanho) {
        return [$texto];
    }

    $paragrafos = preg_split('/\n\s*\n/', $texto);
    $trechos    = [];
    $atual      = '';

    foreach ($paragrafos as $paragrafo) {
        $paragrafo = trim($paragrafo);
        if ($paragrafo === '') {
            continue;
        }

        // Um parágrafo maior do que um trecho inteiro é cortado à força.
        if (mb_strlen($paragrafo) > $tamanho) {
            if ($atual !== '') {
                $trechos[] = $atual;
                $atual     = '';
            }
            $posicao = 0;
            $total   = mb_strlen($paragrafo);
            while ($posicao < $total) {
                $trechos[] = mb_substr($paragrafo, $posicao, $tamanho);
                $posicao  += max(1, $tamanho - $sobreposicao);
            }
            continue;
        }

        if ($atual !== '' && mb_strlen($atual) + mb_strlen($paragrafo) + 2 > $tamanho) {
            $trechos[] = $atual;
            // Arrasta a cauda do trecho anterior para o seguinte.
            $atual = mb_substr($atual, -$sobreposicao) . "\n\n" . $paragrafo;
        } else {
            $atual = $atual === '' ? $paragrafo : $atual . "\n\n" . $paragrafo;
        }
    }

    if (trim($atual) !== '') {
        $trechos[] = $atual;
    }

    return $trechos;
}

/**
 * Tira o texto de um ficheiro carregado pelo admin.
 *
 * Devolve ['texto' => string, 'aviso' => string|null]. O aviso existe para o
 * caso mais chato: um PDF digitalizado (uma fotografia de uma página) não tem
 * texto nenhum lá dentro, e sem aviso o admin ficaria com uma ficha vazia a
 * pensar que tinha carregado a informação.
 */
function dps_sofia_ia_extrair_texto($caminho, $extensao)
{
    $extensao = strtolower($extensao);

    if (in_array($extensao, ['txt', 'md', 'csv'], true)) {
        return ['texto' => dps_sofia_ia_forcar_utf8(file_get_contents($caminho)), 'aviso' => null];
    }

    if ($extensao === 'docx') {
        return ['texto' => dps_sofia_ia_texto_docx($caminho), 'aviso' => null];
    }

    if ($extensao === 'pdf') {
        $texto = dps_sofia_ia_texto_pdf($caminho);

        if (dps_sofia_ia_texto_util($texto)) {
            return ['texto' => $texto, 'aviso' => null];
        }

        return [
            'texto' => $texto,
            'aviso' => 'Não consegui ler texto deste PDF. Costuma acontecer quando o PDF é '
                     . 'uma digitalização (imagens de páginas) em vez de texto. Copie o texto '
                     . 'e cole-o no campo de conteúdo.',
        ];
    }

    return ['texto' => '', 'aviso' => 'Formato não suportado.'];
}

/**
 * Um .docx é um zip; o texto vive no word/document.xml.
 */
function dps_sofia_ia_texto_docx($caminho)
{
    if (!class_exists('ZipArchive')) {
        return '';
    }

    $zip = new ZipArchive();
    if ($zip->open($caminho) !== true) {
        return '';
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        return '';
    }

    // As quebras de parágrafo do Word não sobrevivem ao strip_tags sozinhas.
    $xml = preg_replace('/<\/w:p>/', "\n\n", $xml);
    $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml);

    return dps_sofia_ia_forcar_utf8(html_entity_decode(strip_tags($xml), ENT_QUOTES, 'UTF-8'));
}

/**
 * Extracção de texto de um PDF sem bibliotecas externas.
 *
 * O servidor é alojamento partilhado: não há composer nem garantia de que o
 * pdftotext exista, por isso tenta-se o pdftotext e, se não estiver lá,
 * descomprimem-se os streams do PDF e lê-se os operadores de texto (Tj/TJ).
 * Chega para PDFs gerados por computador — tabelas de preços, dossiers,
 * apresentações exportadas. Não chega para digitalizações, e é isso que o
 * aviso de dps_sofia_ia_extrair_texto diz ao admin.
 */
function dps_sofia_ia_texto_pdf($caminho)
{
    $via_binario = dps_sofia_ia_pdftotext($caminho);
    if (dps_sofia_ia_texto_util($via_binario)) {
        return $via_binario;
    }

    $bruto = file_get_contents($caminho);
    if ($bruto === false) {
        return '';
    }

    /*
     * Tipos de letra embebidos em subconjunto.
     *
     * Os PDFs modernos não escrevem "(Olá) Tj": escrevem "<3F> Tj", onde 3F é
     * o número do glifo dentro do tipo de letra embebido — não é a letra "?".
     * Sem traduzir isso, a leitura devolve zero caracteres, que foi o que
     * aconteceu com o Manual do CRM: 637 streams, texto nenhum.
     *
     * A tradução está no próprio PDF, na tabela ToUnicode de cada tipo de
     * letra. Esta via lê essas tabelas e devolve o texto certo. Corre antes da
     * leitura simples porque é a que serve os PDFs que a equipa costuma ter.
     */
    if (strlen($bruto) <= 25 * 1024 * 1024) {
        $via_cmap = dps_sofia_ia_pdf_por_cmap($bruto);
        if (dps_sofia_ia_texto_util($via_cmap)) {
            return dps_sofia_ia_forcar_utf8($via_cmap);
        }
    }

    $partes = [];

    // Cada stream é um bloco de conteúdo, normalmente comprimido com Flate.
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $bruto, $streams)) {
        foreach ($streams[1] as $stream) {
            $conteudo = @gzuncompress($stream);
            if ($conteudo === false) {
                $conteudo = @gzinflate(substr($stream, 2));
            }
            if ($conteudo === false) {
                // Streams não comprimidos aparecem em PDFs mais simples.
                $conteudo = $stream;
            }
            $partes[] = dps_sofia_ia_texto_de_stream_pdf($conteudo);
        }
    }

    $texto = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", array_filter($partes))));

    return dps_sofia_ia_forcar_utf8($texto);
}

/**
 * Extracção de PDF traduzindo os índices de glifo pela tabela ToUnicode.
 *
 * Três passos: apanhar os objectos todos, construir a tradução de cada tipo de
 * letra, e depois percorrer os conteúdos a seguir o tipo de letra activo — o
 * mesmo código de glifo significa letras diferentes em tipos de letra
 * diferentes, por isso traduzir sem saber qual está activo dá lixo.
 */
function dps_sofia_ia_pdf_por_cmap($bruto)
{
    if (!preg_match_all('/(\d+)\s+0\s+obj(.*?)endobj/s', $bruto, $achados, PREG_SET_ORDER)) {
        return '';
    }

    $objectos = [];
    foreach ($achados as $achado) {
        $objectos[(int) $achado[1]] = $achado[2];
    }

    // Tipo de letra -> tabela de tradução
    $tabelas = [];
    foreach ($objectos as $id => $corpo) {
        if (strpos($corpo, '/Font') === false || !preg_match('/\/ToUnicode\s+(\d+)\s+0\s+R/', $corpo, $m)) {
            continue;
        }
        $cmap = isset($objectos[(int) $m[1]]) ? dps_sofia_ia_pdf_stream($objectos[(int) $m[1]]) : null;
        if ($cmap === null) {
            continue;
        }
        $tabela = dps_sofia_ia_pdf_ler_cmap($cmap);
        if (!empty($tabela)) {
            $tabelas[$id] = $tabela;
        }
    }

    if (empty($tabelas)) {
        return '';
    }

    // Nome do recurso (/F4) -> objecto do tipo de letra
    $nomes = [];
    foreach ($objectos as $corpo) {
        if (!preg_match_all('/\/Font\s*<<(.*?)>>/s', $corpo, $blocos)) {
            continue;
        }
        foreach ($blocos[1] as $bloco) {
            if (preg_match_all('/\/([A-Za-z0-9]+)\s+(\d+)\s+0\s+R/', $bloco, $pares, PREG_SET_ORDER)) {
                foreach ($pares as $par) {
                    $nomes[$par[1]] = (int) $par[2];
                }
            }
        }
    }

    $texto = '';

    foreach ($objectos as $corpo) {
        $conteudo = dps_sofia_ia_pdf_stream($corpo);
        if ($conteudo === null || (strpos($conteudo, 'Tj') === false && strpos($conteudo, 'TJ') === false)) {
            continue;
        }

        $tabela = null;
        $padrao = '/\/([A-Za-z0-9]+)\s+[\d.]+\s+Tf'      // tipo de letra activo
                . '|<([0-9A-Fa-f]+)>\s*Tj'                // texto em hexadecimal
                . '|\[([^\]]*)\]\s*TJ'                    // texto em lista
                . '|(T\*|BT|ET)'                          // mudança de linha / bloco
                . '|(-?[\d.]+)\s+(-?[\d.]+)\s+T[dD]/s';   // deslocação

        if (!preg_match_all($padrao, $conteudo, $eventos, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($eventos as $evento) {
            if (!empty($evento[1])) {
                $id     = isset($nomes[$evento[1]]) ? $nomes[$evento[1]] : null;
                $tabela = ($id !== null && isset($tabelas[$id])) ? $tabelas[$id] : null;
                continue;
            }
            if (!empty($evento[2])) {
                $texto .= $tabela ? dps_sofia_ia_pdf_traduzir($evento[2], $tabela) : '';
                continue;
            }
            if (!empty($evento[3])) {
                if ($tabela && preg_match_all('/<([0-9A-Fa-f]+)>/', $evento[3], $hexes)) {
                    foreach ($hexes[1] as $hex) {
                        $texto .= dps_sofia_ia_pdf_traduzir($hex, $tabela);
                    }
                }
                continue;
            }
            if (!empty($evento[4])) {
                $texto .= "\n";
                continue;
            }
            /*
             * Um Td só muda de linha quando desloca na VERTICAL. Neste tipo de
             * PDF cada letra é posicionada com um Td horizontal — tratar todos
             * como mudança de linha devolvia o texto certo com uma letra por
             * linha, ilegível e inútil para procura.
             */
            if (isset($evento[6]) && $evento[6] !== '' && (float) $evento[6] != 0.0) {
                $texto .= "\n";
            }
        }

        $texto .= "\n\n";
    }

    return trim(preg_replace("/\n{3,}/", "\n\n", $texto));
}

/**
 * Lê uma tabela ToUnicode: pares soltos (bfchar) e intervalos (bfrange).
 */
function dps_sofia_ia_pdf_ler_cmap($cmap)
{
    $tabela = [];

    if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmap, $blocos)) {
        foreach ($blocos[1] as $bloco) {
            if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $bloco, $pares, PREG_SET_ORDER)) {
                foreach ($pares as $par) {
                    $tabela[strtoupper($par[1])] = $par[2];
                }
            }
        }
    }

    if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmap, $blocos)) {
        foreach ($blocos[1] as $bloco) {
            if (!preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $bloco, $pares, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($pares as $par) {
                $inicio  = hexdec($par[1]);
                $fim     = hexdec($par[2]);
                $destino = hexdec($par[3]);
                $largura = strlen($par[1]);

                // Tecto para não explodir com um intervalo mal formado.
                for ($i = $inicio; $i <= $fim && ($i - $inicio) < 65535; $i++) {
                    $chave          = strtoupper(str_pad(dechex($i), $largura, '0', STR_PAD_LEFT));
                    $tabela[$chave] = str_pad(dechex($destino + ($i - $inicio)), 4, '0', STR_PAD_LEFT);
                }
            }
        }
    }

    return $tabela;
}

function dps_sofia_ia_pdf_traduzir($hex, $tabela)
{
    $hex   = strtoupper($hex);
    $saida = '';

    // Os códigos tanto podem ser de 1 byte como de 2; tenta o mais curto.
    for ($i = 0; $i < strlen($hex);) {
        if (isset($tabela[substr($hex, $i, 2)])) {
            $saida .= dps_sofia_ia_pdf_unicode($tabela[substr($hex, $i, 2)]);
            $i     += 2;
        } elseif (isset($tabela[substr($hex, $i, 4)])) {
            $saida .= dps_sofia_ia_pdf_unicode($tabela[substr($hex, $i, 4)]);
            $i     += 4;
        } else {
            $i += 2;
        }
    }

    return $saida;
}

function dps_sofia_ia_pdf_unicode($hex)
{
    $saida = '';

    for ($i = 0; $i < strlen($hex); $i += 4) {
        $ponto = hexdec(substr($hex, $i, 4));
        if ($ponto > 0) {
            $saida .= mb_chr($ponto, 'UTF-8');
        }
    }

    return $saida;
}

function dps_sofia_ia_pdf_stream($corpo)
{
    if (!preg_match('/stream\r?\n(.*?)\r?\nendstream/s', $corpo, $m)) {
        return null;
    }

    $conteudo = @gzuncompress($m[1]);
    if ($conteudo === false) {
        $conteudo = @gzinflate(substr($m[1], 2));
    }

    return $conteudo === false ? $m[1] : $conteudo;
}

/**
 * Lê os operadores de texto de um stream de conteúdo já descomprimido.
 */
function dps_sofia_ia_texto_de_stream_pdf($conteudo)
{
    if (strpos($conteudo, 'Tj') === false && strpos($conteudo, 'TJ') === false) {
        return '';
    }

    $linhas = [];

    // Blocos [(a) -3 (b)] TJ e (texto) Tj.
    if (preg_match_all('/\[(.*?)\]\s*TJ|\(((?:\\\\.|[^\\\\()])*)\)\s*Tj/s', $conteudo, $achados, PREG_SET_ORDER)) {
        foreach ($achados as $achado) {
            if (isset($achado[2]) && $achado[2] !== '') {
                $linhas[] = dps_sofia_ia_destratar_string_pdf($achado[2]);
                continue;
            }
            if (!isset($achado[1])) {
                continue;
            }
            $pedacos = '';
            if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $achado[1], $strings)) {
                foreach ($strings[0] as $string) {
                    $pedacos .= dps_sofia_ia_destratar_string_pdf(substr($string, 1, -1));
                }
            }
            $linhas[] = $pedacos;
        }
    }

    return trim(implode("\n", array_filter($linhas, function ($linha) {
        return trim($linha) !== '';
    })));
}

function dps_sofia_ia_destratar_string_pdf($string)
{
    $mapa = [
        '\\n' => "\n", '\\r' => "\r", '\\t' => "\t",
        '\\(' => '(', '\\)' => ')', '\\\\' => '\\',
    ];

    $string = strtr($string, $mapa);

    // Escapes em octal (\251 e afins).
    return preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
        return chr(octdec($m[1]));
    }, $string);
}

/**
 * O pdftotext existe em muitos alojamentos e é bem melhor do que a leitura
 * manual. Se o exec estiver desligado — o normal em partilhado — desiste em
 * silêncio e o chamador segue para a alternativa.
 */
function dps_sofia_ia_pdftotext($caminho)
{
    if (!function_exists('exec')) {
        return '';
    }

    $desactivadas = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (in_array('exec', $desactivadas, true)) {
        return '';
    }

    $destino = tempnam(sys_get_temp_dir(), 'dps_sofia_');
    if ($destino === false) {
        return '';
    }

    @exec('pdftotext -enc UTF-8 -q ' . escapeshellarg($caminho) . ' ' . escapeshellarg($destino) . ' 2>/dev/null', $saida, $codigo);

    $texto = $codigo === 0 && is_readable($destino) ? (string) file_get_contents($destino) : '';
    @unlink($destino);

    return $texto;
}

/**
 * Um texto extraído só serve se tiver letras que cheguem. Um PDF digitalizado
 * costuma devolver meia dúzia de símbolos soltos, e isso não é conhecimento.
 */
function dps_sofia_ia_texto_util($texto)
{
    $texto = trim((string) $texto);

    if (mb_strlen($texto) < 120) {
        return false;
    }

    $letras = preg_match_all('/\p{L}/u', $texto);

    return $letras > 0 && ($letras / mb_strlen($texto)) > 0.5;
}

function dps_sofia_ia_forcar_utf8($texto)
{
    $texto = (string) $texto;

    if (function_exists('mb_check_encoding') && !mb_check_encoding($texto, 'UTF-8')) {
        $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
    }

    // Bytes de controlo estragam o JSON enviado à API.
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $texto);
}

function dps_sofia_ia_etiqueta_estado($estado)
{
    $mapa = [
        'aberta'     => ['Por responder', 'warning'],
        'respondida' => ['Respondida', 'success'],
        'ignorada'   => ['Ignorada', 'default'],
    ];

    return isset($mapa[$estado]) ? $mapa[$estado] : [$estado, 'default'];
}
