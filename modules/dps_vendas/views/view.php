<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
// Separar os documentos do circuito CPCV/pagamento dos de identificação,
// para cada um viver no seu painel.
$cpcv_docs         = array_values(array_filter($docs, function ($d) { return $d['tipo'] === 'cpcv'; }));
$comprovativo_docs = array_values(array_filter($docs, function ($d) { return $d['tipo'] === 'comprovativo'; }));
$docs_identificacao = array_values(array_filter($docs, function ($d) {
    return !in_array($d['tipo'], ['cpcv', 'comprovativo'], true);
}));
$pode_gerir_cpcv = is_admin() || staff_can('edit', 'dps_vendas');
?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    Venda #<?php echo $venda['id']; ?>
                                    <span class="label <?php echo dps_vendas_cor_estado($venda['estado']); ?>">
                                        <?php echo dps_vendas_nome_estado($venda['estado']); ?>
                                    </span>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('dps_vendas/form/' . $venda['id']); ?>" class="btn btn-default btn-sm">
                                    <i class="fa fa-pencil"></i> Editar
                                </a>
                                <?php if (is_admin()) { ?>
                                    <a href="<?php echo admin_url('dps_vendas/delete/' . $venda['id']); ?>"
                                       class="btn btn-danger btn-sm _delete" data-toggle="tooltip" title="Eliminar venda">
                                        <i class="fa fa-trash"></i> Eliminar
                                    </a>
                                <?php } ?>
                            </div>
                        </div>

                        <hr>

                        <table class="table table-borderless">
                            <tr>
                                <td width="35%"><strong>Empreendimento</strong></td>
                                <td><?php echo html_escape($venda['empreendimento']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Unidade / Fracção</strong></td>
                                <td><?php echo html_escape($venda['unidade']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Valor</strong></td>
                                <td><?php echo app_format_money($venda['valor'], get_base_currency()); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Comercial</strong></td>
                                <td><?php echo html_escape($venda['comercial_nome']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Data da venda</strong></td>
                                <td><?php echo $venda['data_venda'] ? _d($venda['data_venda']) : '—'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Origem</strong></td>
                                <td><?php echo ucfirst($venda['origem'] ?? 'manual'); ?></td>
                            </tr>
                            <?php if (!empty($venda['lead_id'])) { ?>
                                <tr>
                                    <td><strong>Lead</strong></td>
                                    <td>
                                        <a href="<?php echo admin_url('leads/index/' . $venda['lead_id']); ?>" target="_blank">
                                            #<?php echo $venda['lead_id']; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>

                        <h5>Cliente</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td width="35%"><strong>Nome</strong></td>
                                <td><?php echo html_escape($venda['cliente']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Morada</strong></td>
                                <td><?php echo html_escape($venda['cliente_morada'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Código postal</strong></td>
                                <td><?php echo html_escape($venda['cliente_codigo_postal'] ?? '' ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telefone</strong></td>
                                <td><?php echo html_escape($venda['cliente_telefone'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email</strong></td>
                                <td><?php echo html_escape($venda['cliente_email'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Estado civil</strong></td>
                                <td><?php echo html_escape($venda['regime_civil'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Tipo</strong></td>
                                <td><?php echo html_escape(($venda['cliente_tipo'] ?? '') ?: '—'); ?></td>
                            </tr>
                            <?php if (!empty($venda['cliente_crc'])) { ?>
                                <tr>
                                    <td><strong>CRC (empresa)</strong></td>
                                    <td><?php echo html_escape($venda['cliente_crc']); ?></td>
                                </tr>
                            <?php } ?>
                        </table>

                        <h5>Documentos de identificação</h5>
                        <?php if (empty($docs_identificacao)) { ?>
                            <p class="text-muted">Sem documentos anexados.</p>
                        <?php } else { ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Ficheiro</th>
                                        <th>Data</th>
                                        <th class="text-right">Acções</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($docs_identificacao as $doc) { ?>
                                        <tr>
                                            <td><?php echo dps_vendas_nome_doc($doc['tipo']); ?></td>
                                            <td><?php echo html_escape($doc['original_name']); ?></td>
                                            <td><?php echo _dt($doc['dateadded']); ?></td>
                                            <td class="text-right">
                                                <?php if ($pode_download || (int) $venda['staff_id'] === (int) get_staff_user_id()) { ?>
                                                    <a href="<?php echo admin_url('dps_vendas/download_doc/' . $doc['id']); ?>"
                                                       class="btn btn-default btn-xs">
                                                        <i class="fa fa-download"></i> Descarregar
                                                    </a>
                                                <?php } else { ?>
                                                    <span class="text-muted">sem permissão</span>
                                                <?php } ?>
                                                <a href="<?php echo admin_url('dps_vendas/delete_doc/' . $doc['id']); ?>"
                                                   class="btn btn-danger btn-xs _delete">
                                                    <i class="fa fa-remove"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">CPCV &amp; Pagamento</h4>
                        <hr>

                        <p>
                            <strong>CPCV:</strong>
                            <?php if (!empty($venda['cpcv_assinado'])) { ?>
                                <span class="label label-success"><i class="fa fa-check"></i> Assinado</span>
                                <small class="text-muted"><?php echo _dt($venda['cpcv_assinado_em']); ?></small>
                            <?php } elseif (!empty($cpcv_docs)) { ?>
                                <span class="label label-info">Enviado</span>
                                <small class="text-muted">— aguarda assinatura</small>
                            <?php } else { ?>
                                <span class="label label-default">Por enviar</span>
                            <?php } ?>
                            &nbsp;·&nbsp;
                            <strong>Pagamento:</strong>
                            <?php if (!empty($venda['pago'])) { ?>
                                <span class="label label-success"><i class="fa fa-check"></i> Pago</span>
                                <small class="text-muted"><?php echo _dt($venda['pago_em']); ?></small>
                            <?php } else { ?>
                                <span class="label label-default">Pendente</span>
                            <?php } ?>
                        </p>

                        <div class="row mtop15">
                            <div class="col-md-6">
                                <h5>CPCV</h5>
                                <?php if (!empty($cpcv_docs)) { ?>
                                    <?php foreach ($cpcv_docs as $doc) { ?>
                                        <p>
                                            <i class="fa fa-file-pdf-o"></i>
                                            <?php echo html_escape($doc['original_name']); ?>
                                            <a href="<?php echo admin_url('dps_vendas/download_doc/' . $doc['id']); ?>"
                                               class="btn btn-default btn-xs">
                                                <i class="fa fa-download"></i> Descarregar
                                            </a>
                                            <?php if ($pode_gerir_cpcv) { ?>
                                                <a href="<?php echo admin_url('dps_vendas/delete_doc/' . $doc['id']); ?>"
                                                   class="btn btn-danger btn-xs _delete"><i class="fa fa-remove"></i></a>
                                            <?php } ?>
                                        </p>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p class="text-muted">Ainda sem CPCV.</p>
                                <?php } ?>

                                <?php if ($pode_gerir_cpcv) { ?>
                                    <?php echo form_open_multipart(admin_url('dps_vendas/upload_cpcv/' . $venda['id'])); ?>
                                    <div class="form-group">
                                        <input type="file" name="cpcv_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="fa fa-upload"></i> Carregar CPCV
                                    </button>
                                    <?php echo form_close(); ?>
                                    <p class="text-muted mtop10"><small>O comercial vê e descarrega o CPCV para enviar ao cliente.</small></p>
                                <?php } ?>

                                <?php if (is_admin() && !empty($venda['cpcv_assinado'])) { ?>
                                    <a href="<?php echo admin_url('dps_vendas/desmarcar_cpcv/' . $venda['id']); ?>"
                                       class="btn btn-default btn-xs mtop10"
                                       onclick="return confirm('Desmarcar o CPCV como assinado?');">
                                        <i class="fa fa-undo"></i> Corrigir — não estava assinado
                                    </a>
                                <?php } ?>

                                <?php if (is_admin() && !empty($cpcv_docs) && empty($venda['cpcv_assinado'])) { ?>
                                    <a href="<?php echo admin_url('dps_vendas/marcar_cpcv_assinado/' . $venda['id']); ?>"
                                       class="btn btn-success btn-sm mtop10"
                                       onclick="return confirm('Confirmar que o CPCV está assinado?');">
                                        <i class="fa fa-check"></i> Marcar como assinado
                                    </a>
                                <?php } elseif (!is_admin() && !empty($cpcv_docs) && empty($venda['cpcv_assinado'])) { ?>
                                    <p class="text-muted mtop10"><small>A validação do "assinado" é feita pela direção.</small></p>
                                <?php } ?>
                            </div>

                            <div class="col-md-6">
                                <h5>Comprovativo de pagamento</h5>
                                <?php if (!empty($comprovativo_docs)) { ?>
                                    <?php foreach ($comprovativo_docs as $doc) { ?>
                                        <p>
                                            <i class="fa fa-file-o"></i>
                                            <?php echo html_escape($doc['original_name']); ?>
                                            <a href="<?php echo admin_url('dps_vendas/download_doc/' . $doc['id']); ?>"
                                               class="btn btn-default btn-xs">
                                                <i class="fa fa-download"></i> Descarregar
                                            </a>
                                            <a href="<?php echo admin_url('dps_vendas/delete_doc/' . $doc['id']); ?>"
                                               class="btn btn-danger btn-xs _delete"><i class="fa fa-remove"></i></a>
                                        </p>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p class="text-muted">Ainda sem comprovativo.</p>
                                <?php } ?>

                                <?php echo form_open_multipart(admin_url('dps_vendas/upload_comprovativo/' . $venda['id'])); ?>
                                <div class="form-group">
                                    <input type="file" name="comprovativo_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fa fa-upload"></i> Carregar comprovativo
                                </button>
                                <?php echo form_close(); ?>
                                <p class="text-muted mtop10"><small>Quando o cliente envia o comprovativo, o comercial anexa-o aqui.</small></p>

                                <?php if (is_admin() && !empty($venda['pago'])) { ?>
                                    <a href="<?php echo admin_url('dps_vendas/desmarcar_pago/' . $venda['id']); ?>"
                                       class="btn btn-default btn-xs mtop10"
                                       onclick="return confirm('Desmarcar o pagamento desta venda? A comissão deixa de ser devida até voltar a validar.');">
                                        <i class="fa fa-undo"></i> Corrigir — não estava pago
                                    </a>
                                <?php } ?>

                                <?php if (is_admin() && !empty($comprovativo_docs) && empty($venda['pago'])) { ?>
                                    <a href="<?php echo admin_url('dps_vendas/marcar_pago/' . $venda['id']); ?>"
                                       class="btn btn-success btn-sm mtop10"
                                       onclick="return confirm('Confirmar pagamento? A venda passa a Concluída.');">
                                        <i class="fa fa-check"></i> Marcar pago (&rarr; Concluído)
                                    </a>
                                <?php } elseif (!is_admin() && !empty($comprovativo_docs) && empty($venda['pago'])) { ?>
                                    <p class="text-muted mtop10"><small>A confirmação do pagamento é feita pela direção.</small></p>
                                <?php } ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-4">

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Comissão</h5>
                        <hr>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Taxa aplicada</strong></td>
                                <td><?php echo $calculo['taxa']; ?>%</td>
                            </tr>
                            <tr>
                                <td><strong>Origem da taxa</strong></td>
                                <td>
                                    <?php echo $calculo['fonte'] === 'regra' ? 'Regra do empreendimento' : 'Definida na venda'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Valor calculado</strong></td>
                                <td><?php echo app_format_money($calculo['valor'], get_base_currency()); ?></td>
                            </tr>
                            <?php
                            /*
                             * Factura emitida ao promotor, por tranche.
                             *
                             * Vem do Moloni pelo botão do Painel ou do quadro de comissões,
                             * e pode ser corrigida à mão aqui — a busca automática nunca
                             * sobrepõe um número escrito por uma pessoa.
                             *
                             * Estava só no Painel do Negócio e faltava no sítio óbvio: quem
                             * abre a venda para saber se já foi facturada não tem de ir a
                             * outro ecrã descobri-lo.
                             */
                            $f_cpcv = trim((string) ($venda['fatura_moloni_cpcv'] ?? ''));
                            $f_escr = trim((string) ($venda['fatura_moloni_escritura'] ?? ''));
                            ?>
                            <tr>
                                <td><strong>Factura ao promotor</strong>
                                    <br><small class="text-muted">emitida no Moloni</small>
                                </td>
                                <td>
                                    <?php if ($f_cpcv === '' && $f_escr === '') { ?>
                                        <span class="text-muted">ainda sem factura</span>
                                        <?php if (is_admin()) { ?>
                                            <br><small class="text-muted">
                                                O botão <em>Buscar facturas ao Moloni</em>, no
                                                <a href="<?php echo admin_url('dps_painel'); ?>">Painel do Negócio</a>,
                                                procura-a e preenche.
                                            </small>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($f_cpcv !== '') { ?>
                                            <i class="fa fa-file-text-o"></i> CPCV: <strong><?php echo html_escape($f_cpcv); ?></strong>
                                        <?php } ?>
                                        <?php if ($f_escr !== '') { ?>
                                            <?php echo $f_cpcv !== '' ? '<br>' : ''; ?>
                                            <i class="fa fa-file-text-o"></i> Escritura: <strong><?php echo html_escape($f_escr); ?></strong>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php if (!empty($calculo['cpcv_taxa']) || !empty($calculo['escritura_taxa'])) {
                                $pct_cpcv = $calculo['taxa'] > 0 ? round($calculo['cpcv_taxa'] / $calculo['taxa'] * 100) : 0;
                                $pct_escr = $calculo['taxa'] > 0 ? round($calculo['escritura_taxa'] / $calculo['taxa'] * 100) : 0;
                            ?>
                            <tr>
                                <td><strong>— No CPCV</strong></td>
                                <td>
                                    <?php echo app_format_money($calculo['valor_cpcv'], get_base_currency()); ?>
                                    <small class="text-muted">(<?php echo rtrim(rtrim(number_format((float) $calculo['cpcv_taxa'], 2, ',', ''), '0'), ','); ?>% da comissão)</small>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>— Na Escritura</strong></td>
                                <td>
                                    <?php echo app_format_money($calculo['valor_escritura'], get_base_currency()); ?>
                                    <small class="text-muted">(<?php echo rtrim(rtrim(number_format((float) $calculo['escritura_taxa'], 2, ',', ''), '0'), ','); ?>% da comissão)</small>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td><strong>Estado</strong></td>
                                <td>
                                    <?php
                                    $estados_comissao = [
                                        'na'        => 'Ainda não devida',
                                        'a_receber' => 'A receber',
                                        'recebida'  => 'Recebida',
                                    ];
                                    echo $estados_comissao[$venda['comissao_estado']] ?? $venda['comissao_estado'];
                                    ?>
                                </td>
                            </tr>
                        </table>
                        <?php if (!empty($calculo['reconciliado'])) { ?>
                            <div class="alert alert-warning">
                                <strong>Repartição ajustada.</strong>
                                A regra reparte <?php echo $calculo['soma_parciais']; ?>% (CPCV + Escritura),
                                mas a taxa desta venda é <?php echo $calculo['taxa']; ?>%. As parcelas foram
                                reproporcionadas para somarem exactamente a comissão da venda — corrija a
                                regra do empreendimento (ou a taxa da venda) para deixarem de divergir.
                                <?php if (is_admin() || staff_can('gerir_regras', 'dps_vendas')) { ?>
                                    <br><a href="<?php echo admin_url('dps_vendas/regras'); ?>">Rever regras</a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <?php if ($calculo['taxa'] <= 0) { ?>
                            <div class="alert alert-warning">
                                <strong>Sem taxa definida.</strong>
                                Não há regra de comissão para "<?php echo html_escape($venda['empreendimento']); ?>"
                                e a venda também não tem taxa própria. Esta venda não poderá passar a
                                <strong>Recebido</strong> enquanto isso não for resolvido.
                                <?php if (is_admin() || staff_can('gerir_regras', 'dps_vendas')) { ?>
                                    <br><a href="<?php echo admin_url('dps_vendas/regras'); ?>">Definir regra</a>
                                <?php } ?>
                            </div>
                        <?php } elseif ($venda['comissao_estado'] === 'na') { ?>
                            <p class="text-muted">
                                <small>A comissão é fixada quando a venda passa a <strong>Recebido</strong>.</small>
                            </p>
                        <?php } ?>
                    </div>
                </div>

                <?php if (is_admin()) { ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Mudar estado</h5>
                        <hr>
                        <?php echo form_open(admin_url('dps_vendas/change_status/' . $venda['id'])); ?>
                        <div class="form-group">
                            <select name="estado" class="form-control" required>
                                <option value="">Escolher...</option>
                                <?php foreach ($fluxo as $estado) { ?>
                                    <?php if ($estado !== $venda['estado']) { ?>
                                        <option value="<?php echo $estado; ?>">
                                            <?php echo dps_vendas_nome_estado($estado); ?>
                                        </option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <textarea name="nota" class="form-control" rows="2" placeholder="Nota (opcional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-info btn-block">Actualizar estado</button>
                        <?php echo form_close(); ?>
                        <p class="text-muted mtop10">
                            <small>
                                Escolha livremente o estado. A comissão é fixada em <strong>Vendido</strong>.
                            </small>
                        </p>
                    </div>
                </div>
                <?php } else { ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Estado</h5>
                        <hr>
                        <span class="label <?php echo dps_vendas_cor_estado($venda['estado']); ?>">
                            <?php echo dps_vendas_nome_estado($venda['estado']); ?>
                        </span>
                        <p class="text-muted mtop10">
                            <small>A confirmação e as mudanças de estado são feitas pela direção.</small>
                        </p>
                    </div>
                </div>
                <?php } ?>

                <?php
                /*
                 * CPCV do Aura. Só aparece neste empreendimento: o modelo é do
                 * Meixomil, com a FAMIMAR como vendedora e cláusulas próprias.
                 * Os outros têm contratos diferentes e mostrar-lhes um botão
                 * que gera o contrato errado seria pior do que não ter botão.
                 */
                if (stripos((string) $venda['empreendimento'], 'aura') !== false) {
                    $falta_cpcv = [];
                    foreach ([
                        'cliente_nif' => 'NIF', 'cliente_cc' => 'n.º CC',
                        'cliente_cc_validade' => 'validade CC',
                        'cliente_naturalidade' => 'naturalidade',
                        'cliente_nacionalidade' => 'nacionalidade',
                        'cliente_freguesia' => 'freguesia', 'cliente_concelho' => 'concelho',
                    ] as $k => $et) {
                        if (trim((string) ($venda[$k] ?? '')) === '') { $falta_cpcv[] = $et; }
                    }
                    ?>
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="no-margin"><i class="fa fa-file-word-o"></i> Contrato-promessa (CPCV)</h5>
                            <hr>
                            <?php if ($falta_cpcv) { ?>
                                <?php
                                /*
                                 * A reserva não pede estes dados de propósito: pedia-os e travava
                                 * o negócio no simulador por faltar, por exemplo, a freguesia do
                                 * comprador. Recolhem-se aqui, com calma, antes de gerar o
                                 * contrato — que é quando fazem falta a sério.
                                 */
                                ?>
                                <p class="text-muted">
                                    Falta preencher: <strong><?php echo html_escape(implode(', ', $falta_cpcv)); ?></strong>.
                                    <br>Estes dados não são pedidos na reserva — preenchem-se aqui,
                                    antes de gerar o contrato.
                                </p>
                                <a href="<?php echo admin_url('dps_vendas/form/' . (int) $venda['id']); ?>"
                                   class="btn btn-default">
                                    <i class="fa fa-pencil"></i> Preencher os dados do comprador
                                </a>
                            <?php } else { ?>
                                <p class="text-muted">
                                    Gera o contrato em Word já preenchido com os dados do comprador e o plano
                                    de pagamento calculado do preço da fracção.
                                    <br><strong>Fica por preencher</strong> o IBAN do comprador e a fracção
                                    (letra, tipologia e piso) — estão assinalados no documento.
                                </p>
                                <?php
                                /*
                                 * Um botão só, e só para a direção.
                                 *
                                 * Os dois documentos andam juntos — a declaração de cessão é o
                                 * que permite ao comprador ceder a posição antes da escritura,
                                 * e assina-se com o contrato. Descarregá-los à peça obrigava a
                                 * lembrar-se do segundo.
                                 *
                                 * Regra do dono (31/07/2026): passam pela direção antes de
                                 * seguirem para o cliente. Saem com espaços por preencher (o
                                 * IBAN, a fracção) e um contrato-promessa enviado com
                                 * «PREENCHER» no meio é pior do que não enviar nada.
                                 */
                                /*
                                 * Quem fechou o negócio descarrega na hora. Esteve reservado
                                 * à direção e travava o trabalho: os documentos são precisos
                                 * no momento, para completar o que falta e mandar ao cliente.
                                 * Continuam a sair em Word e por rever — quem descarrega é
                                 * quem os revê.
                                 */
                                if (is_admin() || (int) $venda['staff_id'] === (int) get_staff_user_id()) { ?>
                                    <a href="<?php echo admin_url('dps_vendas/documentos_aura/' . (int) $venda['id']); ?>"
                                       class="btn btn-info">
                                        <i class="fa fa-download"></i> CPCV + Declaração de cessão (ZIP)
                                    </a>
                                    <p class="text-muted mtop10" style="font-size:.85em;">
                                        Ficam por preencher o <strong>IBAN do comprador</strong> e a
                                        <strong>fracção</strong> (letra, tipologia e piso) — estão assinalados
                                        nos dois documentos. Reveja antes de enviar ao cliente.
                                    </p>
                                <?php } else { ?>
                                    <p class="text-muted">
                                        Os documentos são descarregados pelo comercial da venda ou pela direcção.
                                    </p>
                                <?php } ?>
                                <p class="text-muted mtop10" style="font-size:.85em;">
                                    É um rascunho. Reveja antes de enviar seja a quem for.
                                </p>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>

                <?php
                /*
                 * O comercial da venda também envia ao promotor. Depois do CPCV
                 * assinado e do comprovativo carregados, quem tem o processo na
                 * mão é quem o deve mandar seguir — esperar pela direcção só
                 * atrasava. O email leva todos os documentos da venda em anexo.
                 */
                if (is_admin() || (int) $venda['staff_id'] === (int) get_staff_user_id()) { ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Enviar ao promotor</h5>
                        <hr>
                        <?php echo form_open(admin_url('dps_vendas/enviar_email/' . $venda['id'])); ?>
                        <div class="form-group">
                            <label class="control-label">Email do destinatário</label>
                            <input type="email" name="email_para" class="form-control" placeholder="promotor@exemplo.pt" required>
                            <small class="text-muted">
                                Seguem em anexo <strong>todos os documentos já carregados nesta venda</strong>
                                — o CPCV assinado e o comprovativo de pagamento entram sozinhos assim que
                                existirem.
                            </small>
                        </div>
                        <div class="form-group">
                            <textarea name="email_mensagem" class="form-control" rows="2" placeholder="Mensagem (opcional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-info btn-block">
                            <i class="fa fa-envelope"></i> Enviar reserva por email
                        </button>
                        <?php echo form_close(); ?>
                        <p class="text-muted mtop10">
                            <small>Envia os dados do cliente e anexa os documentos (incl. Cartão de Cidadão).</small>
                        </p>
                    </div>
                </div>
                <?php } ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Histórico</h5>
                        <hr>
                        <?php if (empty($historico)) { ?>
                            <p class="text-muted">Sem movimentos.</p>
                        <?php } else { ?>
                            <ul class="list-unstyled">
                                <?php foreach ($historico as $h) { ?>
                                    <li class="mbot10">
                                        <strong><?php echo dps_vendas_nome_estado($h['estado_para']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo $h['estado_de'] ? 'de ' . dps_vendas_nome_estado($h['estado_de']) . ' · ' : ''; ?>
                                            <?php echo html_escape($h['staff_nome']); ?> ·
                                            <?php echo _dt($h['dateadded']); ?>
                                        </small>
                                        <?php if (!empty($h['nota'])) { ?>
                                            <br><em><?php echo html_escape($h['nota']); ?></em>
                                        <?php } ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
<?php init_tail(); ?>
