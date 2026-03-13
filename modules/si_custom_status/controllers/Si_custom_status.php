<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Si_custom_status extends AdminController
{
	public function __construct()
	{
		parent::__construct(); 
		if (!is_admin() && !has_permission('si_custom_status', '', 'view')) {
			access_denied(_l('si_custom_status'));
		}
	}
	public function index()
	{
		redirect(admin_url('/'));
	}
	public function statuses($relto='')
	{
		if (!is_admin() && !has_permission('si_custom_status', '', 'view')) {
			access_denied(_l('si_custom_status'));
		}
		$data['default_statuses'] = array();
		if($relto=='')
			redirect(admin_url('si_custom_status/statuses/projects'));
			
		if($relto=='projects')
			$data['default_statuses'] = $this->projects_model->get_project_statuses();
		if($relto=='tasks')
			$data['default_statuses'] = $this->tasks_model->get_statuses();
		$data['statuses']	= $this->si_custom_status_model->get_status('',["relto" => $relto]);
		$data['title']		= _l('si_custom_status_setup_menu');
		$data['relto']		= $relto;
		$this->load->view('statuses/manage_statuses', $data);
	}
	public function status()
	{
		if (!is_admin() && !(has_permission('si_custom_status', '', 'create') || !has_permission('si_custom_status', '', 'edit')) ) {
			access_denied(_l('si_custom_status'));
		}
		if ($this->input->post()) {
			$data = $this->input->post();
			if(!isset($data['filter_default']))
				$data['filter_default'] = 0;
				
			if (!$this->input->post('id')) {
				$inline = isset($data['inline']);
				if (isset($data['inline'])) {
					unset($data['inline']);
				}
				$id = $this->si_custom_status_model->add_status($data);
				if (!$inline) {
					if ($id) {
						set_alert('success', _l('added_successfully', _l('si_custom_statuses')));
					}
				} else {
					echo json_encode(['success' => $id ? true : fales, 'id' => $id]);
				}
			} else {
				$id = $data['id'];
				unset($data['id']);
				$success = $this->si_custom_status_model->update_status($data, $id);
				if ($success) {
					set_alert('success', _l('updated_successfully', _l('si_custom_statuses')));
				}
			}
		}
	}
	public function delete_status($id)
	{
		if (!is_admin() && !has_permission('si_custom_status', '', 'delete')) {
			access_denied(_l('si_custom_status'));
		}
		if (!$id) {
			redirect($_SERVER['HTTP_REFERER']);
		}
		$response = $this->si_custom_status_model->delete_status($id);
		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('is_referenced', _l('si_custom_statuses')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted', _l('si_custom_statuses')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('si_custom_statuses')));
		}
		redirect($_SERVER['HTTP_REFERER']);
	}
}