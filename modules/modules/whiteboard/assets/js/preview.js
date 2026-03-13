$(function() {
  "use strict";
 $('#expand-button').click(function(){
  $('#top-panel').slideToggle( "slow" );
  $('#expand-button').hide();
  $("html, body").animate({ scrollTop: 0 }, "slow");
  return false;
 });

 $('#close').click(function(){
  $('#top-panel').slideToggle( "slow" );
  $('#expand-button').show();
 });   
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
});

  var whiteboard_id = $('input[name="whiteboard_id"]').val();
    var staffid = $('input[name="staffid"]').val();

 $("button.whiteboard-btns").on('click', function (e) {
    console.log('as');
        $.ajax({
        url: admin_url+'whiteboard/update_whiteboard',
        data: ({ 'id':whiteboard_id,whiteboard_content:$('#whiteboard_content').text(),'staffid':staffid }),
       
        type: 'post',
        success: function(data) {
            //response = jQuery.parseJSON(data);
           window.location.reload(true);
        }             
    
    });

});
$(document).ready(function(){
    $('#print').click(function(){
    window.print();
 });
});