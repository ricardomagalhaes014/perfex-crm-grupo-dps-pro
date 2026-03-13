<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Redirect extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function utilities_article_details($id)
    {
        redirect('article/' . $id, 'location', 301);
    }
}
