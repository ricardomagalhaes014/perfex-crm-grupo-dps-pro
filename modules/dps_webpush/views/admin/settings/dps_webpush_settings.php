<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
  <div class="col-md-12">
    <h4 class="bold">Web Push Notifications (PWA) - Grupo DPS</h4>
    <p class="text-muted">Configuracoes para notificacoes push nativas no browser (PWA).</p>
    <hr/>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label>Activar Web Push</label>
      <div class="onoffswitch">
        <input type="checkbox" name="settings[dps_webpush_enabled]" class="onoffswitch-checkbox" id="dps_webpush_enabled" value="1" <?php if(get_option('dps_webpush_enabled')=='1') echo 'checked'; ?>>
        <label class="onoffswitch-label" for="dps_webpush_enabled"></label>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-12"><h5 class="bold">Eventos a notificar</h5></div>
  <div class="col-md-4">
    <div class="form-group">
      <label>Novas Leads</label>
      <div class="onoffswitch">
        <input type="checkbox" name="settings[dps_webpush_notify_leads]" class="onoffswitch-checkbox" id="dps_webpush_notify_leads" value="1" <?php if(get_option('dps_webpush_notify_leads')=='1') echo 'checked'; ?>>
        <label class="onoffswitch-label" for="dps_webpush_notify_leads"></label>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="form-group">
      <label>Novas Tarefas</label>
      <div class="onoffswitch">
        <input type="checkbox" name="settings[dps_webpush_notify_tasks]" class="onoffswitch-checkbox" id="dps_webpush_notify_tasks" value="1" <?php if(get_option('dps_webpush_notify_tasks')=='1') echo 'checked'; ?>>
        <label class="onoffswitch-label" for="dps_webpush_notify_tasks"></label>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="form-group">
      <label>Comentarios</label>
      <div class="onoffswitch">
        <input type="checkbox" name="settings[dps_webpush_notify_comments]" class="onoffswitch-checkbox" id="dps_webpush_notify_comments" value="1" <?php if(get_option('dps_webpush_notify_comments')=='1') echo 'checked'; ?>>
        <label class="onoffswitch-label" for="dps_webpush_notify_comments"></label>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="form-group">
      <label>Comunicados</label>
      <div class="onoffswitch">
        <input type="checkbox" name="settings[dps_webpush_notify_announcements]" class="onoffswitch-checkbox" id="dps_webpush_notify_announcements" value="1" <?php if(get_option('dps_webpush_notify_announcements')=='1') echo 'checked'; ?>>
        <label class="onoffswitch-label" for="dps_webpush_notify_announcements"></label>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="form-group">
      <label>Tickets</label>
      <div class="onoffswitch">
        <input type="checkbox" name="settings[dps_webpush_notify_tickets]" class="onoffswitch-checkbox" id="dps_webpush_notify_tickets" value="1" <?php if(get_option('dps_webpush_notify_tickets')=='1') echo 'checked'; ?>>
        <label class="onoffswitch-label" for="dps_webpush_notify_tickets"></label>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
    <h5 class="bold">Chaves VAPID</h5>
    <div class="form-group">
      <label>Chave Publica VAPID</label>
      <input type="text" class="form-control" name="settings[dps_webpush_vapid_public]" value="<?php echo get_option('dps_webpush_vapid_public'); ?>">
    </div>
    <div class="form-group">
      <label>Chave Privada VAPID</label>
      <input type="text" class="form-control" name="settings[dps_webpush_vapid_private]" value="<?php echo get_option('dps_webpush_vapid_private'); ?>">
    </div>
  </div>
</div>