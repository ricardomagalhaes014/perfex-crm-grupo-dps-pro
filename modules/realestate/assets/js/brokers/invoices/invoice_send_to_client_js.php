<script>
	$(function(){
		'use strict';

		$('#attach_statement').on('change', function() {
			'use strict';

			if($(this).prop('checked') === false) {
				$('#statement-period').addClass('hide');
			} else {
				$('#statement-period').removeClass('hide');
			}
		})

		$('#invoice_send_to_client_modal form').on('submit', function() {
			'use strict';
			

			if($('#attach_statement').prop('checked') === false) {
				return true;
			}

			var $statementPeriod = $('#range');
			var value = $statementPeriod.selectpicker('val');
			var period = new Array();
			if(value != 'period'){
				period = JSON.parse(value);
			} else {
				period[0] = $('input[name="period-from"]').val();
				period[1] = $('input[name="period-to"]').val();
			}

			$(this).find('input[name="statement_from"]').val(period[0]);
			$(this).find('input[name="statement_to"]').val(period[1]);

			return true;
		})
	})
</script>