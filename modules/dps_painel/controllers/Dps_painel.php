<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_painel extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        /*
         * Módulo privado: só o Ricardo (staff 1) entra.
         *
         * NÃO se usa access_denied(): essa função redirecciona para
         * admin/access_denied, rota que o Perfex não tem, e o utilizador
         * acabava na página 404 do skater sem perceber porquê — foi o que
         * aconteceu a 29/07/2026. Aqui diz-se com que conta está a entrar,
         * que é a informação que resolve o problema em dois segundos.
         */
        if (!dps_painel_pode_entrar()) {
            $id   = function_exists('get_staff_user_id') ? (int) get_staff_user_id() : 0;
            $nome = function_exists('get_staff_full_name') ? get_staff_full_name($id) : '';

            log_activity('Painel do Negócio: acesso recusado a staff ' . $id);

            echo '<div style="font-family:system-ui,sans-serif;max-width:640px;margin:80px auto;'
                . 'padding:28px 32px;border:1px solid #e5e7eb;border-radius:10px;line-height:1.6;">'
                . '<h2 style="margin:0 0 12px;">Painel do Negócio &mdash; privado</h2>'
                . '<p>Este painel mostra o que a DPS recebe por venda e <strong>abre apenas a quem tem acesso</strong>. '
                . 'Os restantes administradores não lhe chegam, de propósito.</p>'
                . '<p style="background:#f9fafb;padding:12px 14px;border-radius:6px;">'
                . 'Está a entrar como <strong>' . html_escape($nome ?: 'desconhecido') . '</strong> '
                . '(ID ' . $id . ').</p>'
                . '<p>Se é o Ricardo, saia e volte a entrar com a sua conta.</p>'
                . '<p><a href="' . admin_url() . '">&larr; Voltar ao CRM</a></p>'
                . '</div>';
            exit;
        }

        /*
         * Diagnóstico: este painel falhava a branco/404 sem deixar rasto —
         * o CRM tem o registo de erros desligado em produção. Como só o dono
         * chega aqui, mostra-se-lhe o erro em vez de o esconder. Não há fuga
         * de informação: mais ninguém passa do gate acima.
         */
        register_shutdown_function(function () {
            $e = error_get_last();
            $fatais = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

            if ($e && in_array($e['type'], $fatais, true)) {
                echo '<pre style="white-space:pre-wrap;background:#fee;border:1px solid #f99;'
                    . 'padding:14px;margin:14px;font:13px/1.5 ui-monospace,monospace;">'
                    . "ERRO FATAL NO PAINEL DO NEGOCIO\n\n"
                    . html_escape($e['message']) . "\n\n"
                    . html_escape($e['file']) . ':' . (int) $e['line']
                    . '</pre>';
            }
        });

        $this->load->model('dps_painel_model', 'm');
    }

    /**
     * Corre o miolo de uma página e, se rebentar, mostra o erro ao dono em vez
     * de devolver uma página em branco.
     */
    private function correr(callable $accao)
    {
        try {
            $accao();
        } catch (\Throwable $e) {
            echo '<pre style="white-space:pre-wrap;background:#fee;border:1px solid #f99;'
                . 'padding:14px;margin:14px;font:13px/1.5 ui-monospace,monospace;">'
                . "ERRO NO PAINEL DO NEGOCIO\n\n"
                . html_escape(get_class($e)) . ': ' . html_escape($e->getMessage()) . "\n\n"
                . html_escape($e->getFile()) . ':' . (int) $e->getLine() . "\n\n"
                . html_escape($e->getTraceAsString())
                . '</pre>';
        }
    }

    /**
     * Diagnóstico passo a passo: corre o mesmo que o index(), um bloco de cada
     * vez, com tempos, e diz onde parte. Texto simples, sem tema nem vista, para
     * que uma falha no layout não esconda o resultado.
     *
     * Existe porque o painel falhava sem deixar rasto e eu andei a adivinhar.
     * Pode ser apagado quando isto estiver estável.
     */
    public function diag()
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        header('Content-Type: text/plain; charset=utf-8');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        echo "DIAGNÓSTICO DO PAINEL DO NEGÓCIO\n";
        echo 'staff: ' . (int) get_staff_user_id() . "   PHP " . PHP_VERSION . "\n";
        echo str_repeat('-', 58) . "\n";

        $passo = function ($nome, callable $f) {
            $i = microtime(true);
            try {
                $r = $f();
                printf("OK      %-26s %6.0f ms\n", $nome, (microtime(true) - $i) * 1000);
                return $r;
            } catch (\Throwable $e) {
                printf("FALHOU  %-26s\n\n  %s: %s\n  %s:%d\n\n%s\n",
                    $nome, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
                exit;
            }
        };

        $filtros  = ['ano' => null, 'mes' => null, 'mes_recebido' => null, 'so_recebidas' => null,
                     'comercial' => null, 'empreendimento' => null, 'estado' => null];
        $vendas   = $passo('get_vendas', function () use ($filtros) { return $this->m->get_vendas($filtros); });
        echo '        (' . count($vendas) . " vendas)\n";
        $despesas = $passo('get_despesas',   function () { return $this->m->get_despesas([]); });
        $totais   = $passo('totais',         function () use ($vendas, $despesas) { return $this->m->totais($vendas, $despesas); });
        $resumo   = $passo('resumo_por_emp', function () use ($vendas) { return $this->m->resumo_por_empreendimento($vendas); });
        $regras   = $passo('regras_config',  function () { return $this->m->regras_config(); });
        $opcoes   = $passo('opcoes_filtros', function () { return $this->m->opcoes_filtros(); });
        $moloni   = $passo('moloni_config',  function () { return $this->m->moloni_config(); });

        $passo('render da vista', function () use ($vendas, $despesas, $totais, $resumo, $regras, $filtros, $opcoes, $moloni) {
            $data = compact('vendas', 'despesas', 'totais', 'resumo', 'regras', 'filtros', 'opcoes', 'moloni');
            $data['title'] = 'Painel do Negócio';
            // Render para string: se a vista rebentar, apanha-se aqui.
            $html = $this->load->view('manage', $data, true);
            echo '        (' . strlen($html) . " bytes de HTML)\n";
            return true;
        });

        echo str_repeat('-', 58) . "\n";
        echo "TUDO OK — o painel consegue construir a página.\n";
        echo "Se o /admin/dps_painel mesmo assim não abre, o problema está no\n";
        echo "tema/layout do CRM e não neste módulo.\n";
    }

    public function index()
    {
        $this->correr(function () {
        /*
         * PERÍODO — por omissão, os últimos 3 meses.
         *
         * Antes o painel abria com tudo desde sempre, e a primeira coisa que
         * qualquer pessoa via era um total de anos misturados que não servia
         * para decidir nada. Agora abre no que está a acontecer, e quem quiser
         * o ano ou um mês escolhe-o. "Tudo" continua a existir para quem
         * precisar do acumulado.
         *
         * O valor viaja no endereço em três formas: '3m', 'ano:AAAA' e
         * 'mes:AAAA-MM'. Um endereço antigo com ?ano=&mes= continua a
         * funcionar — é lido como filtro explícito e não leva o período.
         */
        $periodo = (string) $this->input->get('periodo');
        $f_ano   = $this->input->get('ano');
        $f_mes   = $this->input->get('mes');
        $de      = null;
        $ate     = null;

        if ($periodo === '' && $f_ano === null && $f_mes === null) {
            $periodo = '3m';   // primeira visita: os últimos 3 meses
        }

        if (preg_match('/^ano:(\d{4})$/', $periodo, $m)) {
            $f_ano = $m[1];
            $f_mes = null;
        } elseif (preg_match('/^mes:(\d{4})-(\d{2})$/', $periodo, $m)) {
            $f_ano = $m[1];
            $f_mes = (int) $m[2];
        } elseif ($periodo === '3m') {
            $de  = date('Y-m-01', strtotime('-2 months'));
            $ate = date('Y-m-t');
        }

        $filtros = [
            'periodo'        => $periodo,
            'de'             => $de,
            'ate'            => $ate,
            'ano'            => $f_ano,
            'mes'            => $f_mes,
            // Mês em que a DPS RECEBEU (formato 'AAAA-MM'). Diferente de
            // ano/mês, que filtram pela data da venda.
            'mes_recebido'   => $this->input->get('mes_recebido'),
            'so_recebidas'   => $this->input->get('so_recebidas'),
            'comercial'      => $this->input->get('comercial'),
            'empreendimento' => $this->input->get('empreendimento'),
            'estado'         => $this->input->get('estado'),
        ];

        $vendas   = $this->m->get_vendas($filtros);

        /*
         * As despesas têm mês PRÓPRIO, independente do filtro das vendas.
         *
         * Partilhavam o filtro da página, o que dava um absurdo: quem
         * filtrasse as vendas por um empreendimento via as despesas do mês
         * todo na mesma, e quem limpasse o filtro via a soma de sempre. A
         * despesa é do mês em que aconteceu — e por omissão é o mês corrente,
         * que vira sozinho quando o mês vira.
         */
        $mes_desp = $this->input->get('despesas_mes');
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $mes_desp)) {
            $mes_desp = date('Y-m');
        }
        list($d_ano, $d_mes) = explode('-', $mes_desp);
        $despesas = $this->m->get_despesas(['ano' => $d_ano, 'mes' => $d_mes]);

        $data['despesas_mes']    = $mes_desp;
        $data['despesas_meses']  = $this->m->meses_com_despesas();
        $data['despesas_totais'] = $this->m->totais_despesas_por_categoria($despesas);

        $data['vendas']   = $vendas;
        $data['despesas'] = $despesas;
        $data['totais']   = $this->m->totais($vendas, $despesas);
        $data['resumo']   = $this->m->resumo_por_empreendimento($vendas);
        $data['regras']   = $this->m->regras_config();
        $data['filtros']  = $filtros;
        $data['opcoes']   = $this->m->opcoes_filtros();
        $data['moloni']   = $this->m->moloni_config();
        $data['so_o_que_sai'] = !dps_painel_is_owner();
        $data['title']    = 'Painel do Negócio';

        $this->load->view('manage', $data);
        });
    }

    public function guardar_venda($venda_id)
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        // Escritas só por POST: um GET com efeitos abre a porta a que um link
        // qualquer altere números do painel.
        if (!$this->input->post()) {
            redirect(admin_url('dps_painel'));
        }

        $this->m->save_overlay($venda_id, $this->input->post());
        set_alert('success', 'Venda #' . (int) $venda_id . ' atualizada.');

        redirect($this->url_com_filtros());
    }

    /**
     * URL do painel com os filtros que estavam aplicados.
     *
     * Reconstrói-se a partir de campos escondidos do próprio formulário, com
     * uma lista fechada de chaves — antes usava-se $_SERVER['HTTP_REFERER'],
     * que é um cabeçalho controlado pelo cliente e dava um redirect aberto.
     */
    private function url_com_filtros()
    {
        $q = [];
        foreach (['ano', 'mes', 'comercial', 'empreendimento', 'estado'] as $k) {
            $v = $this->input->post('f_' . $k);
            if ($v !== null && $v !== '') {
                $q[$k] = $v;
            }
        }

        return admin_url('dps_painel') . (empty($q) ? '' : '?' . http_build_query($q));
    }

    /* ---------------------------------------------------------------------
     * O que a DPS recebe por empreendimento.
     *
     * Vive dentro do painel privado, e não nas Regras de Comissão do
     * dps_vendas: aquela página é visível aos comerciais e não pode mostrar
     * o que a casa recebe do promotor.
     * ------------------------------------------------------------------ */

    public function recebimento()
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        if ($this->input->post()) {
            $post = $this->input->post();
            $id   = !empty($post['id']) ? (int) $post['id'] : null;
            unset($post['id']);

            $erro = $this->m->validar_recebimento($post);
            if ($erro !== '') {
                set_alert('danger', $erro);
            } else {
                $this->m->guardar_recebimento($post, $id);

                /*
                 * Os prazos vão para a tabela das Regras de Comissão, que é
                 * onde vivem. Editam-se daqui por comodidade, mas guardam-se
                 * num sítio só — dois sítios a dizer datas diferentes para o
                 * mesmo empreendimento seria pior do que não as ter.
                 */
                /*
                 * O mês e o ano vêm em selectores separados — o <input type="month">
                 * não existe no Safari. Juntam-se aqui em AAAA-MM, que é o formato
                 * guardado. Só um dos dois preenchido não é uma data: fica vazio, e
                 * vazio quer dizer "na conclusão".
                 */
                $juntar = function ($campo) use ($post) {
                    $m = trim((string) ($post[$campo . '_mes'] ?? ''));
                    $a = trim((string) ($post[$campo . '_ano'] ?? ''));

                    return ($m !== '' && $a !== '') ? $a . '-' . str_pad($m, 2, '0', STR_PAD_LEFT) : '';
                };

                $this->m->guardar_prazos(
                    $post['empreendimento'] ?? '',
                    $juntar('mes_cpcv'),
                    $juntar('mes_escritura')
                );

                set_alert('success', 'Comissão a receber e prazos guardados.');
            }

            redirect(admin_url('dps_painel/recebimento'));
        }

        // Traz para a lista os empreendimentos que já têm vendas, a 0%.
        $this->m->sincronizar_recebimento_com_vendas();

        $data['recebimentos']    = $this->m->get_recebimentos();
        $data['empreendimentos'] = $this->m->opcoes_filtros()['emps'];
        $data['title']           = 'Comissões a receber';

        $this->load->view('recebimento', $data);
    }

    public function recebimento_delete()
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        // Apagar é destrutivo: só por POST (com o token do form_open).
        if (!$this->input->post()) {
            redirect(admin_url('dps_painel/recebimento'));
        }

        $this->m->delete_recebimento($this->input->post('id'));
        set_alert('success', 'Linha eliminada.');

        redirect(admin_url('dps_painel/recebimento'));
    }

    public function despesa_add()
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        if ($this->input->post()) {
            $doc  = $this->upload_despesa_doc();
            $post = $this->input->post();
            if ($doc) {
                $post['doc'] = $doc;
            }
            $this->m->add_despesa($post);
            set_alert('success', 'Despesa lançada.');
        }
        redirect(admin_url('dps_painel'));
    }

    public function despesa_delete($id)
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        // Idem: destruição só por POST.
        if (!$this->input->post()) {
            redirect(admin_url('dps_painel'));
        }

        $d = $this->m->get_despesa($id);
        if ($d) {
            if (!empty($d['doc'])) {
                @unlink(FCPATH . DPS_PAINEL_UPLOAD . $d['doc']);
            }
            $this->m->delete_despesa($id);
            set_alert('success', 'Despesa eliminada.');
        }
        redirect(admin_url('dps_painel'));
    }

    public function despesa_doc($id)
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        $d = $this->m->get_despesa($id);
        if (!$d || empty($d['doc'])) {
            show_404();
        }
        $caminho = FCPATH . DPS_PAINEL_UPLOAD . $d['doc'];
        if (!file_exists($caminho)) {
            show_404();
        }
        $this->load->helper('download');
        force_download($d['doc'], file_get_contents($caminho));
    }

    /* ----- Moloni ----- */

    public function definicoes()
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        if ($this->input->post()) {
            // Dois formulários na mesma página; o campo escondido 'bloco' diz
            // qual deles submeteu, para não gravarmos Moloni com um POST de
            // regras (e apagar credenciais com campos ausentes).
            if ($this->input->post('bloco') === 'regras') {
                $this->m->regras_save_config($this->input->post());
                set_alert('success', 'Regras do negócio guardadas.');
            } else {
                $this->m->moloni_save_config($this->input->post());
                set_alert('success', 'Definições Moloni guardadas.');
            }

            redirect(admin_url('dps_painel/definicoes'));
        }

        $data['moloni'] = $this->m->moloni_config();
        $data['regras'] = $this->m->regras_config();
        $data['staff']  = $this->m->staff_ativo();
        $data['title']  = 'Definições do Painel';
        $this->load->view('definicoes', $data);
    }

    public function moloni_testar()
    {
        /*
         * Só o dono. Quem só vê "O que sai" entra no módulo mas não passa
         * daqui: estas acções mexem no que a DPS recebe, nas despesas e nas
         * credenciais do Moloni.
         */
        $this->so_o_dono();

        $r = $this->m->moloni_test();
        if ($r['ok']) {
            $lista = implode(', ', array_map(function ($e) {
                return $e['nome'] . ' (#' . $e['id'] . ')';
            }, $r['empresas']));
            set_alert('success', 'Ligação Moloni OK. Empresas: ' . $lista);
        } else {
            set_alert('danger', 'Moloni: ' . $r['error']);
        }
        redirect(admin_url('dps_painel/definicoes'));
    }

    private function upload_despesa_doc()
    {
        if (empty($_FILES['doc']['name'])) {
            return null;
        }
        // Blindar a pasta ANTES de lá pousar seja o que for.
        $destino = dps_painel_pasta_uploads();

        $ext = strtolower(pathinfo($_FILES['doc']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            set_alert('warning', 'Documento da despesa ignorado (só PDF/JPG/PNG).');
            return null;
        }
        $nome = 'despesa_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['doc']['tmp_name'], $destino . $nome)) {
            return $nome;
        }

        return null;
    }

    /**
     * Corta a acção a quem não é o dono.
     *
     * A Samara e o Cláudio entram no painel para ver "O que sai" — o que se
     * paga aos comerciais e à direcção. Tudo o resto (o que a DPS recebe, as
     * despesas, o Moloni) continua fechado. Sem esta guarda, bastava-lhes
     * escrever o endereço da acção à mão para lá chegarem: esconder um botão
     * não é proteger nada.
     */
    private function so_o_dono()
    {
        if (dps_painel_is_owner()) {
            return;
        }

        log_activity('Painel do Negócio: acção reservada ao dono, recusada a staff '
            . (int) get_staff_user_id());

        echo '<div style="font-family:system-ui,sans-serif;max-width:560px;margin:80px auto;'
            . 'padding:28px 32px;border:1px solid #e5e7eb;border-radius:10px;line-height:1.6;">'
            . '<h3 style="margin:0 0 12px;">Esta parte é do dono</h3>'
            . '<p>Vê o quadro <strong>O que sai</strong> — o que se paga aos comerciais e à '
            . 'direcção. O resto do Painel do Negócio não abre nesta conta.</p>'
            . '<p><a href="' . admin_url('dps_painel') . '">&larr; Voltar ao painel</a></p>'
            . '</div>';
        exit;
    }

    /**
     * Todas as despesas de um mês num PDF, uma fatura por página.
     *
     * As faturas chegam em foto e em PDF. O TCPDF coloca imagens mas não sabe
     * importar páginas de PDFs já feitos — por isso as que vierem em PDF são
     * convertidas página a página pelo Imagick, que existe neste servidor e lê
     * PDF. Um PDF de três páginas dá três páginas aqui.
     *
     * Cada página leva, no topo, os dados do lançamento: sem isso o contabilista
     * recebia um maço de imagens sem saber a que respeitam.
     */
    public function despesas_pdf($mes = '')
    {
        $this->so_o_dono();

        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        list($ano, $m) = explode('-', $mes);

        $despesas = $this->m->get_despesas(['ano' => $ano, 'mes' => $m]);
        if (empty($despesas)) {
            set_alert('warning', 'Não há despesas lançadas nesse mês.');
            redirect(admin_url('dps_painel?despesas_mes=' . $mes));
        }

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('DPS CRM');
        $pdf->SetTitle('Despesas ' . $mes);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(false);

        $moeda = get_base_currency();
        $total = 0;

        /* ---- Primeira página: o resumo ---- */
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Despesas — ' . dps_painel_mes_extenso($mes), 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(2);

        $por_cat = [];
        foreach ($despesas as $d) {
            $c = trim((string) $d['categoria']) ?: 'Outros';
            $por_cat[$c] = ($por_cat[$c] ?? 0) + (float) $d['valor'];
            $total += (float) $d['valor'];
        }

        $html = '<table border="0" cellpadding="4"><tr style="background-color:#f0f0f0;">'
              . '<th width="55%"><b>Categoria</b></th><th width="45%" align="right"><b>Total</b></th></tr>';
        foreach ($por_cat as $cat => $v) {
            $html .= '<tr><td>' . htmlspecialchars($cat) . '</td><td align="right">'
                   . number_format($v, 2, ',', '.') . ' EUR</td></tr>';
        }
        $html .= '<tr><td><b>TOTAL</b></td><td align="right"><b>'
               . number_format($total, 2, ',', '.') . ' EUR</b></td></tr></table>';
        $pdf->writeHTML($html, true, false, false, false, '');

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, count($despesas) . ' lançamento(s). As páginas seguintes são os documentos.', 0, 1);

        /* ---- Uma página por documento ---- */
        foreach ($despesas as $d) {
            $caminho = FCPATH . DPS_PAINEL_UPLOAD . $d['doc'];

            $cabecalho = function ($pdf) use ($d, $mes) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 6, _d($d['data']) . '  ·  ' . (trim((string) $d['categoria']) ?: 'Outros')
                    . '  ·  ' . number_format((float) $d['valor'], 2, ',', '.') . ' EUR', 0, 1);
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell(0, 5, trim((string) $d['descricao'])
                    . (!empty($d['fatura_numero']) ? '   (fatura ' . $d['fatura_numero'] . ')' : ''), 0, 1);
                $pdf->Ln(2);
            };

            if (empty($d['doc']) || !file_exists($caminho)) {
                $pdf->AddPage();
                $cabecalho($pdf);
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 8, 'Sem documento anexado.', 0, 1);
                continue;
            }

            $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

            if ($ext === 'pdf') {
                try {
                    $im = new Imagick();
                    $im->setResolution(150, 150);
                    $im->readImage($caminho);

                    foreach ($im as $i => $pagina) {
                        $pagina->setImageFormat('jpeg');
                        $pagina->setImageCompressionQuality(85);
                        $tmp = tempnam(sys_get_temp_dir(), 'desp') . '.jpg';
                        file_put_contents($tmp, $pagina->getImageBlob());

                        $pdf->AddPage();
                        $cabecalho($pdf);
                        $pdf->Image($tmp, 12, $pdf->GetY(), 186, 0, 'JPG', '', '', true, 150);
                        @unlink($tmp);
                    }
                    $im->clear();
                } catch (Throwable $e) {
                    $pdf->AddPage();
                    $cabecalho($pdf);
                    $pdf->SetFont('helvetica', 'I', 10);
                    $pdf->MultiCell(0, 6, 'Não foi possível ler este PDF: ' . $e->getMessage(), 0, 'L');
                }
            } else {
                $pdf->AddPage();
                $cabecalho($pdf);
                $pdf->Image($caminho, 12, $pdf->GetY(), 186, 0, '', '', '', true, 150);
            }
        }

        $pdf->Output('despesas-' . $mes . '.pdf', 'D');
    }
}
