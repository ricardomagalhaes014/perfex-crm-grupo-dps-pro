<?php

defined('BASEPATH') or exit('No direct script access allowed');
include_once APPPATH . 'libraries/pdf/App_pdf.php';

class Property_request_pdf extends App_pdf
{
	protected $property_request;

	private $property_request_number;

	/**
	 * construct
	 * @param [type] $property_request 
	 * @param string $tag              
	 */
	public function __construct($property_request, $tag = '')
	{
		$this->load_language($property_request->clientid);
		$property_request                = hooks()->apply_filters('property_request_html_pdf_data', $property_request);
		$GLOBALS['property_request_pdf'] = $property_request;

		parent::__construct();

		$this->property_request        = $property_request;
		$this->property_request_number = $this->property_request->code;

		$this->SetTitle($this->property_request->code);
	}

	public function prepare()
	{

		$this->set_view_vars([
			'property_request_number' => $this->property_request_number,
			'property_request'        => $this->property_request,
		]);

		return $this->build();
	}

	protected function type()
	{
		return 'property_request';
	}

	protected function file_path()
	{
		$actualPath = APP_MODULES_PATH . '/realestate/views/companies/property_requests/property_request_pdf.php';
		return $actualPath;
	}

}
