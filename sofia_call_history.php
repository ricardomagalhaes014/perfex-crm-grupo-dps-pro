<?php
/**
 * Sofia Outbound - Histórico de Chamadas
 * Consulta o ElevenLabs API para mostrar chamadas realizadas pela Sofia
 * URL: https://crm.grupo-dps.com/sofia_call_history.php
 */

/* =====================================================================
 * PORTA FECHADA
 *
 * Esta página esteve aberta a QUALQUER PESSOA na internet: bastava saber o
 * endereço para ler o histórico de chamadas da Sofia — com quem falou, quando,
 * e o resumo da conversa. Não pedia sessão, nem token, nem nada (03/08/2026).
 *
 * Agora exige uma sessão de funcionário do CRM, validada contra a tabela de
 * sessões. Sendo um script fora do CodeIgniter, não há `is_staff_logged_in()`
 * — lê-se o cookie e confirma-se na base de dados que aquela sessão existe e
 * pertence a alguém autenticado.
 * ================================================================== */

$dps_cfg = (string) @file_get_contents(__DIR__ . '/application/config/app-config.php');
$dps_ler = static function (string $k) use ($dps_cfg): string {
    return preg_match("/define\(\s*'" . $k . "'\s*,\s*'([^']*)'\s*\)/", $dps_cfg, $m) ? $m[1] : '';
};

$dps_bd = @new mysqli(
    $dps_ler('APP_DB_HOSTNAME') ?: 'localhost',
    $dps_ler('APP_DB_USERNAME'),
    $dps_ler('APP_DB_PASSWORD'),
    $dps_ler('APP_DB_NAME')
);

$dps_autorizado = false;

if (!$dps_bd->connect_error) {
    $dps_bd->set_charset('utf8mb4');
    $dps_prefixo = $dps_ler('APP_DB_PREFIX') ?: 'tbl';

    foreach ($_COOKIE as $dps_valor) {
        // Um id de sessão do CodeIgniter é uma cadeia longa de hexadecimais.
        if (!is_string($dps_valor) || !preg_match('/^[A-Za-z0-9]{26,}$/', $dps_valor)) {
            continue;
        }

        $dps_st = $dps_bd->prepare("SELECT data FROM `{$dps_prefixo}sessions` WHERE id = ? LIMIT 1");
        if (!$dps_st) {
            break;
        }
        $dps_st->bind_param('s', $dps_valor);
        $dps_st->execute();
        $dps_linha = $dps_st->get_result()->fetch_assoc();
        $dps_st->close();

        // A sessão tem de existir E pertencer a um funcionário autenticado.
        if ($dps_linha && strpos((string) $dps_linha['data'], 'staff_user_id') !== false) {
            $dps_autorizado = true;
            break;
        }
    }
}

if (!$dps_autorizado) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="font-family:system-ui,sans-serif;max-width:520px;margin:80px auto;'
       . 'padding:26px 30px;border:1px solid #e5e7eb;border-radius:10px;line-height:1.6;">'
       . '<h2 style="margin:0 0 10px;">Histórico de chamadas &mdash; privado</h2>'
       . '<p>Esta página só abre a quem tem sessão iniciada no CRM.</p>'
       . '<p><a href="https://crm.grupo-dps.com/admin">&larr; Entrar no CRM</a></p></div>';
    exit;
}

/* =====================================================================
 * A CHAVE
 *
 * Estava escrita aqui, em texto, e este ficheiro está no repositório — ou
 * seja, a chave viajou para o GitHub e ficou no histórico para sempre.
 * Passa a ser lida de um ficheiro fora do repositório, na pasta protegida
 * uploads/dps_secure/ (o deploy não lhe toca e o .htaccess tapa-a).
 *
 * ATENÇÃO: tirá-la daqui NÃO desfaz a exposição. A chave antiga tem de ser
 * REVOGADA no painel da ElevenLabs e substituída por uma nova nesse ficheiro.
 * ================================================================== */

$dps_ficheiro_chave = __DIR__ . '/uploads/dps_secure/elevenlabs_key';
$ELEVENLABS_API_KEY = is_readable($dps_ficheiro_chave)
    ? trim((string) file_get_contents($dps_ficheiro_chave))
    : '';

if ($ELEVENLABS_API_KEY === '') {
    echo '<div style="font-family:system-ui,sans-serif;max-width:560px;margin:60px auto;'
       . 'padding:20px 24px;border:1px solid #e0575b;border-radius:8px;">'
       . '<strong>Falta a chave da ElevenLabs.</strong><br>'
       . 'Coloque-a em <code>uploads/dps_secure/elevenlabs_key</code>.</div>';
    exit;
}

$AGENT_RAIZES       = "agent_4301kv1pv8g8e259bbdyfk7mrefb";
$AGENT_BH           = "agent_9901kv1pvewveh9s9ebs1rys274k";

// Obter histórico de conversas do ElevenLabs
function getConversations($api_key, $agent_id, $agent_label) {
    $url = "https://api.elevenlabs.io/v1/convai/conversations?agent_id={$agent_id}&page_size=50";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["xi-api-key: {$api_key}"],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) return [];

    $data = json_decode($response, true);
    $conversations = $data['conversations'] ?? [];

    foreach ($conversations as &$conv) {
        $conv['agent_label'] = $agent_label;
    }
    return $conversations;
}

// Obter detalhes de uma conversa (para duração)
function getConversationDetail($api_key, $conv_id) {
    $url = "https://api.elevenlabs.io/v1/convai/conversations/{$conv_id}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["xi-api-key: {$api_key}"],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

// Formatar duração em segundos para mm:ss
function formatDuration($seconds) {
    if (!$seconds) return '—';
    $m = floor($seconds / 60);
    $s = $seconds % 60;
    return sprintf('%d:%02d', $m, $s);
}

// Formatar timestamp Unix para data/hora PT
function formatDate($ts) {
    if (!$ts) return '—';
    return date('d/m/Y H:i:s', $ts);
}

// Status badge
function statusBadge($status) {
    $map = [
        'done'         => ['Concluída',    '#28a745'],
        'processing'   => ['Em curso',     '#007bff'],
        'failed'       => ['Falhou',       '#dc3545'],
        'no-answer'    => ['Não atendeu',  '#fd7e14'],
        'busy'         => ['Ocupado',      '#ffc107'],
    ];
    $s = strtolower($status ?? '');
    $label = $map[$s][0] ?? ucfirst($s);
    $color = $map[$s][1] ?? '#6c757d';
    return "<span style='background:{$color};color:#fff;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:600;'>{$label}</span>";
}

// Buscar dados
$all = array_merge(
    getConversations($ELEVENLABS_API_KEY, $AGENT_BH,     'Belo Horizonte'),
    getConversations($ELEVENLABS_API_KEY, $AGENT_RAIZES, 'Raízes')
);

// Ordenar por data (mais recente primeiro)
usort($all, fn($a, $b) => ($b['start_time_unix_secs'] ?? 0) - ($a['start_time_unix_secs'] ?? 0));

$total     = count($all);
$concluidas = count(array_filter($all, fn($c) => strtolower($c['status'] ?? '') === 'done'));
$hoje      = count(array_filter($all, fn($c) => date('Y-m-d', $c['start_time_unix_secs'] ?? 0) === date('Y-m-d')));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sofia Outbound — Histórico de Chamadas</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f6f9;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #fff;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .header-icon {
            width: 48px; height: 48px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .header h1 { font-size: 22px; font-weight: 700; }
        .header p  { font-size: 13px; opacity: 0.7; margin-top: 2px; }
        .header-right { margin-left: auto; font-size: 12px; opacity: 0.6; text-align: right; }

        .stats {
            display: flex; gap: 16px;
            padding: 20px 32px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px 24px;
            min-width: 160px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            border-left: 4px solid #0f3460;
        }
        .stat-card.green  { border-left-color: #28a745; }
        .stat-card.orange { border-left-color: #fd7e14; }
        .stat-card .num { font-size: 28px; font-weight: 700; color: #0f3460; }
        .stat-card.green  .num { color: #28a745; }
        .stat-card.orange .num { color: #fd7e14; }
        .stat-card .lbl { font-size: 12px; color: #888; margin-top: 2px; }

        .container { padding: 0 32px 32px; }

        .filters {
            display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;
        }
        .filters input, .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            background: #fff;
        }
        .filters input { width: 240px; }
        .btn-refresh {
            margin-left: auto;
            background: #0f3460; color: #fff;
            border: none; border-radius: 6px;
            padding: 8px 16px; font-size: 13px;
            cursor: pointer; text-decoration: none;
            display: inline-block;
        }
        .btn-refresh:hover { background: #16213e; }

        table {
            width: 100%; border-collapse: collapse;
            background: #fff; border-radius: 10px;
            overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        thead th {
            background: #0f3460; color: #fff;
            padding: 12px 14px; font-size: 12px;
            text-align: left; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        tbody tr { border-bottom: 1px solid #f0f0f0; }
        tbody tr:hover { background: #f8f9ff; }
        tbody td { padding: 12px 14px; font-size: 13px; vertical-align: middle; }
        .conv-id { font-family: monospace; font-size: 11px; color: #888; }
        .agent-badge {
            display: inline-block;
            padding: 2px 8px; border-radius: 12px;
            font-size: 11px; font-weight: 600;
        }
        .agent-bh     { background: #e3f2fd; color: #1565c0; }
        .agent-raizes { background: #f3e5f5; color: #6a1b9a; }

        .empty { text-align: center; padding: 48px; color: #aaa; font-size: 14px; }
        .footer { text-align: center; padding: 16px; font-size: 11px; color: #bbb; }

        @media (max-width: 768px) {
            .header, .stats, .container { padding-left: 16px; padding-right: 16px; }
            .filters input { width: 100%; }
            table { font-size: 12px; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-icon">📞</div>
    <div>
        <h1>Sofia Outbound — Histórico de Chamadas</h1>
        <p>Chamadas automáticas realizadas pelos agentes Sofia</p>
    </div>
    <div class="header-right">
        Atualizado em<br><?= date('d/m/Y H:i:s') ?>
    </div>
</div>

<div class="stats">
    <div class="stat-card">
        <div class="num"><?= $total ?></div>
        <div class="lbl">Total de Chamadas</div>
    </div>
    <div class="stat-card green">
        <div class="num"><?= $concluidas ?></div>
        <div class="lbl">Concluídas</div>
    </div>
    <div class="stat-card orange">
        <div class="num"><?= $hoje ?></div>
        <div class="lbl">Hoje</div>
    </div>
    <div class="stat-card">
        <div class="num"><?= $total - $concluidas ?></div>
        <div class="lbl">Não Atendidas / Outras</div>
    </div>
</div>

<div class="container">
    <div class="filters">
        <input type="text" id="searchInput" placeholder="🔍  Pesquisar cliente, telefone..." oninput="filterTable()">
        <select id="agentFilter" onchange="filterTable()">
            <option value="">Todos os Agentes</option>
            <option value="Belo Horizonte">Belo Horizonte</option>
            <option value="Raízes">Raízes</option>
        </select>
        <select id="statusFilter" onchange="filterTable()">
            <option value="">Todos os Status</option>
            <option value="done">Concluída</option>
            <option value="no-answer">Não Atendeu</option>
            <option value="failed">Falhou</option>
        </select>
        <a href="?" class="btn-refresh">↻ Atualizar</a>
    </div>

    <?php if (empty($all)): ?>
        <div class="empty">Nenhuma chamada registada ainda.</div>
    <?php else: ?>
    <table id="callTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Data / Hora</th>
                <th>Cliente</th>
                <th>Telefone</th>
                <th>Agente</th>
                <th>Duração</th>
                <th>Status</th>
                <th>ID Conversa</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($all as $i => $conv):
            $ts       = $conv['start_time_unix_secs'] ?? 0;
            $duration = $conv['call_duration_secs'] ?? ($conv['metadata']['call_duration_secs'] ?? null);
            $phone    = $conv['metadata']['to_number'] ?? ($conv['conversation_initiation_client_data']['dynamic_variables']['lead_phone'] ?? '—');
            $name     = $conv['conversation_initiation_client_data']['dynamic_variables']['lead_name'] ?? '—';
            $status   = $conv['status'] ?? '—';
            $agent    = $conv['agent_label'];
            $conv_id  = $conv['conversation_id'] ?? '';
            $agentClass = ($agent === 'Raízes') ? 'agent-raizes' : 'agent-bh';
        ?>
            <tr>
                <td><?= $total - $i ?></td>
                <td><?= formatDate($ts) ?></td>
                <td><strong><?= htmlspecialchars($name) ?></strong></td>
                <td><?= htmlspecialchars($phone) ?></td>
                <td><span class="agent-badge <?= $agentClass ?>"><?= $agent ?></span></td>
                <td><?= formatDuration($duration) ?></td>
                <td><?= statusBadge($status) ?></td>
                <td class="conv-id"><?= substr($conv_id, 0, 24) ?>...</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="footer">Sofia Outbound System — Grupo DPS &copy; <?= date('Y') ?></div>

<script>
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const agent  = document.getElementById('agentFilter').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
    const rows   = document.querySelectorAll('#callTable tbody tr');
    rows.forEach(row => {
        const text   = row.innerText.toLowerCase();
        const rowAgent  = row.cells[4]?.innerText.toLowerCase() ?? '';
        const rowStatus = row.cells[6]?.innerText.toLowerCase() ?? '';
        const show = (!search || text.includes(search))
                  && (!agent  || rowAgent.includes(agent))
                  && (!status || rowStatus.includes(status));
        row.style.display = show ? '' : 'none';
    });
}
</script>

</body>
</html>
