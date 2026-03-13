<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
  <div class="panel-body">
    
    <!-- Formulário de Pesquisa -->
    <div class="row mbot20">
      <div class="col-md-12">
        <form method="get" action="<?php echo admin_url('appointments/history'); ?>" class="form-inline">
          <div class="form-group">
            <label for="type" class="control-label mr-2"><?php echo _l('Pesquisar por'); ?>:</label>
            <select name="type" id="type" class="form-control mr-2" required>
              <option value="">-- Selecione --</option>
              <option value="empresa" <?php if($this->input->get('type')=='empresa'){echo 'selected';} ?>>Empresa</option>
              <option value="contacto" <?php if($this->input->get('type')=='contacto'){echo 'selected';} ?>>Contacto</option>
              <option value="nif" <?php if($this->input->get('type')=='nif'){echo 'selected';} ?>>NIF</option>
            </select>
          </div>

          <div class="form-group ml-2">
            <input type="text" name="q" class="form-control" placeholder="Digite o valor" value="<?php echo html_escape($this->input->get('q')); ?>" required>
          </div>

          <button type="submit" class="btn btn-info ml-2"><?php echo _l('Pesquisar'); ?></button>
        </form>
      </div>
    </div>

    <!-- Título e tabela de marcações -->
    <h4 class="no-margin">
      <?php echo _l('Histórico de Marcações do Cliente ID: ') . ($client_id ?? 'N/D'); ?>
    </h4>
    <hr class="hr-panel-heading" />

    <?php if (!empty($appointments)) : ?>
      <table class="table table-striped">
        <thead>
          <tr>
            <th><?php echo _l('Data'); ?></th>
            <th><?php echo _l('Assunto'); ?></th>
            <th><?php echo _l('Descrição'); ?></th>
            <th><?php echo _l('Estado'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($appointments as $appointment) : ?>
            <tr>
              <td><?php echo _dt($appointment['date']); ?></td>
              <td><?php echo $appointment['subject']; ?></td>
              <td><?php echo $appointment['description']; ?></td>
              <td>
                <?php
                $status = 'Pendente';
                if ($appointment['finished'] == 1) {
                  $status = 'Concluída';
                } elseif ($appointment['cancelled'] == 1) {
                  $status = 'Cancelada';
                } elseif ($appointment['approved'] == 1) {
                  $status = 'Aprovada';
                }
                echo $status;
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else : ?>
      <p><?php echo _l('Nenhuma marcação encontrada para este cliente.'); ?></p>
    <?php endif; ?>

  </div>
</div>