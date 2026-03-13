
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">

   <div class="content">
   <div class="col-md-5 left-column">

            <div class="panel_s">

               <div class="panel-body">
                <div class="row">
                        <div class="col-md-12">
                            <form action="telegram_chat/addTelegramInfo" method="post"> 
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Telegram Bot Token</label>
                                    <input type="text" class="form-control" name="bot_token" id="bot_token" value="<?php echo $userTeleInfo->bot_token;?>"  placeholder="Enter telegram bot token">
                                    <small id="emailHelp" class="form-text text-muted">Please enter telegram Bot token without bot.</small>
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Telegram Chat/Group Id</label>
                                    <input type="text" class="form-control" name="chat_id" id="chatID" value="<?php echo $userTeleInfo->chat_id;?>"  placeholder="Enter telegram chat id">
                                    <small id="emailHelp" class="form-text text-muted">Please enter telegram chat id.</small>
                                </div>
                                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name();?>" value="<?php echo $this->security->get_csrf_hash();?>">
                                <button type="submit"  class="btn btn-primary">Submit</button>
                            </form>
                        </div>

                </div>

                            </div>
                    </div>
            </div>
</div>


        </div>

        </div>

        </div>

        </div>



        </div>

        </div>

        </div>

<?php init_tail(); ?>



</body>

</html>






