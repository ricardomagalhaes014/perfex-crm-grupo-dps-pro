<?php
// Script temporário para corrigir o imoveis-api.php no servidor
// Remove-se após execução

$api_file = __DIR__ . '/imoveis-api.php';

if (!file_exists($api_file)) {
    die(json_encode(['error' => 'imoveis-api.php nao encontrado em: ' . $api_file]));
}

$content = file_get_contents($api_file);
$original = $content;

// Correcção 1: foto_principal deve ter URL completo para o React
$old1 = "        if (!empty(\$row['foto_principal'])) {\n            \$row['foto_principal_url'] = \$base_url . \$row['foto_principal'];\n        } else {\n            \$row['foto_principal_url'] = null;\n        }";
$new1 = "        if (!empty(\$row['foto_principal'])) {\n            \$row['foto_principal_url'] = \$base_url . \$row['foto_principal'];\n            \$row['foto_principal'] = \$base_url . \$row['foto_principal']; // URL completo para React\n        } else {\n            \$row['foto_principal_url'] = null;\n            \$row['foto_principal'] = null;\n        }";

// Correcção 2: adicionar 'fotos' como alias de 'fotos_urls' para compatibilidade com React
$old2 = "        \$row['fotos_urls'] = \$fotos_arr;";
$new2 = "        \$row['fotos_urls'] = \$fotos_arr;\n        \$row['fotos'] = \$fotos_arr; // Alias para React";

$c1 = false;
$c2 = false;

if (strpos($content, $old1) !== false) {
    $content = str_replace($old1, $new1, $content);
    $c1 = true;
}

if (strpos($content, $old2) !== false) {
    $content = str_replace($old2, $new2, $content);
    $c2 = true;
}

// Verificar se já foi corrigido anteriormente
$already1 = strpos($content, "// URL completo para React") !== false;
$already2 = strpos($content, "// Alias para React") !== false;

if ($content !== $original) {
    file_put_contents($api_file, $content);
    echo json_encode([
        'success' => true,
        'correcao_1_foto_principal' => $c1 ? 'aplicada' : ($already1 ? 'ja aplicada' : 'nao encontrada'),
        'correcao_2_fotos_alias' => $c2 ? 'aplicada' : ($already2 ? 'ja aplicada' : 'nao encontrada'),
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Sem alteracoes necessarias',
        'correcao_1_foto_principal' => $already1 ? 'ja aplicada' : 'nao encontrada - verificar manualmente',
        'correcao_2_fotos_alias' => $already2 ? 'ja aplicada' : 'nao encontrada - verificar manualmente',
    ]);
}

// Auto-remover
unlink(__FILE__);
