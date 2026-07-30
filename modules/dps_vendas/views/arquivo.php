<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <h4 class="no-margin"><i class="fa fa-folder-open"></i> Arquivo</h4>
                        <p class="text-muted" style="font-size:12px;">
                            Documentos de cada empreendimento — dossiers, tabelas, plantas, contratos.
                            Escolha o empreendimento e descarregue o que precisar.
                        </p>

                        <hr class="hr-panel-heading">

                        <?php // Botões dos empreendimentos ?>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;">
                            <?php foreach ($pastas as $p) { ?>
                            <a href="<?php echo admin_url('dps_vendas/arquivo/' . (int) $p['id']); ?>"
                               class="btn <?php echo ((int) $p['id'] === (int) $pasta_id) ? 'btn-info' : 'btn-default'; ?>">
                                <i class="fa fa-building-o"></i> <?php echo html_escape($p['nome']); ?>
                            </a>
                            <?php } ?>

                            <?php if (is_admin()) { ?>
                            <?php echo form_open(admin_url('dps_vendas/arquivo_pasta'), ['style' => 'display:flex;gap:6px;align-items:center;']); ?>
                                <input type="text" name="nome" class="form-control" placeholder="Novo empreendimento…"
                                       style="width:200px;" maxlength="150">
                                <button type="submit" class="btn btn-default"><i class="fa fa-plus"></i></button>
                            <?php echo form_close(); ?>
                            <?php } ?>
                        </div>

                        <?php if (empty($pastas)) { ?>
                            <p class="text-muted">Ainda não há empreendimentos no Arquivo.</p>
                        <?php } else { ?>

                            <?php if (is_admin()) { ?>
                            <div class="panel_s" style="background:#f8f9fb;border:1px solid #e3e7ec;">
                                <div class="panel-body" style="padding:12px 15px;">
                                    <?php echo form_open_multipart(admin_url('dps_vendas/arquivo_upload'), ['style' => 'display:flex;flex-wrap:wrap;gap:8px;align-items:center;']); ?>
                                        <input type="hidden" name="pasta_id" value="<?php echo (int) $pasta_id; ?>">
                                        <strong style="white-space:nowrap;"><i class="fa fa-upload"></i> Adicionar documento:</strong>
                                        <input type="file" name="ficheiro" required
                                               style="max-width:260px;"
                                               accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.mp4">
                                        <input type="text" name="nome" class="form-control" maxlength="191"
                                               placeholder="Nome a mostrar (opcional)" style="width:240px;">
                                        <button type="submit" class="btn btn-info">Carregar</button>
                                        <span class="text-muted" style="font-size:11px;">
                                            PDF, imagens, Office, ZIP ou MP4 · máx. <?php echo html_escape(ini_get('upload_max_filesize')); ?>
                                        </span>
                                    <?php echo form_close(); ?>
                                </div>
                            </div>
                            <?php } ?>

                            <?php if (empty($docs)) { ?>
                                <p class="text-muted" style="margin-top:15px;">
                                    <i class="fa fa-inbox"></i> Esta pasta ainda não tem documentos.
                                </p>
                            <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-striped" style="margin-top:5px;">
                                    <thead>
                                        <tr>
                                            <th>Documento</th>
                                            <th style="width:110px;">Tamanho</th>
                                            <th style="width:130px;">Adicionado</th>
                                            <th style="width:160px;">Por</th>
                                            <th style="width:170px;" class="text-right">&nbsp;</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($docs as $d) {
                                            $kb = (int) $d['tamanho'];
                                            if ($kb >= 1048576) {
                                                $tam = number_format($kb / 1048576, 1, ',', '.') . ' MB';
                                            } elseif ($kb > 0) {
                                                $tam = number_format($kb / 1024, 0, ',', '.') . ' KB';
                                            } else {
                                                $tam = '—';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <i class="fa fa-file-o text-muted"></i>
                                                <?php echo html_escape($d['nome']); ?>
                                            </td>
                                            <td><?php echo $tam; ?></td>
                                            <td><?php echo _dt($d['created_at']); ?></td>
                                            <td><?php echo html_escape($d['quem'] ?: '—'); ?></td>
                                            <td class="text-right">
                                                <a href="<?php echo admin_url('dps_vendas/arquivo_download/' . (int) $d['id']); ?>"
                                                   class="btn btn-info btn-xs">
                                                    <i class="fa fa-download"></i> Download
                                                </a>
                                                <?php if (is_admin()) { ?>
                                                <?php // POST com token CSRF — apagar por GET era vulnerável a links forjados ?>
                                                <?php echo form_open(admin_url('dps_vendas/arquivo_apagar/' . (int) $d['id']), ['style' => 'display:inline;', 'onsubmit' => 'return confirm(\'Apagar «' . html_escape(addslashes($d['nome'])) . '»?\');']); ?>
                                                    <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                                <?php echo form_close(); ?>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php } ?>

                            <?php if (is_admin() && $pasta_id && empty($docs)) { ?>
                            <?php echo form_open(admin_url('dps_vendas/arquivo_pasta_apagar/' . (int) $pasta_id), ['style' => 'display:inline-block;margin-top:10px;', 'onsubmit' => "return confirm('Remover esta pasta vazia?');"]); ?>
                                <button type="submit" class="btn btn-default btn-xs">
                                    <i class="fa fa-trash-o"></i> Remover esta pasta
                                </button>
                            <?php echo form_close(); ?>
                            <?php } ?>

                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
