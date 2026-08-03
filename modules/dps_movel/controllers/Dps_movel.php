<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * O CRM no telemóvel.
 *
 * Estes ecrãs NÃO usam init_head()/init_tail() do Perfex de propósito: essa
 * moldura puxa mais de 1 MB de CSS e JavaScript feitos para rato e ecrã grande.
 * Num telemóvel em rede móvel isso é a diferença entre abrir uma lead em meio
 * segundo ou em dez. O que se perde — menus, modais, tabelas — não faz falta
 * nenhuma no polegar.
 *
 * A sessão é a mesma do CRM (AdminController), portanto quem entra aqui já
 * entrou lá. Não há segunda palavra-passe nem segundo conceito de permissões:
 * a regra de que leads se vê é a mesma do computador,
 * dps_teams_where_leads_visiveis().
 */
class Dps_movel extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('leads_model');
    }

    /* ---------------------------------------------------------------- *
     * Hoje
     * ---------------------------------------------------------------- */

    public function index()
    {
        $eu = (int) get_staff_user_id();

        $data['tarefas'] = $this->minhas_tarefas(5);

        $data['reunioes'] = [];
        if ($this->db->table_exists(db_prefix() . 'dps_reunioes')) {
            $data['reunioes'] = $this->db
                ->select('id, assunto, data_hora, cliente_nome, link')
                ->from(db_prefix() . 'dps_reunioes')
                ->where('estado <>', 'cancelada')
                ->where('data_hora >=', date('Y-m-d 00:00:00'))
                ->group_start()
                    ->where('staff_id', $eu)
                    ->or_where('convidado_id', $eu)
                ->group_end()
                ->order_by('data_hora')
                ->limit(4)
                ->get()->result_array();
        }

        $data['estados'] = $this->contagem_por_estado();
        $data['titulo']  = 'Hoje';
        $data['aba']     = 'inicio';

        $this->load->view('inicio', $data);
    }

    /* ---------------------------------------------------------------- *
     * Leads
     * ---------------------------------------------------------------- */

    public function leads()
    {
        $procura = trim((string) $this->input->get('q'));
        $estado  = (int) $this->input->get('estado');

        $this->db->select('id, name, phonenumber, email, status, lastcontact, dateadded')
                 ->from(db_prefix() . 'leads');

        $regra = $this->regra_visibilidade();
        if ($regra !== '') {
            $this->db->where($regra, null, false);
        }

        if ($estado) {
            $this->db->where('status', $estado);
        }
        if ($procura !== '') {
            $this->db->group_start()
                     ->like('name', $procura)
                     ->or_like('phonenumber', $procura)
                     ->or_like('email', $procura)
                     ->group_end();
        }

        $data['leads'] = $this->db->order_by('lastcontact IS NULL, lastcontact DESC, id DESC')
                                  ->limit(60)->get()->result_array();

        $data['estados'] = $this->contagem_por_estado();
        $data['procura'] = $procura;
        $data['estado']  = $estado;
        $data['titulo']  = 'Leads';
        $data['aba']     = 'leads';

        $this->load->view('leads', $data);
    }

    public function lead($id = null)
    {
        $lead = $this->lead_visivel($id);

        $data['lead']    = $lead;
        $data['estados'] = $this->db->order_by('statusorder')
                                    ->get(db_prefix() . 'leads_status')->result_array();
        $data['empreendimentos'] = function_exists('dps_propostas_empreendimentos')
            ? dps_propostas_empreendimentos()
            : [];

        $data['notas'] = $this->db->select('description, date')
            ->from(db_prefix() . 'lead_activity_log')
            ->where('leadid', (int) $lead['id'])
            ->order_by('date', 'DESC')->limit(6)->get()->result_array();

        $data['titulo'] = $lead['name'];
        $data['aba']    = 'leads';

        $this->load->view('lead', $data);
    }

    /** Mudar o estado. Passa pelo mesmo caminho do quadro kanban, para os
     *  automatismos que escutam lead_status_changed continuarem a disparar —
     *  é assim que a reserva abre quando a lead vai para PARA CONTRATO. */
    public function estado($id = null)
    {
        $lead   = $this->lead_visivel($id);
        $estado = (int) $this->input->post('status');

        if ($estado) {
            $this->leads_model->update_lead_status([
                'leadid' => (int) $lead['id'],
                'status' => $estado,
            ]);
            set_alert('success', 'Estado alterado.');
        }

        redirect(admin_url('dps_movel/lead/' . (int) $lead['id']));
    }

    public function nota($id = null)
    {
        $lead = $this->lead_visivel($id);
        $nota = trim((string) $this->input->post('nota'));

        if ($nota !== '') {
            $this->leads_model->log_lead_activity((int) $lead['id'], '📝 ' . $nota);
            $this->db->where('id', (int) $lead['id'])
                     ->update(db_prefix() . 'leads', ['lastcontact' => date('Y-m-d H:i:s')]);
            set_alert('success', 'Nota guardada.');
        }

        redirect(admin_url('dps_movel/lead/' . (int) $lead['id']));
    }

    /* ---------------------------------------------------------------- *
     * Tarefas
     * ---------------------------------------------------------------- */

    public function tarefas()
    {
        $data['tarefas'] = $this->minhas_tarefas(80);
        $data['titulo']  = 'Tarefas';
        $data['aba']     = 'tarefas';

        $this->load->view('tarefas', $data);
    }

    public function tarefa_feita($id = null)
    {
        $id = (int) $id;
        $eu = (int) get_staff_user_id();

        // Só quem está atribuído à tarefa a fecha — não se fecham tarefas alheias.
        $minha = $this->db->where('taskid', $id)->where('staffid', $eu)
                          ->count_all_results(db_prefix() . 'task_assigned');

        if ($minha) {
            $this->load->model('tasks_model');
            $this->tasks_model->mark_as(5, $id);   // 5 = concluída, no Perfex
            set_alert('success', 'Tarefa concluída.');
        } else {
            set_alert('warning', 'Essa tarefa não é sua.');
        }

        redirect(admin_url('dps_movel/tarefas'));
    }

    /* ---------------------------------------------------------------- *
     * Agenda
     * ---------------------------------------------------------------- */

    public function agenda()
    {
        $eu = (int) get_staff_user_id();

        $data['reunioes'] = [];
        if ($this->db->table_exists(db_prefix() . 'dps_reunioes')) {
            $data['reunioes'] = $this->db
                ->select('r.*, CONCAT(s.firstname," ",s.lastname) AS anfitriao')
                ->from(db_prefix() . 'dps_reunioes r')
                ->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left')
                ->where('r.estado <>', 'cancelada')
                ->where('r.data_hora >=', date('Y-m-d 00:00:00'))
                ->group_start()
                    ->where('r.staff_id', $eu)
                    ->or_where('r.convidado_id', $eu)
                ->group_end()
                ->order_by('r.data_hora')->limit(30)->get()->result_array();
        }

        $data['titulo'] = 'Agenda';
        $data['aba']    = 'agenda';

        $this->load->view('agenda', $data);
    }

    /* ---------------------------------------------------------------- *
     * Peças comuns
     * ---------------------------------------------------------------- */

    /** A MESMA regra do computador. Se o módulo de equipas desaparecer, o
     *  comercial fica a ver só as suas — nunca mais do que devia. */
    private function regra_visibilidade()
    {
        if (function_exists('dps_teams_where_leads_visiveis')) {
            return dps_teams_where_leads_visiveis(db_prefix() . 'leads');
        }

        if (is_admin()) {
            return '';
        }

        $eu = (int) get_staff_user_id();

        return '(' . db_prefix() . 'leads.assigned = ' . $eu
             . ' OR ' . db_prefix() . 'leads.addedfrom = ' . $eu . ')';
    }

    /**
     * Lê a lead e recusa quem não lhe pode chegar.
     *
     * A verificação é feita aqui, e não no ecrã: os ids das leads são
     * sequenciais, e sem isto bastava trocar o número no endereço para ler a
     * ficha de um cliente de outro comercial.
     */
    private function lead_visivel($id)
    {
        $id = (int) $id;

        $this->db->select('*')->from(db_prefix() . 'leads')->where('id', $id);

        $regra = $this->regra_visibilidade();
        if ($regra !== '') {
            $this->db->where($regra, null, false);
        }

        $lead = $this->db->get()->row_array();

        if (!$lead) {
            set_alert('warning', 'Essa lead não existe ou não é sua.');
            redirect(admin_url('dps_movel/leads'));
        }

        return $lead;
    }

    private function minhas_tarefas($limite)
    {
        $eu = (int) get_staff_user_id();

        return $this->db->select('t.id, t.name, t.duedate, t.priority, t.status')
            ->from(db_prefix() . 'tasks t')
            ->join(db_prefix() . 'task_assigned a', 'a.taskid = t.id')
            ->where('a.staffid', $eu)
            ->where('t.status <>', 5)
            ->order_by('t.duedate IS NULL, t.duedate ASC, t.id DESC')
            ->limit($limite)->get()->result_array();
    }

    private function contagem_por_estado()
    {
        $regra = $this->regra_visibilidade();
        $extra = $regra !== '' ? ' AND ' . $regra : '';

        $sql = 'SELECT s.id, s.name, s.color,
                       (SELECT COUNT(*) FROM ' . db_prefix() . 'leads
                         WHERE ' . db_prefix() . 'leads.status = s.id' . $extra . ') AS n
                  FROM ' . db_prefix() . 'leads_status s
              ORDER BY s.statusorder';

        return array_values(array_filter(
            $this->db->query($sql)->result_array(),
            fn ($e) => (int) $e['n'] > 0
        ));
    }

    /* ---------------------------------------------------------------- *
     * Peças que fazem disto uma aplicação instalável
     * ---------------------------------------------------------------- */

    /**
     * O manifesto. Servido daqui e não de um ficheiro solto porque o
     * `start_url` tem de cair dentro do `scope`, e ambos vivem sob /admin.
     */
    public function manifest()
    {
        header('Content-Type: application/manifest+json; charset=utf-8');

        echo json_encode([
            'name'             => 'DPS Imobiliário',
            'short_name'       => 'DPS',
            'description'      => 'As minhas leads, tarefas e agenda.',
            'start_url'        => admin_url('dps_movel'),
            'scope'            => admin_url('dps_movel/'),
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#10151c',
            'theme_color'      => '#10151c',
            'lang'             => 'pt-PT',
            'icons'            => [
                [
                    'src'     => base_url('dps-movel/icone-192.png'),
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src'     => base_url('dps-movel/icone-512.png'),
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src'     => base_url('dps-movel/icone-mascara.png'),
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * O trabalhador de serviço.
     *
     * Faz duas coisas e mais nenhuma: torna a app instalável e troca a página
     * de erro do browser por uma mensagem em português quando não há rede.
     *
     * NÃO guarda leads, tarefas nem reuniões. Seria fácil e daria uma app que
     * "abre offline" — a mostrar números de ontem. Numa equipa em que o estado
     * de uma lead muda de minuto a minuto, dois comerciais a decidir sobre a
     * mesma cópia velha faz mais estragos do que uma mensagem honesta.
     */
    public function sw()
    {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: ' . admin_url('dps_movel/'));
        header('Cache-Control: no-cache');

        $offline = admin_url('dps_movel/offline');
        ?>
const CACHE = 'dps-movel-v1';
const OFFLINE = <?php echo json_encode($offline); ?>;

self.addEventListener('install', function (e) {
    e.waitUntil(caches.open(CACHE).then(function (c) { return c.add(OFFLINE); }));
    self.skipWaiting();
});

self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (chaves) {
            return Promise.all(chaves.filter(function (k) { return k !== CACHE; })
                                    .map(function (k) { return caches.delete(k); }));
        }).then(function () { return self.clients.claim(); })
    );
});

self.addEventListener('fetch', function (e) {
    // Só as navegações. Tudo o resto vai à rede como sempre foi.
    if (e.request.mode !== 'navigate') { return; }

    e.respondWith(
        fetch(e.request).catch(function () { return caches.match(OFFLINE); })
    );
});
        <?php
        exit;
    }

    /** O que se vê quando o telemóvel fica sem rede. */
    public function offline()
    {
        $this->load->view('offline', ['titulo' => 'Sem ligação', 'aba' => '']);
    }
}
