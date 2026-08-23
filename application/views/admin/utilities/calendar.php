<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body" style="overflow-x: auto;">
						<div class="dt-loader hide"></div>
						<?php $this->load->view('admin/utilities/calendar_filters'); ?>
						<?php
						/*
						 * DPS: de quem é a agenda.
						 *
						 * Fica aqui e não dentro do #calendar_filters porque
						 * esse painel nasce escondido, atrás de um botão. Este
						 * tem de estar à vista: é ele que explica porque é que
						 * a agenda mostra o que mostra.
						 *
						 * Recarrega a página em vez de mandar mais um parâmetro
						 * no pedido AJAX — quem monta esse pedido é o
						 * FullCalendar, dentro do main.js minificado.
						 */
						?>
						<?php if (!empty($agenda_equipa)) { ?>
						<div class="tw-mb-3 tw-flex tw-items-center tw-gap-2">
							<label for="dps-agenda-staff" class="tw-mb-0 tw-whitespace-nowrap">
								<i class="fa fa-calendar-o"></i> Agenda de
							</label>
							<select id="dps-agenda-staff" class="form-control" style="max-width:280px;display:inline-block;">
								<?php foreach ($agenda_equipa as $membro) { ?>
								<option value="<?php echo (int) $membro['staffid']; ?>"<?php echo (int) $membro['staffid'] === (int) $agenda_staff ? ' selected' : ''; ?>>
									<?php echo html_escape(trim($membro['firstname'] . ' ' . $membro['lastname'])); ?>
								</option>
								<?php } ?>
								<option value="-1"<?php echo (int) $agenda_staff === -1 ? ' selected' : ''; ?>>— Toda a equipa —</option>
							</select>
						</div>
						<script>
							document.getElementById('dps-agenda-staff').addEventListener('change', function () {
								window.location = '<?php echo admin_url('utilities/calendar'); ?>?agenda_staff=' + this.value;
							});
						</script>
						<?php } ?>
						<div id="calendar"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php $this->load->view('admin/utilities/calendar_template'); ?>
<?php hooks()->do_action('after_calendar_loaded');?>
<script>
	app.calendarIDs = '<?php echo json_encode($google_ids_calendars); ?>';
</script>
<?php init_tail(); ?>
<script>
	$(function(){
		if(get_url_param('eventid')) {
			view_event(get_url_param('eventid'));
		}
	});
</script>
</body>
</html>
