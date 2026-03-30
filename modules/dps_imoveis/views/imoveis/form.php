<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dps-section-title { background:#f5f5f5; padding:8px 15px; margin:15px -15px 15px; font-weight:bold; border-left:4px solid #337ab7; }
.dps-doc-badge { display:inline-block; padding:4px 10px; background:#e8f4e8; border:1px solid #5cb85c; border-radius:4px; font-size:12px; color:#3d8b3d; margin-top:5px; }
.foto-preview { width:80px; height:70px; object-fit:cover; border-radius:4px; border:2px solid #ddd; margin:3px; cursor:pointer; }
.foto-preview:hover { border-color:#337ab7; }
.divisao-row { display:flex; align-items:center; gap:10px; margin-bottom:8px; padding:8px 10px; background:#fafafa; border:1px solid #eee; border-radius:5px; }
.divisao-row label { min-width:110px; font-weight:normal; margin:0; color:#555; font-size:13px; }
.divisao-row input { width:110px; }
.divisao-row .divisao-num { display:inline-block; min-width:24px; height:24px; line-height:24px; text-align:center; background:#337ab7; color:#fff; border-radius:50%; font-size:12px; font-weight:bold; margin-right:4px; }
.tipologia-badge { display:inline-block; padding:4px 14px; background:#337ab7; color:#fff; border-radius:20px; font-size:14px; font-weight:bold; margin-left:10px; }
#tipologia-display { font-size:15px; color:#555; margin-bottom:12px; }
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

        // Carregar áreas individuais guardadas (JSON)
        $areas_quartos   = json_decode($imovel['areas_quartos']   ?? '[]', true) ?: [];
        $areas_suites    = json_decode($imovel['areas_suites']    ?? '[]', true) ?: [];
        $areas_salas     = json_decode($imovel['areas_salas']     ?? '[]', true) ?: [];
        $areas_cozinhas  = json_decode($imovel['areas_cozinhas']  ?? '[]', true) ?: [];
        $areas_casas_banho = json_decode($imovel['areas_casas_banho'] ?? '[]', true) ?: [];
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
                  <label>Tipologia <small class="text-muted">(definida automaticamente pelas divisões)</small></label>
                  <select name="tipologia" id="tipologia_select" class="form-control">
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

          <!-- TAB: ÁREAS E DIVISÕES (DINÂMICA) -->
          <div class="tab-pane" id="tab-areas">

            <!-- Indicador de tipologia -->
            <div id="tipologia-display">
              Tipologia detectada: <span class="tipologia-badge" id="tipologia-badge"><?php echo htmlspecialchars($imovel['tipologia'] ?? 'T0'); ?></span>
              <small class="text-muted mleft10">(actualizada automaticamente conforme os quartos preenchidos)</small>
            </div>

            <!-- QUARTOS -->
            <div class="dps-section-title"><i class="fa fa-bed"></i> Quartos</div>
            <div id="quartos-container">
              <?php
              // Mostrar quartos já guardados + 1 vazio para adicionar
              $quartos_existentes = !empty($areas_quartos) ? $areas_quartos : [];
              foreach($quartos_existentes as $qi => $qa):
              ?>
              <div class="divisao-row" id="quarto-row-<?php echo $qi+1; ?>">
                <span class="divisao-num"><?php echo $qi+1; ?></span>
                <label>Quarto <?php echo $qi+1; ?></label>
                <input type="number" name="areas_quartos[]" class="form-control quarto-area" step="0.01" min="0" placeholder="Área m²" value="<?php echo htmlspecialchars($qa); ?>" data-index="<?php echo $qi+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
              <?php endforeach; ?>
              <!-- Primeiro campo vazio (ou próximo após os existentes) -->
              <div class="divisao-row" id="quarto-row-<?php echo count($quartos_existentes)+1; ?>">
                <span class="divisao-num"><?php echo count($quartos_existentes)+1; ?></span>
                <label>Quarto <?php echo count($quartos_existentes)+1; ?></label>
                <input type="number" name="areas_quartos[]" class="form-control quarto-area" step="0.01" min="0" placeholder="Área m²" value="" data-index="<?php echo count($quartos_existentes)+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
            </div>

            <!-- SUÍTES -->
            <div class="dps-section-title"><i class="fa fa-star"></i> Suítes</div>
            <div id="suites-container">
              <?php
              $suites_existentes = !empty($areas_suites) ? $areas_suites : [];
              foreach($suites_existentes as $si => $sa):
              ?>
              <div class="divisao-row" id="suite-row-<?php echo $si+1; ?>">
                <span class="divisao-num"><?php echo $si+1; ?></span>
                <label>Suíte <?php echo $si+1; ?></label>
                <input type="number" name="areas_suites[]" class="form-control suite-area" step="0.01" min="0" placeholder="Área m²" value="<?php echo htmlspecialchars($sa); ?>" data-index="<?php echo $si+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
              <?php endforeach; ?>
              <div class="divisao-row" id="suite-row-<?php echo count($suites_existentes)+1; ?>">
                <span class="divisao-num"><?php echo count($suites_existentes)+1; ?></span>
                <label>Suíte <?php echo count($suites_existentes)+1; ?></label>
                <input type="number" name="areas_suites[]" class="form-control suite-area" step="0.01" min="0" placeholder="Área m²" value="" data-index="<?php echo count($suites_existentes)+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
            </div>

            <!-- SALAS -->
            <div class="dps-section-title"><i class="fa fa-couch"></i> Salas</div>
            <div id="salas-container">
              <?php
              $salas_existentes = !empty($areas_salas) ? $areas_salas : [];
              foreach($salas_existentes as $li => $la):
              ?>
              <div class="divisao-row" id="sala-row-<?php echo $li+1; ?>">
                <span class="divisao-num"><?php echo $li+1; ?></span>
                <label>Sala <?php echo $li+1; ?></label>
                <input type="number" name="areas_salas[]" class="form-control sala-area" step="0.01" min="0" placeholder="Área m²" value="<?php echo htmlspecialchars($la); ?>" data-index="<?php echo $li+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
              <?php endforeach; ?>
              <div class="divisao-row" id="sala-row-<?php echo count($salas_existentes)+1; ?>">
                <span class="divisao-num"><?php echo count($salas_existentes)+1; ?></span>
                <label>Sala <?php echo count($salas_existentes)+1; ?></label>
                <input type="number" name="areas_salas[]" class="form-control sala-area" step="0.01" min="0" placeholder="Área m²" value="" data-index="<?php echo count($salas_existentes)+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
            </div>

            <!-- COZINHAS -->
            <div class="dps-section-title"><i class="fa fa-cutlery"></i> Cozinhas</div>
            <div id="cozinhas-container">
              <?php
              $cozinhas_existentes = !empty($areas_cozinhas) ? $areas_cozinhas : [];
              foreach($cozinhas_existentes as $ci => $ca):
              ?>
              <div class="divisao-row" id="cozinha-row-<?php echo $ci+1; ?>">
                <span class="divisao-num"><?php echo $ci+1; ?></span>
                <label>Cozinha <?php echo $ci+1; ?></label>
                <input type="number" name="areas_cozinhas[]" class="form-control cozinha-area" step="0.01" min="0" placeholder="Área m²" value="<?php echo htmlspecialchars($ca); ?>" data-index="<?php echo $ci+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
              <?php endforeach; ?>
              <div class="divisao-row" id="cozinha-row-<?php echo count($cozinhas_existentes)+1; ?>">
                <span class="divisao-num"><?php echo count($cozinhas_existentes)+1; ?></span>
                <label>Cozinha <?php echo count($cozinhas_existentes)+1; ?></label>
                <input type="number" name="areas_cozinhas[]" class="form-control cozinha-area" step="0.01" min="0" placeholder="Área m²" value="" data-index="<?php echo count($cozinhas_existentes)+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
            </div>

            <!-- CASAS DE BANHO -->
            <div class="dps-section-title"><i class="fa fa-bath"></i> Casas de Banho</div>
            <div id="casasbanho-container">
              <?php
              $casasbanho_existentes = !empty($areas_casas_banho) ? $areas_casas_banho : [];
              foreach($casasbanho_existentes as $bi => $ba):
              ?>
              <div class="divisao-row" id="casabanho-row-<?php echo $bi+1; ?>">
                <span class="divisao-num"><?php echo $bi+1; ?></span>
                <label>Casa de Banho <?php echo $bi+1; ?></label>
                <input type="number" name="areas_casas_banho[]" class="form-control casabanho-area" step="0.01" min="0" placeholder="Área m²" value="<?php echo htmlspecialchars($ba); ?>" data-index="<?php echo $bi+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
              <?php endforeach; ?>
              <div class="divisao-row" id="casabanho-row-<?php echo count($casasbanho_existentes)+1; ?>">
                <span class="divisao-num"><?php echo count($casasbanho_existentes)+1; ?></span>
                <label>Casa de Banho <?php echo count($casasbanho_existentes)+1; ?></label>
                <input type="number" name="areas_casas_banho[]" class="form-control casabanho-area" step="0.01" min="0" placeholder="Área m²" value="" data-index="<?php echo count($casasbanho_existentes)+1; ?>" />
                <span class="text-muted" style="font-size:12px;">m²</span>
              </div>
            </div>

          </div><!-- /tab-areas -->

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

  // ---------------------------------------------------------------
  // TABS — activação com vanilla JS
  // ---------------------------------------------------------------
  function initTabs() {
    var tabLinks = document.querySelectorAll('.nav-tabs [data-toggle="tab"]');
    tabLinks.forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        var targetId = this.getAttribute('href');
        document.querySelectorAll('.tab-pane').forEach(function(p) {
          p.classList.remove('active', 'in');
        });
        document.querySelectorAll('.nav-tabs li').forEach(function(li) {
          li.classList.remove('active');
        });
        var pane = document.querySelector(targetId);
        if (pane) { pane.classList.add('active', 'in'); }
        this.parentElement.classList.add('active');
      });
    });
  }

  // ---------------------------------------------------------------
  // DIVISÕES DINÂMICAS
  // Ao preencher a área de uma divisão, aparece automaticamente a seguinte.
  // Ao limpar uma área, as divisões seguintes são removidas.
  // ---------------------------------------------------------------

  /**
   * Configuração de cada tipo de divisão:
   * containerId  — id do container div
   * cssClass     — classe CSS dos inputs de área
   * inputName    — name do campo (array)
   * labelSingular — texto do label
   * rowIdPrefix  — prefixo do id das rows
   */
  var DIVISOES = [
    { containerId: 'quartos-container',    cssClass: 'quarto-area',    inputName: 'areas_quartos[]',     labelSingular: 'Quarto',        rowIdPrefix: 'quarto-row-'    },
    { containerId: 'suites-container',     cssClass: 'suite-area',     inputName: 'areas_suites[]',      labelSingular: 'Suíte',         rowIdPrefix: 'suite-row-'     },
    { containerId: 'salas-container',      cssClass: 'sala-area',      inputName: 'areas_salas[]',       labelSingular: 'Sala',          rowIdPrefix: 'sala-row-'      },
    { containerId: 'cozinhas-container',   cssClass: 'cozinha-area',   inputName: 'areas_cozinhas[]',    labelSingular: 'Cozinha',       rowIdPrefix: 'cozinha-row-'   },
    { containerId: 'casasbanho-container', cssClass: 'casabanho-area', inputName: 'areas_casas_banho[]', labelSingular: 'Casa de Banho', rowIdPrefix: 'casabanho-row-' }
  ];

  /**
   * Cria uma nova row de divisão
   */
  function criarRow(cfg, index) {
    var row = document.createElement('div');
    row.className = 'divisao-row';
    row.id = cfg.rowIdPrefix + index;
    row.innerHTML =
      '<span class="divisao-num">' + index + '</span>' +
      '<label>' + cfg.labelSingular + ' ' + index + '</label>' +
      '<input type="number" name="' + cfg.inputName + '" class="form-control ' + cfg.cssClass + '" step="0.01" min="0" placeholder="Área m²" data-index="' + index + '" />' +
      '<span class="text-muted" style="font-size:12px;">m²</span>';
    return row;
  }

  /**
   * Ao alterar o valor de um campo de área:
   * - Se preenchido: garante que existe o campo seguinte
   * - Se vazio: remove todos os campos seguintes que também estejam vazios
   */
  function handleAreaChange(cfg, input) {
    var container = document.getElementById(cfg.containerId);
    var allInputs = container.querySelectorAll('.' + cfg.cssClass);
    var thisIndex = parseInt(input.getAttribute('data-index'), 10);
    var val = input.value.trim();

    if (val !== '' && parseFloat(val) > 0) {
      // Verificar se já existe o próximo campo
      var nextIndex = thisIndex + 1;
      var nextRow = document.getElementById(cfg.rowIdPrefix + nextIndex);
      if (!nextRow) {
        var newRow = criarRow(cfg, nextIndex);
        container.appendChild(newRow);
        // Adicionar listener ao novo campo
        var newInput = newRow.querySelector('.' + cfg.cssClass);
        addListener(cfg, newInput);
      }
    } else {
      // Campo limpo — remover todos os campos seguintes que estejam vazios
      var toRemove = [];
      allInputs.forEach(function(inp) {
        var idx = parseInt(inp.getAttribute('data-index'), 10);
        if (idx > thisIndex && (inp.value.trim() === '' || parseFloat(inp.value) === 0)) {
          toRemove.push(inp.closest('.divisao-row'));
        }
      });
      toRemove.forEach(function(row) {
        if (row) row.remove();
      });
    }

    // Actualizar tipologia se for quartos
    if (cfg.cssClass === 'quarto-area') {
      actualizarTipologia();
    }
  }

  function addListener(cfg, input) {
    input.addEventListener('input', function() {
      handleAreaChange(cfg, this);
    });
    input.addEventListener('change', function() {
      handleAreaChange(cfg, this);
    });
  }

  /**
   * Conta os quartos preenchidos e actualiza a tipologia
   */
  function actualizarTipologia() {
    var container = document.getElementById('quartos-container');
    if (!container) return;
    var inputs = container.querySelectorAll('.quarto-area');
    var count = 0;
    inputs.forEach(function(inp) {
      if (inp.value.trim() !== '' && parseFloat(inp.value) > 0) {
        count++;
      }
    });

    var tipologias = ['T0','T1','T2','T3','T4','T4+'];
    var tipologia = count >= 5 ? 'T4+' : tipologias[count];

    // Actualizar badge
    var badge = document.getElementById('tipologia-badge');
    if (badge) badge.textContent = tipologia;

    // Actualizar select na tab Informação Básica
    var sel = document.getElementById('tipologia_select');
    if (sel) {
      for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === tipologia) {
          sel.selectedIndex = i;
          break;
        }
      }
    }
  }

  /**
   * Inicializar listeners em todos os campos existentes
   */
  function initDivisoes() {
    DIVISOES.forEach(function(cfg) {
      var container = document.getElementById(cfg.containerId);
      if (!container) return;
      var inputs = container.querySelectorAll('.' + cfg.cssClass);
      inputs.forEach(function(input) {
        addListener(cfg, input);
      });
    });

    // Calcular tipologia inicial (para edição de imóvel existente)
    actualizarTipologia();
  }

  // ---------------------------------------------------------------
  // REMOVER FOTO
  // ---------------------------------------------------------------
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
          var wrapper = btnEl.closest ? btnEl.closest('.inline-block') : btnEl.parentElement;
          if (wrapper) wrapper.remove();
        } else {
          alert('Erro ao remover foto.');
        }
      })
      .catch(function() { alert('Erro de rede ao remover foto.'); });
  };

  // ---------------------------------------------------------------
  // INICIALIZAR
  // ---------------------------------------------------------------
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initTabs();
      initDivisoes();
    });
  } else {
    initTabs();
    initDivisoes();
  }

})();
</script>
