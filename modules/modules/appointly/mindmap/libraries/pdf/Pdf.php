<?php defined('BASEPATH') OR exit('No direct script access allowed');

 
 require_once dirname(__FILE__).'vendor/autoload.php';
use Dompdf;
class Pdf
{
    public function __construct(){
        
        // include autoloader
        
        
        // instantiate and use the dompdf class
        $pdf = new Dompdf();
       
        $CI =& get_instance();
        $CI->dompdf = $pdf;
        
    }
}
?>
