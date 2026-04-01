<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.cal-card { border: 2px solid #e5e7eb; border-radius: 8px; padding: 12px 15px; margin-bottom: 8px; cursor: pointer; transition: all .15s; display: flex; align-items: center; gap: 10px; }
.cal-card:hover { border-color: #4285f4; background: #f0f4ff; }
.cal-card.selected { border-color: #4285f4; background: #e8f0fe; }
.cal-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
.cal-card .cal-name { font-weight: 600; font-size: 13px; }
.cal-card .cal-id { font-size: 11px; color: #888; }
.cal-card .cal-badge { margin-left: auto; }
</style>

<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <?php $this->load->view('admin/includes/alerts'); ?>
        <h4 class="mbot15">
          <i class="fa fa-calendar" style="color:#4285f4;margin-right:6px;"></i>
          <?php echo _l('dps_calendar_sync_events'); ?>
        </h4>

        <?php if (!$tokens): ?>
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            <?php echo _l('dps_calendar_sync_no_account_connected'); ?>
          </div>
          <a href="<?php echo admin_url('dps_calendar_sync/connect'); ?>" class="btn btn-primary">
            <i class="fa fa-google"></i> <?php echo _l('dps_calendar_sync_connect_account'); ?>
          </a>

        <?php else: ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
            <a href="<?php echo admin_url('dps_calendar_sync/sync_google'); ?>" class="btn btn-info btn-sm">
              <i class="fa fa-refresh"></i> <?php echo _l('dps_calendar_sync_import_google'); ?>
            </a>
            <a href="<?php echo admin_url('dps_calendar_sync/disconnect'); ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('<?php echo _l('dps_calendar_sync_disconnect_confirm'); ?>');">
              <i class="fa fa-unlink"></i> <?php echo _l('dps_calendar_sync_disconnect_account'); ?>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($tokens): ?>
    <div class="row">

      <!-- Coluna esquerda: Selector de calendário + Adicionar evento -->
      <div class="col-md-5">

        <!-- Selector de Calendário -->
        <div class="panel_s">
          <div class="panel-body">
            <h5 style="font-weight:600;margin-bottom:5px;">
              <i class="fa fa-calendar-check-o" style="color:#4285f4;margin-right:5px;"></i>
              <?php echo _l('dps_calendar_sync_choose_calendar'); ?>
            </h5>
            <p style="font-size:12px;color:#888;margin-bottom:15px;">
              <?php echo _l('dps_calendar_sync_choose_calendar_desc'); ?>
            </p>

            <?php
            $current_cal_id = $tokens->calendar_id ?? 'primary';
            ?>

            <?php if (!empty($calendars)): ?>
              <div id="calendar-list">
                <?php foreach ($calendars as $cal): ?>
                <?php
                $is_selected = ($cal['id'] === $current_cal_id) || ($current_cal_id === 'primary' && !empty($cal['primary']));
                $color = '#4285f4';
                ?>
                <div class="cal-card <?php echo $is_selected ? 'selected' : ''; ?>"
                     data-id="<?php echo htmlspecialchars($cal['id'], ENT_QUOTES); ?>"
                     data-name="<?php echo htmlspecialchars($cal['summary'], ENT_QUOTES); ?>">
                  <span class="cal-dot" style="background:<?php echo $color; ?>;"></span>
                  <div>
                    <div class="cal-name"><?php echo htmlspecialchars($cal['summary']); ?></div>
                    <div class="cal-id"><?php echo htmlspecialchars($cal['id']); ?></div>
                  </div>
                  <?php if (!empty($cal['primary'])): ?>
                    <span class="label label-primary cal-badge">Principal</span>
                  <?php endif; ?>
                  <?php if ($is_selected): ?>
                    <i class="fa fa-check-circle" style="color:#4285f4;margin-left:auto;font-size:16px;" id="check-<?php echo md5($cal['id']); ?>"></i>
                  <?php else: ?>
                    <i class="fa fa-circle-o" style="color:#ccc;margin-left:auto;font-size:16px;" id="check-<?php echo md5($cal['id']); ?>"></i>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
              <div id="cal-save-result" style="display:none;margin-top:10px;padding:8px;border-radius:4px;"></div>
            <?php else: ?>
              <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                <?php echo _l('dps_calendar_sync_no_calendars'); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Adicionar Evento -->
        <div class="panel_s">
          <div class="panel-heading">
            <i class="fa fa-plus-circle"></i> <?php echo _l('dps_calendar_sync_add_event'); ?>
          </div>
          <div class="panel-body">
            <?php echo form_open(admin_url('dps_calendar_sync')); ?>
            <input type="hidden" name="add_event" value="1" />
            <?php echo render_input('title', 'dps_calendar_sync_event_title'); ?>
            <?php echo render_textarea('description', 'dps_calendar_sync_event_description'); ?>
            <div class="form-group">
              <label for="start_time"><?php echo _l('dps_calendar_sync_event_start'); ?></label>
              <input type="datetime-local" id="start_time" name="start_time" class="form-control" required />
            </div>
            <div class="form-group">
              <label for="end_time"><?php echo _l('dps_calendar_sync_event_end'); ?></label>
              <input type="datetime-local" id="end_time" name="end_time" class="form-control" required />
            </div>
            <?php echo render_input('location', 'dps_calendar_sync_event_location'); ?>
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> <?php echo _l('dps_calendar_sync_save_event'); ?>
            </button>
            <?php echo form_close(); ?>
          </div>
        </div>

      </div>

      <!-- Coluna direita: Lista de eventos -->
      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-heading">
            <i class="fa fa-list"></i> <?php echo _l('dps_calendar_sync_my_events'); ?>
          </div>
          <div class="panel-body">
            <?php if (empty($events)): ?>
              <p style="color:#999;text-align:center;padding:20px 0;"><?php echo _l('dps_calendar_sync_no_events'); ?></p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover" style="font-size:13px;">
                  <thead>
                    <tr>
                      <th><?php echo _l('dps_calendar_sync_table_title'); ?></th>
                      <th><?php echo _l('dps_calendar_sync_table_start'); ?></th>
                      <th><?php echo _l('dps_calendar_sync_table_end'); ?></th>
                      <th><?php echo _l('dps_calendar_sync_table_location'); ?></th>
                      <th><i class="fa fa-google"></i></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($events as $e): ?>
                      <tr>
                        <td><?php echo html_escape($e->title); ?></td>
                        <td><?php echo _dt($e->start_time); ?></td>
                        <td><?php echo _dt($e->end_time); ?></td>
                        <td><?php echo html_escape($e->location); ?></td>
                        <td>
                          <?php if ($e->google_event_id): ?>
                            <span class="label label-success" title="Sincronizado com Google">
                              <i class="fa fa-check"></i>
                            </span>
                          <?php else: ?>
                            <span class="label label-default">—</span>
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
    <?php endif; ?>
  </div>
</div>

<script>
var WA_SAVE_CAL_URL = '<?php echo admin_url("dps_calendar_sync/ajax_save_calendar"); ?>';

jQuery(document).ready(function($) {
    // Seleccionar calendário ao clicar
    $(document).on('click', '.cal-card', function() {
        var $card      = $(this);
        var calId      = $card.data('id');
        var calName    = $card.data('name');
        var $result    = $('#cal-save-result');

        // Actualizar UI imediatamente
        $('.cal-card').removeClass('selected').find('i.fa').removeClass('fa-check-circle').addClass('fa-circle-o').css('color','#ccc');
        $card.addClass('selected').find('i.fa').removeClass('fa-circle-o').addClass('fa-check-circle').css('color','#4285f4');

        $result.hide();

        // Guardar no servidor
        $.ajax({
            url: WA_SAVE_CAL_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: { calendar_id: calId, calendar_name: calName },
            success: function(data) {
                if (data.success) {
                    $result.show().css({'background':'#f0fdf4','color':'#16a34a','border':'1px solid #bbf7d0','display':'block'})
                           .html('<i class="fa fa-check"></i> Calendário "<strong>' + calName + '</strong>" seleccionado. Os eventos serão sincronizados com este calendário.');
                } else {
                    $result.show().css({'background':'#fef2f2','color':'#dc2626','border':'1px solid #fca5a5','display':'block'})
                           .text(data.error || 'Erro ao guardar.');
                }
            },
            error: function() {
                $result.show().css({'background':'#fef2f2','color':'#dc2626','border':'1px solid #fca5a5','display':'block'})
                       .text('Erro de comunicação.');
            }
        });
    });
});
</script>

<?php init_tail(); ?>
