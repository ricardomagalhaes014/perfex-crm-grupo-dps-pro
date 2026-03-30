<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dps-status-bar { padding:12px 20px; border-radius:6px; margin-bottom:20px; }
.dps-status-pendente { background:#fff3cd; border:1px solid #ffc107; }
.dps-status-aprovado { background:#d4edda; border:1px solid #28a745; }
.dps-status-rejeitado { background:#f8d7da; border:1px solid #dc3545; }
.dps-status-arquivado { background:#e2e3e5; border:1px solid #6c757d; }
.dps-info-label { font-weight:bold; color:#555; font-size:12px; text-transform:uppercase; letter-spacing:.5px; }
.dps-info-value { font-size:15px; margin-bottom:12px; }
.dps-doc-link { display:inline-block; padding:6px 14px; background:#f0f0f0; border:1px solid #ccc; border-radius:4px; text-decoration:none; color:#333; margin-right:8px; margin-bottom:8px; }
.dps-doc-link:hover { background:#e0e0e0; }
.foto-thumb { width:100px; height:80px; object-fit:cover; border-radius:4px; border:2px solid #ddd; margin:4px; cursor:pointer; transition:border-color .2s; }
.foto-thumb:hover { border-color:#337ab7; }
.foto-principal-img { width:100%; max-height:350px; object-fit:cover; border-radius:8px; border:3px solid #5cb85c; }
</style>
<div id="wrapper">
<div class="content">
<div class="row">
  <div class="col-md-12">

    <!-- BREADCRUMB -->
    <ol class="breadcrumb">
      <li><a href="<?php echo admin_url('dps_imoveis'); ?>">DPS Imóveis</a></li>
      <li class="active"><?php echo htmlspecialchars($imovel['titulo']); ?></li>
    </ol>

    <!-- BARRA DE STATUS -->
    <div class="dps-status-bar dps-status-<?php echo $imovel['status']; ?>">
      <div class="row">
        <div class="col-md-8">
          <?php
          $icons = ['pendente'=>'fa-clock-o text-warning','aprovado'=>'fa-check-circle text-success','rejeitado'=>'fa-times-circle text-danger','arquivado'=>'fa-archive text-muted'];
          $icon = $icons[$imovel['status']] ?? 'fa-question';
          ?>
          <i class="fa <?php echo $icon; ?> fa-lg"></i>
          <strong>Estado: <?php echo ucfirst($imovel['status']); ?></strong>
          <?php if($imovel['published_website']): ?>
          &nbsp;&nbsp;<span class="label label-success"><i class="fa fa-globe"></i> Publicado em dpsimobiliario.pt</span>
          <?php endif; ?>
          <?php if(!empty($imovel['notas_aprovacao'])): ?>
          <br/><small class="text-muted mtop5"><i class="fa fa-comment"></i> <?php echo htmlspecialchars($imovel['notas_aprovacao']); ?></small>
          <?php endif; ?>
        </div>
        <div class="col-md-4 text-right">
          <?php if($pode_aprovar && $imovel['status'] == 'pendente'): ?>
          <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-aprovar">
            <i class="fa fa-check"></i> Aprovar e Publicar
          </button>
          <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-rejeitar">
            <i class="fa fa-times"></i> Rejeitar
          </button>
          <?php endif; ?>
          <?php if($pode_aprovar && $imovel['status'] == 'aprovado' && $imovel['published_website']): ?>
          <a href="<?php echo admin_url('dps_imoveis/despublicar/'.$imovel['id']); ?>" class="btn btn-warning btn-sm" onclick="return confirm('Retirar do site?')">
            <i class="fa fa-eye-slash"></i> Retirar do Site
          </a>
          <?php endif; ?>
          <?php if($pode_aprovar && $imovel['status'] == 'rejeitado'): ?>
          <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-aprovar">
            <i class="fa fa-check"></i> Aprovar mesmo assim
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- COLUNA ESQUERDA: Fotos -->
      <div class="col-md-5">
        <?php if(!empty($imovel['foto_principal'])): ?>
        <img src="<?php echo base_url($imovel['foto_principal']); ?>" class="foto-principal-img mbottom10" />
        <?php else: ?>
        <div style="background:#f5f5f5;height:250px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#aaa;margin-bottom:10px;">
          <i class="fa fa-home fa-4x"></i>
        </div>
        <?php endif; ?>

        <?php if(!empty($imovel['fotos_array'])): ?>
        <div id="galeria-thumbs">
          <?php foreach($imovel['fotos_array'] as $foto): ?>
          <img src="<?php echo base_url($foto); ?>" class="foto-thumb" onclick="document.querySelector('.foto-principal-img').src=this.src" />
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- COLUNA DIREITA: Informações -->
      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-body">
            <h3 class="mtop0"><?php echo htmlspecialchars($imovel['titulo']); ?></h3>
            <h4 class="text-success"><?php echo $imovel['preco'] ? number_format($imovel['preco'],0,',','.').' €' : 'Preço sob consulta'; ?></h4>

            <div class="row mtop15">
              <div class="col-md-4">
                <div class="dps-info-label">Tipo</div>
                <div class="dps-info-value"><span class="label label-default"><?php echo $imovel['tipo']; ?></span> <?php if($imovel['tipologia']): ?><span class="label label-info"><?php echo $imovel['tipologia']; ?></span><?php endif; ?></div>
              </div>
              <div class="col-md-4">
                <div class="dps-info-label">Localização</div>
                <div class="dps-info-value"><?php echo htmlspecialchars(($imovel['cidade'] ?? '') . ($imovel['distrito'] ? ', '.$imovel['distrito'] : '')); ?></div>
              </div>
              <div class="col-md-4">
                <div class="dps-info-label">Área Total</div>
                <div class="dps-info-value"><?php echo $imovel['area_total'] ? $imovel['area_total'].' m²' : '-'; ?></div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3">
                <div class="dps-info-label"><i class="fa fa-bed"></i> Quartos</div>
                <div class="dps-info-value"><?php echo $imovel['nr_quartos'] ?? 0; ?> <?php echo $imovel['nr_suites'] ? '('.$imovel['nr_suites'].' suítes)' : ''; ?></div>
              </div>
              <div class="col-md-3">
                <div class="dps-info-label"><i class="fa fa-bath"></i> WC</div>
                <div class="dps-info-value"><?php echo $imovel['nr_casas_banho'] ?? 0; ?></div>
              </div>
              <div class="col-md-3">
                <div class="dps-info-label"><i class="fa fa-car"></i> Garagem</div>
                <div class="dps-info-value"><?php echo $imovel['garagem'] ? 'Sim' : 'Não'; ?></div>
              </div>
              <div class="col-md-3">
                <div class="dps-info-label">Ano</div>
                <div class="dps-info-value"><?php echo $imovel['ano_construcao'] ?? '-'; ?></div>
              </div>
            </div>

            <?php if(!empty($imovel['equipamento'])): ?>
            <div class="dps-info-label">Equipamento</div>
            <div class="dps-info-value"><?php echo nl2br(htmlspecialchars($imovel['equipamento'])); ?></div>
            <?php endif; ?>

            <?php if(!empty($imovel['texto_livre'])): ?>
            <div class="dps-info-label">Descrição</div>
            <div class="dps-info-value"><?php echo nl2br(htmlspecialchars($imovel['texto_livre'])); ?></div>
            <?php endif; ?>

            <!-- AGENTE -->
            <hr/>
            <div class="dps-info-label">Agente Responsável</div>
            <div class="dps-info-value">
              <?php if(!empty($imovel['agente_foto'])): ?>
              <img src="<?php echo base_url('uploads/staff_profile_images/'.$imovel['agente_foto']); ?>" style="width:32px;height:32px;border-radius:50%;margin-right:8px;" />
              <?php endif; ?>
              <?php echo htmlspecialchars($imovel['agente_nome'] ?? '-'); ?>
              <?php if(!empty($imovel['agente_telefone'])): ?>
              &nbsp;<small class="text-muted"><?php echo $imovel['agente_telefone']; ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DADOS PRIVADOS — visíveis para o agente responsável, aprovadores e admin -->
    <?php if($pode_aprovar || $imovel['agente_id'] == get_staff_user_id()): ?>
    <div class="panel_s">
      <div class="panel-body">
        <h5><i class="fa fa-lock text-warning"></i> Dados Privados do Proprietário</h5>
        <div class="row">
          <div class="col-md-4">
            <div class="dps-info-label">Nome</div>
            <div class="dps-info-value"><?php echo htmlspecialchars($imovel['nome_proprietarios'] ?? '-'); ?></div>
          </div>
          <div class="col-md-4">
            <div class="dps-info-label">Contacto</div>
            <div class="dps-info-value"><?php echo htmlspecialchars($imovel['contacto_proprietario'] ?? '-'); ?></div>
          </div>
          <div class="col-md-4">
            <div class="dps-info-label">Email</div>
            <div class="dps-info-value"><?php echo htmlspecialchars($imovel['mail_proprietario'] ?? '-'); ?></div>
          </div>
        </div>

        <h5><i class="fa fa-file-pdf-o text-danger"></i> Documentos</h5>
        <div>
          <?php if(!empty($imovel['doc_cmi'])): ?>
          <a href="<?php echo base_url($imovel['doc_cmi']); ?>" target="_blank" class="dps-doc-link"><i class="fa fa-file-pdf-o"></i> CMI</a>
          <?php else: ?><span class="text-muted mright10">CMI: não enviado</span><?php endif; ?>

          <?php if(!empty($imovel['doc_cc_proprietarios'])): ?>
          <a href="<?php echo base_url($imovel['doc_cc_proprietarios']); ?>" target="_blank" class="dps-doc-link"><i class="fa fa-file-pdf-o"></i> CC Proprietários</a>
          <?php else: ?><span class="text-muted mright10">CC: não enviado</span><?php endif; ?>

          <?php if(!empty($imovel['doc_caderneta_predial'])): ?>
          <a href="<?php echo base_url($imovel['doc_caderneta_predial']); ?>" target="_blank" class="dps-doc-link"><i class="fa fa-file-pdf-o"></i> Caderneta Predial</a>
          <?php else: ?><span class="text-muted">Caderneta Predial: não enviada</span><?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- BOTÕES DE ACÇÃO -->
    <div class="text-right mbottom20">
      <a href="<?php echo admin_url('dps_imoveis'); ?>" class="btn btn-default mright5"><i class="fa fa-arrow-left"></i> Voltar</a>
      <!-- Editar — todos podem editar (controller restringe ao próprio imóvel para não-aprovadores) -->
      <a href="<?php echo admin_url('dps_imoveis/editar/'.$imovel['id']); ?>" class="btn btn-info mright5"><i class="fa fa-pencil"></i> Editar</a>
      <!-- Apagar — apenas aprovadores -->
      <?php if($pode_aprovar): ?>
      <a href="<?php echo admin_url('dps_imoveis/apagar/'.$imovel['id']); ?>" class="btn btn-danger" onclick="return confirm('Apagar este imóvel permanentemente?')"><i class="fa fa-trash"></i> Apagar</a>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>
</div>

<!-- MODAL APROVAR -->
<div class="modal fade" id="modal-aprovar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-check-circle text-success"></i> Aprovar Imóvel</h4>
      </div>
      <?php echo form_open(admin_url('dps_imoveis/aprovar/'.$imovel['id'])); ?>
      <div class="modal-body">
        <p>Ao aprovar, este imóvel ficará <strong>visível em dpsimobiliario.pt</strong> imediatamente.</p>
        <div class="form-group">
          <label>Notas de aprovação (opcional)</label>
          <textarea name="notas_aprovacao" class="form-control" rows="3" placeholder="Ex: Aprovado. Fotos de boa qualidade."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Confirmar Aprovação</button>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<!-- MODAL REJEITAR -->
<div class="modal fade" id="modal-rejeitar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-times-circle text-danger"></i> Rejeitar Imóvel</h4>
      </div>
      <?php echo form_open(admin_url('dps_imoveis/rejeitar/'.$imovel['id'])); ?>
      <div class="modal-body">
        <p>O imóvel será marcado como <strong>rejeitado</strong> e não será publicado no site.</p>
        <div class="form-group">
          <label>Motivo da rejeição <span class="text-danger">*</span></label>
          <textarea name="notas_aprovacao" class="form-control" rows="3" required placeholder="Ex: Fotos insuficientes. Por favor adicione mais fotos do interior."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> Confirmar Rejeição</button>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<?php init_tail(); ?>
