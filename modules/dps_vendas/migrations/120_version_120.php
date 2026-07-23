<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_120 extends App_module_migration
{
    public function up()
    {
        $CI     = &get_instance();
        $tabela = db_prefix() . 'comissao_regras';

        $existe = $CI->db
            ->where('empreendimento', 'Gaia Douro')
            ->count_all_results($tabela);

        if ($existe === 0) {
            $CI->db->insert($tabela, [
                'empreendimento' => 'Gaia Douro',
                'taxa'           => 0,
                'ativo'          => 1,
                'notas'          => 'TAXA POR DEFINIR (criada para aparecer na lista de empreendimentos)',
                'dateadded'      => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
