<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dps-section-title { background:#f5f5f5; padding:8px 15px; margin:15px -15px 15px; font-weight:bold; border-left:4px solid #337ab7; }
.dps-doc-badge { display:inline-block; padding:4px 10px; background:#e8f4e8; border:1px solid #5cb85c; border-radius:4px; font-size:12px; color:#3d8b3d; margin-top:5px; }
.foto-preview { width:80px; height:70px; object-fit:cover; border-radius:4px; border:2px solid #ddd; margin:3px; cursor:pointer; }
.foto-preview:hover { border-color:#337ab7; }
</style>
<div id="wrapper">
<div class="content">
<div class="row">
  <div class="col-md-12">
    <div class="panel_s">
      <div class="panel-body">
        <h4><?php echo $title; ?></h4>
        <hr/>

        <?php
        $imovel = isset($imovel) ? $imovel : [];
        $action = isset($imovel['id']) ? admin_url('dps_imoveis/editar/'.$imovel['id']) : admin_url('dps_imoveis/novo');
        ?>

        <?php echo form_open_multipart($action, ['id'=>'dps_imovel_form']); ?>

        <!-- TABS -->
        <ul class="nav nav-tabs" role="tablist">
          <li class="active"><a href="#tab-basico" data-toggle="tab"><i class="fa fa-info-circle"></i> Informação Básica</a></li>
          <li><a href="#tab-areas" data-toggle="tab"><i class="fa fa-th-large"></i> Áreas e Divisões</a></li>
          <li><a href="#tab-fotos" data-toggle="tab"><i class="fa fa-camera"></i> Fotos</a></li>
          <li><a href="#tab-proprietario" data-toggle="tab"><i class="fa fa-user"></i> Proprietário</a></li>
          <li><a href="#tab-documentos" data-toggle="tab"><i class="fa fa-lock"></i> Documentos Privados</a></li>
        </ul>

        <div class="tab-content mtop15">

          <!-- TAB: INFORMAÇÃO BÁSICA -->
          <div class="tab-pane active" id="tab-basico">
            <div class="row">
              <div class="col-md-8">
                <div class="form-group">
                  <label>Título do Imóvel <span class="text-danger">*</span></label>
                  <input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($imovel['titulo'] ?? ''); ?>" placeholder="Ex: Apartamento T2 no Porto, Bonfim" />
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Preço (€)</label>
                  <input type="number" name="preco" class="form-control" step="0.01" value="<?php echo $imovel['preco'] ?? ''; ?>" placeholder="250000" />
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Tipo de Imóvel <span class="text-danger">*</span></label>
                  <select name="tipo" class="form-control" required>
                    <?php foreach(['Apartamento','Moradia','Loja','Terreno','Escritório','Armazém','Outro'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo (($imovel['tipo']??'')==$t?'selected':''); ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Tipologia</label>
                  <select name="tipologia" class="form-control">
                    <option value="">Seleccionar...</option>
                    <?php foreach(['T0','T1','T2','T3','T4','T4+'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo (($imovel['tipologia']??'')==$t?'selected':''); ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Área Total (m²)</label>
                  <input type="number" name="area_total" class="form-control" step="0.01" value="<?php echo $imovel['area_total'] ?? ''; ?>" placeholder="120" />
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Distrito <span class="text-danger">*</span></label>
                  <select name="distrito" class="form-control" required>
                    <option value="">Seleccionar distrito...</option>
                    <?php foreach(['Aveiro','Beja','Braga','Bragança','Castelo Branco','Coimbra','Évora','Faro','Guarda','Leiria','Lisboa','Portalegre','Porto','Santarém','Setúbal','Viana do Castelo','Vila Real','Viseu','Açores','Madeira'] as $d): ?>
                    <option value="<?php echo $d; ?>" <?php echo (($imovel['distrito']??'')==$d?'selected':''); ?>><?php echo $d; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Cidade</label>
                  <input type="text" name="cidade" class="form-control" value="<?php echo htmlspecialchars($imovel['cidade'] ?? ''); ?>" placeholder="Porto" />
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Morada</label>
                  <input type="text" name="morada" class="form-control" value="<?php echo htmlspecialchars($imovel['morada'] ?? ''); ?>" placeholder="Rua..." />
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Ano de Construção</label>
                  <input type="number" name="ano_construcao" class="form-control" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo $imovel['ano_construcao'] ?? ''; ?>" placeholder="2020" />
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Garagem</label>
                  <select name="garagem" class="form-control">
                    <option value="0" <?php echo (($imovel['garagem']??0)==0?'selected':''); ?>>Não</option>
                    <option value="1" <?php echo (($imovel['garagem']??0)==1?'selected':''); ?>>Sim</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Lugar de Garagem</label>
                  <select name="lugar_garagem" class="form-control">
                    <option value="0" <?php echo (($imovel['lugar_garagem']??0)==0?'selected':''); ?>>Não</option>
                    <option value="1" <?php echo (($imovel['lugar_garagem']??0)==1?'selected':''); ?>>Sim</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>Agente Responsável</label>
                  <select name="agente_id" class="form-control">
                    <option value="">Seleccionar agente...</option>
                    <?php foreach($agentes as $a): ?>
                    <option value="<?php echo $a['staffid']; ?>" <?php echo (($imovel['agente_id']??'')==$a['staffid']?'selected':''); ?>>
                      <?php echo htmlspecialchars($a['firstname'].' '.$a['lastname']); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>Equipamento / Características</label>
                  <textarea name="equipamento" class="form-control" rows="3" placeholder="Ex: Ar condicionado, aquecimento central, cozinha equipada..."><?php echo htmlspecialchars($imovel['equipamento'] ?? ''); ?></textarea>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>Descrição Livre</label>
                  <textarea name="texto_livre" class="form-control" rows="5" placeholder="Descrição detalhada do imóvel para o site..."><?php echo htmlspecialchars($imovel['texto_livre'] ?? ''); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: ÁREAS E DIVISÕES -->
          <div class="tab-pane" id="tab-areas">
            <div class="dps-section-title">Quartos</div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Nº de Quartos</label>
                  <input type="number" name="nr_quartos" class="form-control" min="0" value="<?php echo $imovel['nr_quartos'] ?? 0; ?>" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Área Quartos (m²)</label>
                  <input type="number" name="area_quartos" class="form-control" step="0.01" value="<?php echo $imovel['area_quartos'] ?? ''; ?>" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Nº de Suítes</label>
                  <input type="number" name="nr_suites" class="form-control" min="0" value="<?php echo $imovel['nr_suites'] ?? 0; ?>" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Área Suítes (m²)</label>
                  <input type="number" name="area_suites" class="form-control" step="0.01" value="<?php echo $imovel['area_suites'] ?? ''; ?>" />
                </div>
              </div>
            </div>

            <div class="dps-section-title">Salas e Cozinha</div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Nº de Salas</label>
                  <input type="number" name="nr_salas" class="form-control" min="0" value="<?php echo $imovel['nr_salas'] ?? 0; ?>" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Área Salas (m²)</label>
                  <input type="number" name="area_salas" class="form-control" step="0.01" value="<?php echo $imovel['area_salas'] ?? ''; ?>" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Área Cozinha (m²)</label>
                  <input type="number" name="area_cozinha" class="form-control" step="0.01" value="<?php echo $imovel['area_cozinha'] ?? ''; ?>" />
                </div>
              </div>
            </div>

            <div class="dps-section-title">Casas de Banho</div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Nº de Casas de Banho</label>
                  <input type="number" name="nr_casas_banho" class="form-control" min="0" value="<?php echo $imovel['nr_casas_banho'] ?? 0; ?>" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Área Casas de Banho (m²)</label>
                  <input type="number" name="area_casas_banho" class="form-control" step="0.01" value="<?php echo $imovel['area_casas_banho'] ?? ''; ?>" />
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: FOTOS -->
          <div class="tab-pane" id="tab-fotos">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label><i class="fa fa-star text-warning"></i> Foto Principal</label>
                  <?php if(!empty($imovel['foto_principal'])): ?>
                  <div class="mbottom10">
                    <img src="<?php echo base_url($imovel['foto_principal']); ?>" style="max-width:200px;max-height:150px;border-radius:6px;border:2px solid #5cb85c;" />
                    <br/><small class="text-success"><i class="fa fa-check"></i> Foto principal actual</small>
                  </div>
                  <?php endif; ?>
                  <input type="file" name="foto_principal" accept="image/*" class="form-control" />
                  <small class="text-muted">JPG, PNG, WebP. Recomendado: 1200×800px</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label><i class="fa fa-images"></i> Galeria de Fotos (máx. 20)</label>
                  <?php if(!empty($imovel['fotos_array'])): ?>
                  <div class="mbottom10" id="galeria-actual">
                    <?php foreach($imovel['fotos_array'] as $foto): ?>
                    <div class="inline-block" style="position:relative;margin:3px;">
                      <img src="<?php echo base_url($foto); ?>" class="foto-preview" />
                      <?php if(isset($imovel['id'])): ?>
                      <button type="button" class="btn btn-xs btn-danger" style="position:absolute;top:-5px;right:-5px;padding:1px 5px;" onclick="removerFoto('<?php echo $imovel['id']; ?>','<?php echo addslashes($foto); ?>', this.parentElement)">×</button>
                      <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  <input type="file" name="fotos_galeria[]" accept="image/*" multiple class="form-control" />
                  <small class="text-muted">Seleccione até 20 fotos. JPG, PNG, WebP.</small>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: PROPRIETÁRIO -->
          <div class="tab-pane" id="tab-proprietario">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nome do(s) Proprietário(s)</label>
                  <input type="text" name="nome_proprietarios" class="form-control" value="<?php echo htmlspecialchars($imovel['nome_proprietarios'] ?? ''); ?>" placeholder="Nome completo" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Contacto (Telemóvel)</label>
                  <input type="text" name="contacto_proprietario" class="form-control" value="<?php echo htmlspecialchars($imovel['contacto_proprietario'] ?? ''); ?>" placeholder="+351 9XX XXX XXX" />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Email do Proprietário</label>
                  <input type="email" name="mail_proprietario" class="form-control" value="<?php echo htmlspecialchars($imovel['mail_proprietario'] ?? ''); ?>" placeholder="email@exemplo.com" />
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: DOCUMENTOS PRIVADOS -->
          <div class="tab-pane" id="tab-documentos">
            <div class="alert alert-warning">
              <i class="fa fa-lock"></i> <strong>Documentos Privados</strong> — Visíveis apenas para o agente responsável, gestor de área e super admin. Nunca são publicados no site.
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>CMI (Caderneta de Mais-Valias Imobiliárias)</label>
                  <?php if(!empty($imovel['doc_cmi'])): ?>
                  <div class="mbottom5">
                    <span class="dps-doc-badge"><i class="fa fa-file-pdf-o"></i> Documento actual: <a href="<?php echo base_url($imovel['doc_cmi']); ?>" target="_blank">Ver</a></span>
                  </div>
                  <?php endif; ?>
                  <input type="file" name="doc_cmi" class="form-control" accept=".pdf,.jpg,.jpeg,.png" />
                  <small class="text-muted">PDF ou imagem</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>CC dos Proprietários</label>
                  <?php if(!empty($imovel['doc_cc_proprietarios'])): ?>
                  <div class="mbottom5">
                    <span class="dps-doc-badge"><i class="fa fa-file-pdf-o"></i> Documento actual: <a href="<?php echo base_url($imovel['doc_cc_proprietarios']); ?>" target="_blank">Ver</a></span>
                  </div>
                  <?php endif; ?>
                  <input type="file" name="doc_cc_proprietarios" class="form-control" accept=".pdf,.jpg,.jpeg,.png" />
                  <small class="text-muted">PDF ou imagem</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Caderneta Predial</label>
                  <?php if(!empty($imovel['doc_caderneta_predial'])): ?>
                  <div class="mbottom5">
                    <span class="dps-doc-badge"><i class="fa fa-file-pdf-o"></i> Documento actual: <a href="<?php echo base_url($imovel['doc_caderneta_predial']); ?>" target="_blank">Ver</a></span>
                  </div>
                  <?php endif; ?>
                  <input type="file" name="doc_caderneta_predial" class="form-control" accept=".pdf,.jpg,.jpeg,.png" />
                  <small class="text-muted">PDF ou imagem</small>
                </div>
              </div>
            </div>
          </div>

        </div><!-- /tab-content -->

        <hr/>
        <div class="text-right">
          <a href="<?php echo admin_url('dps_imoveis'); ?>" class="btn btn-default mright5">Cancelar</a>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> <?php echo isset($imovel['id']) ? 'Guardar Alterações' : 'Registar Imóvel'; ?>
          </button>
        </div>

        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
</div>
</div>

<?php init_tail(); ?>
<script>
(function() {
  var REMOVE_FOTO_URL = '<?php echo admin_url("dps_imoveis/remover_foto/"); ?>';

  // Activar tabs com vanilla JS (Bootstrap pode nao estar disponivel imediatamente)
  function initTabs() {
    var tabLinks = document.querySelectorAll('.nav-tabs [data-toggle="tab"]');
    tabLinks.forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        var targetId = this.getAttribute('href');
        // Desactivar todos
        document.querySelectorAll('.tab-pane').forEach(function(p) {
          p.classList.remove('active', 'in');
        });
        document.querySelectorAll('.nav-tabs li').forEach(function(li) {
          li.classList.remove('active');
        });
        // Activar o seleccionado
        var pane = document.querySelector(targetId);
        if (pane) { pane.classList.add('active', 'in'); }
        this.parentElement.classList.add('active');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTabs);
  } else {
    initTabs();
  }

  // Funcao para remover foto (usa fetch em vez de $.post)
  window.removerFoto = function(imovelId, fotoPath, btnEl) {
    if (!confirm('Remover esta foto?')) return;
    var formData = new FormData();
    formData.append('foto', fotoPath);
    if (typeof csrfData !== 'undefined') {
      formData.append(csrfData.token_name, csrfData.hash);
    }
    fetch(REMOVE_FOTO_URL + imovelId, { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(r) {
        if (r.success) {
          var wrapper = btnEl.closest('.foto-item');
          if (wrapper) wrapper.remove();
        } else {
          alert('Erro ao remover foto.');
        }
      })
      .catch(function() { alert('Erro de rede ao remover foto.'); });
  };
})();
</script>
