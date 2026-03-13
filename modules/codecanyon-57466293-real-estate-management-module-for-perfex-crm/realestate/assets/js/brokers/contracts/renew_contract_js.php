<script>
    $(function(){
        'use strict';
        
        $('#renew_keep_signature').on('change', function(e) {
            $("#new_value").prop('disabled', this.checked)
        });
    })
</script>
