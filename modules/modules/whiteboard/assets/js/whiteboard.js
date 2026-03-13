function loadGridView() {
    
    var formData = {
        search: $("input#search").val(),
        start: 0,
        length: _lnth,
        draw: 1
    }
    gridViewDataCall(formData, function (resposne) {

        $('div#grid-tab').html(resposne);

        setTimeout(__renderGridViewWhiteboard, 900)
    })
}
function gridViewDataCall(formData, successFn, errorFn) {

    $.ajax({
        url:  admin_url + 'whiteboard/grid/'+(formData.start+1),
        method: 'POST',
        data: formData,
        async: false,
        error: function (res, st, err) {
            console.log("error API", err)
        },
        beforeSend: function () {
        },
        complete: function () {
        },
        success: function (response) {
            if ($.isFunction(successFn)) {
                successFn.call(this, response);
            }
        }
    });
}

function __renderGridViewWhiteboard() {
    var i=0;
    $('div[id^="map_"]').each(function(index) {    
        setTimeout('', 200)
       
      
});
   
}

// Init modal and get data from server
function init_whiteboard_modal(id) {
    var $whiteboardModal = $('#whiteboard-modal');

    requestGet('whiteboard/get_whiteboard_data/' + id).done(function(response) {
        _task_append_html(response);
        setTimeout(__initWhiteboard, 500)
    }).fail(function(data) {
        alert_float('danger', data.responseText);
    });
}

function __initWhiteboard() {
     var lc = LC.init(
      document.getElementsByClassName('literally localstorage')[0]);
    var localStorageKey = 'drawing';
   
  
     var editfile = '';
        editfile = $('textarea#whiteboard_content').val();
       var node = document.getElementById('whiteboard_content'),
        jsondata = node.innerHTML;
        if(jsondata!='')
        {
         lc.loadSnapshot(JSON.parse(jsondata));
        }
    lc.on('drawingChange', function() {
      localStorage.setItem(localStorageKey, JSON.stringify(lc.getSnapshot()));
      var checkpic =  localStorage.getItem(localStorageKey);
      $('#whiteboard_content').text(checkpic);
    });

}