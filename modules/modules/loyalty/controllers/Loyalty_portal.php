<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Loyalty portal Controller
 */
class Loyalty_portal extends ClientsController
{   
    /**
     * construct
     */
    public function __construct() {
        parent::__construct();
        $this->load->model('loyalty_model');
    }


    /**
     * index
     * @return view
     */
    public function index()
    {   
    	if (!is_client_logged_in() && !is_staff_logged_in()) {
            
            redirect(site_url('authentication'));
        }
        $data['title']            = _l('loyalty_portal');
        $data['transations'] = $this->loyalty_model->get_transation_by_client(get_client_user_id());
        $data['programs'] = $this->loyalty_model->get_program_by_client(get_client_user_id());
        $data['rd_logs'] = $this->loyalty_model->get_redeem_log_by_client(get_client_user_id());
        $this->data($data);
        $this->view('loyalty_portal/home');
        $this->layout();
    }

    /**
     * { program detail }
     *
     * @param      $program  The program
     * @return json
     */
    public function program_detail($program){
        if (!is_client_logged_in() && !is_staff_logged_in()) {
            
            redirect(site_url('authentication'));
        }

        $pg = $this->loyalty_model->get_membership_program($program);

        $html = '';
        $html .= '<span class="label label-warning">Program '.$pg->program_name.'</span><br><br>';
        if($pg->discount == 'card_total'){
            $html .= '<p class="bold">Discount '.$pg->discount_percent.'% for every order.</p>';
        }elseif($pg->discount == 'product_category'){
            $html .= '<table class="table table-bordered table-striped">';
            $html .=    '<tbody>';
            $html .= '<tr>'; 
            $html .= '<td>'._l('product_category').'</td>';
            $html .= '<td>'._l('discount_percent').'</td>';
            $html .= '</tr>';
            foreach($pg->discount_detail as $dt){
                $html .= '<tr>'; 
                $html .= '<td>'.product_category_by_id($dt['rel_id']).'</td>';
                $html .= '<td>'.$dt['percent'].'%</td>';
                $html .= '</tr>';
            }

            $html .=    '</tbody>';
            $html .= '</table>';
        }elseif($pg->discount == 'product'){
            $html .= '<table class="table table-bordered table-striped">';
            $html .=    '<tbody>';
            $html .= '<tr>'; 
            $html .= '<td>'._l('product_loy').'</td>';
            $html .= '<td>'._l('discount_percent').'</td>';
            $html .= '</tr>';
            foreach($pg->discount_detail as $dt){
                $html .= '<tr>'; 
                $html .= '<td>'.product_by_id($dt['rel_id']).'</td>';
                $html .= '<td>'.$dt['percent'].'%</td>';
                $html .= '</tr>';
            }

            $html .=    '</tbody>';
            $html .= '</table>';
        }
         $html .= '<hr>';
        echo json_encode([
            'html' => $html,
        ]);
    }
}