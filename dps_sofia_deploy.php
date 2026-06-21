<?php
/**
 * DPS Sofia Deploy Script
 * Faz o deploy do sofia-widget.js nos ficheiros raizes e belohorizonte
 * Executar uma vez e depois apagar
 */

// Segurança básica
if (!isset($_GET['key']) || $_GET['key'] !== 'dps2026deploy') {
    die('Acesso negado');
}

$results = [];

// Função para adicionar sofia-widget.js a um ficheiro HTML
function addSofiaWidget($filepath, $label) {
    global $results;
    
    if (!file_exists($filepath)) {
        $results[] = "❌ $label: Ficheiro não encontrado: $filepath";
        return;
    }
    
    $content = file_get_contents($filepath);
    $script = '<script src="https://cdn.jsdelivr.net/gh/ricardomagalhaes014/perfex-crm-grupo-dps-pro@main/sofia-widget.js"></script>';
    
    // Verificar se já tem o widget
    if (strpos($content, 'sofia-widget.js') !== false) {
        $results[] = "✅ $label: Sofia widget já presente";
        return;
    }
    
    // Remover bloco antigo dps-btn se existir
    $content = preg_replace('/<style>\s*#dps-btn.*?<\/style>/s', '', $content);
    $content = preg_replace('/<button id="dps-btn".*?<\/button>/s', '', $content);
    $content = preg_replace('/<div id="dps-tip".*?<\/div>/s', '', $content);
    $content = preg_replace('/<script src="https:\/\/cdn\.jsdelivr\.net[^"]*sofia-widget[^"]*"><\/script>/s', '', $content);
    
    // Adicionar antes de </body>
    if (strpos($content, '</body>') !== false) {
        $content = str_replace('</body>', $script . "\n</body>", $content);
        if (file_put_contents($filepath, $content)) {
            $results[] = "✅ $label: Sofia widget adicionado com sucesso";
        } else {
            $results[] = "❌ $label: Erro ao escrever ficheiro";
        }
    } else {
        $results[] = "❌ $label: Tag </body> não encontrada";
    }
}

// Determinar o caminho base do servidor
$base = dirname(dirname(__FILE__)); // subir 2 níveis do /admin
$results[] = "📁 Base path: $base";

// Deploy raizes
addSofiaWidget($base . '/raizes/index.html', 'Raizes');

// Deploy belohorizonte  
addSofiaWidget($base . '/belohorizonte/index.html', 'Belo Horizonte');

// Mostrar resultados
echo '<pre style="font-family:monospace;font-size:14px;padding:20px;">';
echo "=== DPS Sofia Deploy ===\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n=== Concluído ===";
echo '</pre>';
