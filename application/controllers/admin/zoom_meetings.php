<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Zoom_auth extends AdminController
{
    public function callback()
    {
        log_activity('Zoom Callback Accessed');
        echo "Zoom Callback Executado com Sucesso!";
    }
}