<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">
<div class="row">
  <div class="col-md-12">

    <!-- STATS -->
    <div class="row mtop10 mbottom15">
      <div class="col-md-2 col-sm-4 col-xs-6">
        <div class="panel_s text-center">
          <div class="panel-body">
            <h2 class="text-warning bold"><?php echo $stats['pendente']; ?></h2>
            <small>Pendentes</small>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-xs-6">
        <div class="panel_s text-center">
          <div class="panel-body">
            <h2 class="text-success bold"><?php echo $stats['aprovado']; ?></h2>
            <small>Aprovados</small>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-xs-6">
        <div class="panel_s text-center">
          <div class="panel-body">
            <h2 class="text-info bold"><?php echo $stats['publicados']; ?></h2>
            <small>No Site</small>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-xs-6">
        <div class="panel_s text-center">
          <div class="panel-body">
            <h2 class="text-danger bold"><?php echo $stats['rejeitado']; ?></h2>
            <small>Rejeitados</small>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-xs-6">
        <div class="panel_s text-center">
          <div class="panel-body">
            <h2 class="text-muted bold"><?php echo $stats['arquivado']; ?></h2>
            <small>Arquivados</small>
          </div>
        </div>
      </div>
      <!-- Botões de acção — visíveis para todos os staff -->
      <div class="col-md-2 col-sm-4 col-xs-6 text-right">
        <a href="<?php echo admin_url('dps_imoveis/novo'); ?>" class="btn btn-primary btn-block mtop5">
          <i class="fa fa-plus"></i> Novo Imóvel
        </a>
        <a href="<?php echo admin_url('dps_imoveis/nova_necessidade'); ?>" class="btn btn-default btn-block mtop5">
          <i class="fa fa-search"></i> Nova Necessidade
        </a>
      </div>
    </div>

    <!-- TABS DE NAVEGAÇÃO -->
    <ul class="nav nav-tabs tw-mb-4">
      <li class="active">
        <a href="<?php echo admin_url('dps_imoveis'); ?>">
          <i class="fa fa-home"></i> Imóveis
        </a>
      </li>
      <li>
        <a href="<?php echo admin_url('dps_imoveis/necessidades'); ?>">
          <i class="fa fa-search"></i> Necessidades
        </a>
      </li>
    </ul>

    <!-- FILTROS -->
    <div class="panel_s">
      <div class="panel-body">
        <form method="GET" class="form-inline">
          <div class="form-group mright5">
            <select name="status" class="form-control input-sm">
              <option value="">Todos os estados</option>
              <option value="pendente" <?php echo ($filters['status']=='pendente'?'selected':''); ?>>Pendentes</option>
              <option value="aprovado" <?php echo ($filters['status']=='aprovado'?'selected':''); ?>>Aprovados</option>
              <option value="rejeitado" <?php echo ($filters['status']=='rejeitado'?'selected':''); ?>>Rejeitados</option>
              <option value="arquivado" <?php echo ($filters['status']=='arquivado'?'selected':''); ?>>Arquivados</option>
            </select>
          </div>
          <div class="form-group mright5">
            <select name="tipo" class="form-control input-sm">
              <option value="">Todos os tipos</option>
              <?php foreach(['Apartamento','Moradia','Loja','Terreno','Escritório','Armazém','Outro'] as $t): ?>
              <option value="<?php echo $t; ?>" <?php echo ($filters['tipo']==$t?'selected':''); ?>><?php echo $t; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mright5">
            <select name="distrito" class="form-control input-sm">
              <option value="">Todos os distritos</option>
              <?php foreach(['Aveiro','Beja','Braga','Bragança','Castelo Branco','Coimbra','Évora','Faro','Guarda','Leiria','Lisboa','Portalegre','Porto','Santarém','Setúbal','Viana do Castelo','Vila Real','Viseu','Açores','Madeira'] as $d): ?>
              <option value="<?php echo $d; ?>" <?php echo ($filters['distrito']==$d?'selected':''); ?>><?php echo $d; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Filtro por agente — visível para aprovadores e admin -->
          <?php if($pode_aprovar): ?>
          <div class="form-group mright5">
            <select name="agente_id" class="form-control input-sm">
              <option value="">Todos os agentes</option>
              <?php foreach($agentes as $a): ?>
              <option value="<?php echo $a['staffid']; ?>" <?php echo ($filters['agente_id']==$a['staffid']?'selected':''); ?>><?php echo $a['firstname'].' '.$a['lastname']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-filter"></i> Filtrar</button>
          <a href="<?php echo admin_url('dps_imoveis'); ?>" class="btn btn-link btn-sm">Limpar</a>
        </form>
      </div>
    </div>

    <!-- TABELA -->
    <div class="panel_s">
      <div class="panel-body">
        <?php if(empty($imoveis)): ?>
          <div class="text-center text-muted p20">
            <i class="fa fa-home fa-3x mbottom10"></i>
            <p>Nenhum imóvel encontrado.</p>
            <a href="<?php echo admin_url('dps_imoveis/novo'); ?>" class="btn btn-primary">Registar primeiro imóvel</a>
          </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Imóvel</th>
                <th>Tipo / Tipologia</th>
                <th>Localização</th>
                <th>Preço</th>
                <th>Agente</th>
                <th>Estado</th>
                <th>Site</th>
                <th>Acções</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($imoveis as $i): ?>
              <tr>
                <td>
                  <?php if(!empty($i['foto_principal'])): ?>
                  <img src="<?php echo base_url($i['foto_principal']); ?>" style="width:50px;height:40px;object-fit:cover;border-radius:4px;margin-right:8px;" />
                  <?php endif; ?>
                  <a href="<?php echo admin_url('dps_imoveis/detalhe/'.$i['id']); ?>">
                    <strong><?php echo htmlspecialchars($i['titulo']); ?></strong>
                  </a>
                </td>
                <td>
                  <span class="label label-default"><?php echo $i['tipo']; ?></span>
                  <?php if($i['tipologia']): ?><span class="label label-info"><?php echo $i['tipologia']; ?></span><?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($i['cidade'] . ($i['distrito'] ? ', '.$i['distrito'] : '')); ?></td>
                <td><?php echo $i['preco'] ? number_format($i['preco'],0,',','.').' €' : '-'; ?></td>
                <td><?php echo htmlspecialchars($i['agente_nome'] ?? '-'); ?></td>
                <td>
                  <?php
                  $badges = ['pendente'=>'warning','aprovado'=>'success','rejeitado'=>'danger','arquivado'=>'default'];
                  $badge = $badges[$i['status']] ?? 'default';
                  ?>
                  <span class="label label-<?php echo $badge; ?>"><?php echo ucfirst($i['status']); ?></span>
                </td>
                <td>
                  <?php if($i['published_website']): ?>
                  <span class="label label-success"><i class="fa fa-globe"></i> Publicado</span>
                  <?php else: ?>
                  <span class="label label-default">Não publicado</span>
                  <?php endif; ?>
                </td>
                <td>
                  <!-- Ver — todos podem ver -->
                  <a href="<?php echo admin_url('dps_imoveis/detalhe/'.$i['id']); ?>" class="btn btn-xs btn-default" title="Ver"><i class="fa fa-eye"></i></a>
                  <!-- Editar — todos podem editar (controller restringe ao próprio imóvel para não-aprovadores) -->
                  <a href="<?php echo admin_url('dps_imoveis/editar/'.$i['id']); ?>" class="btn btn-xs btn-info" title="Editar"><i class="fa fa-pencil"></i></a>
                  <!-- Aprovar — apenas aprovadores -->
                  <?php if($pode_aprovar && $i['status']=='pendente'): ?>
                  <a href="<?php echo admin_url('dps_imoveis/aprovar/'.$i['id']); ?>" class="btn btn-xs btn-success" title="Aprovar" onclick="return confirm('Aprovar e publicar este imóvel?')"><i class="fa fa-check"></i></a>
                  <?php endif; ?>
                  <!-- Apagar — apenas aprovadores -->
                  <?php if($pode_aprovar): ?>
                  <a href="<?php echo admin_url('dps_imoveis/apagar/'.$i['id']); ?>" class="btn btn-xs btn-danger" title="Apagar" onclick="return confirm('Apagar este imóvel?')"><i class="fa fa-trash"></i></a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
</div>
</div>
<?php init_tail(); ?>
