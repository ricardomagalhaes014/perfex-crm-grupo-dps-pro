<?php
$apiKey = 'sk-9M0PZAb-vA-2eRaJTRZX_NWdenq_xswf8UxNMKWiWRT3BlbkFJtyRsR0_RzuTG9F9S84g_fAtePvunBF2zgg84H4K08A'; 

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.openai.com/v1/completions",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode(array(
    "model" => "text-davinci-003",
    "prompt" => "Escreva um e-mail de exemplo",
    "max_tokens" => 100
  )),
  CURLOPT_HTTPHEADER => array(
    "Content-Type: application/json",
    "Authorization: " . "Bearer " . $apiKey
  ),
));

$response = curl_exec($curl);

// Verifique se há erros na requisição
if ($response === false) {
    $error = curl_error($curl);
    echo "Erro no cURL: " . $error;
} else {
    // Capturar o status HTTP
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpStatus != 200) {
        echo "Erro HTTP Status: " . $httpStatus . "\n";
        echo "Resposta: " . $response;
    } else {
        // Mostrar a resposta da API se tudo estiver correto
        echo $response;
    }
}

curl_close($curl);
?>