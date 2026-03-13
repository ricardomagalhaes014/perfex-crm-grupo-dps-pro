<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
  <div class="panel-body">
    <h4 class="no-margin"><?php echo _l('Histórico de Marcações do Cliente ID: ') . $client_id; ?></h4>
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