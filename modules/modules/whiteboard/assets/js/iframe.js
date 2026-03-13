var editfile = $('#whiteboardfile').val();
var myurl = $('#geturl').val();
$.ajax({ 
      url: myurl+'whiteboard/get_iframe_data/'+editfile,
      type: 'GET',
      async:false,
      success: function (result) {
         $('#whiteboardfile').val(result);
      }
  });

var lc = LC.init(
document.getElementsByClassName('literally localstorage')[0]);
var localStorageKey = 'drawing';
editfile = $('#whiteboardfile').val();
lc.loadSnapshot(JSON.parse(editfile));