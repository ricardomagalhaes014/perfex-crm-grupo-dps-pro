<?php

defined('BASEPATH') or exit('No direct script access allowed');

# Navegacao
$lang['dps_moloni']              = 'Moloni';
$lang['dps_moloni_financeiro']   = 'Gestao financeira';
$lang['dps_moloni_conciliacao']  = 'Conciliacao';
$lang['dps_moloni_entidades']    = 'Entidades';
$lang['dps_moloni_definicoes']   = 'Ligacao Moloni';
$lang['dps_moloni_mapeamento']   = 'Fonte de dados';
$lang['dps_moloni_logs']         = 'Registo de chamadas';
$lang['dps_moloni_emitir']       = 'Emitir documento';

# Estado
$lang['dps_moloni_connected']    = 'Ligado';
$lang['dps_moloni_disconnected'] = 'Por ligar';

# Definicoes
$lang['dps_moloni_settings_intro']    = 'Credenciais da area de programador da Moloni. Ficam cifradas na base de dados e nunca aparecem nos registos.';
$lang['dps_moloni_credentials']       = 'Credenciais da API';
$lang['dps_moloni_dev_id']            = 'Developer ID (Client ID)';
$lang['dps_moloni_client_secret']     = 'Client Secret';
$lang['dps_moloni_stored_leave_blank'] = 'Guardado — deixa em branco para manter';
$lang['dps_moloni_username']          = 'Email da conta Moloni';
$lang['dps_moloni_password']          = 'Password da conta Moloni';
$lang['dps_moloni_company']           = 'Empresa';
$lang['dps_moloni_company_id']        = 'Empresa (Company ID)';
$lang['dps_moloni_company_hint']      = 'Grava as credenciais e carrega em Testar ligacao para escolheres a empresa de uma lista.';
$lang['dps_moloni_choose']            = '— escolher —';

$lang['dps_moloni_series']            = 'Series de documentos';
$lang['dps_moloni_set_invoice']       = 'Serie para faturas ao promotor';
$lang['dps_moloni_set_invoice_hint']  = 'Usada quando faturas a comissao que a DPS recebe.';
$lang['dps_moloni_set_receipt']       = 'Serie para documentos de comissao';
$lang['dps_moloni_set_receipt_hint']  = 'Usada nos documentos ligados a comissao dos comerciais.';

$lang['dps_moloni_tax_invoice']       = 'Taxa de IVA — faturas ao promotor';
$lang['dps_moloni_tax_receipt']       = 'Taxa de IVA — comissoes';
$lang['dps_moloni_no_tax']            = 'Sem taxa (isento)';
$lang['dps_moloni_exemption_reason']  = 'Codigo de isencao';
$lang['dps_moloni_exemption_hint']    = 'Obrigatorio quando a taxa e zero. Ex.: M07 para o artigo 9.o do CIVA. Confirma o codigo com a tua contabilidade.';

$lang['dps_moloni_product']           = 'Artigo usado nas linhas';
$lang['dps_moloni_free_line']         = 'Linha livre (sem artigo)';
$lang['dps_moloni_product_hint']      = 'Se a Moloni exigir artigo, escolhe aqui um artigo de servico.';

$lang['dps_moloni_document_classes']  = 'Tipos de documento';
$lang['dps_moloni_class_invoice']     = 'Classe API para faturas';
$lang['dps_moloni_class_receipt']     = 'Classe API para comissoes';
$lang['dps_moloni_class_hint']        = 'Nomes das classes da API Moloni: invoices, invoiceReceipts, simplifiedInvoices, receipts.';

$lang['dps_moloni_always_draft']      = 'Criar sempre em rascunho';
$lang['dps_moloni_always_draft_hint'] = 'Recomendado. Os documentos ficam por fechar na Moloni ate os validares manualmente.';
$lang['dps_moloni_auto_create_customers'] = 'Criar clientes em falta na Moloni';

$lang['dps_moloni_test']              = 'Testar ligacao';
$lang['dps_moloni_test_ok']           = 'Ligacao Moloni OK. Empresas:';
$lang['dps_moloni_test_failed']       = 'A ligacao falhou.';
$lang['dps_moloni_settings_saved']    = 'Definicoes Moloni guardadas.';

$lang['dps_moloni_howto']             = 'Como obter';
$lang['dps_moloni_howto_1']           = 'Em moloni.pt vai a Area de Programador / API e activa a API. Copia o Developer ID e o Client Secret.';
$lang['dps_moloni_howto_2']           = 'No campo URI de Resposta (Callback) coloca:';
$lang['dps_moloni_howto_3']           = 'Depois grava aqui as credenciais com o email e a password da conta e carrega em Testar ligacao.';
$lang['dps_moloni_security']          = 'Seguranca';
$lang['dps_moloni_security_note']     = 'O Client Secret e a password sao guardados cifrados e substituidos por *** em todos os registos de chamadas.';

# Mapeamento
$lang['dps_moloni_mapping_intro']     = 'Indica onde estao as vendas e comissoes no CRM. O modulo le estas colunas sem alterar a estrutura existente.';
$lang['dps_moloni_src_table']         = 'Tabela das vendas';
$lang['dps_moloni_suggested_tables']  = 'Sugeridas';
$lang['dps_moloni_all_tables']        = 'Todas as tabelas';
$lang['dps_moloni_autodetect']        = 'Detectar colunas automaticamente';
$lang['dps_moloni_mapping_saved']     = 'Mapeamento guardado.';
$lang['dps_moloni_mapping_missing']   = 'A fonte de dados ainda nao esta configurada.';
$lang['dps_moloni_preview']           = 'Pre-visualizacao';
$lang['dps_moloni_preview_hint']      = 'Se os valores acima baterem certo com o Painel do Negocio, o mapeamento esta correcto.';

$lang['dps_moloni_col_id']             = 'Coluna do ID';
$lang['dps_moloni_col_project']        = 'Empreendimento';
$lang['dps_moloni_col_unit']           = 'Unidade';
$lang['dps_moloni_col_client']         = 'Cliente';
$lang['dps_moloni_col_commercial']     = 'Comercial';
$lang['dps_moloni_col_sale_value']     = 'Valor da venda';
$lang['dps_moloni_col_commission']     = 'Comissao ao comercial';
$lang['dps_moloni_col_received']       = 'Comissao recebida';
$lang['dps_moloni_col_receipt_flag']   = 'Recibo emitido (0/1)';
$lang['dps_moloni_col_receipt_number'] = 'Numero do recibo';
$lang['dps_moloni_col_date']           = 'Data';

# Conciliacao
$lang['dps_moloni_reconcile_intro'] = 'Traz os documentos da Moloni e propoe a que linha de venda pertencem. Nada e gravado sem a tua confirmacao.';
$lang['dps_moloni_from']            = 'De';
$lang['dps_moloni_to']              = 'Ate';
$lang['dps_moloni_load']            = 'Carregar';
$lang['dps_moloni_suggestions']     = 'Sugestoes de conciliacao';
$lang['dps_moloni_confidence']      = 'Confianca';
$lang['dps_moloni_sale']            = 'Venda';
$lang['dps_moloni_document']        = 'Documento';
$lang['dps_moloni_entity']          = 'Entidade';
$lang['dps_moloni_doc_value']       = 'Valor do documento';
$lang['dps_moloni_reason']          = 'Motivo';
$lang['dps_moloni_write_back']      = 'Escrever o numero do documento na linha de venda';
$lang['dps_moloni_apply']           = 'Aplicar seleccionados';
$lang['dps_moloni_no_suggestions']  = 'Sem sugestoes para este periodo.';
$lang['dps_moloni_all_documents']   = 'Documentos na Moloni';
$lang['dps_moloni_nothing_selected'] = 'Nao seleccionaste nada.';
$lang['dps_moloni_reconciled_n']    = '%d conciliacoes aplicadas.';
$lang['dps_moloni_link_removed']    = 'Ligacao removida.';

$lang['dps_moloni_conf_certain']    = 'Certeza';
$lang['dps_moloni_conf_high']       = 'Alta';
$lang['dps_moloni_conf_medium']     = 'Media';

# Documentos
$lang['dps_moloni_date']    = 'Data';
$lang['dps_moloni_vat']     = 'NIF';
$lang['dps_moloni_net'] = 'Base (s/IVA)';
$lang['dps_moloni_gross'] = 'Total';
$lang['dps_moloni_status']  = 'Estado';
$lang['dps_moloni_draft']   = 'Rascunho';
$lang['dps_moloni_closed']  = 'Fechado';

# Emissao
$lang['dps_moloni_issue_invoice']       = 'Emitir fatura ao promotor';
$lang['dps_moloni_issue_receipt']       = 'Emitir documento de comissao';
$lang['dps_moloni_issue_invoice_short'] = 'Fatura';
$lang['dps_moloni_issue_receipt_short'] = 'Comissao';
$lang['dps_moloni_draft_notice']        = 'Este documento sera criado em rascunho na Moloni. Fechas manualmente depois de confirmares.';
$lang['dps_moloni_vat_hint']            = 'O NIF e usado para encontrar ou criar o cliente na Moloni.';
$lang['dps_moloni_description']         = 'Descricao da linha';
$lang['dps_moloni_amount']              = 'Valor';
$lang['dps_moloni_expiration']          = 'Vencimento';
$lang['dps_moloni_set']                 = 'Serie';
$lang['dps_moloni_tax']                 = 'Taxa de IVA';
$lang['dps_moloni_notes']               = 'Observacoes';
$lang['dps_moloni_close_document']      = 'Fechar o documento imediatamente';
$lang['dps_moloni_close_hint']          = 'Um documento fechado na Moloni nao pode ser alterado nem apagado.';
$lang['dps_moloni_create_in_moloni']    = 'Criar na Moloni';
$lang['dps_moloni_default_line']        = 'Comissao de intermediacao imobiliaria';
$lang['dps_moloni_draft_created']       = 'Rascunho criado na Moloni.';
$lang['dps_moloni_document_created']    = 'Documento emitido na Moloni.';
$lang['dps_moloni_issue_failed']        = 'Nao foi possivel criar o documento.';
$lang['dps_moloni_invalid_amount']      = 'O valor tem de ser maior que zero.';
$lang['dps_moloni_no_set']              = 'Escolhe a serie de documentos.';
$lang['dps_moloni_sale_not_found']      = 'Linha de venda nao encontrada.';
$lang['dps_moloni_customer_failed']     = 'Nao foi possivel encontrar nem criar o cliente na Moloni.';

# Entidades
$lang['dps_moloni_entities_intro']   = 'Liga os NIF do CRM aos clientes da Moloni para nao se criarem duplicados na emissao.';
$lang['dps_moloni_find_and_link']    = 'Procurar e ligar';
$lang['dps_moloni_mapped_entities']  = 'Entidades ligadas';
$lang['dps_moloni_no_entities']      = 'Ainda nao ha entidades ligadas.';
$lang['dps_moloni_synced']           = 'Sincronizado';
$lang['dps_moloni_entity_linked']    = 'Entidade ligada ao cliente Moloni';
$lang['dps_moloni_entity_not_found'] = 'Nao existe nenhum cliente com esse NIF na Moloni.';
$lang['dps_moloni_invalid_vat']      = 'NIF invalido.';
$lang['dps_moloni_unlink']           = 'Remover ligacao';
$lang['dps_moloni_unlink_confirm']   = 'Remover esta ligacao?';

# Painel financeiro
$lang['dps_moloni_kpi_sales']         = 'Volume de vendas';
$lang['dps_moloni_kpi_lines']         = 'operacoes';
$lang['dps_moloni_kpi_received']      = 'Comissoes recebidas';
$lang['dps_moloni_kpi_from_promoter'] = 'do promotor';
$lang['dps_moloni_kpi_due']           = 'Comissoes a comerciais';
$lang['dps_moloni_kpi_to_agents']     = 'a pagar / pagas';
$lang['dps_moloni_kpi_result']        = 'Resultado';
$lang['dps_moloni_kpi_with_docs']     = 'com documento';
$lang['dps_moloni_by_project']        = 'Por empreendimento';
$lang['dps_moloni_by_agent']          = 'Por comercial';
$lang['dps_moloni_operations']        = 'Operacoes';
$lang['dps_moloni_documents']         = 'Documentos Moloni';
$lang['dps_moloni_actions']           = 'Accoes';

# Logs
$lang['dps_moloni_logs_intro']         = 'Ultimas chamadas a API. Util para perceber respostas de erro da Moloni.';
$lang['dps_moloni_endpoint']           = 'Endpoint';
$lang['dps_moloni_message']            = 'Mensagem';
$lang['dps_moloni_detail']             = 'Detalhe';
$lang['dps_moloni_request']            = 'Pedido';
$lang['dps_moloni_response']           = 'Resposta';
$lang['dps_moloni_clear_logs']         = 'Limpar registos';
$lang['dps_moloni_clear_logs_confirm'] = 'Apagar todos os registos?';
$lang['dps_moloni_logs_cleared']       = 'Registos apagados.';
$lang['dps_moloni_pdf_failed']         = 'Nao foi possivel obter o PDF.';

# Erros
$lang['dps_moloni_err_no_credentials'] = 'Faltam credenciais da Moloni.';
$lang['dps_moloni_err_no_company']     = 'Escolhe primeiro a empresa Moloni nas definicoes.';
$lang['dps_moloni_err_bad_json']       = 'Resposta invalida da Moloni:';
$lang['dps_moloni_skipped_n'] = '%d ignoradas (documento ja conciliado).';
$lang['dps_moloni_write_back_failed'] = 'Nao foi possivel escrever o numero na linha #%d.';
$lang['dps_moloni_commercial_is_staff'] = 'A coluna do comercial guarda o ID do colaborador (procurar o nome na tabela de staff)';
$lang['dps_moloni_overlay'] = 'Tabela sobreposta (campos editaveis)';
$lang['dps_moloni_overlay_intro'] = 'Se o painel guarda a comissao recebida e o recibo numa tabela a parte, ligada a das vendas por uma chave, indica-a aqui. Deixa vazio se estiver tudo na mesma tabela.';
$lang['dps_moloni_overlay_table'] = 'Tabela sobreposta';
$lang['dps_moloni_overlay_none'] = 'nenhuma';
$lang['dps_moloni_overlay_fk'] = 'Coluna que aponta para o ID da venda';
$lang['dps_moloni_overlay_doc'] = 'Coluna do ID do documento Moloni';
$lang['dps_moloni_import'] = 'Importar credenciais ja guardadas no CRM';
$lang['dps_moloni_import_hint'] = 'Se ja configuraste a Moloni noutro modulo desta instalacao, nao precisas de reescrever nada:';
$lang['dps_moloni_import_ok'] = 'Credenciais importadas —';
$lang['dps_moloni_import_none'] = 'Nao foram encontradas credenciais Moloni noutro sitio da instalacao.';
$lang['dps_moloni_promoters'] = 'Promotores por empreendimento';
$lang['dps_moloni_promoters_intro'] = 'O NIF do promotor e o sinal mais fiavel da conciliacao. Nomes parecidos enganam; um NIF nao.';
$lang['dps_moloni_promoter_vat'] = 'NIF do promotor';
$lang['dps_moloni_promoter_saved'] = 'Promotor guardado.';
$lang['dps_moloni_paid'] = 'Pago';
$lang['dps_moloni_unpaid'] = 'Por liquidar';
$lang['dps_moloni_kpi_invoiced_open'] = 'facturado por receber';
$lang['dps_moloni_recalc'] = 'Recalcular recebido';
$lang['dps_moloni_recalc_hint'] = 'Repoe a comissao recebida a partir dos documentos pagos (Fatura-Recibo). Uma Fatura por liquidar nao conta.';
$lang['dps_moloni_recalc_done'] = '%d linhas actualizadas. Total recebido: %s';
$lang['dps_moloni_overrides'] = 'Comissoes de override';
$lang['dps_moloni_overrides_intro'] = 'Percentagem que alguem recebe sobre as vendas da equipa toda, alem da sua propria comissao. Excluem-se os comerciais cujas vendas nao entram na base.';
$lang['dps_moloni_override_who'] = 'Quem recebe';
$lang['dps_moloni_override_rate'] = 'Percentagem (ex.: 0,5)';
$lang['dps_moloni_override_excluded'] = 'Vendas a excluir da base';
$lang['dps_moloni_override_excluded_hint'] = 'Comerciais cujas vendas nao contam — as que ficam na empresa, ou quem recebe a 100% e emite recibo proprio.';
$lang['dps_moloni_override_note'] = 'Nota';
$lang['dps_moloni_override_active'] = 'Activo';
$lang['dps_moloni_override_base'] = 'Base de calculo';
$lang['dps_moloni_override_amount'] = 'Valor';
$lang['dps_moloni_override_total'] = 'Total de overrides';
$lang['dps_moloni_override_excluding'] = 'excluindo:';
$lang['dps_moloni_override_skipped'] = 'excluidas';
$lang['dps_moloni_override_short'] = 'sobre a carteira';
$lang['dps_moloni_overrides_current'] = 'Em vigor';
$lang['dps_moloni_overrides_none'] = 'Ainda nao ha overrides definidos.';
$lang['dps_moloni_overrides_inactive'] = 'Inactivos (nao entram nos calculos):';
$lang['dps_moloni_override_saved'] = 'Override guardado.';
$lang['dps_moloni_override_invalid'] = 'Indica o comercial e uma percentagem maior que zero.';
$lang['dps_moloni_kpi_includes_overrides'] = 'inclui overrides de';
