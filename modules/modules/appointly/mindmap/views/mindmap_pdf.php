<link rel="stylesheet" type="text/css" id="bootstrap-css" href="<?php echo site_url('assets/plugins/bootstrap/css/bootstrap.min.css'); ?>">

<link rel="stylesheet" type="text/css" id="roboto-css" href="<?php echo site_url('assets/plugins/roboto/roboto.css'); ?>">
<link rel="stylesheet" type="text/css" id="datatables-css" href="<?php echo site_url('assets/plugins/datatables/datatables.min.css'); ?>">
<link rel="stylesheet" type="text/css" id="fontawesome-css" href="<?php echo site_url('assets/plugins/font-awesome/css/font-awesome.min.css'); ?>">
<link rel="stylesheet" type="text/css" id="datetimepicker-css" href="<?php echo site_url('assets/plugins/datetimepicker/jquery.datetimepicker.min.css'); ?>">
<link rel="stylesheet" type="text/css" id="bootstrap-select-css" href="<?php echo site_url('assets/plugins/bootstrap-select/css/bootstrap-select.min.css'); ?>">
<link rel="stylesheet" type="text/css" id="lightbox-css" href="<?php echo site_url('assets/plugins/lightbox/css/lightbox.min.css'); ?>">
<link rel="stylesheet" type="text/css" id="colorpicker-css" href="<?php echo site_url('assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css'); ?>">
<link rel="stylesheet" type="text/css" id="bootstrap-overrides-css" href="<?php echo site_url('assets/css/bs-overides.min.css'); ?>">
<link rel="stylesheet" type="text/css" id="theme-css" href="<?php echo site_url('assets/themes/perfex/css/style.min.css'); ?>">
<nav class="navbar navbar-default header">
  <style type="text/css">
    .mindmpa-view-page {
border: 2px solid #505f7b;
background-color: #f6f6f6;
}
  </style>
   <div class="container">
      <!-- Brand and toggle get grouped for better mobile display -->
      <div class="navbar-header">
         <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#theme-navbar-collapse" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
         </button>
         <a href="<?php echo site_url()?>" class="navbar-brand logo logo logo-text"><?php echo !empty(get_option('companyname')) ? get_option('companyname') : 'Perfex';?></a>      </div>
      <!-- Collect the nav links, forms, and other content for toggling -->
      <div class="collapse navbar-collapse" id="theme-navbar-collapse">
         <ul class="nav navbar-nav navbar-right">
                <li class="customers-nav-item-knowledge-base">
                  <a href="<?php echo site_url('knowledge-base'); ?>">
                     Knowledge Base  </a>
               </li>
                 
               <?php if(is_client_logged_in()) { ?>
                 <li class="customers-nav-item-logout">
                        <a href="<?php echo site_url('authentication/logout'); ?>">
                           <?php echo _l('clients_nav_logout'); ?>
                        </a>
                     </li>
               <?php }else{?>
                <li class="customers-nav-item-login">
                        <a href="<?php echo site_url('authentication/login'); ?>">
                           <?php echo _l('clients_nav_login'); ?>
                        </a>
                     </li>

               <?php }  ?>
         </ul>
      </div>
      <!-- /.navbar-collapse -->
   </div>
   <!-- /.container-fluid -->
</nav>
<div id="wrapper">
   <div id="content">
      <div class="container mindmpa-view-page">

         <div class="row">
            <?php 
          $value = (isset($mindmap) ? $mindmap->mindmap_content : '');
          ?>
            <textarea style="display: none" id="mindmap_content" name="mindmap_content"><?php echo $value;?></textarea>
            <div class="container" style="width: 1170px;">
                <div class="row">
                <div class="col-md-12">
                    <div id="map"></div>
                    <style>
                        #map {
                            height: 800px;
                            width: 100%;
                        }
                    </style>
                </div>
                </div>
            </div>
         </div>
       </div>
      
     </div>
   



  <footer class="navbar-fixed-bottom footer">
    <div class="container">
      <div class="row">
        <div class="col-md-12 text-center">
         <span class="copyright-footer"><?php echo date('Y'); ?> <?php echo _l('clients_copyright', get_option('companyname')); ?></span>
      </div>
    </div>
</footer>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/regenerator-runtime"></script>
<script src="https://cdn.jsdelivr.net/npm/mind-elixir/dist/mind-elixir.js"></script>
<script type="text/javascript">
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

 
$(function() {
    var mind = new MindElixir({
        el: '#map',
        direction: 2,
        data: ($('textarea#mindmap_content').val() != '')?JSON.parse($('textarea#mindmap_content').val()): MindElixir.new('new topic'),
        draggable: false,
        contextMenu: false,
        toolBar: false,
        nodeMenu: false,
        keypress: false,
    })
    mind.init();
});

$(document).ready(function(){
    $('#print').click(function(){
    window.print();
 });
});
</script>

<style type="text/css">
    .lt{width: 40px !important;}
    nmenu { border: 1px solid blue !important;}
</style>