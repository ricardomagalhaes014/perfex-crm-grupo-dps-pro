 <script type="text/javascript">
      $(function(){
      'use strict';

      // Enable pusher logging - don't include this in production
      // Pusher.logToConsole = true;
         <?php $pusher_options = hooks()->apply_filters('pusher_options', array(['disableStats'=>true]));
         if(!isset($pusher_options['cluster']) && get_option('pusher_cluster') != ''){
            $pusher_options['cluster'] = get_option('pusher_cluster');
         }
         ?>
         var pusher_options = <?php echo json_encode($pusher_options); ?>;
         var pusher = new Pusher("<?php echo get_option('pusher_app_key'); ?>", pusher_options);
         var channel = pusher.subscribe('broker-notifications-channel-<?php echo get_broker_id(); ?>');
         channel.bind('notification', function(data) {
            broker_fetch_notifications();
         });
      });
   </script>