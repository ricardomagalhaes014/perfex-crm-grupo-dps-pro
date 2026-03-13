<script>
	$(function() {
		'use strict';

		var ContractsServerParams = {};
		$.each($('._hidden_inputs._filters input'), function() {
			ContractsServerParams[$(this).attr('name')] = '[name="' + $(this).attr('name') + '"]';
		});

		initDataTable('.table-contracts', site_url + 'realestate/broker/contract_table', [0],[1], ContractsServerParams,[0, 'asc']);

		new Chart($('#contracts-by-type-chart'), {
			type: 'bar',
			data: <?php echo html_entity_decode($chart_types); ?>,
			options: {
				legend: {
					display: false,
				},
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						display: true,
						ticks: {
							suggestedMin: 0,
						}
					}]
				}
			}
		});
		new Chart($('#contracts-value-by-type-chart'), {
			type: 'line',
			data: <?php echo html_entity_decode($chart_types_values); ?>,
			options: {
				responsive: true,
				legend: {
					display: false,
				},
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						display: true,
						ticks: {
							suggestedMin: 0,
						}
					}]
				}
			}
		});
	});
</script>