<?php defined('BASEPATH') OR exit('No direct script access allowed');

 
 require_once dirname(__FILE__).'/pdf/vendor/autoload.php';
use Dompdf\Dompdf;
class Pdf
{
    public function __construct(){
        
        // include autoloader
        
        
        // instantiate and use the dompdf class
        $pdf = new Dompdf();
       
        $CI =& get_instance();
        $CI->dompdf = $pdf;
        
    }
    protected function ci()
{
    return get_instance();
}
    public function load_view($view, $data = array())
	{
	    $dompdf = new Dompdf();
	    $html = $this->ci()->load->view($view, $data, TRUE);

	    $dompdf->loadHtml($html);

	    // (Optional) Setup the paper size and orientation
	    $dompdf->setPaper('A4', 'landscape');

	    // Render the HTML as PDF
	    $dompdf->render();
	    $time = time();

	    // Output the generated PDF to Browser
	    $dompdf->stream("mindmap-". $time);
	}
}
?>
