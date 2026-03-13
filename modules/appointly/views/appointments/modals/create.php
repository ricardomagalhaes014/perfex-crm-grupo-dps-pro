<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <h4 class="tw-font-semibold"><?= _l('Nova Marcação'); ?></h4>
        <hr />
        <?php echo form_open(admin_url('appointly/appointments/create'), ['id' => 'appointment-form']); ?>

        <div class="form-group">
          <label for="subject"><?= _l('appointment_subject'); ?></label>
          <input type="text" class="form-control" name="subject" id="subject" required>
        </div>

        <div class="form-group">
          <label for="date"><?= _l('appointment_meeting_date'); ?></label>
          <input type="date" class="form-control" name="date" id="date" required>
        </div>

        <div class="form-group">
          <label for="start_hour"><?= _l('appointment_meeting_start_hour'); ?></label>
          <input type="time" class="form-control" name="start_hour" id="start_hour" required>
        </div>

        <div class="form-group">
          <label for="description"><?= _l('appointment_description'); ?></label>
          <textarea class="form-control" name="description" id="description"></textarea>
        </div>

        <!-- Campos obrigatórios para criação da marcação -->
        <input type="hidden" name="contact_id" value="<?= html_escape($client_id); ?>">
        <input type="hidden" name="rel_type" value="external">
        <input type="hidden" name="attendees[]" value="<?= get_staff_user_id(); ?>">

        <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>

        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
  $('#appointment-form').on('submit', function(e) {
    e.preventDefault();

    var $form = $(this);
    var $btn = $form.find('button[type="submit"]');

    $btn.prop('disabled', true).text('A guardar...');

    $.post($form.attr('action'), $form.serialize()).done(function(resp) {
      let res = {};
      try {
        res = JSON.parse(resp);
      } catch (e) {
        alert('Erro inesperado na resposta do servidor.');
        $btn.prop('disabled', false).text('<?= _l('submit'); ?>');
        return;
      }

      if (res.result) {
        alert('Marcação criada com sucesso!');
        window.location.href = '<?= admin_url('appointly/appointments'); ?>';
      } else {
        alert('Erro ao criar marcação.');
        $btn.prop('disabled', false).text('<?= _l('submit'); ?>');
      }
    }).fail(function(jqXHR, textStatus, errorThrown) {
      alert('Erro ao comunicar com o servidor: ' + textStatus);
      $btn.prop('disabled', false).text('<?= _l('submit'); ?>');
    });
  });
</script>
</body>
</html>