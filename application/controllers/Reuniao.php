<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Página pública onde o cliente confirma a reunião proposta.
 *
 * Estende o ClientsController, como o Forms do núcleo: quem abre isto não tem
 * conta no CRM e não pode ser mandado para o ecrã de entrada. O acesso é feito
 * pela chave da proposta, que nasce de random_bytes e não é adivinhável.
 *
 * Optou-se por um controlador em vez de mais um ficheiro solto na raiz para
 * poder usar o modelo do módulo: a criação da reunião arrasta sala de vídeo,
 * entradas nas agendas, Google Calendar, lembrete dos 30 minutos e tarefa de
 * seguimento. Reescrever isso num script à parte deixaria de fora uma dessas
 * peças, e seria sempre a que ninguém testa.
 */
class Reuniao extends ClientsController
{
    public function index()
    {
        show_404();
    }

    public function confirmar($chave = '')
    {
        $this->load->model('dps_reunioes/dps_reunioes_model');

        $chave = trim((string) $chave);
        if ($chave === '') {
            show_404();
        }

        $proposta = $this->dps_reunioes_model->proposta_por_chave($chave);
        if (!$proposta) {
            show_404();
        }

        $accao    = $this->input->post('accao');
        $resultado = null;

        if ($accao === 'aceitar') {
            $resultado = $this->dps_reunioes_model->aceitar_proposta($chave);
            $proposta  = $this->dps_reunioes_model->proposta_por_chave($chave);
        } elseif ($accao === 'recusar') {
            $this->dps_reunioes_model->recusar_proposta($chave);
            $proposta = $this->dps_reunioes_model->proposta_por_chave($chave);
        }

        $this->data['proposta']  = $proposta;
        $this->data['reuniao']   = $resultado;
        $this->data['comercial'] = get_staff_full_name((int) $proposta['staff_id']);
        $this->data['title']     = 'Confirmação de reunião';

        $this->load->view('reuniao_confirmar', $this->data);
    }
}
