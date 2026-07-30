<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cor da etiqueta de cada estado do circuito de venda.
 * Vai aquecendo à medida que a venda avança, para se ler a lista de relance.
 */
function dps_vendas_cor_estado($estado)
{
    $cores = [
        'pedido'    => 'label-danger',
        'reservado' => 'label-warning',
        'submetido' => 'label-primary',
        'vendido'   => 'label-info',
        'concluido' => 'label-success',
        'cancelado' => 'label-danger',
    ];

    return $cores[$estado] ?? 'label-default';
}

function dps_vendas_nome_estado($estado)
{
    if (empty($estado)) {
        return 'Reservado';
    }

    $nomes = [
        'pedido'    => 'Pedido — Por Confirmar',
        'reservado' => 'Reservado',
        'submetido' => 'Submetido',
        'vendido'   => 'CPCV',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ];

    return $nomes[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}

function dps_vendas_nome_doc($tipo)
{
    $nomes = [
        'cc_frente'    => 'Cartão de Cidadão (frente)',
        'cc_verso'     => 'Cartão de Cidadão (verso)',
        'cpcv'         => 'CPCV',
        'comprovativo' => 'Comprovativo de pagamento',
        'recibo_comissao' => 'Recibo da comissão',
        'outro'        => 'Outro documento',
    ];

    return $nomes[$tipo] ?? $tipo;
}

/**
 * Regimes civis usados nas escrituras. Lista fechada para não haver
 * dez maneiras de escrever a mesma coisa no histórico.
 */
function dps_vendas_regimes_civis()
{
    return [
        'Solteiro(a)',
        'Casado(a) - Comunhão de adquiridos',
        'Casado(a) - Comunhão geral de bens',
        'Casado(a) - Separação de bens',
        'União de facto',
        'Divorciado(a)',
        'Viúvo(a)',
    ];
}

/**
 * Mês 'YYYY-MM' em português legível ("Novembro de 2026").
 *
 * Escrito à mão em vez de strftime()/IntlDateFormatter: o locale pt_PT não
 * está garantido no alojamento e, quando falta, os meses saíam em inglês.
 */
function dps_vendas_mes_legivel($mes)
{
    $mes = trim((string) $mes);

    if ($mes === '') {
        return 'Sem data (imediato)';
    }

    $nomes = [
        1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    $partes = explode('-', $mes);
    $numero = isset($partes[1]) ? (int) $partes[1] : 0;

    if (!isset($nomes[$numero])) {
        return $mes;
    }

    return $nomes[$numero] . ' de ' . $partes[0];
}

/**
 * Avisa um membro da equipa por três vias: notificação no CRM (o sino),
 * email, e WhatsApp. As duas últimas são "melhor esforço" — se o SMTP ou a
 * Evolution falharem, a notificação do CRM fica na mesma e nada rebenta.
 *
 * Pedido da validação (Cláudio): os passos do circuito CPCV têm de avisar
 * o outro lado por email e WhatsApp, não só dentro do sistema.
 */
function dps_vendas_notificar($staff_id_destino, $texto, $link = null)
{
    $CI = &get_instance();
    $staff_id_destino = (int) $staff_id_destino;

    if ($staff_id_destino <= 0) {
        return;
    }

    // 1) Sino do CRM
    add_notification([
        'description' => $texto,
        'touserid'    => $staff_id_destino,
        'link'        => $link ?: 'dps_vendas',
        'fromcompany' => true,
    ]);

    $CI->db->select('email, phonenumber, firstname');
    $CI->db->where('staffid', $staff_id_destino);
    $destino = $CI->db->get(db_prefix() . 'staff')->row_array();

    if (!$destino) {
        return;
    }

    // 2) Email (usa a configuração SMTP das Definições)
    if (!empty($destino['email'])) {
        try {
            $CI->load->library('email');
            $CI->email->clear(true);
            $CI->email->from(get_option('smtp_email') ?: get_option('email'), get_option('companyname') ?: 'DPS');
            $CI->email->to($destino['email']);
            $CI->email->subject('DPS Vendas — ' . mb_substr($texto, 0, 70));
            $CI->email->message($texto . ($link ? "\n\n" . admin_url($link) : ''));
            @$CI->email->send(false);
        } catch (Throwable $e) {
            // sem email não se trava o resto
        }
    }

    // 3) WhatsApp para o telemóvel do staff, enviado pela instância de quem
    //    praticou a ação (se estiver ligada; caso contrário, salta em silêncio)
    $evo_url = rtrim((string) get_option('dps_whatsapp_evolution_url'), '/');
    $evo_key = (string) get_option('dps_whatsapp_evolution_api_key');
    $numero  = preg_replace('/[^0-9]/', '', (string) $destino['phonenumber']);

    if ($evo_url !== '' && $evo_key !== '' && $numero !== '') {
        // Números PT sem indicativo ficam com 351 à frente
        if (strlen($numero) === 9) {
            $numero = '351' . $numero;
        }
        $instancia = 'staff-' . (int) get_staff_user_id();
        $ch = curl_init($evo_url . '/message/sendText/' . $instancia);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $evo_key],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                // Evolution v2: 'text' no primeiro nível (a v1 aninhava em textMessage).
                'number' => $numero,
                'text'   => '🔔 ' . $texto . ($link ? "\n" . admin_url($link) : ''),
            ]),
        ]);
        @curl_exec($ch);
        curl_close($ch);
    }
}

/**
 * O mesmo aviso, mas para todos os administradores ativos (excepto quem
 * praticou a ação — não vale a pena avisar-se a si próprio).
 */
function dps_vendas_notificar_admins($texto, $link = null)
{
    $CI = &get_instance();

    $CI->db->select('staffid');
    $CI->db->where('admin', 1);
    $CI->db->where('active', 1);

    foreach ($CI->db->get(db_prefix() . 'staff')->result_array() as $adm) {
        if ((int) $adm['staffid'] === (int) get_staff_user_id()) {
            continue;
        }
        dps_vendas_notificar((int) $adm['staffid'], $texto, $link);
    }
}

/* =========================================================================
 * CPCV do Aura (Meixomil) — preenchimento automático
 *
 * Um .docx é um ZIP com XML lá dentro, por isso dá para preencher sem
 * bibliotecas externas: abre-se, troca-se o texto dos parágrafos que levam
 * dados, e volta-se a fechar. A formatação do original fica intacta.
 *
 * ARMADILHA: o Word parte o texto em dezenas de <w:r> (o parágrafo do
 * pagamento tem 40). Procurar e substituir texto à letra NÃO funciona — um
 * marcador fica dividido entre fragmentos. Por isso reconstrói-se o parágrafo
 * inteiro num único fragmento, aproveitando as propriedades do primeiro.
 * Perde-se o negrito DENTRO desses parágrafos; ganha-se um documento que sai
 * sempre certo.
 * ====================================================================== */

/**
 * Número inteiro por extenso, em português de Portugal.
 * Escrito à mão: não há NumberFormatter garantido no alojamento, e um valor
 * escrito por extenso errado num contrato é pior do que não o ter.
 */
function dps_cpcv_extenso($n)
{
    $u = ['zero', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez',
        'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezasseis', 'dezassete', 'dezoito', 'dezanove'];
    $d = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    $c = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos',
        'setecentos', 'oitocentos', 'novecentos'];

    $ate999 = function ($x) use ($u, $d, $c) {
        if ($x === 0) {
            return '';
        }
        if ($x === 100) {
            return 'cem';
        }
        $p = [];
        if ($x >= 100) {
            $p[] = $c[intdiv($x, 100)];
            $x %= 100;
        }
        if ($x >= 20) {
            $t = $d[intdiv($x, 10)];
            $x %= 10;
            $p[] = $t . ($x ? ' e ' . $u[$x] : '');
        } elseif ($x > 0) {
            $p[] = $u[$x];
        }

        return implode(' e ', array_filter($p));
    };

    $n = (int) round($n);
    if ($n === 0) {
        return 'zero';
    }

    $partes  = [];
    $milhoes = intdiv($n, 1000000);
    $resto   = $n % 1000000;
    $milhares = intdiv($resto, 1000);
    $unidades = $resto % 1000;

    if ($milhoes) {
        $partes[] = $milhoes === 1 ? 'um milhão' : $ate999($milhoes) . ' milhões';
    }
    if ($milhares) {
        $partes[] = $milhares === 1 ? 'mil' : $ate999($milhares) . ' mil';
    }
    if ($unidades) {
        $partes[] = $ate999($unidades);
    }

    return implode(' e ', $partes);
}

function dps_cpcv_eur($v)
{
    return '€ ' . number_format((float) $v, 2, ',', '.');
}

/**
 * Gera o CPCV preenchido para uma venda do Aura.
 *
 * @return array [bool ok, string erro, string bytes do .docx, string nome]
 */
function dps_cpcv_gerar(array $v)
{
    $modelo = __DIR__ . '/../templates/cpcv_aura.docx';

    if (!is_file($modelo)) {
        return [false, 'Falta o modelo do contrato no servidor (templates/cpcv_aura.docx).', '', ''];
    }

    $zip = new ZipArchive();
    if ($zip->open($modelo) !== true) {
        return [false, 'Não foi possível abrir o modelo do contrato.', '', ''];
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        return [false, 'O modelo do contrato está corrompido.', '', ''];
    }

    $preco = (float) $v['valor'];
    $sinal = 5000.0;                          // CPCV, dedutível na escritura
    $p10   = round($preco * 0.10, 2);
    $rem   = round($preco - $sinal - (3 * $p10), 2);

    if ($rem < 0) {
        return [false, 'O preço da fracção é baixo demais para este plano de pagamento (' . dps_cpcv_eur($preco) . ').', '', ''];
    }

    $iban_falta = '«IBAN DO COMPRADOR — PREENCHER»';
    $iban_venda = 'PT50 0045 1325 40416282470 83';

    $e = function ($x) {
        return htmlspecialchars((string) $x, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };

    $val_cc = !empty($v['cliente_cc_validade']) ? date('d/m/Y', strtotime($v['cliente_cc_validade'])) : '__/__/____';

    $subs = [];

    $subs['PRIMEIRO OUTORGANTE:'] =
        '____ PRIMEIRO OUTORGANTE: ' . $v['cliente'] . ', NIF ' . $v['cliente_nif']
        . ' (Cartão de Cidadão n.º ' . $v['cliente_cc'] . ', emitido pela República Portuguesa, válido até '
        . $val_cc . '), ' . mb_strtolower((string) $v['regime_civil'], 'UTF-8')
        . ', natural de ' . $v['cliente_naturalidade']
        . ', de nacionalidade ' . mb_strtolower((string) $v['cliente_nacionalidade'], 'UTF-8')
        . ', residente na ' . $v['cliente_morada']
        . ', freguesia de ' . $v['cliente_freguesia']
        . ', concelho de ' . $v['cliente_concelho']
        . ' (CP ' . $v['cliente_codigo_postal'] . '). _______________________________';

    $subs['O preço global da presente promessa'] =
        'O preço global da presente promessa é de ' . dps_cpcv_eur($preco) . ' ('
        . dps_cpcv_extenso($preco) . ' euros) e será pago pela PROMITENTE COMPRADORA à '
        . 'PROMITENTE VENDEDORA da seguinte forma: ------------------------------------------';

    $subs['No ato da assinatura do presente contrato'] =
        'a) No ato da assinatura do presente contrato, será entregue à PROMITENTE VENDEDORA a '
        . 'título de sinal e princípio de pagamento, o montante de ' . dps_cpcv_eur($sinal) . ' ('
        . dps_cpcv_extenso($sinal) . ' euros), a deduzir no preço no ato da escritura, cujo pagamento '
        . 'será efetuado por transferência bancária da conta ordenante com o IBAN ' . $iban_falta
        . ', para a conta beneficiária IBAN ' . $iban_venda . '; caso a transferência não seja '
        . 'recebida até 5 dias após a assinatura do presente contrato, o mesmo fica sem efeito. ----';

    $subs['Após a aprovação do projeto de arquitetura'] =
        'b) Após a aprovação do projeto de arquitetura, a PROMITENTE VENDEDORA notificará pelos meios '
        . 'convencionados a PROMITENTE COMPRADORA para no prazo de 5 dias entregar o montante '
        . 'correspondente a 10% do preço, ' . dps_cpcv_eur($p10) . ' (' . dps_cpcv_extenso($p10)
        . ' euros), a título de sinal, que será efetuado por transferência bancária da conta '
        . 'ordenante com o IBAN ' . $iban_falta . ' para a conta beneficiária IBAN ' . $iban_venda . '. ----';

    $subs['Após o início de obra'] =
        'c) No mês de dezembro, a PROMITENTE VENDEDORA notificará a PROMITENTE COMPRADORA para no '
        . 'prazo de 5 dias entregar o montante correspondente a 10% do preço, ' . dps_cpcv_eur($p10)
        . ' (' . dps_cpcv_extenso($p10) . ' euros), a título de reforço de sinal, que será efetuado '
        . 'por transferência bancária da conta ordenante com o IBAN ' . $iban_falta
        . ' para a conta beneficiária IBAN ' . $iban_venda . '. --------------------------------';

    $subs['Após o termo da fase de estrutura'] =
        'd) Após o termo da fase de estrutura, um ano depois, a PROMITENTE VENDEDORA notificará pelos '
        . 'meios convencionados a PROMITENTE COMPRADORA para no prazo de 5 dias entregar o montante '
        . 'correspondente a 10% do preço, ' . dps_cpcv_eur($p10) . ' (' . dps_cpcv_extenso($p10)
        . ' euros), a título de sinal, que será efetuado por transferência bancária da conta ordenante '
        . 'com o IBAN ' . $iban_falta . ' para a conta beneficiária IBAN ' . $iban_venda . '. -------';

    $subs['O remanescente do preço'] =
        'e) O remanescente do preço, no montante de ' . dps_cpcv_eur($rem) . ' ('
        . dps_cpcv_extenso($rem) . ' euros), a liquidar no ato da escritura de compra e venda, por '
        . 'cheque bancário, que a PROMITENTE VENDEDORA declarará receber e à qual atribuirá completa '
        . 'e integral quitação, após boa cobrança do mesmo. ---------------------------------';

    $subs['PROMITENTE COMPRADORA:'] = 'PROMITENTE COMPRADORA: ' . $v['cliente_email'] . '  ______________';

    // --- reconstruir os parágrafos que levam dados ---
    preg_match_all('/<w:p[ >].*?<\/w:p>/s', $xml, $m);
    $usadas = [];

    foreach ($m[0] as $par) {
        $texto = preg_replace('/<[^>]+>/', '', $par);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');

        foreach ($subs as $marca => $novo) {
            if (isset($usadas[$marca]) || mb_strpos($texto, $marca) === false) {
                continue;
            }

            $ppr = preg_match('/<w:pPr>.*?<\/w:pPr>/s', $par, $mp) ? $mp[0] : '';
            $rpr = preg_match('/<w:rPr>.*?<\/w:rPr>/s', $par, $mr) ? $mr[0] : '';

            $novo_par = '<w:p>' . $ppr . '<w:r>' . $rpr
                . '<w:t xml:space="preserve">' . $e($novo) . '</w:t></w:r></w:p>';

            $xml = str_replace($par, $novo_par, $xml);
            $usadas[$marca] = true;
            break;
        }
    }

    if (count($usadas) !== count($subs)) {
        $faltam = array_diff(array_keys($subs), array_keys($usadas));

        return [false, 'O modelo do contrato mudou: não encontrei ' . count($faltam)
            . ' secção(ões) — ' . implode(' | ', $faltam), '', ''];
    }

    // --- reescrever o .docx ---
    $tmp = tempnam(sys_get_temp_dir(), 'cpcv');
    if (!copy($modelo, $tmp)) {
        return [false, 'Não foi possível preparar o documento.', '', ''];
    }

    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) {
        @unlink($tmp);

        return [false, 'Não foi possível escrever o documento.', '', ''];
    }
    $zip->deleteName('word/document.xml');
    $zip->addFromString('word/document.xml', $xml);
    $zip->close();

    $bytes = file_get_contents($tmp);
    @unlink($tmp);

    $nome = 'CPCV ' . $v['empreendimento'] . ' - ' . $v['unidade'] . ' - '
        . preg_replace('/[^\p{L}\p{N} ]+/u', '', (string) $v['cliente']) . '.docx';

    return [true, '', $bytes, $nome];
}
