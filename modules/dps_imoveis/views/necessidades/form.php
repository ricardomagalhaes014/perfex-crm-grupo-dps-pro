<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">
<div class="row">
  <div class="col-md-12">
        <div class="tw-mb-4">
          <a href="<?php echo admin_url('dps_imoveis/necessidades'); ?>" class="btn btn-default btn-sm">
            <i class="fa fa-arrow-left"></i> <?php echo _l('dps_imoveis_voltar'); ?>
          </a>
        </div>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4">
              <?php
              /*
               * "Editar" só quando há mesmo uma necessidade gravada. Vindo de
               * uma lead, o $necessidade traz os dados do cliente já
               * preenchidos mas ainda não existe nada — chamar-lhe edição
               * dizia ao comercial que estava a mexer num registo antigo.
               */
              echo !empty($necessidade['id'])
                  ? _l('dps_imoveis_editar_necessidade')
                  : _l('dps_imoveis_nova_necessidade');
              ?>
            </h4>

            <?php echo form_open(admin_url('dps_imoveis/guardar_necessidade'), ['id' => 'form-necessidade', 'enctype' => 'multipart/form-data']); ?>
            <?php if (!empty($necessidade['id'])): ?>
              <input type="hidden" name="id" value="<?php echo $necessidade['id']; ?>">
            <?php endif; ?>
            <?php if (!empty($lead_id)): ?>
              <input type="hidden" name="lead_id" value="<?php echo (int) $lead_id; ?>">
            <?php endif; ?>

            <!-- Informação do Cliente -->
            <div class="row">
              <div class="col-md-12">
                <h5 class="tw-text-neutral-500 tw-font-semibold tw-mb-3 tw-border-b tw-pb-2">
                  <i class="fa fa-user"></i> Informação do Cliente
                </h5>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label"><?php echo _l('dps_imoveis_nome_cliente'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="nome_cliente" class="form-control"
                    value="<?php echo $necessidade ? htmlspecialchars($necessidade['nome_cliente']) : ''; ?>"
                    required placeholder="Nome completo do cliente">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label"><?php echo _l('dps_imoveis_contacto_cliente'); ?></label>
                  <input type="text" name="contacto_cliente" class="form-control"
                    value="<?php echo $necessidade ? htmlspecialchars($necessidade['contacto_cliente']) : ''; ?>"
                    placeholder="Telefone / WhatsApp">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label"><?php echo _l('dps_imoveis_email_cliente'); ?></label>
                  <input type="email" name="email_cliente" class="form-control"
                    value="<?php echo $necessidade ? htmlspecialchars($necessidade['email_cliente']) : ''; ?>"
                    placeholder="Email do cliente">
                </div>
              </div>
            </div>

            <!-- Critérios de Procura -->
            <div class="row tw-mt-4">
              <div class="col-md-12">
                <h5 class="tw-text-neutral-500 tw-font-semibold tw-mb-3 tw-border-b tw-pb-2">
                  <i class="fa fa-search"></i> Critérios de Procura
                </h5>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label"><?php echo _l('dps_imoveis_tipo'); ?></label>
                  <select name="tipo" class="form-control selectpicker" data-live-search="true">
                    <option value="">-- Qualquer tipo --</option>
                    <?php
                    $tipos = ['Apartment' => 'Apartamento', 'House' => 'Moradia', 'Commercial' => 'Loja/Comercial', 'Land' => 'Terreno'];
                    foreach ($tipos as $val => $label):
                      $sel = ($necessidade && $necessidade['tipo'] == $val) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label"><?php echo _l('dps_imoveis_tipologia'); ?></label>
                  <select name="tipologia" class="form-control selectpicker">
                    <option value="">-- Qualquer tipologia --</option>
                    <?php
                    foreach (['T0','T1','T2','T3','T4','T4+'] as $t):
                      $sel = ($necessidade && $necessidade['tipologia'] == $t) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $t; ?>" <?php echo $sel; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label"><?php echo _l('dps_imoveis_distrito'); ?></label>
                  <select name="distrito" class="form-control selectpicker" data-live-search="true">
                    <option value="">-- Qualquer distrito --</option>
                    <?php
                    $distritos = ['Aveiro','Beja','Braga','Bragança','Castelo Branco','Coimbra','Évora','Faro','Guarda','Leiria','Lisboa','Portalegre','Porto','Santarém','Setúbal','Viana do Castelo','Vila Real','Viseu','Açores','Madeira'];
                    foreach ($distritos as $d):
                      $sel = ($necessidade && $necessidade['distrito'] == $d) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $d; ?>" <?php echo $sel; ?>><?php echo $d; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Cidade / Zona</label>
                  <input type="text" name="cidade" class="form-control"
                    value="<?php echo $necessidade ? htmlspecialchars($necessidade['cidade']) : ''; ?>"
                    placeholder="Ex: Porto, Matosinhos...">
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Preço Mínimo (€)</label>
                  <input type="number" name="preco_min" class="form-control" min="0" step="1000"
                    value="<?php echo $necessidade ? htmlspecialchars($necessidade['preco_min']) : ''; ?>"
                    placeholder="0">
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Preço Máximo (€)</label>
                  <input type="number" name="preco_max" class="form-control" min="0" step="1000"
                    value="<?php echo $necessidade ? htmlspecialchars($necessidade['preco_max']) : ''; ?>"
                    placeholder="500000">
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Área Mínima (m²)</label>
                  <input type="number" name="area_min" class="form-control" min="0"
                    value="<?php echo $necessidade ? htmlspecialchars($necessidade['area_min']) : ''; ?>"
                    placeholder="0">
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Nº Quartos (mínimo)</label>
                  <select name="nr_quartos_min" class="form-control selectpicker">
                    <option value="">-- Indiferente --</option>
                    <?php for ($i = 0; $i <= 5; $i++):
                      $sel = ($necessidade && $necessidade['nr_quartos_min'] == $i) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $sel; ?>><?php echo $i; ?>+</option>
                    <?php endfor; ?>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Garagem</label>
                  <select name="garagem" class="form-control selectpicker">
                    <option value="">-- Indiferente --</option>
                    <?php $sel_s = ($necessidade && $necessidade['garagem'] == 1) ? 'selected' : ''; ?>
                    <?php $sel_n = ($necessidade && $necessidade['garagem'] == 0 && $necessidade['garagem'] !== '') ? 'selected' : ''; ?>
                    <option value="1" <?php echo $sel_s; ?>>Sim</option>
                    <option value="0" <?php echo $sel_n; ?>>Não</option>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Urgência</label>
                  <select name="urgencia" class="form-control selectpicker">
                    <option value="normal" <?php echo ($necessidade && $necessidade['urgencia'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                    <option value="urgente" <?php echo ($necessidade && $necessidade['urgencia'] == 'urgente') ? 'selected' : ''; ?>>Urgente</option>
                    <option value="sem_pressa" <?php echo ($necessidade && $necessidade['urgencia'] == 'sem_pressa') ? 'selected' : ''; ?>>Sem pressa</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Observações -->
            <div class="row tw-mt-2">
              <div class="col-md-12">
                <div class="form-group">
                  <label class="control-label">Observações / Detalhes adicionais</label>
                  <textarea name="observacoes" class="form-control" rows="4"
                    placeholder="Descreva outros requisitos do cliente (ex: perto de escola, rés-do-chão, vista mar, etc.)"><?php echo $necessidade ? htmlspecialchars($necessidade['observacoes']) : ''; ?></textarea>
                </div>
              </div>
            </div>

            <div class="row tw-mt-2">
              <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-save"></i> <?php echo $necessidade ? 'Guardar Alterações' : 'Registar Necessidade'; ?>
                </button>
                <a href="<?php echo admin_url('dps_imoveis/necessidades'); ?>" class="btn btn-default tw-ml-2">Cancelar</a>
              </div>
            </div>

            <?php echo form_close(); ?>
          </div>
        </div>
  </div>
</div>
</div>
</div>
<?php init_tail(); ?>
