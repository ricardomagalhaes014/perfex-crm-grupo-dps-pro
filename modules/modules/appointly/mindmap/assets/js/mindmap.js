

function loadGridView() {
     $('.grid_view').on('click',function(){
        console.log('asd');
       $('.btn-group').toggleClass('open');

    });
    var formData = {
        search: $("input#search").val(),
        start: 0,
        length: _lnth,
        draw: 1
    }
    gridViewDataCall(formData, function (resposne) {
        $('div#grid-tab').html(resposne);
        setTimeout(__renderGridViewMindMaps, 900)
    })
}
function gridViewDataCall(formData, successFn, errorFn) {
    $.ajax({
        url:  admin_url + 'mindmap/grid/'+(formData.start+1),
        method: 'POST',
        data: formData,
        async: false,
        // cache: false,
        error: function (res, st, err) {
            console.log("error API", err)
        },
        beforeSend: function () {
            // showalert('Please wait...', 'alert-info');
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

function __renderGridViewMindMaps() {
    $('div[id^="map_"]').each(function( index ) {
        setTimeout('', 200)
        var mId= $(this).attr('id');

        let cntrl = new MindElixir({
            el: '#'+mId,
            direction: 2,
            data: ($('textarea#m_'+mId).val() != '')?JSON.parse($('textarea#m_'+mId).val()): MindElixir.new('new topic'),
            draggable: false,
            contextMenu: false,
            toolBar: false,
            nodeMenu: false,
            keypress: false,
        });
        cntrl.init();
    });
}

// Init modal and get data from server
function init_mindmap_modal(id) {
    var $mindmapModal = $('#mindmap-modal');

    requestGet('mindmap/get_mindmap_data/' + id).done(function(response) {
        _task_append_html(response);
        setTimeout(__initMindMap, 500)
    }).fail(function(data) {
        alert_float('danger', data.responseText);
    });
}

function __initMindMap() {
    var mind = new MindElixir({
        el: '#map',
        direction: 2,
        data: ($('textarea#mindmap_content').val() != '')?JSON.parse($('textarea#mindmap_content').val()): MindElixir.new('new topic'),
        draggable: false,
        contextMenu: false,
        toolBar: true,
        nodeMenu: false,
        keypress: false,
    })
    mind.init();
}