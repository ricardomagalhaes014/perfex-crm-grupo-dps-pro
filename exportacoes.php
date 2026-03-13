<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
Class config {
        var $db;
        var $login;
        var $senha;
        var $odbc;
        var $driver;
        var $servidor;

		function __construct () {
                 
                 $this->db="u677382737_crm";
                 $this->login="u677382737_crm";
                 $this->senha="oHMDM%s5cdm4";
                 $this->servidor="localhost";
        }

}


function ExecutaSQL($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
	
	$sql = $script;
	//echo "<br>".$sql;
	$results = $conn->query($sql);
	
	$results = null;
	$conn = null;

}


function DaRecordset($script){
	$cConfig = new config();
	$conn = new PDO("mysql:host=".$cConfig->servidor.";dbname=".$cConfig->db, $cConfig->login, $cConfig->senha);
	
	$sql = $script;
	//echo "<br>".$sql;
	$results = $conn->query($sql);
	
	return $results;
}

function PrintR($text){
    print_r("<pre>");
    print_r($text);
    print_r("</pre>");
}
*/


$sql = "SELECT ev.*, st.email FROM tblevents ev inner join tblstaff st on ev.userid=st.staffid;"
PrintR($sql);

//$result=DaRecordset($sql);

//PrintR($result);

?>