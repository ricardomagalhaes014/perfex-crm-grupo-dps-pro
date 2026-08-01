<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Funções partilhadas entre o controller, as vistas e o cron de follow-ups.
 * Tudo o que envia (WhatsApp/Email/SMS) vive aqui para o comportamento ser
 * idêntico venha o pedido de onde vier.
 */

/**
 * Interruptor geral. A '0' NADA é enviado — nem massa, nem testes, nem
 * follow-ups. O banner nas vistas é informativo; a proteção real é chamar
 * isto no servidor antes de qualquer envio.
 */
function dps_automacao_ativo()
{
    return get_option('dps_automacao_ativo') === '1';
}

/**
 * Substitui {nome}/{comercial} sobre TEXTO SIMPLES.
 *
 * O escape para HTML (html_escape + nl2br) faz-se DEPOIS, ao construir o
 * corpo do email — na ordem inversa, um nome de lead com HTML injetaria
 * markup no email.
 */
function dps_automacao_render_vars($template, $nome_lead, $nome_comercial)
{
    return str_replace(
        ['{nome}', '{comercial}'],
        [(string) $nome_lead, (string) $nome_comercial],
        (string) $template
    );
}

/**
 * Números de tblleads vêm com espaços, '+', parênteses e formatos mistos.
 * Reduz a dígitos e prefixa 351 aos números nacionais de 9 dígitos — a
 * Evolution e as gateways SMS querem formato internacional sem '+'.
 * Devolve '' se não sobrar nada utilizável.
 */
function dps_automacao_normalizar_numero($tel)
{
    $digitos = preg_replace('/\D+/', '', (string) $tel);

    if ($digitos === '' || $digitos === null) {
        return '';
    }

    // '00351...' é o mesmo que '351...'
    if (strpos($digitos, '00') === 0) {
        $digitos = substr($digitos, 2);
    }

    if (strlen($digitos) === 9) {
        $digitos = '351' . $digitos;
    }

    // Curto demais para ser um número real — melhor excluir do que enviar
    // para um destino aleatório.
    if (strlen($digitos) < 9) {
        return '';
    }

    return $digitos;
}

/**
 * Configuração da Evolution API (guardada em tbloptions pelos módulos DPS).
 * Devolve null se estiver incompleta — quem chama trata como "não enviável".
 */
function dps_automacao_evolution_config()
{
    $url = rtrim(trim((string) get_option('dps_whatsapp_evolution_url')), '/');
    $key = trim((string) get_option('dps_whatsapp_evolution_api_key'));

    if ($url === '' || $key === '') {
        return null;
    }

    return ['url' => $url, 'key' => $key];
}

/**
 * Pedido HTTP à Evolution com timeout curto: se a API estiver lenta ou em
 * baixo, regista-se a falha por lead em vez de pendurar o lote inteiro.
 * Devolve [http_code|0, corpo|mensagem de erro do curl].
 *
 * $timeout sobe para o sendMedia (o upload de um PDF demora mais do que um
 * texto — o padrão de dps_proposta_receber.php usa 60s).
 */
function dps_automacao_evolution_request($metodo, $url, $apikey, $payload = null, $timeout = 10)
{
    $ch = curl_init($url);

    $headers = ['apikey: ' . $apikey];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => (int) $timeout,
    ]);

    $corpo = curl_exec($ch);
    $erro  = curl_error($ch);
    $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($corpo === false) {
        return [0, $erro ?: 'Falha de ligação à Evolution API'];
    }

    return [$code, (string) $corpo];
}

/**
 * Verifica ANTES de enviar se a instância do comercial está ligada.
 * Só se envia com "state":"open" — enviar contra uma instância fechada
 * queima o lote inteiro em falhas.
 */
function dps_automacao_whatsapp_estado($staff_id)
{
    $cfg = dps_automacao_evolution_config();
    if ($cfg === null) {
        return false;
    }

    list($code, $corpo) = dps_automacao_evolution_request(
        'GET',
        $cfg['url'] . '/instance/connectionState/staff-' . (int) $staff_id,
        $cfg['key']
    );

    if ($code < 200 || $code >= 300) {
        return false;
    }

    $json = json_decode($corpo, true);
    if (!is_array($json)) {
        return false;
    }

    // A Evolution responde {"instance":{"state":"open"}} nas versões atuais,
    // mas já respondeu {"state":"open"} — aceitar ambas.
    $estado = isset($json['instance']['state']) ? $json['instance']['state'] : (isset($json['state']) ? $json['state'] : '');

    return $estado === 'open';
}

/**
 * Envia um texto pela instância do comercial (staff-<id>).
 * Devolve [ok(bool), detalhe(string)] — o detalhe vai para o registo.
 */
function dps_automacao_whatsapp_enviar($staff_id, $numero, $texto)
{
    $cfg = dps_automacao_evolution_config();
    if ($cfg === null) {
        return [false, 'Evolution API não configurada (URL/chave em falta)'];
    }

    list($code, $corpo) = dps_automacao_evolution_request(
        'POST',
        $cfg['url'] . '/message/sendText/staff-' . (int) $staff_id,
        $cfg['key'],
        [
            // Evolution v2: 'text' no primeiro nível (a v1 aninhava em textMessage).
            'number' => (string) $numero,
            'text'   => (string) $texto,
        ]
    );

    if ($code >= 200 && $code < 300) {
        return [true, 'Enviado via staff-' . (int) $staff_id];
    }

    return [false, substr('Evolution HTTP ' . $code . ': ' . $corpo, 0, 250)];
}

/**
 * Envia um documento (PDF de proposta) pela instância do comercial, via
 * sendMedia da Evolution — o mesmo endpoint e payload de dps_proposta_receber.php.
 * $pdf_base64 já vem codificado por quem chama (UMA vez por lote, não por lead).
 * Devolve [ok(bool), detalhe(string)].
 */
function dps_automacao_whatsapp_enviar_documento($staff_id, $numero, $pdf_base64, $file_name, $legenda)
{
    $cfg = dps_automacao_evolution_config();
    if ($cfg === null) {
        return [false, 'Evolution API não configurada (URL/chave em falta)'];
    }

    list($code, $corpo) = dps_automacao_evolution_request(
        'POST',
        $cfg['url'] . '/message/sendMedia/staff-' . (int) $staff_id,
        $cfg['key'],
        [
            // Evolution v2: campos do media no primeiro nível (a v1 aninhava em mediaMessage).
            'number'    => (string) $numero,
            'mediatype' => 'document',
            'mimetype'  => 'application/pdf',
            'fileName'  => (string) $file_name,
            'media'     => (string) $pdf_base64,
            'caption'   => (string) $legenda,
        ],
        60 // upload de PDF: timeout do padrão de dps_proposta_receber.php
    );

    if ($code >= 200 && $code < 300) {
        return [true, 'Documento enviado via staff-' . (int) $staff_id];
    }

    return [false, substr('Evolution HTTP ' . $code . ': ' . $corpo, 0, 250)];
}

/**
 * Diretoria dos PDFs de propostas, criada (com .htaccess a negar acesso
 * direto — os ficheiros só saem pelo WhatsApp/email, nunca por URL) na
 * primeira utilização. Devolve o caminho com barra final, ou false se não
 * for possível criar/escrever.
 */
function dps_automacao_propostas_dir()
{
    $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'dps_automacao' . DIRECTORY_SEPARATOR . 'propostas' . DIRECTORY_SEPARATOR;

    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }

    // A proteção contra acesso direto depende deste ficheiro: se a escrita
    // falhar, a pasta NÃO pode ser usada — devolver false em vez de expor
    // os PDFs (dados pessoais/financeiros) por URL.
    $htaccess = $dir . '.htaccess';
    if (!file_exists($htaccess)) {
        $escrito = @file_put_contents(
            $htaccess,
            "Require all denied\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>"
        );
        if ($escrito === false) {
            log_activity('DPS Automação: falha ao escrever o .htaccess de ' . $dir . ' — pasta de propostas recusada');

            return false;
        }
    }

    // Padrão do próprio Perfex (uploads): index.html vazio para que um
    // autoindex ativo nunca liste os nomes dos PDFs.
    $index = $dir . 'index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }

    return is_writable($dir) ? $dir : false;
}

/**
 * Número de WhatsApp de um membro da equipa.
 *
 * Nem toda a gente tem o `phonenumber` preenchido — muitos têm o número só
 * no campo "WhatsApp (número para landing page)" (coluna landing_whatsapp),
 * que é o que foi usado ao criar as contas. Aceitam-se os dois, senão o
 * botão de WhatsApp não aparecia para quase ninguém.
 */
function dps_automacao_telefone_staff($staff)
{
    if (!is_array($staff)) {
        return '';
    }

    foreach (['phonenumber', 'landing_whatsapp'] as $campo) {
        $v = trim((string) ($staff[$campo] ?? ''));
        if ($v !== '' && preg_replace('/\D/', '', $v) !== '') {
            return $v;
        }
    }

    return '';
}

/**
 * Credenciais de envio a usar em nome de um comercial.
 *
 * O cliente deve ver o email DELE, não uma caixa genérica da empresa. Se o
 * comercial tiver o Webmail configurado, usamos a sua própria caixa (é ele
 * quem autentica, por isso o remetente é legítimo e as respostas vão para
 * ele). Só quando não houver configuração é que se recorre ao SMTP geral do
 * CRM — e mesmo aí o "responder a" aponta para o email do comercial.
 *
 * @return array{host:string,porta:int,user:string,pass:string,de:string,nome:string,reply:string}|null
 */
function dps_automacao_smtp_do_comercial($staff_id)
{
    $CI       = &get_instance();
    $staff_id = (int) $staff_id;

    $staff = $CI->db->where('staffid', $staff_id)->get(db_prefix() . 'staff')->row_array();
    $nome  = $staff ? trim($staff['firstname'] . ' ' . $staff['lastname']) : '';
    $email_staff = $staff ? (string) $staff['email'] : '';

    // 1) Caixa própria do comercial (módulo Webmail)
    if ($CI->db->table_exists(db_prefix() . 'dps_webmail_config')) {
        $w = $CI->db->where('staff_id', $staff_id)
            ->get(db_prefix() . 'dps_webmail_config')->row_array();

        if ($w && !empty($w['email']) && !empty($w['password'])) {
            // Mesma cifra do módulo Webmail (AES-256-CBC com md5 da chave).
            $k    = md5($CI->config->item('encryption_key') ?: 'dps_webmail_key');
            $pass = openssl_decrypt(base64_decode($w['password']), 'AES-256-CBC', $k, 0, substr($k, 0, 16));

            if ($pass !== false && $pass !== '') {
                return [
                    'host'  => $w['smtp_host'] ?: 'smtp.hostinger.com',
                    'porta' => (int) ($w['smtp_port'] ?: 587),
                    'user'  => $w['email'],
                    'pass'  => $pass,
                    'de'    => $w['email'],
                    'nome'  => $nome ?: $w['email'],
                    'reply' => $w['email'],
                    'tel'   => dps_automacao_telefone_staff($staff),
                ];
            }
        }
    }

    // 2) SMTP geral do CRM, mas a responder ao comercial
    $pass_geral = (string) $CI->encryption->decrypt(get_option('smtp_password'));
    $host       = (string) get_option('smtp_host');

    if ($host === '') {
        return null;
    }

    return [
        'host'  => $host,
        'porta' => (int) get_option('smtp_port'),
        'user'  => (string) get_option('smtp_username'),
        'pass'  => $pass_geral,
        'de'    => (string) (get_option('smtp_email') ?: get_option('smtp_username')),
        'nome'  => $nome ?: (get_option('companyname') ?: 'DPS'),
        'reply' => $email_staff,
        'tel'   => dps_automacao_telefone_staff($staff),
    ];
}

/**
 * Envolve a mensagem num email HTML simples com um botão de WhatsApp
 * clicável para o número do próprio comercial.
 *
 * O cliente responde onde lhe é mais cómodo — e no telemóvel um toque abre
 * logo a conversa com quem lhe escreveu.
 */
function dps_automacao_corpo_html($texto, $nome_comercial, $telefone)
{
    $numero = dps_automacao_normalizar_numero($telefone);

    $corpo = '<div style="font-family:-apple-system,Segoe UI,Roboto,sans-serif;font-size:15px;line-height:1.6;color:#1b2432;">'
        . nl2br(html_escape($texto))
        . '</div>';

    return $corpo . dps_automacao_botao_whatsapp($nome_comercial, $telefone);
}

/** O botão verde de WhatsApp, sozinho (usado também no envio em massa). */
function dps_automacao_botao_whatsapp($nome_comercial, $telefone)
{
    $numero = dps_automacao_normalizar_numero($telefone);
    $corpo  = '';

    if ($numero !== '') {
        $corpo .= '<div style="margin-top:26px;">'
            . '<a href="https://wa.me/' . rawurlencode($numero) . '" '
            . 'style="display:inline-block;background:#25D366;color:#ffffff;text-decoration:none;'
            . 'font-family:-apple-system,Segoe UI,Roboto,sans-serif;font-size:15px;font-weight:700;'
            . 'padding:13px 26px;border-radius:8px;">'
            . 'Falar por WhatsApp'
            . '</a>'
            . '<div style="margin-top:8px;font-family:-apple-system,Segoe UI,Roboto,sans-serif;'
            . 'font-size:13px;color:#5a6675;">'
            . html_escape($nome_comercial) . ' · ' . html_escape($telefone)
            . '</div></div>';
    }

    return $corpo;
}

/**
 * O servidor de correio recusou por limite de envios?
 *
 * A Hostinger corta a conta ao fim de algumas centenas de emails por hora
 * ("hostinger_out_ratelimit"). Reconhecer isto permite PARAR o lote em vez
 * de queimar as leads todas com falhas — e dizer ao utilizador que basta
 * esperar, em vez de o deixar com um erro técnico.
 */
function dps_automacao_erro_de_limite($erro)
{
    $e = strtolower((string) $erro);

    return strpos($e, 'ratelimit') !== false
        || strpos($e, 'rate limit') !== false
        || strpos($e, 'too many') !== false
        || strpos($e, 'quota') !== false;
}

/**
 * Devolve um PHPMailer JÁ LIGADO e autenticado para este comercial,
 * reutilizando a mesma ligação em todos os emails do mesmo pedido.
 *
 * Sem isto abria-se uma ligação (e uma autenticação) por cada email: a
 * Hostinger corta ao fim de poucas dezenas e o resto do lote falhava todo
 * com "Could not authenticate" — foi exactamente o que aconteceu num envio
 * de 134 propostas, em que só as 13 primeiras passaram.
 *
 * @return PHPMailer\PHPMailer\PHPMailer|null
 */
function dps_automacao_mailer($staff_id)
{
    static $cache = [];

    $staff_id = (int) $staff_id;

    if (isset($cache[$staff_id])) {
        return $cache[$staff_id];
    }

    $cred = dps_automacao_smtp_do_comercial($staff_id);

    if ($cred === null || $cred['host'] === '' || $cred['porta'] === 0) {
        return $cache[$staff_id] = null;
    }

    require_once FCPATH . 'application/vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet      = 'UTF-8';
    $mail->Host         = $cred['host'];
    $mail->Port         = $cred['porta'];
    $mail->SMTPAuth     = ($cred['user'] !== '');
    $mail->Username     = $cred['user'];
    $mail->Password     = $cred['pass'];
    $mail->SMTPSecure   = $cred['porta'] === 465 ? 'ssl' : 'tls';
    $mail->SMTPKeepAlive = true;      // <- a ligação fica aberta entre envios
    $mail->Timeout      = 20;

    $mail->setFrom($cred['de'], $cred['nome']);

    // O "responder a" e a assinatura ficam à parte: o PHP 8.2 avisa contra
    // propriedades criadas à solta numa classe que não as declara.
    dps_automacao_mailer_reply($mail, $cred);

    return $cache[$staff_id] = $mail;
}

/**
 * Guarda (ou reaplica) o "responder a" associado a um mailer.
 * Chamada com $cred guarda; chamada sem $cred reaplica.
 */
function dps_automacao_mailer_reply($mail, $cred = null, $ler = false)
{
    static $reply = [];

    $chave = spl_object_id($mail);

    if ($ler) {
        return $reply[$chave] ?? null;
    }

    if ($cred !== null) {
        $reply[$chave] = [
            'para' => (string) ($cred['reply'] ?? ''),
            'de'   => (string) $cred['de'],
            'nome' => (string) $cred['nome'],
            'tel'  => (string) ($cred['tel'] ?? ''),
        ];

        return;
    }

    if (isset($reply[$chave])
        && $reply[$chave]['para'] !== ''
        && $reply[$chave]['para'] !== $reply[$chave]['de']) {
        $mail->addReplyTo($reply[$chave]['para'], $reply[$chave]['nome']);
    }
}

/**
 * Assinatura (nome e telefone) associada a um mailer, para o botão de WhatsApp.
 */
function dps_automacao_mailer_assinatura($mail)
{
    static $vazio = ['nome' => '', 'tel' => ''];

    $r = dps_automacao_mailer_reply($mail, null, true);

    return $r ?: $vazio;
}

/**
 * Limpa os destinatários/anexos do envio anterior, mantendo a ligação viva.
 */
function dps_automacao_mailer_limpar($mail)
{
    $mail->clearAddresses();
    $mail->clearAttachments();
    $mail->clearReplyTos();

    dps_automacao_mailer_reply($mail);
}

/**
 * Email com o PDF da proposta anexado — padrão exato do ramo email de
 * dps_proposta_receber.php (PHPMailer do Perfex + SMTP das Definições: se o
 * "Send test email" do CRM funciona, isto funciona). O corpo segue em texto
 * simples, tal como lá.
 * Devolve [ok(bool), detalhe(string)].
 */
function dps_automacao_enviar_email_proposta($para, $nome_destinatario, $assunto, $texto, $caminho_pdf, $file_name, $staff_id = null)
{
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Email inválido: ' . $para];
    }

    // Ligação partilhada por todo o lote (ver dps_automacao_mailer).
    $mail = dps_automacao_mailer($staff_id ?: get_staff_user_id());

    if ($mail === null) {
        return [false, 'SMTP não configurado (Setup → Definições → Email, ou o seu Webmail)'];
    }

    try {
        dps_automacao_mailer_limpar($mail);
        $ass = dps_automacao_mailer_assinatura($mail);

        $mail->addAddress($para, (string) $nome_destinatario);
        $mail->Subject = $assunto;
        $mail->isHTML(true);
        $mail->Body    = dps_automacao_corpo_html((string) $texto, $ass['nome'], $ass['tel']);
        $mail->AltBody = (string) $texto;
        $mail->addAttachment($caminho_pdf, $file_name, 'base64', 'application/pdf');
        $mail->send();

        return [true, 'Email enviado para ' . $para];
    } catch (\Throwable $e) {
        return [false, substr('email: ' . ($mail->ErrorInfo ?: $e->getMessage()), 0, 250)];
    }

}

/**
 * Há gateway SMS ativa no Perfex? Serve para a UI esconder o canal E para o
 * servidor revalidar — o POST pode trazer canal='sms' forjado.
 */
function dps_automacao_sms_disponivel()
{
    $CI = &get_instance();

    if (!isset($CI->app_sms) || !is_object($CI->app_sms)) {
        return false;
    }

    return $CI->app_sms->get_active_gateway() !== false;
}

/**
 * Envia um SMS pela gateway ativa do Perfex.
 * Devolve [ok(bool), detalhe(string)].
 */
function dps_automacao_sms_enviar($numero, $texto)
{
    $CI = &get_instance();

    if (!dps_automacao_sms_disponivel()) {
        return [false, 'Nenhuma gateway SMS ativa'];
    }

    $gateway = $CI->app_sms->get_active_gateway();
    $classe  = 'sms_' . $gateway['id'];

    if (!isset($CI->{$classe})) {
        return [false, 'Gateway SMS "' . $gateway['id'] . '" não carregada'];
    }

    // As gateways do Perfex comunicam o erro por variável global — limpar
    // antes para não herdar o erro de um envio anterior do mesmo pedido.
    unset($GLOBALS['sms_error']);

    $ok = $CI->{$classe}->send($numero, $texto);

    if (isset($GLOBALS['sms_error'])) {
        return [false, substr((string) $GLOBALS['sms_error'], 0, 250)];
    }

    return [$ok ? true : false, $ok ? 'SMS enviado' : 'Falha no envio de SMS'];
}

/**
 * Wrapper do padrão de email que já funciona no CRM
 * (ver Dps_vendas::enviar_email): SMTP do Perfex, remetente das opções.
 * $corpo_html já vem escapado (html_escape + nl2br) por quem chama.
 */
function dps_automacao_enviar_email_lead($para, $assunto, $corpo_html, $staff_id = null)
{
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Ligação partilhada por todo o lote (ver dps_automacao_mailer).
    $mail = dps_automacao_mailer($staff_id ?: get_staff_user_id());

    if ($mail === null) {
        return false;
    }

    try {
        dps_automacao_mailer_limpar($mail);
        $ass = dps_automacao_mailer_assinatura($mail);

        $mail->addAddress($para);
        $mail->Subject = $assunto;
        $mail->isHTML(true);
        $mail->Body    = '<div style="font-family:-apple-system,Segoe UI,Roboto,sans-serif;'
            . 'font-size:15px;line-height:1.6;color:#1b2432;">' . $corpo_html . '</div>'
            . dps_automacao_botao_whatsapp($ass['nome'], $ass['tel']);
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $corpo_html));
        $mail->send();

        return true;
    } catch (\Throwable $e) {
        log_activity('dps_automacao email falhou: ' . ($mail->ErrorInfo ?: $e->getMessage()));

        return false;
    }
}

/**
 * Lê o que a descrição de uma tarefa sabe sobre a pessoa.
 *
 * As tarefas da Sofia trazem a ficha escrita no corpo, sempre com os mesmos
 * rótulos:
 *
 *     Nome: António
 *     Telefone: +351913832079
 *     Email: —
 *     Empreendimento: Aura Residence
 *
 * A leitura é feita com um rótulo a servir de fim do anterior, para o valor
 * não engolir o campo seguinte quando tudo vem na mesma linha — que é o que
 * acontece depois de o HTML ser limpo.
 *
 * Devolve sempre as quatro chaves. O que não estiver lá vem vazio; é o
 * chamador que decide o que fazer com isso.
 *
 * @param object $tarefa linha de tbltasks
 *
 * @return array{nome:string,telefone:string,email:string,empreendimento:string}
 */
function dps_automacao_pessoa_da_tarefa($tarefa)
{
    $bruto = (string) ($tarefa->description ?? '');
    $texto = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $bruto);
    $texto = trim(html_entity_decode(strip_tags($texto), ENT_QUOTES, 'UTF-8'));

    /*
     * Todos os rótulos que a Sofia usa. Cada um serve de fim ao valor
     * anterior, por isso a lista tem de estar completa: um rótulo em falta
     * faz o campo antes dele engolir tudo o que vem a seguir. Foi o que
     * aconteceu com "Resumo da conversa:", que não casava com "Resumo:".
     */
    $rotulos = 'Nome|Telefone|Telem[oó]vel|Contacto|E-?mail|Empreendimento|Origem'
        . '|Resumo(?:\s+da\s+conversa)?|Observa[çc][õo]es|Atribu[ií]da\s+a|Estado|Data';

    $ler = function ($etiqueta) use ($texto, $rotulos) {
        $padrao = '/\b(?:' . $etiqueta . ')\s*:\s*(.+?)(?=\s*\b(?:' . $rotulos . ')\s*:|$)/uis';
        if (!preg_match($padrao, $texto, $m)) {
            return '';
        }
        $v = trim(preg_replace('/\s+/u', ' ', $m[1]));

        // Marcador interno da Sofia, [sofia:conv_...]. Não é dado de ninguém.
        $v = trim(preg_replace('/\[sofia:[^\]]*\]/u', '', $v));

        // A Sofia escreve "—" quando não apanhou o campo. Isso é vazio.
        return in_array($v, ['—', '-', '–', 'n/a', 'N/A'], true) ? '' : $v;
    };

    $nome = $ler('Nome');
    if ($nome === '') {
        /*
         * Sem rótulo, serve o título da tarefa — mas sem o prefixo que a
         * Sofia lhe põe, senão a lead nascia chamada "☎️ Sofia: contactar".
         */
        $nome = (string) ($tarefa->name ?? '');
        $nome = preg_replace('/^\s*☎️?\s*Sofia\s*:\s*contactar\s*[:\-–—]?\s*/ui', '', $nome);
        $nome = preg_replace('/^[^:]{0,40}:\s*/u', '', $nome);   // "— Aura Residence: Carlos"
        $nome = trim($nome);
    }

    $telefone = $ler('Telefone|Telem[oó]vel|Contacto');
    $telefone = trim(preg_replace('/[^\d+\s()-]/u', '', $telefone));

    $email = $ler('E-?mail');
    $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';

    return [
        'nome'           => $nome !== '' ? $nome : 'Sem nome',
        'telefone'       => $telefone,
        'email'          => $email,
        'empreendimento' => $ler('Empreendimento'),
    ];
}
