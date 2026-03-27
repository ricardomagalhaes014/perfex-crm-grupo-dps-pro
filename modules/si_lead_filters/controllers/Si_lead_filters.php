<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Si_lead_filters extends AdminController 
{
	public function __construct()
	{
		parent::__construct(); 
		$this->load->model('si_lead_filter_model');
		$this->load->model('leads_model');
		if (!is_admin() && !has_permission('si_lead_filters', '', 'view')) {
			access_denied(_l('si_lead_filters'));
		}
	}
	
	private function get_where_report_period($field = 'date',$months_report='this_month')
	{
		$custom_date_select = '';
		if ($months_report != '') {
			if (is_numeric($months_report)) {
				// Last month
				if ($months_report == '1') {
					$beginMonth = date('Y-m-01', strtotime('first day of last month'));
					$endMonth   = date('Y-m-t', strtotime('last day of last month'));
				} else {
					$months_report = (int) $months_report;
					$months_report--;
					$beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
					$endMonth   = date('Y-m-t');
				}

				$custom_date_select = 'AND (' . $field . ' >= "' . $beginMonth . ' 00:00:00" AND ' . $field . ' <= "' . $endMonth . ' 23:59:59")';
			} elseif ($months_report == 'this_month') {
				$custom_date_select = 'AND (' . $field . ' >= "' . date('Y-m-01 00:00:00') . '" AND ' . $field . ' < "' . date('Y-m-01', strtotime('+1 month')) . ' 00:00:00")';
			} elseif ($months_report == 'this_year') {
				$custom_date_select = 'AND (' . $field . ' >= "' . date('Y-01-01 00:00:00') . '" AND ' . $field . ' < "' . (date('Y')+1) . '-01-01 00:00:00")';
			} elseif ($months_report == 'last_year') {
				$last_year = date('Y', strtotime('last year'));
				$custom_date_select = 'AND (' . $field . ' >= "' . $last_year . '-01-01 00:00:00" AND ' . $field . ' < "' . ($last_year+1) . '-01-01 00:00:00")';
			} elseif ($months_report == 'custom') {
				$from_date = to_sql_date($this->input->post('report_from'));
				$to_date   = to_sql_date($this->input->post('report_to'));
				if ($from_date == $to_date) {
					$custom_date_select = 'AND (' . $field . ' >= "' . $from_date . ' 00:00:00" AND ' . $field . ' <= "' . $from_date . ' 23:59:59")';
				} else {
					$custom_date_select = 'AND (' . $field . ' >= "' . $from_date . ' 00:00:00" AND ' . $field . ' <= "' . $to_date . ' 23:59:59")';
				}
			}
		}
		
		 return $custom_date_select;
	}

	/**
	 * AJAX: retorna os membros de uma equipa para o select de utilizador
	 */
	public function get_team_members_ajax()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$team_id = (int)$this->input->get('team_id');
		if ($team_id <= 0) {
			// Sem equipa seleccionada: devolver todos os staff activos
			$members = $this->db
				->select('staffid, CONCAT(firstname, \' \', lastname) as full_name')
				->where('active', 1)
				->order_by('firstname', 'asc')
				->get(db_prefix() . 'staff')
				->result_array();
		} else {
			// Devolver apenas os membros da equipa seleccionada
			$this->db->select(
				db_prefix() . 'staff.staffid, ' .
				'CONCAT(' . db_prefix() . 'staff.firstname, \' \', ' . db_prefix() . 'staff.lastname) as full_name',
				FALSE  // Desactivar escape automático para evitar SQL inválido
			);
			$this->db->from(db_prefix() . 'dps_team_members');
			$this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'dps_team_members.staff_id');
			$this->db->where(db_prefix() . 'dps_team_members.team_id', $team_id);
			$this->db->where(db_prefix() . 'staff.active', 1);
			$this->db->order_by(db_prefix() . 'staff.firstname', 'asc');
			$members = $this->db->get()->result_array();
		}
		header('Content-Type: application/json');
		echo json_encode($members);
	}

	/**
	 * Conta interacções em batch para um array de lead IDs.
	 * Retorna array [lead_id => count]
	 */
	private function count_interactions_batch(array $lead_ids)
	{
		if (empty($lead_ids)) return [];
		$prefix = db_prefix();
		$ids    = implode(',', array_map('intval', $lead_ids));
		$sql    = "SELECT leadid, COUNT(*) as cnt
				   FROM {$prefix}lead_activity_log
				   WHERE leadid IN ({$ids})
				   GROUP BY leadid";
		$result = $this->db->query($sql);
		$map    = [];
		if ($result) {
			foreach ($result->result_array() as $row) {
				$map[(int)$row['leadid']] = (int)$row['cnt'];
			}
		}
		return $map;
	}

	public function leads_filter()
	{
		$overview = [];
		
		$saved_filter_name='';
		$filter_id = $this->input->get('filter_id');
		if($filter_id!='' && is_numeric($filter_id) && empty($this->input->post()))
		{
			$filter_obj = $this->si_lead_filter_model->get($filter_id);
			if(!empty($filter_obj))
			{
				$_POST = unserialize($filter_obj->filter_parameters);
				$saved_filter_name = $filter_obj->filter_name;
			}	
		}	

		$has_permission_view = has_permission('leads', '', 'view');
		$current_user_id     = get_staff_user_id();

		// ── Filtro de equipa ──────────────────────────────────────────────────
		$team_id = $this->input->post('team_id');
		if (!is_numeric($team_id)) $team_id = '';

		// ── Filtro de membro ──────────────────────────────────────────────────
		// Visibilidade por papel DPS:
		//   Super Admin / Admin: vê tudo (pode filtrar por equipa/membro)
		//   Gestor: vê apenas leads dos membros da(s) sua(s) equipa(s)
		//   Comercial: vê apenas as suas próprias leads
		if ($has_permission_view) {
			// Admin / Super Admin
			if ($this->input->post('member')) {
				$staff_id = $this->input->post('member');
			} elseif ($team_id !== '') {
				// Se filtrou por equipa mas não por membro: filtrar pelos membros da equipa
				$staff_id = '';
			} else {
				$staff_id = '';
			}
		} else {
			// Verificar papel DPS do utilizador actual
			$dps_role = $this->db
				->select('role')
				->where('staff_id', $current_user_id)
				->order_by("FIELD(role,'manager','commercial')", null, false)
				->limit(1)
				->get(db_prefix() . 'dps_team_members')
				->row_array();

			if (!empty($dps_role) && $dps_role['role'] === 'manager') {
				// Gestor: vê as leads de todos os membros das suas equipas
				$managed_teams = $this->db
					->select('team_id')
					->where('staff_id', $current_user_id)
					->where('role', 'manager')
					->get(db_prefix() . 'dps_team_members')
					->result_array();
				$managed_team_ids = array_column($managed_teams, 'team_id');

				if (!empty($managed_team_ids)) {
					$team_members = $this->db
						->select('staff_id')
						->where_in('team_id', $managed_team_ids)
						->get(db_prefix() . 'dps_team_members')
						->result_array();
					$team_staff_ids = array_column($team_members, 'staff_id');
					// Incluir o próprio gestor
					if (!in_array($current_user_id, $team_staff_ids)) {
						$team_staff_ids[] = $current_user_id;
					}
					$staff_id = $team_staff_ids; // array para where_in
				} else {
					$staff_id = $current_user_id;
				}
			} else {
				// Comercial ou sem papel: vê apenas as suas próprias leads
				$staff_id = $current_user_id;
			}
		}

		$status = $this->input->post('status');
		if(empty($status))
			$status=array('');
		$source = $this->input->post('source');
		if(empty($source))
			$source=array('');	
		$tag = $this->input->post('tags');
		if(empty($tag))
			$tag=array('');
		$country = $this->input->post('countries');
		if(empty($country))
			$country=array('');	
		
		$type = $this->input->post('type');	
				
		$hide_columns = $this->input->post('hide_columns');
		if(empty($hide_columns))
			$hide_columns=array();
			
		if ($this->input->post('date_by')) {
			$date_by = $this->input->post('date_by');
		} else {
			$date_by = 'dateadded';
		}
		
		$fetch_month_from = $date_by;
		
		if ($this->input->post('report_months')!='')
			$report_months = $this->input->post('report_months');
		elseif($this->input->post('report_months')=='' && $filter_id=='' && $this->input->server('REQUEST_METHOD') !== 'POST')
			$report_months = 'this_month';//by default when loaded (GET)
		elseif($this->input->post('report_months')=='' && $this->input->server('REQUEST_METHOD') === 'POST')
			$report_months = 'this_month';//by default when submitted via POST without date filter
		else
			$report_months = '';
		
		$save_filter = $this->input->post('save_filter');
		$filter_name='';
		if($save_filter==1)
		{
			$filter_name=$this->input->post('filter_name');
			$all_filter = $this->input->post();
			unset($all_filter['save_filter']);
			unset($all_filter['filter_name']);
			$saved_filter_name = $filter_name;
			$filter_parameters = serialize($all_filter);
			$filter_data = array('filter_name'=>$filter_name,
								 'filter_parameters'=>$filter_parameters,
								 'staff_id'=>$current_user_id);
			if($filter_id!='' && is_numeric($filter_id))
				$this->si_lead_filter_model->update($filter_data,$filter_id);
			else					 
				$new_filter_id = $this->si_lead_filter_model->add($filter_data);
		}

		// ── Construir query de leads com SQL raw (evita problemas com MariaDB 11.8) ──
		$p = db_prefix();

		// ── Determinar staff_ids a filtrar ────────────────────────────────────
		$where_parts = [];

		if ($has_permission_view) {
			$member_post = $this->input->post('member');
			if ($team_id !== '' && is_numeric($team_id)) {
				// Obter sub-equipas (1 nível)
				$sub_q = $this->db->query("SELECT id FROM {$p}dps_teams WHERE parent_id = ?", [(int)$team_id]);
				$all_team_ids = [(int)$team_id];
				foreach ($sub_q->result_array() as $st) {
					$all_team_ids[] = (int)$st['id'];
				}
				$team_ids_str = implode(',', $all_team_ids);
				$members_q = $this->db->query("SELECT DISTINCT staff_id FROM {$p}dps_team_members WHERE team_id IN ({$team_ids_str})");
				$team_staff_ids = array_column($members_q->result_array(), 'staff_id');

				if ($member_post !== '' && $member_post !== null && is_numeric($member_post)) {
					$where_parts[] = "l.assigned = " . (int)$member_post;
				} elseif (!empty($team_staff_ids)) {
					$where_parts[] = "l.assigned IN (" . implode(',', array_map('intval', $team_staff_ids)) . ")";
				} else {
					$where_parts[] = "1=0"; // equipa sem membros
				}
			} elseif ($member_post !== '' && $member_post !== null && is_numeric($member_post)) {
				$where_parts[] = "l.assigned = " . (int)$member_post;
			}
			// Admin sem filtro: vê tudo
		} else {
			if (is_array($staff_id)) {
				$where_parts[] = "l.assigned IN (" . implode(',', array_map('intval', $staff_id)) . ")";
			} else {
				$where_parts[] = "l.assigned = " . (int)$staff_id;
			}
		}

		// ── Filtro de data ────────────────────────────────────────────────────
		if ($report_months != '') {
			$date_clause = $this->get_where_report_period("l.{$fetch_month_from}", $report_months);
			// get_where_report_period devolve "AND (...)" — remover o "AND " inicial
			if ($date_clause !== '') {
				$where_parts[] = ltrim(substr($date_clause, 4)); // remover "AND "
			}
		}

		// ── Filtro de status ──────────────────────────────────────────────────
		if ($status && !in_array('', $status)) {
			$status_esc = implode(',', array_map(function($v){ return (int)$v; }, $status));
			$where_parts[] = "l.status IN ({$status_esc})";
		}

		// ── Filtro de source ──────────────────────────────────────────────────
		if ($source && !in_array('', $source)) {
			$source_esc = implode(',', array_map(function($v){ return (int)$v; }, $source));
			$where_parts[] = "l.source IN ({$source_esc})";
		}

		// ── Filtro de país ────────────────────────────────────────────────────
		if ($country && !in_array('', $country)) {
			if (in_array(-1, $country)) $country[] = 0;
			$country_esc = implode(',', array_map(function($v){ return (int)$v; }, $country));
			$where_parts[] = "l.country IN ({$country_esc})";
		}

		// ── Filtro de tipo ────────────────────────────────────────────────────
		if ($type != '') {
			if ($type == 'lost')         $where_parts[] = "l.lost = 1";
			if ($type == 'junk')         $where_parts[] = "l.junk = 1";
			if ($type == 'public')       $where_parts[] = "l.is_public = 1";
			if ($type == 'not_assigned') $where_parts[] = "l.assigned = 0";
			if ($type == 'converted')    $where_parts[] = "l.id IN (SELECT leadid FROM {$p}clients WHERE leadid IS NOT NULL AND leadid > 0)";
		}

		// ── Filtro de tags (JOIN necessário) ──────────────────────────────────
		$tag_join = '';
		if ($tag && !in_array('', $tag)) {
			$tag_esc = implode(',', array_map(function($v){ return (int)$v; }, $tag));
			$tag_join = "LEFT JOIN {$p}taggables tg ON (tg.rel_id = l.id AND tg.rel_type = 'lead')";
			$where_parts[] = "tg.tag_id IN ({$tag_esc})";
		}

		// ── Montar WHERE ──────────────────────────────────────────────────────
		$where_sql = '';
		if (!empty($where_parts)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where_parts);
		}

		// ── GROUP BY (necessário quando há JOIN de tags) ──────────────────────
		$group_sql = (!empty($tag_join)) ? 'GROUP BY l.id' : '';

		// ── Query final com SQL raw ───────────────────────────────────────────
		$sql = "SELECT
			l.id, l.hash, l.name, l.title, l.company, l.description, l.country,
			l.zip, l.city, l.state, l.address, l.assigned, l.status, l.source,
			l.email, l.website, l.phonenumber, l.lead_value, l.junk, l.lost,
			l.is_public, l.dateadded, l.lastcontact, l.date_converted,
			l.addedfrom, l.default_language, l.dateassigned, l.last_status_change,
			l.leadorder, l.from_form_id, l.last_lead_status, l.client_id,
			l.converted_by_lead_manager, l.lm_follow_up, l.from_ma_form_id, l.ma_point,
			CONCAT(s.firstname, ' ', s.lastname) AS staff_name,
			ls.name AS source_name
		FROM {$p}leads l
		LEFT JOIN {$p}staff s ON s.staffid = l.assigned
		LEFT JOIN {$p}leads_sources ls ON ls.id = l.source
		{$tag_join}
		{$where_sql}
		{$group_sql}
		ORDER BY l.{$fetch_month_from} DESC";

		$leads_raw = $this->db->query($sql)->result_array();

		// ── Calcular interacções em batch (uma única query) ──────────────────
		$lead_ids = array_column($leads_raw, 'id');
		$interactions_map = $this->count_interactions_batch($lead_ids);
		foreach ($leads_raw as &$lead) {
			$lead['interactions'] = $interactions_map[(int)$lead['id']] ?? 0;
		}
		unset($lead);

		$overview[''] = $leads_raw;
		
		// ── Batch loading de custom fields e tags (evita N+1 queries) ────────
		$custom_fields_list = get_custom_fields('leads', ['show_on_table' => 1]);
		$custom_fields_map  = [];  // [lead_id][field_id] => value
		$tags_map           = [];  // [lead_id] => [tag_name, ...]
		
		if (!empty($lead_ids)) {
			$ids_str = implode(',', array_map('intval', $lead_ids));
			
			// Carregar todos os custom field values de uma vez
			if (!empty($custom_fields_list)) {
				$field_ids = array_column($custom_fields_list, 'id');
				$field_ids_str = implode(',', array_map('intval', $field_ids));
				$cf_sql = "SELECT relid, fieldid, value FROM " . db_prefix() . "customfieldsvalues 
				           WHERE fieldto = 'leads' AND relid IN ({$ids_str}) AND fieldid IN ({$field_ids_str})";
				$cf_result = $this->db->query($cf_sql);
				if ($cf_result) {
					foreach ($cf_result->result_array() as $cf_row) {
						$custom_fields_map[(int)$cf_row['relid']][(int)$cf_row['fieldid']] = $cf_row['value'];
					}
				}
			}
			
			// Carregar todas as tags de uma vez
			$tags_sql = "SELECT t.rel_id, tg.name 
			             FROM " . db_prefix() . "taggables t
			             JOIN " . db_prefix() . "tags tg ON tg.id = t.tag_id
			             WHERE t.rel_type = 'lead' AND t.rel_id IN ({$ids_str})
			             ORDER BY t.tag_order ASC";
			$tags_result = $this->db->query($tags_sql);
			if ($tags_result) {
				foreach ($tags_result->result_array() as $tag_row) {
					$tags_map[(int)$tag_row['rel_id']][] = $tag_row['name'];
				}
			}
		}
		
		$data['title']    = _l('si_lf_submenu_lead_filters');
		$data['lead_statuses'] = $this->leads_model->get_status();
		$data['lead_sources']  = $this->leads_model->get_source();
		$data['lead_countries']  = $this->si_lead_filter_model->get_leads_country_list();
		$data['members']  = $this->staff_model->get();
		$data['staff_id'] = is_array($staff_id) ? '' : $staff_id;
		$data['saved_filter_name'] = $saved_filter_name;
		$data['date_by'] = $date_by;
		$data['statuses']  =$status;
		$data['sources']  =$source;
		$data['tags']  =$tag;
		$data['countries']  =$country;
		$data['type']=$type;
		$data['report_months'] = $report_months;
		$data['report_from'] = $this->input->post('report_from');
		$data['report_to'] = $this->input->post('report_to');
		$data['hide_columns'] = $hide_columns;
		$data['filter_templates'] = $this->si_lead_filter_model->get_templates($current_user_id);
		$data['overview'] = $overview;
		$data['has_permission_view'] = $has_permission_view;
		$data['team_id'] = $team_id;

		// Carregar equipas DPS para o selector
		$data['dps_teams'] = $this->db
			->order_by('area', 'asc')
			->get(db_prefix() . 'dps_teams')
			->result_array();
		$data['custom_fields_list']  = $custom_fields_list;
		$data['custom_fields_map']   = $custom_fields_map;
		$data['tags_map']            = $tags_map;
		
		$this->load->view('lead_report', $data);
	}
	
	function list_filters()
	{
		$data=array();
		$data['title']    = _l('si_lf_submenu_filter_templates');
		$current_user_id = get_staff_user_id();
		$data['filter_templates'] = $this->si_lead_filter_model->get_templates($current_user_id);
		$this->load->view('lead_list_filters', $data);
	}
	function del_lead_filter($id)
	{
		$current_user_id = get_staff_user_id();
		$this->si_lead_filter_model->delete($id,$current_user_id);
		redirect('si_lead_filters/list_filters');
	}
}
