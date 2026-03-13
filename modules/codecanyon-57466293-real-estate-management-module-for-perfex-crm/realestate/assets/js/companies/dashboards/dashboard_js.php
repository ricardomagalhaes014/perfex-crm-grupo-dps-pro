<script type="text/javascript">
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	(function($) {
		"use strict";
		report_by_sale_request_by_status('sale_request_by_status', '', '');
		report_by_sale_request_property_type('sale_percent_by_property_type', '', '<?php echo _l('real_report_by_sale_request_by_property_type'); ?>', 'buy');
		<?php if(is_staff_logged_in()){ ?>
			report_by_rent_request_by_status('rent_request_by_status', '', '');
			report_by_sale_request_property_type('rent_percent_by_property_type', '', '<?php echo _l('real_report_by_rent_request_by_property_type'); ?>', 'rent');
		<?php } ?>
		report_by_property_status('property_by_status', '', '');
		report_by_top_city_listing('report_by_top_city_listing','', '');
	})(jQuery);

	function report_by_sale_request_by_status(id, value, title_c){
		'use strict';

		var months_report = $('select[name="real_months-report"]').val(); 
		var report_from = $('input[name="real_report-from"]').val();
		var report_to = $('input[name="real_report-to"]').val();

		requestGetJSON(page_url + 'report_buy_sale_request_by_status?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

	   //get data for hightchart
			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false,
					type: 'pie'
				},
				credits: {
					enabled: false
				},
				title: {
					text: '<?php echo _l("real_report_by_sale_request_by_status")?> '
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				accessibility: {
					point: {
						valueSuffix: '%'
					}
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true
						},
						showInLegend: false
					}
				},
				series: [
				{
					minPointSize: 20,
					zMin: 0,
					borderRadius: 15,
					name: 'Percentage',
					colorByPoint: true,
					data: response.data_result
				}
				]
			});
		});
	}

	function report_by_rent_request_by_status(id, value, title_c){
		'use strict';

		var months_report = $('select[name="real_months-report"]').val(); 
		var report_from = $('input[name="real_report-from"]').val();
		var report_to = $('input[name="real_report-to"]').val();

		requestGetJSON(page_url + 'report_rent_sale_request_by_status?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

	   //get data for hightchart
			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false,
					type: 'pie'
				},
				credits: {
					enabled: false
				},
				title: {
					text: '<?php echo _l("real_report_by_rent_request_by_status")?> '
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				accessibility: {
					point: {
						valueSuffix: '%'
					}
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true
						},
						showInLegend: false
					}
				},
				series: [
				{
					minPointSize: 20,
					zMin: 0,
					borderRadius: 15,
					name: 'Percentage',
					colorByPoint: true,
					data: response.data_result
				}
				]
			});

		});

	}

	function report_by_sale_request_property_type(id, value, title_c, request_type){
		'use strict';

		var months_report = $('select[name="real_months-report"]').val(); 
		var report_from = $('input[name="real_report-from"]').val();
		var report_to = $('input[name="real_report-to"]').val();

		requestGetJSON(page_url + 'report_request_by_property_type?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to+'&request_type='+request_type).done(function (response) {

	   //get data for hightchart
			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false,
					type: 'pie'
				},
				credits: {
					enabled: false
				},
				title: {
					text: title_c
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				accessibility: {
					point: {
						valueSuffix: '%'
					}
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true
						},
						showInLegend: false
					}
				},
				series: [
				{
					minPointSize: 20,
					zMin: 0,
					borderRadius: 15,
					name: 'Percentage',
					colorByPoint: true,
					data: response.data_result
				}
				]
			});
		});

	}

	function report_by_property_status(id, value, title_c){
		'use strict';

		var months_report = $('select[name="real_months-report"]').val(); 
		var report_from = $('input[name="real_report-from"]').val();
		var report_to = $('input[name="real_report-to"]').val();

		requestGetJSON(page_url + 'report_by_listing_status?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {
			 //get data for hightchart
			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#ef370dc7','#15f34f','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false,
					type: 'pie'
				},
				credits: {
					enabled: false
				},
				title: {
					text: '<?php echo _l("real_report_by_property_status")?> '
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				accessibility: {
					point: {
						valueSuffix: '%'
					}
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true
						},
						showInLegend: true
					}
				},
				series: [{
					name: '<?php echo _l('ratio'); ?>',
					colorByPoint: true,
					data: response.data_result
				}]
			});


		});

	}


	function report_by_top_city_listing(id, value, title_c){
		'use strict';

		var months_report = $('select[name="real_months-report"]').val(); 
		var report_from = $('input[name="real_report-from"]').val();
		var report_to = $('input[name="real_report-to"]').val();

		requestGetJSON(page_url + 'report_by_top_city_listing?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fontWeight:'normal',
						fill: 'black'
					}
				},
				colors: ['#c0a43c' ,'#f2510e' ,'#651be6' ,'#79806e' ,'#61da5e' ,'#cd2f00','#a4d17a', '#225b8', '#be608b', '#96b00c', '#088baf',	'#63b598', '#ce7d78', '#ea9e70' ,'#a48a9e', '#c6e1e8', '#648177' ,'#0d5ac1','#00FF7F', '#0cffe95c','#80da22','#f37b15','#da1818','#176cea','#5be4f0', '#57c4d8', '#d2737d']
			});

			Highcharts.chart(id, {
				chart: {
					backgroundcolor: '#fcfcfc8a',
					type: 'column'
				},
				accessibility: {
					description: null
				},
				title: {
					text: '<?php echo _l('real_report_by_top_city_listing'); ?>'
				},
				credits: {
					enabled: false
				},
				tooltip: {
					pointFormat: '<span style="color:{series.color}">'+<?php echo json_encode(_l('invoice_table_quantity_heading')); ?>+'</span>: <b>{point.y}</b> '+<?php echo json_encode(_l('real_listing')); ?>+'  <br/>',
					shared: true
				},
				legend: {
					enabled: false
				},
				xAxis: {
					categories: response.categories,
					crosshair: true
				},
				yAxis: {
					title: {
						text: ''
					}

				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						depth: 35,
						dataLabels: {
							enabled: true,
							format: '{point.name}'
						}        
					}
				},
				series: [{
					name: '',
					data: response.data_result 

				}]
			});
		});
	}

</script>