<?php
// Script de emergência - desactiva módulo Promotores na BD
// Executado automaticamente pelo Perfex na próxima visita
// NÃO usa defined('BASEPATH') para poder correr standalone

define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

// Desactivar o módulo promotores na BD
$conn->query("UPDATE tblmodules SET active = 0 WHERE module_name = 'promotores'");
// Em alternativa, remover completamente
// $conn->query("DELETE FROM tblmodules WHERE module_name = 'promotores'");

$conn->close();

header('Location: /admin');
exit;
