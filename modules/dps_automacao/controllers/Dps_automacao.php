<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * DPS Automação: envio em massa por estado de lead, guiões da Sofia e
 * consulta do registo de envios.
 *
 * Regras transversais (não confiar na UI):
 * - Toda a escrita exige POST (show_404 se GET) e passa pelo CSRF do form_open.
 * - Nenhum envio acontece com o interruptor geral a '0' — verificado AQUI.
 * - Para não-admin, o comercial é SEMPRE get_staff_user_id(): o que vier no
 *   POST é ignorado, porque o filtro assigned é a fronteira entre comerciais.
 */
class Dps_automacao extends AdminController
{
    /** Leads processadas por pedido AJAX — o lote seguinte continua do last_id. */
    const TAMANHO_LOTE = 50;

    public function __construct()
    {
        parent::__construct();

        /*
         * Entra qualquer staff autenticado. A separação entre comerciais NÃO
         * se faz aqui, faz-se nos dados: comercial_do_post() força sempre o
         * próprio id para não-admin, o envio filtra por assigned e o registo
         * de envios só mostra os dele. O admin é o único que vê tudo e
         * escolhe o comercial.
         *
         * (Antes exigia-se a capacidade 'view', que nenhum comercial tinha —
         * davam todos com "acesso negado" em Envio em Massa, Proposta em
         * Massa e Registo de Envios.)
         */
        if (!is_staff_logged_in()) {
            access_denied('dps_automacao');
        }

        $this->load->model('dps_automacao_model');
        $this->load->model('dps_automacao_guioes_model');
    }

    public function index()
    {
        redirect(admin_url('dps_automacao/envio_massa'));
    }

    /* -----------------------------------------------------------------
     * Envio em massa
     * -------------------------------------------------------------- */

    public function envio_massa()
    {
        $data['estados']         = $this->dps_automacao_model->get_estados_lead();
        $data['comerciais']      = is_admin() ? $this->dps_automacao_model->get_comerciais() : [];
        $data['sms_disponivel']  = dps_automacao_sms_disponivel();
        $data['automacao_ativa'] = dps_automacao_ativo();
        $data['title']           = 'Envio em Massa';

        $this->load->view('envio_massa', $data);
    }

    /**
     * POST (AJAX) — contagens antes de enviar. Nada sai daqui.
     */
    public function envio_massa_preview()
    {
        if (!$this->input->post()) {
            show_404();
        }

        $canal = $this->canal_do_post();
        if ($canal === null) {
            $this->responder_json(['erro' => 'Canal inválido.']);
        }

        $estados = $this->estados_do_post();
        if (empty($estados)) {
            $this->responder_json(['erro' => 'Escolha pelo menos um estado de leads.']);
        }

        $comercial_id = $this->comercial_do_post();

        $linhas = $this->dps_automacao_model->contar_leads($estados, $comercial_id, $canal);

        $total        = 0;
        $com_contacto = 0;
        foreach ($linhas as $linha) {
            $total        += (int) $linha['total'];
            $com_contacto += (int) $linha['com_contacto'];
        }

        $this->responder_json([
            'estados'      => $linhas,
            'total'        => $total,
            'com_contacto' => $com_contacto,
            'excluidas'    => $total - $com_contacto,
        ]);
    }

    /**
     * POST — envia a mensagem renderizada para o PRÓPRIO staff (email ou
     * telefone de tblstaff), com dados fictícios de lead. Regista tipo='teste'.
     */
    public function envio_massa_teste()
    {
        if (!$this->input->post()) {
            show_404();
        }

        if (!dps_automacao_ativo()) {
            $this->responder_json(['erro' => 'A automação está desligada nas Definições — nada é enviado.']);
        }

        $canal = $this->canal_do_post();
        if ($canal === null) {
            $this->responder_json(['erro' => 'Canal inválido.']);
        }

        $mensagem = trim((string) $this->input->post('mensagem'));
        if ($mensagem === '') {
            $this->responder_json(['erro' => 'Escreva a mensagem antes de testar.']);
        }

        $staff_id = (int) get_staff_user_id();
        $staff    = $this->db->where('staffid', $staff_id)
            ->get(db_prefix() . 'staff')
            ->row_array();

        if (!$staff) {
            $this->responder_json(['erro' => 'Não foi possível carregar os seus dados de staff.']);
        }

        $comercial_nome = trim($staff['firstname'] . ' ' . $staff['lastname']);
        $texto          = dps_automacao_render_vars($mensagem, 'Maria Exemplo', $comercial_nome);

        $ok      = false;
        $detalhe = '';

        if ($canal === 'email') {
            if (empty($staff['email'])) {
                $this->responder_json(['erro' => 'O seu perfil de staff não tem email.']);
            }
            $assunto = $this->assunto_do_post();
            $ok      = dps_automacao_enviar_email_lead($staff['email'], '[TESTE] ' . $assunto, nl2br(html_escape($texto)));
            $detalhe = $ok ? 'Teste enviado para ' . $staff['email'] : 'Falha no envio SMTP';
        } elseif ($canal === 'whatsapp') {
            $numero = dps_automacao_normalizar_numero(dps_automacao_telefone_staff($staff));
            if ($numero === '') {
                $this->responder_json(['erro' => 'O seu perfil de staff não tem número de telefone válido.']);
            }
            if (!dps_automacao_whatsapp_estado($staff_id)) {
                $this->responder_json(['erro' => 'A sua instância WhatsApp (staff-' . $staff_id . ') não está ligada.']);
            }
            list($ok, $detalhe) = dps_automacao_whatsapp_enviar($staff_id, $numero, $texto);
        } else { // sms
            // Esconder na UI não chega: o POST pode trazer canal='sms' forjado.
            if (!dps_automacao_sms_disponivel()) {
                $this->responder_json(['erro' => 'Não há nenhuma gateway SMS ativa no CRM.']);
            }
            $numero = dps_automacao_normalizar_numero(dps_automacao_telefone_staff($staff));
            if ($numero === '') {
                $this->responder_json(['erro' => 'O seu perfil de staff não tem número de telefone válido.']);
            }
            list($ok, $detalhe) = dps_automacao_sms_enviar($numero, $texto);
        }

        $this->dps_automacao_model->registar_envio([
            'lead_id'  => 0,
            'staff_id' => $staff_id,
            'canal'    => $canal,
            'tipo'     => 'teste',
            'mensagem' => $texto,
            'ok'       => $ok ? 1 : 0,
            'detalhe'  => substr((string) $detalhe, 0, 255),
        ]);

        if ($ok) {
            $this->responder_json(['sucesso' => 'Teste enviado para si (' . $canal . ').']);
        }

        $this->responder_json(['erro' => 'O teste falhou: ' . $detalhe]);
    }

    /**
     * POST (AJAX sequencial) — processa UM lote de leads (cursor last_id) e
     * devolve o progresso; o browser volta a chamar até fim=true. Assim uma
     * lista grande nunca excede o max_execution_time num único pedido.
     */
    public function envio_massa_enviar()
    {
        if (!$this->input->post()) {
            show_404();
        }

        // O banner na vista é informativo; a proteção real é esta.
        if (!dps_automacao_ativo()) {
            $this->responder_json(['erro' => 'A automação está desligada nas Definições — nada é enviado.']);
        }

        $canal = $this->canal_do_post();
        if ($canal === null) {
            $this->responder_json(['erro' => 'Canal inválido.']);
        }

        if ($canal === 'sms' && !dps_automacao_sms_disponivel()) {
            $this->responder_json(['erro' => 'Não há nenhuma gateway SMS ativa no CRM.']);
        }

        $estados = $this->estados_do_post();
        if (empty($estados)) {
            $this->responder_json(['erro' => 'Escolha pelo menos um estado de leads.']);
        }

        $mensagem = trim((string) $this->input->post('mensagem'));
        if ($mensagem === '') {
            $this->responder_json(['erro' => 'A mensagem está vazia.']);
        }

        $comercial_id = $this->comercial_do_post();
        $last_id      = (int) $this->input->post('last_id');

        @set_time_limit(0);

        $leads = $this->dps_automacao_model->get_leads_por_estados(
            $estados,
            $comercial_id,
            $canal,
            $last_id,
            self::TAMANHO_LOTE
        );

        $enviados = 0;
        $falhados = 0;
        $assunto  = $this->assunto_do_post();

        // Estado das instâncias WhatsApp verificado UMA vez por comercial e
        // por lote: enviar contra uma instância fechada queima o lote inteiro.
        $instancias = [];

        foreach ($leads as $lead) {
            $last_id = (int) $lead['id'];

            $comercial_nome = trim((string) (isset($lead['comercial']) ? $lead['comercial'] : ''));
            if ($comercial_nome === '') {
                $comercial_nome = get_option('companyname') ?: 'A nossa equipa';
            }

            // Variáveis substituídas sobre texto simples; o escape para HTML
            // do email vem depois (a ordem inversa injetaria markup).
            $texto = dps_automacao_render_vars($mensagem, $lead['name'], $comercial_nome);

            $ok      = false;
            $detalhe = '';

            if ($canal === 'whatsapp') {
                $assigned = (int) $lead['assigned'];

                if ($assigned === 0) {
                    // Cada lead sai pela instância do SEU comercial — sem
                    // comercial atribuído não há instância por onde sair.
                    $detalhe = 'Lead sem comercial atribuído — sem instância WhatsApp';
                } else {
                    if (!array_key_exists($assigned, $instancias)) {
                        $instancias[$assigned] = dps_automacao_whatsapp_estado($assigned);
                    }

                    if (!$instancias[$assigned]) {
                        $detalhe = 'Instância staff-' . $assigned . ' não está ligada (connectionState != open)';
                    } else {
                        $numero = dps_automacao_normalizar_numero($lead['phonenumber']);
                        if ($numero === '') {
                            $detalhe = 'Número de telefone inválido: ' . $lead['phonenumber'];
                        } else {
                            list($ok, $detalhe) = dps_automacao_whatsapp_enviar($assigned, $numero, $texto);
                            // Pausa entre mensagens para não rebentar a Evolution.
                            usleep(300000);
                        }
                    }
                }
            } elseif ($canal === 'email') {
                $ok      = dps_automacao_enviar_email_lead(
                    $lead['email'],
                    $assunto,
                    nl2br(html_escape($texto)),
                    $comercial_id ?: (int) ($lead['assigned'] ?: get_staff_user_id())
                );
                $detalhe = $ok ? 'Enviado para ' . $lead['email'] : 'Falha no envio SMTP';
            } else { // sms
                $numero = dps_automacao_normalizar_numero($lead['phonenumber']);
                if ($numero === '') {
                    $detalhe = 'Número de telefone inválido: ' . $lead['phonenumber'];
                } else {
                    list($ok, $detalhe) = dps_automacao_sms_enviar($numero, $texto);
                }
            }

            $this->dps_automacao_model->registar_envio([
                'lead_id'     => (int) $lead['id'],
                'staff_id'    => (int) $lead['assigned'],
                'canal'       => $canal,
                'tipo'        => 'massa',
                'estado_lead' => (int) $lead['status'],
                'mensagem'    => $texto,
                'ok'          => $ok ? 1 : 0,
                'detalhe'     => substr((string) $detalhe, 0, 255),
            ]);

            if ($ok) {
                $enviados++;
            } else {
                $falhados++;

                /*
                 * Limite do servidor de correio atingido (a Hostinger corta a
                 * conta ao fim de algumas centenas por hora): PARAR já. Sem
                 * isto o lote seguia em frente e marcava todas as leads
                 * restantes como falhadas, sem nada ser entregue. Como a
                 * guarda só bloqueia envios BEM SUCEDIDOS, as que ficaram por
                 * enviar voltam a entrar quando se repetir a campanha.
                 */
                if (dps_automacao_erro_de_limite($detalhe)) {
                    $this->responder_json([
                        'processados' => $enviados + $falhados,
                        'enviados'    => $enviados,
                        'falhados'    => $falhados,
                        'last_id'     => $last_id,
                        'fim'         => true,
                        'aviso'       => 'O servidor de correio atingiu o limite de envios por hora. '
                            . 'Foram entregues ' . $enviados . '. Espere cerca de uma hora e volte a '
                            . 'lançar o envio — os já entregues são saltados.',
                    ]);
                }
            }

            // Respirar entre emails: reduz a probabilidade de bater no limite.
            if ($canal === 'email') {
                usleep(400000);
            }
        }

        $this->responder_json([
            'processados' => count($leads),
            'enviados'    => $enviados,
            'falhados'    => $falhados,
            'last_id'     => $last_id,
            'fim'         => count($leads) < self::TAMANHO_LOTE,
        ]);
    }

    /* -----------------------------------------------------------------
     * Proposta em Massa (PDF do simulador anexado a cada lead)
     * -------------------------------------------------------------- */

    /** Tamanho máximo do PDF de proposta: 25 MB. */
    const PROPOSTA_MAX_BYTES = 26214400;

    public function proposta_massa()
    {
        $data['estados']         = $this->dps_automacao_model->get_estados_lead();
        $data['comerciais']      = is_admin() ? $this->dps_automacao_model->get_comerciais() : [];
        $data['automacao_ativa'] = dps_automacao_ativo();
        // Cada comercial vê APENAS as suas propostas; o admin vê todas.
        $data['propostas'] = $this->dps_automacao_model->get_propostas(
            is_admin() ? null : (int) get_staff_user_id()
        );
        $data['title'] = 'Proposta em Massa';

        $this->load->view('proposta_massa', $data);
    }

    /**
     * POST (multipart) — carrega um PDF de proposta para o servidor.
     * Só .pdf, validado por finfo, máx. 25 MB. O ficheiro fica com nome hex
     * aleatório numa pasta protegida por .htaccess: nunca é servido por URL,
     * só sai anexado pelo WhatsApp/email.
     */
    public function proposta_carregar()
    {
        /*
         * Nunca responder 404 a um carregamento falhado: o utilizador ficava
         * sem saber o que correu mal. Quando o PDF ultrapassa o limite de
         * POST do servidor, o PHP descarta o pedido INTEIRO e chega aqui um
         * pedido sem $_POST nem $_FILES — é preciso dizer isso por palavras.
         */
        if (!$this->input->post() && empty($_FILES)) {
            $enviados = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

            if ($enviados > 0) {
                set_alert('danger', 'O ficheiro (' . round($enviados / 1048576, 1)
                    . ' MB) foi recusado pelo servidor antes de chegar ao CRM. '
                    . 'Tente um PDF mais pequeno ou avise o administrador.');
            } else {
                set_alert('danger', 'O carregamento chegou vazio. Escolha o ficheiro e tente de novo.');
            }

            log_activity('dps_automacao: upload de proposta sem POST (content-length=' . $enviados . ')');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        $ficheiro = isset($_FILES['pdf']) ? $_FILES['pdf'] : null;

        if (!$ficheiro || !isset($ficheiro['error']) || is_array($ficheiro['error'])
            || $ficheiro['error'] === UPLOAD_ERR_NO_FILE) {
            set_alert('danger', 'Escolha o ficheiro PDF da proposta antes de carregar.');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
            // UPLOAD_ERR_INI_SIZE/FORM_SIZE e afins — mensagem única chega.
            set_alert('danger', 'O carregamento falhou (código ' . (int) $ficheiro['error'] . '). O ficheiro pode exceder o limite do servidor.');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        if ((int) $ficheiro['size'] > self::PROPOSTA_MAX_BYTES) {
            set_alert('danger', 'O PDF excede o limite de 25 MB.');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        $original = (string) $ficheiro['name'];
        $extensao = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if ($extensao !== 'pdf') {
            set_alert('danger', 'Apenas ficheiros PDF são aceites.');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        // A extensão sozinha não chega: confirmar o conteúdo com finfo.
        // Alguns browsers/SOs marcam PDFs como octet-stream — aceitável, mas
        // só porque a extensão .pdf já foi exigida acima.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? (string) finfo_file($finfo, $ficheiro['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mime, ['application/pdf', 'application/octet-stream'], true)) {
            set_alert('danger', 'O ficheiro não parece ser um PDF válido (' . $mime . ').');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        if (!is_uploaded_file($ficheiro['tmp_name'])) {
            set_alert('danger', 'O ficheiro não chegou corretamente ao servidor. Tente de novo.');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        /*
         * O finfo devolve "octet-stream" justamente quando NÃO reconhece o
         * conteúdo — sem esta verificação, qualquer binário renomeado para
         * .pdf passava e seria distribuído aos clientes como "Proposta DPS".
         * Um PDF começa sempre por "%PDF-".
         */
        if ($mime === 'application/octet-stream') {
            $inicio = @file_get_contents($ficheiro['tmp_name'], false, null, 0, 5);
            if ($inicio !== '%PDF-') {
                set_alert('danger', 'O ficheiro não é um PDF — o conteúdo não corresponde à extensão.');
                redirect(admin_url('dps_automacao/proposta_massa'));
            }
        }

        $dir = dps_automacao_propostas_dir();
        if ($dir === false) {
            set_alert('danger', 'Não foi possível criar a pasta de propostas no servidor (uploads/dps_automacao/propostas).');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        // Nome aleatório no disco: sem colisões e sem adivinhação por URL.
        $nome_disco = bin2hex(random_bytes(16)) . '.pdf';

        if (!move_uploaded_file($ficheiro['tmp_name'], $dir . $nome_disco)) {
            set_alert('danger', 'Não foi possível guardar o PDF no servidor.');
            redirect(admin_url('dps_automacao/proposta_massa'));
        }

        // Garantir o sufixo .pdf no nome que a lead vai ver (mesma regra do
        // dps_proposta_receber.php).
        if (strtolower(substr($original, -4)) !== '.pdf') {
            $original .= '.pdf';
        }

        $this->dps_automacao_model->registar_proposta([
            'staff_id'      => (int) get_staff_user_id(),
            'filename'      => $nome_disco,
            'original_name' => substr($original, 0, 255),
            'tamanho'       => (int) $ficheiro['size'],
        ]);

        set_alert('success', 'Proposta carregada. Escolha-a na lista e prepare o envio.');
        redirect(admin_url('dps_automacao/proposta_massa'));
    }

    /**
     * POST — apaga uma proposta carregada (ficheiro + registo). Só o dono ou
     * o admin. Os envios já feitos ficam no registo (e a guarda de
     * deduplicação com eles).
     */
    public function proposta_apagar($id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        $proposta = $this->dps_automacao_model->get_proposta((int) $id);
        if (!$proposta) {
            show_404();
        }

        if (!is_admin() && (int) $proposta['staff_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_automacao');
        }

        $dir = dps_automacao_propostas_dir();
        // basename() por precaução — o filename vem da BD, mas nunca compor
        // caminhos com valores externos sem o reduzir ao nome do ficheiro.
        if ($dir !== false && $proposta['filename'] !== '') {
            @unlink($dir . basename($proposta['filename']));
        }

        $this->dps_automacao_model->apagar_proposta((int) $id);

        set_alert('success', 'Proposta apagada.');
        redirect(admin_url('dps_automacao/proposta_massa'));
    }

    /**
     * POST (AJAX) — envia a proposta com a mensagem renderizada (dados
     * fictícios) para o PRÓPRIO staff, com prefixo [TESTE]. Regista tipo='teste'.
     */
    public function proposta_massa_teste()
    {
        if (!$this->input->post()) {
            show_404();
        }

        if (!dps_automacao_ativo()) {
            $this->responder_json(['erro' => 'A automação está desligada nas Definições — nada é enviado.']);
        }

        $canal = $this->canal_proposta_do_post();
        if ($canal === null) {
            $this->responder_json(['erro' => 'Canal inválido.']);
        }

        $mensagem = trim((string) $this->input->post('mensagem'));
        if ($mensagem === '') {
            $this->responder_json(['erro' => 'Escreva a mensagem antes de testar.']);
        }

        $proposta = $this->proposta_do_post();
        $caminho  = $this->caminho_da_proposta($proposta);

        $staff_id = (int) get_staff_user_id();
        $staff    = $this->db->where('staffid', $staff_id)
            ->get(db_prefix() . 'staff')
            ->row_array();

        if (!$staff) {
            $this->responder_json(['erro' => 'Não foi possível carregar os seus dados de staff.']);
        }

        $comercial_nome = trim($staff['firstname'] . ' ' . $staff['lastname']);
        $texto          = '[TESTE] ' . dps_automacao_render_vars($mensagem, 'Maria Exemplo', $comercial_nome);

        $ok      = false;
        $detalhe = '';

        if ($canal === 'email') {
            if (empty($staff['email'])) {
                $this->responder_json(['erro' => 'O seu perfil de staff não tem email.']);
            }
            list($ok, $detalhe) = dps_automacao_enviar_email_proposta(
                $staff['email'],
                $comercial_nome,
                '[TESTE] Proposta DPS',
                $texto,
                $caminho,
                $proposta['original_name'],
                $staff_id
            );
        } else { // whatsapp
            $numero = dps_automacao_normalizar_numero(dps_automacao_telefone_staff($staff));
            if ($numero === '') {
                $this->responder_json(['erro' => 'O seu perfil de staff não tem número de telefone válido.']);
            }
            if (!dps_automacao_whatsapp_estado($staff_id)) {
                $this->responder_json(['erro' => 'A sua instância WhatsApp (staff-' . $staff_id . ') não está ligada — leia o QR no CRM e tente de novo.']);
            }
            list($ok, $detalhe) = dps_automacao_whatsapp_enviar_documento(
                $staff_id,
                $numero,
                base64_encode((string) file_get_contents($caminho)),
                $proposta['original_name'],
                $texto
            );
        }

        // tipo='teste' (e não 'proposta_massa') de propósito: um teste nunca
        // pode ativar a guarda de deduplicação da campanha real.
        $this->dps_automacao_model->registar_envio([
            'lead_id'  => 0,
            'staff_id' => $staff_id,
            'canal'    => $canal,
            'tipo'     => 'teste',
            'mensagem' => $texto,
            'ok'       => $ok ? 1 : 0,
            'detalhe'  => substr('proposta:' . (int) $proposta['id'] . ' ' . $detalhe, 0, 255),
        ]);

        if ($ok) {
            $this->responder_json(['sucesso' => 'Teste enviado para si (' . $canal . ') com o PDF anexado.']);
        }

        $this->responder_json(['erro' => 'O teste falhou: ' . $detalhe]);
    }

    /**
     * POST (AJAX sequencial) — UM lote da proposta em massa, com o mesmo
     * padrão de cursor do envio_massa_enviar. A deduplicação vive na query
     * (get_leads_para_proposta) + no registo inserido ANTES do envio, por
     * isso repetir a campanha só apanha leads ainda sem registo.
     */
    public function proposta_massa_lote()
    {
        if (!$this->input->post()) {
            show_404();
        }

        // O banner na vista é informativo; a proteção real é esta.
        if (!dps_automacao_ativo()) {
            $this->responder_json(['erro' => 'A automação está desligada nas Definições — nada é enviado.']);
        }

        $canal = $this->canal_proposta_do_post();
        if ($canal === null) {
            $this->responder_json(['erro' => 'Canal inválido.']);
        }

        $estados = $this->estados_do_post();
        if (empty($estados)) {
            $this->responder_json(['erro' => 'Escolha pelo menos um estado de leads.']);
        }

        $mensagem = trim((string) $this->input->post('mensagem'));
        if ($mensagem === '') {
            $this->responder_json(['erro' => 'A mensagem está vazia.']);
        }

        $proposta    = $this->proposta_do_post();
        $proposta_id = (int) $proposta['id'];
        $caminho     = $this->caminho_da_proposta($proposta);

        $comercial_id = $this->comercial_do_post();
        $last_id      = (int) $this->input->post('last_id');

        // Fail-fast no PRIMEIRO lote quando o alvo é UM comercial (não-admin,
        // ou admin com comercial escolhido): se a instância não estiver
        // "open", parar já com uma mensagem clara em vez de queimar registos
        // de deduplicação em falhas (padrão de dps_proposta_receber.php).
        if ($canal === 'whatsapp' && $last_id === 0 && $comercial_id !== null
            && !dps_automacao_whatsapp_estado($comercial_id)) {
            $this->responder_json([
                'erro' => 'A instância WhatsApp (staff-' . (int) $comercial_id . ') não está ligada — leia o QR no CRM antes de enviar. Nada foi enviado.',
            ]);
        }

        // Fail-fast equivalente para o canal email: sem SMTP configurado
        // nenhum envio é possível — parar já em vez de queimar a campanha
        // inteira em registos falhados.
        if ($canal === 'email'
            && ((string) get_option('smtp_host') === '' || (int) get_option('smtp_port') === 0)) {
            $this->responder_json([
                'erro' => 'SMTP não configurado (Setup → Definições → Email). Nada foi enviado.',
            ]);
        }

        @set_time_limit(0);

        // Reenviar a quem já recebeu: escolha explícita do utilizador em cada
        // envio, nunca por omissão.
        $repetir = (int) $this->input->post('repetir') === 1;

        $leads = $this->dps_automacao_model->get_leads_para_proposta(
            $estados,
            $comercial_id,
            $canal,
            $proposta_id,
            $last_id,
            self::TAMANHO_LOTE,
            $repetir
        );

        // O PDF é lido/codificado UMA vez por pedido de lote, nunca por lead.
        $pdf_base64 = null;
        if ($canal === 'whatsapp' && !empty($leads)) {
            $pdf_base64 = base64_encode((string) file_get_contents($caminho));
        }

        $enviados = 0;
        $falhados = 0;

        // Estado das instâncias WhatsApp verificado UMA vez por comercial e
        // por lote — enviar contra uma instância fechada queima o lote inteiro.
        $instancias = [];

        foreach ($leads as $lead) {
            $last_id = (int) $lead['id'];

            $comercial_nome = trim((string) (isset($lead['comercial']) ? $lead['comercial'] : ''));
            if ($comercial_nome === '') {
                $comercial_nome = get_option('companyname') ?: 'A nossa equipa';
            }

            $texto = dps_automacao_render_vars($mensagem, $lead['name'], $comercial_nome);

            // Guarda inserida ANTES do envio (padrão dos follow-ups): um lote
            // concorrente ou uma repetição da campanha já vê este registo e
            // salta a lead. O prefixo "proposta:<id> " é a chave do LIKE da
            // guarda — tem de existir SEMPRE, também no update final.
            $registo_id = $this->dps_automacao_model->registar_envio([
                'lead_id'     => (int) $lead['id'],
                'staff_id'    => (int) $lead['assigned'],
                'canal'       => $canal,
                'tipo'        => 'proposta_massa',
                'estado_lead' => (int) $lead['status'],
                'mensagem'    => $texto,
                'ok'          => 0,
                'detalhe'     => 'proposta:' . $proposta_id . ' em processamento',
            ]);

            $ok      = false;
            $detalhe = '';
            // Só é "tentativa real" quando um request de envio foi mesmo
            // feito. Falhas de pré-condição (instância desligada, lead sem
            // comercial, contacto inválido) apagam a guarda em vez de a
            // atualizar — corrigida a causa, repetir a campanha volta a
            // apanhar a lead.
            $tentado = false;

            if ($canal === 'whatsapp') {
                $assigned = (int) $lead['assigned'];

                if ($assigned === 0) {
                    // Cada lead sai pela instância do SEU comercial — sem
                    // comercial atribuído não há instância por onde sair.
                    $detalhe = 'Lead sem comercial atribuído — sem instância WhatsApp';
                } else {
                    if (!array_key_exists($assigned, $instancias)) {
                        $instancias[$assigned] = dps_automacao_whatsapp_estado($assigned);
                    }

                    if (!$instancias[$assigned]) {
                        $detalhe = 'Instância staff-' . $assigned . ' não está ligada (connectionState != open)';
                    } else {
                        $numero = dps_automacao_normalizar_numero($lead['phonenumber']);
                        if ($numero === '') {
                            $detalhe = 'Número de telefone inválido: ' . $lead['phonenumber'];
                        } else {
                            $tentado = true;
                            list($ok, $detalhe) = dps_automacao_whatsapp_enviar_documento(
                                $assigned,
                                $numero,
                                $pdf_base64,
                                $proposta['original_name'],
                                $texto
                            );
                            // Pausa entre mensagens para não rebentar a Evolution.
                            usleep(300000);
                        }
                    }
                }
            } else { // email
                if (!filter_var((string) $lead['email'], FILTER_VALIDATE_EMAIL)) {
                    $detalhe = 'Email inválido: ' . $lead['email'];
                } else {
                    $tentado = true;
                    list($ok, $detalhe) = dps_automacao_enviar_email_proposta(
                        $lead['email'],
                        (string) $lead['name'],
                        'Proposta DPS',
                        $texto,
                        $caminho,
                        $proposta['original_name'],
                        // Assina quem trabalha a lead: o comercial escolhido,
                        // ou o responsável pela própria lead quando o admin
                        // dispara para "todos".
                        $comercial_id ?: (int) ($lead['assigned'] ?: get_staff_user_id())
                    );
                }
            }

            if ($ok || $tentado) {
                $this->dps_automacao_model->atualizar_envio(
                    $registo_id,
                    $ok,
                    'proposta:' . $proposta_id . ' ' . $detalhe
                );
            } else {
                // Pré-condição falhou sem qualquer envio tentado: apagar a
                // guarda para não queimar a lead nesta proposta para sempre.
                $this->dps_automacao_model->apagar_envio($registo_id);
                log_activity('DPS Automação: proposta ' . $proposta_id . ' não tentada para a lead '
                    . (int) $lead['id'] . ' — ' . $detalhe);
            }

            if ($ok) {
                $enviados++;
            } else {
                $falhados++;

                /*
                 * Limite do servidor de correio atingido (a Hostinger corta a
                 * conta ao fim de algumas centenas por hora): PARAR já. Sem
                 * isto o lote seguia em frente e marcava todas as leads
                 * restantes como falhadas, sem nada ser entregue. Como a
                 * guarda só bloqueia envios BEM SUCEDIDOS, as que ficaram por
                 * enviar voltam a entrar quando se repetir a campanha.
                 */
                if (dps_automacao_erro_de_limite($detalhe)) {
                    $this->responder_json([
                        'processados' => $enviados + $falhados,
                        'enviados'    => $enviados,
                        'falhados'    => $falhados,
                        'last_id'     => $last_id,
                        'fim'         => true,
                        'aviso'       => 'O servidor de correio atingiu o limite de envios por hora. '
                            . 'Foram entregues ' . $enviados . '. Espere cerca de uma hora e volte a '
                            . 'lançar o envio — os já entregues são saltados.',
                    ]);
                }
            }

            // Respirar entre emails: reduz a probabilidade de bater no limite.
            if ($canal === 'email') {
                usleep(400000);
            }
        }

        $this->responder_json([
            'processados' => count($leads),
            'enviados'    => $enviados,
            'falhados'    => $falhados,
            'last_id'     => $last_id,
            'fim'         => count($leads) < self::TAMANHO_LOTE,
        ]);
    }

    /* -----------------------------------------------------------------
     * Registo de envios
     * -------------------------------------------------------------- */

    public function envios()
    {
        $filtros = [
            'canal'    => $this->input->get('canal'),
            'staff_id' => $this->input->get('comercial_id'),
        ];

        // Comercial só vê os seus envios; admin vê tudo, com filtros.
        $so_do_staff = is_admin() ? null : (int) get_staff_user_id();

        $data['envios']     = $this->dps_automacao_model->get_envios($filtros, $so_do_staff);
        $data['comerciais'] = is_admin() ? $this->dps_automacao_model->get_comerciais() : [];
        $data['filtros']    = $filtros;
        $data['title']      = 'Registo de Envios';

        $this->load->view('envios_log', $data);
    }

    /* -----------------------------------------------------------------
     * Guiões da Sofia
     * -------------------------------------------------------------- */

    public function guioes()
    {
        if (is_admin()) {
            $data['guioes'] = $this->dps_automacao_guioes_model->get_guioes(false);

            $editar               = (int) $this->input->get('editar');
            $data['guiao_editar'] = $editar ? $this->dps_automacao_guioes_model->get_guiao($editar) : null;
        } else {
            $data['guioes']  = $this->dps_automacao_guioes_model->get_guioes(true);
            $data['escolha'] = $this->dps_automacao_guioes_model->get_escolha_do_staff(get_staff_user_id());
        }

        $data['title'] = 'Guiões Sofia';

        $this->load->view('guioes', $data);
    }

    public function guiao_guardar($id = null)
    {
        if (!is_admin()) {
            access_denied('dps_automacao');
        }

        if (!$this->input->post()) {
            show_404();
        }

        $nome = trim((string) $this->input->post('nome'));
        if ($nome === '') {
            set_alert('danger', 'O nome do guião é obrigatório.');
            redirect(admin_url('dps_automacao/guioes'));
        }

        $this->dps_automacao_guioes_model->guardar([
            'nome'       => $nome,
            'descricao'  => trim((string) $this->input->post('descricao')),
            'instrucoes' => trim((string) $this->input->post('instrucoes')),
            'ativo'      => $this->input->post('ativo') ? 1 : 0,
        ], $id ? (int) $id : null);

        set_alert('success', $id ? 'Guião atualizado.' : 'Guião criado.');
        redirect(admin_url('dps_automacao/guioes'));
    }

    public function guiao_apagar($id)
    {
        if (!is_admin()) {
            access_denied('dps_automacao');
        }

        // Nunca GET para ações destrutivas.
        if (!$this->input->post()) {
            show_404();
        }

        $guiao = $this->dps_automacao_guioes_model->get_guiao($id);
        if (!$guiao) {
            show_404();
        }

        $resultado = $this->dps_automacao_guioes_model->apagar($id);

        if ($resultado === 'desativado') {
            set_alert('warning', 'O guião tinha comerciais a usá-lo, por isso foi apenas desativado (as escolhas mantêm o histórico).');
        } else {
            set_alert('success', 'Guião apagado.');
        }

        redirect(admin_url('dps_automacao/guioes'));
    }

    public function guiao_escolher()
    {
        if (!$this->input->post()) {
            show_404();
        }

        $guiao_id = (int) $this->input->post('guiao_id');
        $guiao    = $this->dps_automacao_guioes_model->get_guiao($guiao_id);

        if (!$guiao || (int) $guiao['ativo'] !== 1) {
            set_alert('danger', 'Esse guião não existe ou já não está disponível.');
            redirect(admin_url('dps_automacao/guioes'));
        }

        // O staff_id gravado é SEMPRE o da sessão — nunca do POST.
        $this->dps_automacao_guioes_model->escolher($guiao_id, get_staff_user_id());

        // Mensagem estática: o nome do guião é texto livre e o alert_float do
        // Perfex injeta o flashdata sem escape num contexto JS.
        set_alert('success', 'A Sofia vai usar o guião escolhido nas suas leads.');
        redirect(admin_url('dps_automacao/guioes'));
    }

    /* -----------------------------------------------------------------
     * Definições (só admin)
     * -------------------------------------------------------------- */

    public function definicoes()
    {
        if (!is_admin()) {
            access_denied('dps_automacao');
        }

        $data['automacao_ativa']  = get_option('dps_automacao_ativo') === '1';
        $data['followups_ativos'] = get_option('dps_automacao_followups_ativo') === '1';
        $data['msg_7']            = get_option('dps_automacao_msg_followup_7');
        $data['msg_15']           = get_option('dps_automacao_msg_followup_15');
        $data['msg_30']           = get_option('dps_automacao_msg_followup_30');
        $data['title']            = 'Definições — Automação';

        $this->load->view('definicoes', $data);
    }

    public function definicoes_guardar()
    {
        if (!is_admin()) {
            access_denied('dps_automacao');
        }

        if (!$this->input->post()) {
            show_404();
        }

        update_option('dps_automacao_ativo', $this->input->post('dps_automacao_ativo') ? '1' : '0');
        update_option('dps_automacao_followups_ativo', $this->input->post('dps_automacao_followups_ativo') ? '1' : '0');

        foreach ([7, 15, 30] as $marco) {
            update_option('dps_automacao_msg_followup_' . $marco, (string) $this->input->post('msg_followup_' . $marco));
        }

        set_alert('success', 'Definições guardadas.');
        redirect(admin_url('dps_automacao/definicoes'));
    }

    /* -----------------------------------------------------------------
     * Privados
     * -------------------------------------------------------------- */

    /** Canal validado contra a whitelist; null se inválido. */
    private function canal_do_post()
    {
        $canal = (string) $this->input->post('canal');

        return in_array($canal, ['whatsapp', 'email', 'sms'], true) ? $canal : null;
    }

    /**
     * Canal da proposta em massa — SEM sms (não há anexos por SMS). O POST
     * pode trazer qualquer coisa; null se inválido.
     */
    private function canal_proposta_do_post()
    {
        $canal = (string) $this->input->post('canal');

        return in_array($canal, ['whatsapp', 'email'], true) ? $canal : null;
    }

    /**
     * A proposta escolhida no POST, já com a fronteira entre comerciais
     * imposta: não-admin só usa propostas suas. Responde com erro JSON (e
     * termina) se não existir ou não for dele.
     */
    private function proposta_do_post()
    {
        $proposta_id = (int) $this->input->post('proposta_id');

        $proposta = $proposta_id > 0
            ? $this->dps_automacao_model->get_proposta($proposta_id)
            : null;

        if (!$proposta) {
            $this->responder_json(['erro' => 'Escolha (ou carregue) a proposta em PDF antes de enviar.']);
        }

        if (!is_admin() && (int) $proposta['staff_id'] !== (int) get_staff_user_id()) {
            $this->responder_json(['erro' => 'Essa proposta não é sua.']);
        }

        return $proposta;
    }

    /**
     * Caminho do PDF no disco, verificado. Responde com erro JSON (e termina)
     * se o ficheiro tiver desaparecido — enviar sem anexo seria pior.
     */
    private function caminho_da_proposta(array $proposta)
    {
        $dir     = dps_automacao_propostas_dir();
        $caminho = $dir !== false ? $dir . basename((string) $proposta['filename']) : '';

        if ($caminho === '' || !is_file($caminho)) {
            $this->responder_json(['erro' => 'O ficheiro desta proposta já não existe no servidor — carregue-o de novo.']);
        }

        return $caminho;
    }

    /** Estados escolhidos, reduzidos a inteiros positivos. */
    private function estados_do_post()
    {
        return array_values(array_filter(array_map('intval', (array) $this->input->post('estados'))));
    }

    /**
     * O comercial cujas leads são o alvo. Para não-admin é SEMPRE o próprio
     * (o POST é ignorado); o admin pode escolher um comercial ou todas (null).
     */
    private function comercial_do_post()
    {
        if (!is_admin()) {
            return (int) get_staff_user_id();
        }

        $escolhido = (int) $this->input->post('comercial_id');

        return $escolhido > 0 ? $escolhido : null;
    }

    /** Assunto do email, com fallback sensato — só usado no canal email. */
    private function assunto_do_post()
    {
        $assunto = trim((string) $this->input->post('assunto'));

        return $assunto !== '' ? $assunto : 'Mensagem de ' . (get_option('companyname') ?: 'Grupo DPS');
    }

    /**
     * Resposta JSON dos endpoints AJAX. Devolve sempre o token CSRF fresco:
     * com csrf_regenerate ativo, o POST seguinte da sequência precisa dele.
     */
    private function responder_json(array $payload)
    {
        $payload['csrf'] = [
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash(),
        ];

        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    /* =====================================================================
     * ENVIO EM MASSA POR ESTADO DE TAREFA
     * ================================================================== */

    public function envio_massa_tarefa()
    {
        $data['estados']    = $this->dps_automacao_model->get_estados_tarefa();
        // Só quem tem tarefas: um selector com nomes sem tarefa nenhuma não
        // ajuda a escolher, atrapalha.
        $data['comerciais'] = is_admin() ? $this->dps_automacao_model->get_comerciais_com_tarefas() : [];
        $data['title']      = 'Envio Massa Tarefa';

        $this->load->view('envio_massa_tarefa', $data);
    }

    /** POST (AJAX) — contagens antes de enviar. Nada sai daqui. */
    public function envio_massa_tarefa_preview()
    {
        if (!$this->input->post()) {
            show_404();
        }

        $estados = $this->estados_do_post();
        if (empty($estados)) {
            $this->responder_json(['erro' => 'Escolha pelo menos um estado de tarefa.']);
        }

        $linhas = $this->dps_automacao_model->contar_tarefas($estados, $this->comercial_do_post());

        $nomes = [];
        foreach ($this->dps_automacao_model->get_estados_tarefa() as $e) {
            $nomes[(int) $e['id']] = $e['name'];
        }

        $total = $com = 0;
        foreach ($linhas as &$l) {
            $l['estado_nome'] = $nomes[(int) $l['estado_id']] ?? ('Estado ' . $l['estado_id']);
            $total += (int) $l['total'];
            $com   += (int) $l['com_contacto'];
        }
        unset($l);

        $this->responder_json([
            'estados'      => $linhas,
            'total'        => $total,
            'com_contacto' => $com,
            'excluidas'    => $total - $com,
        ]);
    }

    /**
     * POST — envia mesmo.
     *
     * Só email: uma tarefa não tem telefone próprio e mandar WhatsApp a partir
     * daqui repetia o erro das leads novas — uma mensagem de um número que a
     * pessoa não contactou.
     */
    public function envio_massa_tarefa_enviar()
    {
        if (!$this->input->post()) {
            show_404();
        }

        $estados = $this->estados_do_post();
        if (empty($estados)) {
            $this->responder_json(['erro' => 'Escolha pelo menos um estado de tarefa.']);
        }

        $mensagem = trim((string) $this->input->post('mensagem', false));
        $assunto  = trim((string) $this->input->post('assunto', false));

        if ($mensagem === '' || $assunto === '') {
            $this->responder_json(['erro' => 'O assunto e a mensagem não podem ficar vazios.']);
        }

        /*
         * ANEXO — opcional, um só ficheiro, guardado uma vez e reaproveitado
         * em todos os emails do lote. Guardar por destinatário seria escrever
         * o mesmo ficheiro centenas de vezes no disco.
         *
         * Só se aceitam os formatos que fazem sentido mandar a um cliente. Um
         * envio em massa é o pior sítio possível para deixar passar um
         * executável: sai para centenas de caixas de uma vez.
         */
        $anexo = null;
        $anexo_nome = '';

        if (!empty($_FILES['anexo']['name']) && (int) ($_FILES['anexo']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            $ext = strtolower(pathinfo((string) $_FILES['anexo']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $permitidas, true)) {
                $this->responder_json(['erro' => 'Formato não permitido (' . $ext . '). Aceita-se '
                    . implode(', ', $permitidas) . '.']);
            }
            if ((int) $_FILES['anexo']['size'] > 10 * 1024 * 1024) {
                $this->responder_json(['erro' => 'O anexo passa dos 10 MB. Muitos servidores de email '
                    . 'recusam anexos grandes e o lote inteiro falharia.']);
            }

            $pasta = FCPATH . 'uploads/dps_automacao_anexos/';
            if (!is_dir($pasta)) {
                mkdir($pasta, 0755, true);
            }

            $anexo_nome = preg_replace('/[^\w .\-]+/u', '', (string) $_FILES['anexo']['name']);
            $anexo      = $pasta . uniqid('anexo_', true) . '.' . $ext;

            if (!move_uploaded_file($_FILES['anexo']['tmp_name'], $anexo)) {
                $this->responder_json(['erro' => 'Não foi possível guardar o anexo.']);
            }
        }

        $destinos = $this->dps_automacao_model->get_tarefas_para_envio(
            $estados,
            $this->comercial_do_post(),
            5000
        );

        /*
         * TECTO DE 80 POR ENVIO — é o máximo que o fornecedor de email aceita.
         *
         * O que passa disso não se perde nem se manda à força: fica em fila,
         * agendado de 24 em 24 horas, 80 por dia, até acabar. Mandar tudo e ver
         * o que passa queima a reputação da caixa e faz o fornecedor recusar o
         * lote inteiro, não só o excedente.
         *
         * Quem envia é sempre o utilizador com sessão aberta: é a caixa dele
         * que sai no remetente e o WhatsApp dele que vai no botão do email.
         */
        $por_lote = 80;
        $eu       = (int) get_staff_user_id();

        // Um só identificador para o envio inteiro: os que saem hoje e os que
        // ficam para amanhã pertencem ao mesmo lote e têm de aparecer juntos
        // no registo.
        $lote = uniqid('lote', false);

        $agora   = array_slice($destinos, 0, $por_lote);
        $adiados = array_slice($destinos, $por_lote);

        $agendados = 0;
        if (!empty($adiados)) {
            $agendados = $this->dps_automacao_model->agendar_envio_tarefa(
                $adiados, $eu, $assunto, $mensagem, $anexo, $anexo_nome, $por_lote, $lote
            );
        }

        $enviados = 0;
        $falhas   = [];

        foreach ($agora as $d) {
            $nome_com = get_staff_full_name($eu) ?: (get_option('companyname') ?: 'A nossa equipa');
            $texto    = dps_automacao_render_vars($mensagem, (string) $d['name'], $nome_com);

            /*
             * O email sai pela caixa do comercial da tarefa, como no envio das
             * leads: o cliente responde a quem o acompanha, não a uma caixa
             * geral que ninguém lê.
             */
            if ($anexo !== null) {
                $ok = dps_automacao_enviar_email_proposta(
                    $d['email'],
                    (string) $d['name'],
                    $assunto,
                    $texto,
                    $anexo,
                    $anexo_nome,
                    $eu
                );
            } else {
                $ok = dps_automacao_enviar_email_lead(
                    $d['email'],
                    $assunto,
                    nl2br(html_escape($texto)),
                    $eu
                );
            }

            if ($ok) {
                $enviados++;
            } else {
                $falhas[] = $d['email'];
            }

            // Fica registo de cada um, tenha saído ou não.
            $this->dps_automacao_model->registar_envio_tarefa(
                $lote, $eu, $d['email'], $d['name'], $assunto, $mensagem,
                $anexo_nome, $ok, $ok ? '' : 'o servidor de email recusou'
            );

            // Pausa curta: um lote grande contra o SMTP sem respirar é a
            // maneira mais rápida de ser tratado como spam.
            usleep(200000);
        }

        /*
         * O anexo só se apaga quando não fica nada agendado. Com lotes por
         * enviar amanhã, apagá-lo hoje deixava-os a seguir sem o ficheiro —
         * quem o apaga é o cron, no fim do último lote.
         */
        if ($anexo !== null && $agendados === 0 && is_file($anexo)) {
            @unlink($anexo);
        }

        $this->responder_json([
            'enviados' => $enviados,
            'falhas'   => count($falhas),
            'exemplos' => array_slice($falhas, 0, 5),
            'total'     => count($destinos),
            'anexo'     => $anexo_nome,
            'agendados' => $agendados,
            'por_lote'  => $por_lote,
        ]);
    }

    /**
     * Registo dos envios em massa por tarefa: a quem foi, quem recebeu, quem
     * falhou.
     *
     * Um comercial vê os seus; a direção vê os de todos. Não se filtra pelo
     * que ele "enviou como" — filtra-se por quem carregou no botão, que é
     * quem responde pelo envio.
     */
    public function registo_envio_tarefa($lote = '')
    {
        $so_meus = is_admin() ? null : (int) get_staff_user_id();

        if ($lote !== '') {
            $linhas = $this->dps_automacao_model->detalhe_envio_tarefa($lote);

            // Um comercial não abre o lote de outro escrevendo o endereço.
            if ($so_meus !== null) {
                foreach ($linhas as $l) {
                    if ((int) $l['staff_id'] !== $so_meus) {
                        access_denied('dps_automacao');
                    }
                }
            }
            $data['linhas'] = $linhas;
            $data['lote']   = $lote;
        } else {
            $data['lotes'] = $this->dps_automacao_model->registo_envios_tarefa($so_meus);
            $data['lote']  = '';
        }

        $data['title'] = 'Registo — Envio Massa Tarefa';
        $this->load->view('registo_envio_tarefa', $data);
    }

    /**
     * Converte uma tarefa numa lead.
     *
     * As tarefas da Sofia nascem de uma chamada e trazem a ficha da pessoa
     * escrita no corpo, mas não ficam ligadas a lead nenhuma — ficam num
     * limbo, fora dos funis, dos filtros e das automações. Este botão passa-as
     * para lá.
     *
     * Regras:
     * - Só converte quem já podia abrir a tarefa. A tarefa é carregada com o
     *   mesmo filtro de visibilidade que o CRM usa em todo o lado; se não
     *   aparecer, não se converte.
     * - Uma tarefa que JÁ veio de uma lead não se converte outra vez. Sem
     *   isto, cada carregar no botão criava uma lead duplicada da mesma
     *   pessoa.
     * - A lead é criada pelo modelo do Perfex, não com um INSERT à mão: é o
     *   que garante o registo de atividade, os campos personalizados, o aviso
     *   ao comercial e o hook lead_created.
     * - No fim a tarefa fica ligada à lead nova, para o caminho de volta
     *   existir e para o botão não voltar a aparecer.
     */
    public function converter_em_lead($task_id = '')
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $task_id = (int) $task_id;
        $this->load->model('tasks_model');
        $this->load->model('leads_model');

        // O mesmo filtro que o CRM aplica em todo o lado. Admin vê tudo.
        $tarefa = $this->tasks_model->get(
            $task_id,
            is_admin() ? [] : get_tasks_where_string(false)
        );

        if (!$tarefa) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Tarefa não encontrada ou sem acesso.']);

            return;
        }

        if ($tarefa->rel_type === 'lead' && (int) $tarefa->rel_id > 0) {
            echo json_encode([
                'sucesso'  => false,
                'mensagem' => 'Esta tarefa já pertence a uma lead.',
                'url'      => admin_url('leads/index/' . (int) $tarefa->rel_id),
            ]);

            return;
        }

        $pessoa = dps_automacao_pessoa_da_tarefa($tarefa);

        /*
         * A lead fica com quem já estava a tratar da tarefa. Só se ninguém
         * estiver atribuído é que fica com quem carregou no botão — atribuir
         * ao próprio o trabalho de outro seria roubar-lhe a lead.
         */
        $atribuidos = $this->tasks_model->get_task_assignees($task_id);
        $dono       = !empty($atribuidos) ? (int) $atribuidos[0]['staffid'] : (int) get_staff_user_id();

        $descricao = 'Convertida da tarefa #' . $task_id . ' — ' . $tarefa->name;
        if ($pessoa['empreendimento'] !== '') {
            $descricao .= "\nEmpreendimento: " . $pessoa['empreendimento'];
        }
        $descricao .= "\n\n" . trim(html_entity_decode(
            strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", (string) $tarefa->description)),
            ENT_QUOTES,
            'UTF-8'
        ));

        $lead_id = $this->leads_model->add([
            'name'        => $pessoa['nome'],
            'email'       => $pessoa['email'],
            'phonenumber' => $pessoa['telefone'],
            'description' => $descricao,
            'address'     => '',
            'status'      => (int) get_option('leads_default_status') ?: $this->dps_automacao_primeiro_estado_lead(),
            'source'      => $this->dps_automacao_primeira_fonte_lead(),
            'assigned'    => $dono,
            'is_public'   => 0,
            'lastcontact' => null,
        ]);

        if (!$lead_id) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível criar a lead.']);

            return;
        }

        $this->db->where('id', $task_id)->update(db_prefix() . 'tasks', [
            'rel_type' => 'lead',
            'rel_id'   => $lead_id,
        ]);

        log_activity('Tarefa #' . $task_id . ' convertida na lead #' . $lead_id);

        echo json_encode([
            'sucesso'  => true,
            'mensagem' => 'Lead criada a partir da tarefa.',
            'url'      => admin_url('leads/index/' . $lead_id),
        ]);
    }

    /** Primeiro estado da lista de estados de lead, pela ordem definida no CRM. */
    private function dps_automacao_primeiro_estado_lead()
    {
        $l = $this->db->select('id')->order_by('statusorder', 'asc')
                      ->limit(1)->get(db_prefix() . 'leads_status')->row();

        return $l ? (int) $l->id : 1;
    }

    /** Primeira fonte de lead. O comercial corrige na ficha se for outra. */
    private function dps_automacao_primeira_fonte_lead()
    {
        $l = $this->db->select('id')->order_by('id', 'asc')
                      ->limit(1)->get(db_prefix() . 'leads_sources')->row();

        return $l ? (int) $l->id : 1;
    }
}
