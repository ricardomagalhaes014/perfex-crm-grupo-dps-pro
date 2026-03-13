<div class="modal fade" id="edit_category_data" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="exampleModalLabel">Category</h4>
      </div>
      <?php echo form_open(admin_url('video_library/update_category_data') , ['id'=>'update-category-form']); ?>
        <div class="modal-body">
         <div class="card">
          <div class="card-body">
            <div id="response"> </div>
            <div class="row">
             <?php echo form_hidden('video_id', $edit_data[0]['id']); ?>
             <div class="col-md-8">
              <div class="form-group">
                <label for="category">Edit Category:</label>
                <input type="text" class="form-control" value="<?php echo $edit_data[0]['category']?>" name="category" id="category" placeholder="Enter Category" required />
              </div>
            </div>   
            <div class="col-md-4">
              <div class="form-group">
                <button type="button" onclick="update_categeory_form();" class="btn btn-primary" style="margin-top: 25px;">Save </button>
              </div>
            </div>   
          </div>

        </div>
      </div>
    </div>
  <?php echo form_close(); ?>
</div>
</div>