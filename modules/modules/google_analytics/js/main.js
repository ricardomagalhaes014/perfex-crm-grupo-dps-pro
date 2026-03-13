function save_google_analytics() {
	"use strict"; 
    $.post(admin_url + 'google_analytics/save', {
        admin_area: $('#google_analytics_admin_area').val(),
        clients_area: $('#google_analytics_clients_area').val(),
        clients_and_admin: $('#google_analytics_clients_and_admin_area').val(),
    }).done(function(response) {
        window.location = admin_url + 'google_analytics';
    });
}

function enable_google_analytics() {
	"use strict"; 
    $.post(admin_url + 'google_analytics/enable', {}).done(function() {
        window.location = admin_url + 'google_analytics';
    });
}

function disable_google_analytics() {
	"use strict"; 
    $.post(admin_url + 'google_analytics/disable', {}).done(function() {
        window.location = admin_url + 'google_analytics';
    });
}