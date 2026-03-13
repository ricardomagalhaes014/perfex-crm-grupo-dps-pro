<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Iframe content</title>
    <style type="text/css">
        .literally.toolbar-at-top .lc-drawing {
    bottom: 0;
    top: 0px !important;
}
.literally .lc-drawing.with-gui {
     left: 0 !important; 
}
    </style>
 	<link href="<?php echo base_url();?>modules/whiteboard/_assets/literallycanvas.css" rel="stylesheet">
    <link href="<?php echo base_url();?>modules/whiteboard/assets/css/iframe.css" rel="stylesheet">
 <script src="<?php echo base_url();?>modules/whiteboard/_js_libs/react-0.14.3.js"></script>
<script src="<?php echo base_url();?>modules/whiteboard/_js_libs/literallycanvas.js"></script>
<script src="<?php echo base_url();?>modules/whiteboard/assets/js/myjquery.js"></script>
     <div class="literally localstorage" ></div>
     <form method="POST" action="view_iframe.php">
     <textarea id="whiteboardfile"><?php echo $_GET['filename'];?></textarea>
     <input type="hidden" id="geturl" name="<?php echo base_url();?>">
    </form> 
    <script src="<?php echo base_url();?>modules/whiteboard/assets/js/iframe.js"></script>
  </body>
</html>

