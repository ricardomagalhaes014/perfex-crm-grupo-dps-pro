<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * Separador "Compras" da ficha do cliente.
 *
 * Um cliente do CRM nasce de uma venda concluída, mas a ficha não dizia o que
 * ele tinha comprado — era preciso ir ao mapa de vendas procurar pelo nome.
 * Aqui está: empreendimento, fracção, valor e data, uma linha por compra.
 *
 * A ligação é o client_id na venda, que é o mesmo caminho que o envio em
 * massa por empreendimento usa. Se um dia uma compra não aparecer aqui,
 * também não aparece nesse envio — e é o mesmo defeito a corrigir.
 */
$CI = &get_instance();

$cliente_id = 0;
if (function_exists('get_client') && ($c = get_client())) {
    $cliente_id = (int) $c->userid;
}
if (!$cliente_id) {
    $cliente_id = (int) $CI->uri->segment(4);
}

$compras = [];
if ($cliente_id) {
    $compras = $CI->db
        ->select('v.id, v.empreendimento, v.unidade, v.valor, v.estado, v.data_venda,
                  CONCAT(s.firstname, " ", s.lastname) AS comercial')
        ->from(db_prefix() . 'simulador_vendas v')
        ->join(db_prefix() . 'staff s', 's.staffid = v.staff_id', 'left')
        ->where('v.client_id', $cliente_id)
        ->order_by('v.data_venda', 'DESC')
        ->get()->result_array();
}
?>
<div class="tab-pane" id="dps_compras">
    <h4 class="no-margin">Compras</h4>
    <hr class="hr-panel-heading">

    <?php if (empty($compras)) { ?>
        <p class="text-muted">
            Sem compras registadas para este cliente.
            <br>
            <small>
                As compras aparecem aqui quando a venda é dada como concluída no
                <a href="<?php echo admin_url('dps_vendas'); ?>">mapa de vendas</a>.
            </small>
        </p>
    <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Empreendimento</th>
                        <th>Fracção</th>
                        <th class="text-right">Valor</th>
                        <th>Data</th>
                        <th>Estado</th>
                        <th>Comercial</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total = 0;
foreach ($compras as $v) {
    $total += (float) $v['valor']; ?>
                    <tr>
                        <td><?php echo html_escape($v['empreendimento']); ?></td>
                        <td><strong><?php echo html_escape($v['unidade']); ?></strong></td>
                        <td class="text-right"><?php echo app_format_money($v['valor'], get_base_currency()); ?></td>
                        <td><?php echo !empty($v['data_venda']) ? _d($v['data_venda']) : '—'; ?></td>
                        <td>
                            <span class="label <?php echo dps_vendas_cor_estado($v['estado']); ?>">
                                <?php echo html_escape(dps_vendas_nome_estado($v['estado'])); ?>
                            </span>
                        </td>
                        <td><?php echo html_escape((string) $v['comercial']); ?></td>
                        <td class="text-right">
                            <a href="<?php echo admin_url('dps_vendas/view/' . (int) $v['id']); ?>"
                               class="btn btn-default btn-xs">Abrir venda</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
                <?php if (count($compras) > 1) { ?>
                    <tfoot>
                        <tr>
                            <th colspan="2">Total</th>
                            <th class="text-right"><?php echo app_format_money($total, get_base_currency()); ?></th>
                            <th colspan="4"></th>
                        </tr>
                    </tfoot>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>
