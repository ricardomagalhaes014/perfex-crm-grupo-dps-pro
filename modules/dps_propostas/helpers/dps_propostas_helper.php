<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Telefone de WhatsApp de um comercial: o do perfil, e se estiver vazio o que
 * ficou no campo da landing page. Devolve só dígitos, com 351 à frente quando
 * é um número português de 9 dígitos.
 */
function dps_propostas_telefone_staff($staff_id)
{
    $CI = &get_instance();

    $CI->db->select('phonenumber, landing_whatsapp');
    $CI->db->where('staffid', (int) $staff_id);
    $s = $CI->db->get(db_prefix() . 'staff')->row_array();

    if (!$s) {
        return '';
    }

    $n = preg_replace('/[^0-9]/', '', (string) ($s['phonenumber'] ?: ''));
    if ($n === '') {
        $n = preg_replace('/[^0-9]/', '', (string) ($s['landing_whatsapp'] ?? ''));
    }
    if ($n !== '' && strlen($n) === 9) {
        $n = '351' . $n;
    }

    return $n;
}

/**
 * Corpo HTML do email das unidades disponíveis.
 *
 * Leva sempre: um texto curto a explicar o empreendimento, a lista de
 * disponibilidades por tipologia, os links do dossier e do site, e um botão
 * verde de WhatsApp para o comercial que envia — pedido do Ricardo, para o
 * cliente responder pelo canal onde é mais fácil responder.
 */
function dps_propostas_email_disponiveis($emp, $disp, $lead_nome, $com)
{
    $primeiro = trim(explode(' ', (string) $lead_nome)[0]);
    $ola      = 'Olá' . ($primeiro !== '' ? ' ' . $primeiro : '');

    $linhas = '';
    foreach (($disp['por_tipologia'] ?? []) as $t) {
        $preco = !empty($t['min'])
            ? '<span style="color:#6b7280;"> — desde ' . number_format($t['min'], 0, ',', '.') . ' €</span>'
            : '';
        $linhas .= '<tr>'
            . '<td style="padding:8px 12px;border-bottom:1px solid #eee;">' . html_escape($t['tipologia']) . '</td>'
            . '<td style="padding:8px 12px;border-bottom:1px solid #eee;text-align:right;font-weight:600;">'
            . (int) $t['n'] . $preco . '</td>'
            . '</tr>';
    }

    $botoes = '';
    if (!empty($emp['dossier'])) {
        $botoes .= '<a href="' . $emp['dossier'] . '" style="display:inline-block;background:#1d6fb8;color:#fff;'
            . 'text-decoration:none;font-weight:600;padding:11px 20px;border-radius:8px;margin:0 8px 8px 0;">'
            . '📄 Ver o dossier</a>';
    }
    if (!empty($emp['site'])) {
        $botoes .= '<a href="' . $emp['site'] . '" style="display:inline-block;background:#f3f4f6;color:#111;'
            . 'text-decoration:none;font-weight:600;padding:11px 20px;border-radius:8px;margin:0 8px 8px 0;">'
            . '🌐 Página do empreendimento</a>';
    }

    /*
     * Botão de WhatsApp do comercial. Sem número no perfil não se inventa um
     * link — mostra-se o telefone/email em texto, que é melhor do que um botão
     * que abre uma conversa com ninguém.
     */
    $wa = '';
    if (!empty($com['telefone'])) {
        $texto = rawurlencode('Olá ' . ($com['nome'] ?: '') . ', vi as unidades do ' . $emp['nome'] . ' e queria saber mais.');
        $wa = '<a href="https://wa.me/' . $com['telefone'] . '?text=' . $texto . '" '
            . 'style="display:inline-block;background:#25D366;color:#fff;text-decoration:none;font-weight:700;'
            . 'padding:13px 24px;border-radius:8px;font-size:15px;">'
            . '💬 Falar comigo por WhatsApp</a>';
    }

    return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#111;line-height:1.6;max-width:620px;">'
        . '<p>' . html_escape($ola) . ',</p>'
        . '<p>Envio-lhe a informação atualizada do <strong>' . html_escape($emp['nome']) . '</strong>.</p>'
        /*
         * O texto da direcção manda sobre a descrição genérica: quando existe,
         * é ele que o cliente lê. A descrição fica para os empreendimentos que
         * ainda não têm texto próprio.
         */
        . (dps_propostas_apresentacao_html($emp['chave'] ?? '') !== ''
            ? '<div style="background:#f9fafb;padding:16px 18px;border-radius:8px;border-left:4px solid #c8a04a;margin:18px 0;">'
                . dps_propostas_apresentacao_html($emp['chave'] ?? '') . '</div>'
            : (!empty($emp['descricao'])
                ? '<p style="background:#f9fafb;padding:14px 16px;border-radius:8px;border-left:4px solid #1d6fb8;">'
                    . html_escape($emp['descricao']) . '</p>'
                : ''))
        . '<p style="margin-top:22px;"><strong>' . (int) $disp['count'] . ' unidade'
        . ((int) $disp['count'] === 1 ? '' : 's') . ' disponíve'
        . ((int) $disp['count'] === 1 ? 'l' : 'is') . ' neste momento</strong>'
        . '<br><span style="color:#6b7280;font-size:13px;">Em anexo vai a tabela completa, com as áreas e os preços.</span></p>'
        . ($linhas !== ''
            ? '<table style="border-collapse:collapse;width:100%;margin:6px 0 22px;">'
                . '<thead><tr>'
                . '<th style="text-align:left;padding:8px 12px;border-bottom:2px solid #111;">Tipologia</th>'
                . '<th style="text-align:right;padding:8px 12px;border-bottom:2px solid #111;">Disponíveis</th>'
                . '</tr></thead><tbody>' . $linhas . '</tbody></table>'
            : '')
        . ($botoes !== '' ? '<p style="margin:22px 0;">' . $botoes . '</p>' : '')
        . ($wa !== ''
            ? '<p style="margin:28px 0 8px;">Qualquer dúvida, é só responder a este email ou falar comigo directamente:</p>'
                . '<p>' . $wa . '</p>'
            : '<p style="margin-top:26px;">Qualquer dúvida, é só responder a este email.</p>')
        . '<p style="margin-top:30px;color:#6b7280;font-size:13px;">'
        . html_escape($com['nome'] ?: 'Equipa DPS') . '<br>Grupo DPS — DPS Imobiliário'
        . (!empty($com['email']) ? '<br>' . html_escape($com['email']) : '')
        . '</p></div>';
}

/**
 * Texto de apresentação por empreendimento.
 *
 * É o que o cliente lê quando o sistema lhe manda as disponibilidades ou uma
 * proposta. Escrito pela direcção, palavra a palavra — não é para reescrever
 * aqui sem o pedir. Pedido do dono (06/08/2026).
 *
 * Texto simples, com quebras de linha: o WhatsApp usa-o tal e qual e o email
 * converte-o em parágrafos. Sem saudação no início — a mensagem já abre com
 * "Olá {nome}!" e duas saudações seguidas soam a robô.
 *
 * @return string vazio quando o empreendimento ainda não tem texto próprio
 */
function dps_propostas_apresentacao($key)
{
    $textos = [
        'aura' => "Temos o prazer de lhe apresentar o Aura Residence, o novo lançamento da DPS Imobiliário em Paços de Ferreira.\n\n"
            . "✔ Excelente oportunidade para investidores e habitação própria.\n"
            . "✔ Mercado imobiliário em forte crescimento e valorização.\n"
            . "✔ Apenas 30% de pagamento até à escritura.\n"
            . "✔ Possibilidade de cedência de posição contratual.\n"
            . "✔ Apartamentos com arquitetura moderna, excelentes áreas e acabamentos de elevada qualidade.\n"
            . "✔ Localização privilegiada, próxima dos principais acessos, comércio, escolas e serviços.\n\n"
            . "Conheça todos os detalhes, plantas, preços e disponibilidades em:\n"
            . "http://dpsimobiliario.pt/auraresidence\n\n"
            . "10% no CPCV\n"
            . "10% em Junho de 2027\n"
            . "10% em Julho de 2028\n"
            . "70% na escritura\n"
            . "Obra a acabar no fim de 2029.",

        'boavista' => "Obrigado por ter despertado interesse na oportunidade na Avenida da Boavista.\n\n"
            . "Porque está a despertar tanto interesse? 100 unidades vendidas em menos de uma semana.\n\n"
            . "• Até 30% abaixo do valor de mercado no lançamento\n"
            . "• Localização premium na Avenida da Boavista\n"
            . "• Elevado potencial de valorização\n"
            . "• Pagamento faseado\n"
            . "• Possibilidade de cedência da posição contratual\n"
            . "• Rooftop mais alto do Porto, exclusivo para residentes\n"
            . "• Terraços panorâmicos com vistas sobre a cidade\n"
            . "• Coworking, ginásio, sala de convívio e sala de podcast\n"
            . "• Jardins privados e estacionamento\n"
            . "• MetroBus Bessa em frente ao empreendimento\n"
            . "• A poucos minutos da Casa da Música, Foz, Parque da Cidade e Aeroporto\n"
            . "• Tipologias disponíveis: T1 Smart, T2 Smart e T2\n"
            . "• Acabamentos premium com cozinhas totalmente equipadas Bosch, ar condicionado, bomba de calor e isolamento acústico\n\n"
            . "Pretende mais informações e condições de compra?\n"
            . "Qual a melhor altura para ser contactado?\n\n"
            . "http://dpsimobiliario.pt/boavistatowers",

        'gaiadouro' => "Quero apresentar-lhe o D'Ouro Mar Towers, um novo empreendimento em Vila Nova de Gaia, numa localização privilegiada junto à Douro Marina, entre o rio e o mar.\n\n"
            . "🏡 Apartamentos T2 e T2 Smart\n"
            . "🌊 Vistas sobre o Douro, mar e envolvente verde\n"
            . "📍 A 1 minuto da Douro Marina e cerca de 5 minutos do Porto\n"
            . "🏖️ Praias e Foz do Douro a poucos minutos\n"
            . "☀️ Apartamentos luminosos, com terraços privativos\n"
            . "🚗 Estacionamento e arrumos\n"
            . "❄️ Ar condicionado, cozinha equipada e acabamentos de elevada qualidade\n"
            . "🏢 Apenas 100 frações distribuídas por duas torres\n\n"
            . "É um projeto muito interessante tanto para habitação própria como para investimento, numa das zonas de Gaia com maior procura e potencial de valorização.\n\n"
            . "15% CPCV\n"
            . "15% Outubro\n"
            . "10% no 3.º trimestre de 2027\n"
            . "10% no 3.º trimestre de 2028\n"
            . "50% Escritura, no final de 2029",

        'belohorizonte' => "Escrevo da DPS Imobiliário no seguimento do seu pedido de informações sobre o Belo Horizonte Residences, em Setúbal.\n\n"
            . "É uma oportunidade muito interessante, num empreendimento com localização diferenciada, vista rio, proximidade a Tróia e um forte potencial de valorização 📈\n\n"
            . "Além disso, a estrutura de pagamento faseado — 30% até à escritura, com possibilidade de cedência após o CPCV e apenas 2% para reserva — além do posicionamento das unidades, torna esta proposta especialmente apelativa para quem procura entrar bem no mercado, com margem para crescimento.\n\n"
            . "👉 Pode ver todos os detalhes e falar com a Sofia aqui:\n"
            . "https://www.dpsimobiliario.pt/belohorizonte\n\n"
            . "2% reserva\n"
            . "10% CPCV entre Setembro e Dezembro\n"
            . "10% na conclusão do betão\n"
            . "8% no início da colocação do parquet\n\n"
            . "Pode ceder posição após o CPCV.",
    ];

    return $textos[$key] ?? '';
}

/**
 * O mesmo texto em HTML, para o email: parágrafos e links clicáveis.
 */
function dps_propostas_apresentacao_html($key)
{
    $texto = dps_propostas_apresentacao($key);
    if ($texto === '') {
        return '';
    }

    $html = '';
    foreach (preg_split("/\n{2,}/", $texto) as $paragrafo) {
        $seguro = html_escape(trim($paragrafo));

        // Os URLs escritos à mão têm de ficar clicáveis no email.
        $seguro = preg_replace(
            '~(https?://[^\s<]+)~',
            '<a href="$1" style="color:#1d6fb8;">$1</a>',
            $seguro
        );

        $html .= '<p style="margin:0 0 14px;line-height:1.55;">' . nl2br($seguro) . '</p>';
    }

    return $html;
}

/**
 * O nome do empreendimento como ele deve ficar gravado.
 *
 * O simulador manda ora a chave interna ("aura", "boavista", "gaiadouro"),
 * ora o nome de mostrar — depende do ecra de onde parte. O que ficava na
 * tabela era o que viesse, e o resultado eram propostas REAIS arrumadas em
 * "AuraResidence", "boavista" e "gaiadouro", fora das contas do empreendimento
 * a que pertencem. Corrigido a 08/08/2026.
 *
 * Um valor desconhecido devolve-se como veio: mais vale um nome estranho do
 * que perder a proposta por nao caber numa lista.
 */
function dps_propostas_nome_canonico($valor)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '';
    }

    $emps = dps_propostas_empreendimentos();

    if (isset($emps[$valor])) {
        return $emps[$valor]['nome'];
    }

    $simplifica = function ($t) {
        return preg_replace('/[^a-z0-9]/u', '', mb_strtolower(trim((string) $t)));
    };
    $procurado = $simplifica($valor);

    foreach ($emps as $chave => $e) {
        if ($simplifica($e['nome']) === $procurado || $simplifica($chave) === $procurado) {
            return $e['nome'];
        }
    }

    return $valor;
}

/**
 * Configuração dos empreendimentos (dados vivem no simuladorportugal / dpsimobiliario.pt).
 */
function dps_propostas_empreendimentos()
{
    return [
        'boavista' => [
            'nome'         => 'Boavista Towers',
            'descricao'    => 'Empreendimento novo no Porto, na zona da Boavista, com apartamentos de tipologias variadas, acabamentos de gama alta e garagem. Localização central, com tudo a poucos minutos a pé.',
            'states_key'   => 'boavista_states',
            'site'         => 'https://dpsimobiliario.pt/boavistatowers/',
            'dossier'      => 'https://dpsimobiliario.pt/boavistatowers/dossier-boavista-tower.pdf',
            'tem_proposta' => true,
        ],
        'raizes' => [
            // 'Raízes Fanzeres' e nao 'Raizes': e assim que se chama na regra de
            // comissao e nas 9 propostas ja gravadas. Dois nomes para o mesmo
            // empreendimento era o que se esta aqui a corrigir.
            'nome' => 'Raízes Fanzeres', 'states_key' => 'raizes_states',
            'descricao' => 'Empreendimento em Fanzeres, Gondomar, com tipologias do T0 ao T2 distribuidas por 6 pisos. Boa opcao para primeira habitacao e para investimento.',
            'site' => 'https://dpsimobiliario.pt/raizes/', 'dossier' => null, 'tem_proposta' => true,
        ],
        'belohorizonte' => [
            'nome' => 'Belo Horizonte', 'states_key' => 'bh_states',
            'descricao' => 'Empreendimento em Setubal, com apartamentos modernos e areas generosas, a curta distancia da praia e do centro da cidade.',
            'site' => 'https://dpsimobiliario.pt/belohorizonte/', 'dossier' => null, 'tem_proposta' => true,
        ],
        'lake' => [
            'nome' => 'Lake Towers', 'states_key' => 'lake_states',
            'descricao' => 'Empreendimento com vista sobre a agua, apartamentos de areas amplas e espacos comuns cuidados.',
            'site' => 'https://dpsimobiliario.pt/', 'dossier' => null, 'tem_proposta' => false,
        ],
        'gaiadouro' => [
            'nome'         => 'Douro Mar',
            'descricao'    => 'Empreendimento em Vila Nova de Gaia, junto a Douro Marina, com apartamentos de gama premium e vista sobre o rio Douro. Cem apartamentos, a poucos minutos do centro do Porto.',
            'states_key'   => 'gaiadouro_states',
            'site'         => 'https://dpsimobiliario.pt/',
            'dossier'      => 'https://dpsimobiliario.pt/simuladorportugal/docs/gaiadouro/dossier.pdf',
            'tem_proposta' => true,
        ],
        'aura' => [
            'nome'         => 'Aura Residence',
            'descricao'    => 'Empreendimento residencial com acabamentos contemporaneos e areas exteriores privativas, pensado para quem procura conforto e boa exposicao solar.',
            'states_key'   => 'aura_states',
            'site'         => 'https://dpsimobiliario.pt/auraresidence/',
            'dossier'      => 'https://dpsimobiliario.pt/simuladorportugal/Dossier_Aura_Residence.pdf',
            'tem_proposta' => true,
        ],
    ];
}

/**
 * Catálogo de unidades (fraccao => {tipologia, area, preco}), extraído do simulador.
 */
function dps_propostas_units()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $path = module_dir_path(DPS_PROPOSTAS_MODULE_NAME, 'units.json');
    $cache = is_file($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
    return $cache;
}

/**
 * Link do site do empreendimento pelo nome.
 */
function dps_propostas_site_por_nome($nome)
{
    foreach (dps_propostas_empreendimentos() as $e) {
        if (mb_strtolower(trim($e['nome'])) === mb_strtolower(trim((string) $nome))) {
            return $e['site'];
        }
    }
    return '';
}

/* ---------------- Evolution API (reutiliza opções do dps_whatsapp) ---------------- */

function dps_propostas_evo_url()
{
    return rtrim((string) get_option('dps_whatsapp_evolution_url'), '/');
}
function dps_propostas_evo_key()
{
    return get_option('dps_whatsapp_evolution_api_key');
}
function dps_propostas_instance($staff_id)
{
    return 'staff-' . (int) $staff_id;
}

function dps_propostas_evo_request($method, $path, $body = null, $timeout = 20)
{
    $url = dps_propostas_evo_url();
    $key = dps_propostas_evo_key();
    if (empty($url) || empty($key)) {
        return ['ok' => false, 'http' => 0, 'error' => 'Evolution nao configurada'];
    }
    $payload = ($body !== null) ? json_encode($body) : null;

    // UMA só tentativa (nunca repetir envios: um 500 transitório da Evolution
    // pode já ter entregue a mensagem, e repetir causaria duplicados).
    $ch = curl_init($url . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $key],
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['ok' => ($http >= 200 && $http < 300), 'http' => $http, 'error' => $err, 'raw' => $raw];
}

/**
 * Traduz o erro da Evolution numa mensagem amigável.
 */
function dps_propostas_erro_wa($r, $number)
{
    $raw = (string) ($r['raw'] ?? '');
    if (strpos($raw, '"exists":false') !== false) {
        return 'O número ' . $number . ' não tem WhatsApp — não é possível enviar por aqui.';
    }
    if (strpos($raw, 'No sessions') !== false || strpos($raw, 'does not exist') !== false) {
        return 'O teu WhatsApp precisa de reconectar (lê o QR no módulo de WhatsApp).';
    }
    if ((int) $r['http'] === 0 || (int) $r['http'] >= 500) {
        return 'A Evolution não respondeu neste momento — tenta de novo daqui a instantes.';
    }
    return 'Falha no envio pelo WhatsApp (HTTP ' . (int) $r['http'] . ').';
}

function dps_propostas_send_text($staff_id, $number, $text)
{
    return dps_propostas_evo_request('POST', '/message/sendText/' . dps_propostas_instance($staff_id), [
        // Evolution v2: 'text' no primeiro nível (a v1 aninhava em textMessage).
        'number' => $number,
        'text'   => $text,
    ]);
}

function dps_propostas_send_document($staff_id, $number, $url, $filename, $caption = '')
{
    return dps_propostas_evo_request('POST', '/message/sendMedia/' . dps_propostas_instance($staff_id), [
        // Evolution v2: campos do media no primeiro nível (a v1 aninhava em mediaMessage).
        'number'    => $number,
        'mediatype' => 'document',
        'mimetype'  => 'application/pdf',
        'fileName'  => $filename,
        'media'     => $url,
        'caption'   => $caption,
    ], 60);
}

/**
 * Envia um documento em base64 (ex.: PDF gerado no momento).
 */
function dps_propostas_send_document_b64($staff_id, $number, $b64, $filename, $caption = '')
{
    return dps_propostas_evo_request('POST', '/message/sendMedia/' . dps_propostas_instance($staff_id), [
        // Evolution v2: campos do media no primeiro nível (a v1 aninhava em mediaMessage).
        'number'    => $number,
        'mediatype' => 'document',
        'mimetype'  => 'application/pdf',
        'fileName'  => $filename,
        'media'     => $b64,
        'caption'   => $caption,
    ], 60);
}

/**
 * Gera (ao vivo) o PDF da tabela de unidades DISPONÍVEIS e devolve em base64.
 */
function dps_propostas_gerar_pdf_disponiveis($emp_nome, $unidades)
{
    if (! class_exists('TCPDF')) {
        @include_once APPPATH . 'vendor/autoload.php';
    }

    $fmt_area = function ($v) {
        return ($v !== null && $v !== '')
            ? rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',') . ' m²'
            : '—';
    };
    $txt = function ($v) {
        $s = htmlspecialchars((string) ($v ?? ''));
        return $s !== '' ? $s : '—';
    };

    $rows = '';
    foreach ($unidades as $u) {
        $preco = ($u['preco'] > 0) ? number_format($u['preco'], 0, ',', '.') . ' €' : '—';
        $rows .= '<tr>'
            . '<td>' . $txt($u['fraccao']) . '</td>'
            . '<td>' . $txt($u['bloco'] ?? null) . '</td>'
            . '<td align="center">' . $txt($u['piso'] ?? null) . '</td>'
            . '<td>' . $txt($u['tipologia']) . '</td>'
            . '<td align="right">' . $fmt_area($u['area']) . '</td>'
            . '<td align="right">' . $fmt_area($u['varanda'] ?? null) . '</td>'
            . '<td>' . $txt($u['orientacao'] ?? null) . '</td>'
            . '<td align="right">' . $preco . '</td>'
            . '</tr>';
    }

    $html = '<h2>Unidades Disponíveis — ' . htmlspecialchars($emp_nome) . '</h2>'
        . '<p>' . count($unidades) . ' unidades · ' . date('d/m/Y H:i') . '</p>'
        . '<table border="1" cellpadding="3" cellspacing="0"><thead>'
        . '<tr style="background-color:#f0f0f0;font-weight:bold;">'
        . '<th>Fração</th><th>Bloco</th><th>Piso</th><th>Tipologia</th>'
        . '<th>Área</th><th>Varanda</th><th>Orientação</th><th>Preço</th>'
        . '</tr></thead><tbody>' . $rows . '</tbody></table>';

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetCreator('DPS');
    $pdf->SetTitle('Unidades Disponiveis - ' . $emp_nome);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');

    return base64_encode($pdf->Output('disponiveis.pdf', 'S'));
}

/**
 * Lê a disponibilidade ao vivo do simulador (save_states.php).
 * Devolve ['count'=>int, 'codes'=>[...]] para a chave dada.
 */
function dps_propostas_disponibilidade($slug)
{
    $emps = dps_propostas_empreendimentos();
    if (! isset($emps[$slug])) {
        return ['ok' => false, 'count' => 0, 'por_tipologia' => [], 'codes' => []];
    }
    $states_key = $emps[$slug]['states_key'];

    /*
     * Ler os estados do simulador. Três tentativas com tempo largo: uma
     * leitura falhada devolvia "0 disponíveis" e chegou a sair para clientes
     * uma mensagem a dizer que não havia unidades quando havia 119. Mais vale
     * demorar uns segundos do que enviar informação errada.
     */
    $data = null;

    for ($tentativa = 1; $tentativa <= 3; $tentativa++) {
        $ch = curl_init('https://dpsimobiliario.pt/simuladorportugal/save_states.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        $tentado = json_decode((string) $raw, true);

        if (is_array($tentado) && isset($tentado[$states_key]) && is_array($tentado[$states_key])) {
            $data = $tentado;
            break;
        }

        if ($tentativa < 3) {
            sleep(1);
        }
    }

    if ($data === null) {
        /*
         * NÃO devolver 0 disponíveis: quem chama tem de perceber que a
         * leitura falhou e recusar o envio, em vez de mandar ao cliente uma
         * lista vazia como se fosse verdade.
         */
        return [
            'ok'            => false,
            'erro'          => 'Não consegui ler as unidades disponíveis do simulador. Tente de novo dentro de instantes — nada foi enviado.',
            'count'         => null,
            'por_tipologia' => [],
            'codes'         => [],
        ];
    }

    $units = dps_propostas_units();
    $cat   = isset($units[$slug]) ? $units[$slug] : [];

    $codes    = [];
    $byTipo   = [];
    $unidades = [];
    foreach ($data[$states_key] as $code => $estado) {
        if (trim((string) $estado) !== 'Disponível') {
            continue;
        }
        $codes[] = $code;
        $u     = isset($cat[$code]) ? $cat[$code] : null;
        $tip   = ($u && ! empty($u['tipologia'])) ? $u['tipologia'] : 'Outros';
        $area  = ($u && isset($u['area'])) ? $u['area'] : null;
        $preco = ($u && isset($u['preco'])) ? (float) $u['preco'] : 0;
        if (! isset($byTipo[$tip])) {
            $byTipo[$tip] = ['tipologia' => $tip, 'n' => 0, 'min' => null];
        }
        $byTipo[$tip]['n']++;
        if ($preco > 0 && ($byTipo[$tip]['min'] === null || $preco < $byTipo[$tip]['min'])) {
            $byTipo[$tip]['min'] = $preco;
        }
        $unidades[] = [
            'fraccao'    => $code,
            'tipologia'  => $tip,
            'area'       => $area,
            'preco'      => $preco,
            'bloco'      => $u['bloco'] ?? null,
            'piso'       => $u['piso'] ?? null,
            'orientacao' => $u['orientacao'] ?? null,
            'varanda'    => $u['varanda'] ?? null,
        ];
    }
    ksort($byTipo);
    // Ordenar unidades por tipologia e depois por preço.
    usort($unidades, function ($a, $b) {
        return [$a['tipologia'], $a['preco']] <=> [$b['tipologia'], $b['preco']];
    });

    return ['ok' => true, 'count' => count($codes), 'por_tipologia' => array_values($byTipo), 'codes' => $codes, 'unidades' => $unidades];
}

/**
 * Instância do comercial está ligada?
 */
function dps_propostas_staff_connected($staff_id)
{
    $CI  = &get_instance();
    $cfg = $CI->db->select('is_connected')->where('staff_id', (int) $staff_id)
        ->get(db_prefix() . 'dps_whatsapp_config')->row();
    return $cfg && (int) $cfg->is_connected === 1;
}

/**
 * Renderiza a aba "Propostas" na ficha da lead (hook after_lead_tabs_content).
 */
function dps_propostas_render_lead_tab($lead)
{
    if (empty($lead) || ! is_staff_member()) {
        return;
    }
    $CI   = &get_instance();
    $rows = $CI->db->where('lead_id', (int) $lead->id)->order_by('id', 'DESC')
        ->get(db_prefix() . 'dps_propostas')->result();

    $staff_id = get_staff_user_id();
    $CI->load->view('dps_propostas/lead_tab', [
        'lead'     => $lead,
        'emps'     => dps_propostas_empreendimentos(),
        'rows'     => $rows,
        'staff_id' => $staff_id,
        'token'    => dps_propostas_proposta_token($lead->id, $staff_id),
        'timeline' => dps_propostas_timeline($lead->id),
    ]);
}

/**
 * Junta as interações da lead (atividade, notas, WhatsApp, propostas) numa
 * linha do tempo ordenada (mais recente primeiro).
 */
function dps_propostas_timeline($lead_id, $limit = 60)
{
    $CI = &get_instance();
    $p  = db_prefix();
    $ev = [];

    foreach ($CI->db->select('description, date, full_name')->where('leadid', (int) $lead_id)
        ->order_by('date', 'DESC')->limit($limit)->get($p . 'lead_activity_log')->result() as $r) {
        $tipo = 'log';
        if (stripos($r->description, 'estado') !== false) { $tipo = 'estado'; }
        $ev[] = ['t' => $r->date, 'tipo' => $tipo, 'txt' => $r->description, 'quem' => $r->full_name];
    }

    foreach ($CI->db->select('description, dateadded, addedfrom')->where('rel_type', 'lead')->where('rel_id', (int) $lead_id)
        ->order_by('dateadded', 'DESC')->limit($limit)->get($p . 'notes')->result() as $r) {
        $tipo = (stripos($r->description, 'whatsapp') !== false) ? 'whatsapp' : 'nota';
        $ev[] = ['t' => $r->dateadded, 'tipo' => $tipo, 'txt' => $r->description, 'quem' => $r->addedfrom ? get_staff_full_name($r->addedfrom) : ''];
    }

    foreach ($CI->db->where('lead_id', (int) $lead_id)->order_by('id', 'DESC')->get($p . 'dps_propostas')->result() as $r) {
        $quem = $r->staff_id ? get_staff_full_name($r->staff_id) : '';
        if ($r->tipo === 'proposta') {
            $txt = 'Proposta enviada — ' . $r->empreendimento . ' ' . $r->unidade;
            $ev[] = ['t' => $r->created_at, 'tipo' => 'proposta', 'txt' => $txt, 'quem' => $quem];
            if (($r->outcome ?? 'pendente') !== 'pendente' && ! empty($r->outcome_at)) {
                $ot = $r->outcome === 'aceite'
                    ? 'Proposta ACEITE (' . number_format((float) $r->valor, 0, ',', '.') . ' €)'
                    : 'Proposta RECUSADA';
                $ev[] = ['t' => $r->outcome_at, 'tipo' => ($r->outcome === 'aceite' ? 'aceite' : 'recusado'), 'txt' => $ot, 'quem' => ''];
            }
        } else {
            $ev[] = ['t' => $r->created_at, 'tipo' => 'info', 'txt' => 'Informação enviada — ' . $r->empreendimento, 'quem' => $quem];
        }
    }

    usort($ev, function ($a, $b) { return strcmp((string) $b['t'], (string) $a['t']); });
    return array_slice($ev, 0, $limit);
}

/**
 * Token HMAC para autenticar o callback da proposta vindo do simulador.
 * Segredo em ficheiro fora do repositório.
 */
function dps_propostas_proposta_token($lead_id, $staff_id)
{
    $secret = @file_get_contents('/home/u172337921/.dps_proposta_secret');
    $secret = $secret !== false ? trim($secret) : '';
    if ($secret === '') {
        return '';
    }
    return hash_hmac('sha256', (int) $lead_id . '|' . (int) $staff_id . '|' . date('Y-m-d'), $secret);
}

/**
 * Preço de tabela de uma fracção, tirado do catálogo do simulador.
 *
 * É isto que faz o valor da venda aparecer sozinho quando uma proposta é
 * aceite: o comercial já escolheu a unidade ao enviar a proposta, e o preço
 * dessa unidade é um facto que está escrito — não há razão para lho pedir
 * outra vez de cor, nem para arriscar que escreva um número diferente do da
 * montra.
 *
 * O nome do empreendimento vem escrito à mão nas propostas ("AuraResidence",
 * "Aura Residence"), por isso compara-se sem espaços, sem acentos e sem
 * maiúsculas — senão metade não casava.
 *
 * @return float 0 quando não há preço conhecido (nunca inventa um)
 */
function dps_propostas_preco_unidade($empreendimento, $unidade)
{
    $unidade = trim((string) $unidade);
    if ($unidade === '') {
        return 0.0;
    }

    $achatar = static function ($t) {
        $t = mb_strtolower((string) $t, 'UTF-8');
        $t = strtr($t, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e',
                        'í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);

        return preg_replace('/[^a-z0-9]/', '', $t);
    };

    $alvo = $achatar($empreendimento);
    $slug = null;

    foreach (dps_propostas_empreendimentos() as $s => $e) {
        if ($achatar($e['nome']) === $alvo || $achatar($s) === $alvo) {
            $slug = $s;
            break;
        }
    }

    if ($slug === null) {
        return 0.0;
    }

    $catalogo = dps_propostas_units();
    $chave    = dps_propostas_chave_catalogo($slug, $unidade);

    return ($chave !== null && isset($catalogo[$slug][$chave]['preco']))
        ? (float) $catalogo[$slug][$chave]['preco']
        : 0.0;
}

/**
 * O slug do empreendimento a partir do nome escrito na proposta.
 */
function dps_propostas_slug($empreendimento)
{
    $achatar = static function ($t) {
        $t = mb_strtolower((string) $t, 'UTF-8');
        $t = strtr($t, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e',
                        'í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);

        return preg_replace('/[^a-z0-9]/', '', $t);
    };

    $alvo = $achatar($empreendimento);

    foreach (dps_propostas_empreendimentos() as $s => $e) {
        if ($achatar($e['nome']) === $alvo || $achatar($s) === $alvo) {
            return $s;
        }
    }

    return null;
}

/**
 * A fracção como o catálogo a conhece.
 *
 * O nome que chega na proposta nem sempre é o do catálogo: o Douro Mar guarda
 * "1_AL" (torre à frente) e a proposta trazia só "AL". Em 09/08/2026 isso fez
 * duas vendas nascerem sem preço e sem mudança de estado no simulador —
 * silenciosamente, porque uma procura falhada devolve zero e zero parece um
 * número legítimo.
 *
 * Compara-se primeiro em exacto, depois pelo que vem a seguir ao separador.
 * Havendo mais do que um candidato (a mesma letra em duas torres), devolve-se
 * null: pôr o preço da fracção errada é pior do que não pôr nenhum.
 */
function dps_propostas_chave_catalogo($slug, $unidade)
{
    $unidade  = trim((string) $unidade);
    $catalogo = dps_propostas_units();

    if ($unidade === '' || empty($catalogo[$slug])) {
        return null;
    }

    if (isset($catalogo[$slug][$unidade])) {
        return $unidade;
    }

    $limpar = static function ($t) {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $t));
    };

    $alvo = $limpar($unidade);

    // A cauda do que veio na proposta: de "1_AL" fica "AL".
    $partes_unidade = preg_split('/[^A-Za-z0-9]+/', $unidade);
    $cauda_unidade  = strtoupper((string) end($partes_unidade));

    $candidatos = [];

    foreach (array_keys($catalogo[$slug]) as $chave) {
        if ($limpar($chave) === $alvo) {
            return $chave;
        }

        $partes_chave = preg_split('/[^A-Za-z0-9]+/', $chave);
        $cauda_chave  = strtoupper((string) end($partes_chave));

        // "AL" encontra "1_AL"; e "1_AL" encontra "AL" (o caso do Lake).
        if ($cauda_chave === $alvo || $limpar($chave) === $cauda_unidade) {
            $candidatos[] = $chave;
        }
    }

    $candidatos = array_unique($candidatos);

    if (count($candidatos) === 1) {
        return reset($candidatos);
    }

    if (count($candidatos) > 1) {
        log_activity('Propostas: fracção "' . $unidade . '" corresponde a '
            . count($candidatos) . ' do catálogo (' . implode(', ', $candidatos)
            . '); preço não resolvido.');
    }

    return null;
}

/* =====================================================================
 * PROPOSTAS DE UNIDADES QUE JÁ SAÍRAM DO MERCADO
 *
 * Uma proposta por responder de uma fracção entretanto reservada, vendida ou
 * marcada DPS é uma proposta morta: o cliente está à espera de resposta sobre
 * uma casa que já não existe para ele, e o comercial não sabe. Passa a ser
 * cancelada sozinha — o cliente é avisado por email e o comercial recebe a
 * lista de quem tem de voltar a contactar. Pedido do dono (14/08/2026).
 * ================================================================== */

/** Os estados que tiram uma fracção do mercado. */
function dps_propostas_estados_fora_do_mercado()
{
    return ['Reservado', 'Vendido', 'DPS'];
}

/**
 * A chave de $chaves que corresponde a $unidade.
 *
 * O mesmo problema de sempre: a proposta traz "AL" e o mapa tem "1_AL", ou a
 * proposta traz "T1-W" e o mapa tem "1_W". Compara-se em exacto, depois sem
 * símbolos (com e sem o "T" da torre à frente), e por fim pelo que vem a
 * seguir ao separador. Havendo mais do que um candidato devolve-se null:
 * cancelar a proposta da fracção errada é pior do que não cancelar nenhuma.
 */
function dps_propostas_chave_no_mapa(array $chaves, $unidade)
{
    $unidade = trim((string) $unidade);

    if ($unidade === '' || empty($chaves)) {
        return null;
    }

    if (in_array($unidade, $chaves, true)) {
        return $unidade;
    }

    $limpar = static function ($t) {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $t));
    };

    $alvo  = $limpar($unidade);
    $sem_t = preg_match('/^T\d/i', $unidade) ? $limpar(substr($unidade, 1)) : null;

    foreach ($chaves as $chave) {
        $k = $limpar($chave);

        if ($k === $alvo || $k === 'A' . $alvo || ($sem_t !== null && $k === $sem_t)) {
            return $chave;
        }
    }

    $cauda = static function ($t) {
        $partes = preg_split('/[^A-Za-z0-9]+/', (string) $t);

        return strtoupper((string) end($partes));
    };

    $candidatos = [];

    foreach ($chaves as $chave) {
        if ($cauda($chave) === $cauda($unidade)) {
            $candidatos[] = $chave;
        }
    }

    $candidatos = array_unique($candidatos);

    return count($candidatos) === 1 ? reset($candidatos) : null;
}

/**
 * O mapa de estados que o simulador mostra, por empreendimento.
 *
 * É a montra que manda: é lá que o administrador marca à mão e é lá que o CRM
 * escreve quando uma venda avança. Lê-se uma vez por pedido — a mesma execução
 * do cron pode ter centenas de propostas para avaliar.
 */
function dps_propostas_montra($forcar = false)
{
    static $cache = null;

    if ($cache !== null && ! $forcar) {
        return $cache;
    }

    $ch = curl_init('https://dpsimobiliario.pt/simuladorportugal/save_states.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $bruto = curl_exec($ch);
    curl_close($ch);

    $dados = json_decode((string) $bruto, true);
    $cache = is_array($dados) ? $dados : [];

    return $cache;
}

/** O estado da fracção na montra, ou null se não se conseguir determinar. */
function dps_propostas_estado_montra($empreendimento, $unidade)
{
    $CI = &get_instance();

    if (! class_exists('Dps_vendas_model')) {
        $CI->load->model('dps_vendas/dps_vendas_model');
    }

    $chave = Dps_vendas_model::chave_empreendimento($empreendimento);
    $mapa  = dps_propostas_montra();

    if ($chave === null || empty($mapa[$chave . '_states']) || ! is_array($mapa[$chave . '_states'])) {
        return null;
    }

    $estados = $mapa[$chave . '_states'];
    $k       = dps_propostas_chave_no_mapa(array_keys($estados), $unidade);

    return $k === null ? null : (string) $estados[$k];
}

/**
 * Cancela as propostas por responder cuja fracção já saiu do mercado.
 *
 * @param string|null $empreendimento  limitar a um empreendimento
 * @param string|null $unidade         limitar a uma fracção
 * @param bool        $avisar          enviar email ao cliente e aviso ao comercial
 * @return array  ['canceladas' => n, 'emails' => n, 'comerciais' => [id => [nomes]]]
 */
function dps_propostas_cancelar_indisponiveis($empreendimento = null, $unidade = null, $avisar = true)
{
    $CI = &get_instance();

    $CI->db->select('p.id, p.lead_id, p.staff_id, p.empreendimento, p.unidade,
                     l.name AS lead_nome, l.email AS lead_email');
    $CI->db->from(db_prefix() . 'dps_propostas p');
    $CI->db->join(db_prefix() . 'leads l', 'l.id = p.lead_id', 'left');
    $CI->db->where('p.tipo', 'proposta');
    $CI->db->where('(p.outcome IS NULL OR p.outcome = "" OR p.outcome = "pendente")', null, false);
    $CI->db->where('p.unidade !=', '');

    if ($empreendimento !== null && $empreendimento !== '') {
        $CI->db->where('p.empreendimento', $empreendimento);
    }
    if ($unidade !== null && $unidade !== '') {
        $CI->db->where('p.unidade', $unidade);
    }

    /*
     * SÓ AS PROPOSTAS ENVIADAS A PARTIR DO ARRANQUE.
     *
     * No dia em que isto entrou havia 218 propostas por responder cujas
     * fracções já tinham saído do mercado, de 150 clientes, algumas de 10 de
     * Julho. Escrever a essa gente hoje a dizer que "a fracção foi vendida"
     * faria mais estranheza do que serviço, e cancelá-las em bloco reescrevia
     * um mês de histórico de uma assentada. Decisão do dono (14/08/2026):
     * vale só daqui para a frente.
     *
     * A data está gravada e não é calculada — assim o corte não anda para a
     * frente sozinho, e as propostas de hoje continuam abrangidas amanhã.
     */
    $desde = (string) get_option('dps_propostas_cancelar_desde');

    if ($desde !== '') {
        $CI->db->where('p.created_at >=', $desde);
    }

    $pendentes = $CI->db->get()->result();

    $fora       = dps_propostas_estados_fora_do_mercado();
    $canceladas = 0;
    $emails     = 0;
    $por_com    = [];
    $agora      = date('Y-m-d H:i:s');

    foreach ($pendentes as $prop) {
        $estado = dps_propostas_estado_montra($prop->empreendimento, $prop->unidade);

        if ($estado === null || ! in_array($estado, $fora, true)) {
            continue;
        }

        $CI->db->where('id', (int) $prop->id)->update(db_prefix() . 'dps_propostas', [
            'outcome'      => 'cancelado',
            'motivo_perda' => 'unidade_indisponivel',
            'outcome_at'   => $agora,
        ]);

        /*
         * Fica no histórico da lead. Quem a abrir daqui a três meses percebe
         * porque é que a proposta parou sem ninguém ter dito que não.
         */
        $CI->db->insert(db_prefix() . 'lead_activity_log', [
            'leadid'      => (int) $prop->lead_id,
            'staffid'     => (int) $prop->staff_id,
            'full_name'   => get_staff_full_name((int) $prop->staff_id),
            'date'        => $agora,
            'description' => '🚫 Proposta cancelada — a fracção ' . $prop->unidade
                . ' (' . $prop->empreendimento . ') passou a "' . $estado . '".',
        ]);

        /*
         * A lead volta a VIP 1.
         *
         * Não vai para "Para outras oportunidades": esse estado é para quem
         * disse que não. Aqui ninguém disse nada — o cliente continua
         * interessado e foi a casa que ficou sem a fracção. É precisamente
         * quem tem de ser contactado já com outra proposta, e volta a
         * PROPOSTAS ENVIADAS assim que ela sair. Regra do dono (14/08/2026).
         */
        dps_propostas_mover_lead_vip1((int) $prop->lead_id, (int) $prop->staff_id);

        $canceladas++;

        if (! $avisar) {
            continue;
        }

        if (dps_propostas_avisar_cliente_unidade_saiu($prop, $estado)) {
            $emails++;
        }

        $sid = (int) $prop->staff_id;
        $por_com[$sid] = $por_com[$sid] ?? [];
        $nome = trim((string) $prop->lead_nome) ?: ('lead #' . (int) $prop->lead_id);

        if (! in_array($nome, $por_com[$sid], true)) {
            $por_com[$sid][] = $nome;
        }
    }

    if ($avisar) {
        foreach ($por_com as $sid => $clientes) {
            dps_propostas_avisar_comercial_canceladas($sid, $clientes);
        }
    }

    return ['canceladas' => $canceladas, 'emails' => $emails, 'comerciais' => $por_com];
}

/**
 * Põe a lead em VIP 1, deixando o mesmo rasto que uma mudança feita à mão:
 * linha no histórico e o hook que o resto do CRM escuta.
 */
function dps_propostas_mover_lead_vip1($lead_id, $staff_id)
{
    $CI   = &get_instance();
    $vip1 = 17;

    $lead = $CI->db->select('status')->where('id', $lead_id)->get(db_prefix() . 'leads')->row();

    if (! $lead || (int) $lead->status === $vip1) {
        return;
    }

    $agora = date('Y-m-d H:i:s');

    $CI->db->where('id', $lead_id)->update(db_prefix() . 'leads', [
        'status'             => $vip1,
        'last_status_change' => $agora,
    ]);

    $CI->db->insert(db_prefix() . 'lead_activity_log', [
        'leadid'      => $lead_id,
        'staffid'     => $staff_id,
        'full_name'   => get_staff_full_name($staff_id),
        'date'        => $agora,
        'description' => 'Estado alterado (proposta cancelada — a fracção saiu do mercado) para VIP 1',
    ]);

    hooks()->do_action('lead_status_changed', [
        'lead_id'    => $lead_id,
        'old_status' => (int) $lead->status,
        'new_status' => $vip1,
    ]);
}

/**
 * Email ao cliente: a unidade saiu do mercado, o gestor volta a contactar.
 *
 * O texto não diz "a que reservou" — a esmagadora maioria destas propostas
 * nunca chegou a reserva, e dizer a alguém que perdeu uma coisa que nunca
 * teve é pior do que não dizer nada.
 */
function dps_propostas_avisar_cliente_unidade_saiu($prop, $estado)
{
    $para = trim((string) ($prop->lead_email ?? ''));

    if ($para === '' || ! filter_var($para, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $CI = &get_instance();
    $CI->load->library('email');

    $nome     = trim((string) ($prop->lead_nome ?? '')) ?: 'Estimado(a) Cliente';
    $primeiro = explode(' ', $nome)[0];
    $gestor   = get_staff_full_name((int) $prop->staff_id) ?: 'o seu gestor';
    $empresa  = get_option('companyname') ?: 'DPS Imobiliário';
    $saiu     = ($estado === 'Reservado') ? 'foi entretanto reservada' : 'foi entretanto vendida';

    $corpo = '<p>Caro(a) ' . html_escape($primeiro) . ',</p>'
        . '<p>A fracção <strong>' . html_escape($prop->unidade) . '</strong> do empreendimento <strong>'
        . html_escape($prop->empreendimento) . '</strong>, sobre a qual lhe enviámos proposta, '
        . $saiu . ' e já não se encontra disponível.</p>'
        . '<p>Lamentamos o incómodo. ' . html_escape($gestor)
        . ', o seu gestor, entrará em contacto consigo em breve com uma nova proposta — no mesmo '
        . 'empreendimento ou em empreendimentos equivalentes, ajustada ao que procura.</p>'
        . '<p>Com os melhores cumprimentos,<br>' . html_escape($empresa) . '</p>';

    $CI->email->clear(true);
    $CI->email->from(get_option('smtp_email') ?: get_option('email'), $empresa);
    $CI->email->to($para);
    $CI->email->subject('A fracção ' . $prop->unidade . ' — ' . $prop->empreendimento . ' — já não está disponível');
    $CI->email->message($corpo);
    $CI->email->set_mailtype('html');

    return (bool) $CI->email->send(false);
}

/** Aviso ao comercial, com os clientes que tem de voltar a contactar. */
function dps_propostas_avisar_comercial_canceladas($staff_id, array $clientes)
{
    if (empty($clientes)) {
        return;
    }

    /*
     * Um aviso com a lista toda, e não um por proposta: dez avisos seguidos
     * são dez avisos que ninguém lê.
     */
    $lista = count($clientes) > 6
        ? implode(', ', array_slice($clientes, 0, 6)) . ' e mais ' . (count($clientes) - 6)
        : implode(', ', $clientes);

    add_notification([
        'description' => '🚫 ' . count($clientes) . ' proposta' . (count($clientes) === 1 ? '' : 's')
            . ' cancelada' . (count($clientes) === 1 ? '' : 's') . ' — a fracção deixou de estar disponível: '
            . $lista . '. Estão em VIP 1 — envie nova proposta.',
        'touserid'    => (int) $staff_id,
        'fromcompany' => true,
        'link'        => 'dps_propostas/todas?resultado=cancelado',
    ]);
}
