<?php
/**
 * Página de Equipa - DPS Imobiliário
 * URL: dpsimobiliario.pt/equipa
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');
define('CRM_URL', 'https://crm.grupo-dps.com');
define('SERVER_BASE', '/home/u172337921/domains/grupo-dps.com/public_html/');

function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die("Erro de ligação");
    $conn->set_charset('utf8mb4');
    return $conn;
}

function get_foto_url($staffid, $landing_foto, $profile_image) {
    $base_url = CRM_URL . '/';
    $base_path = SERVER_BASE;
    if (!empty($landing_foto)) {
        $p = $base_path . 'uploads/landing_fotos/' . $staffid . '/' . $landing_foto;
        if (file_exists($p)) return $base_url . 'uploads/landing_fotos/' . $staffid . '/' . $landing_foto;
    }
    if (!empty($profile_image)) {
        $p1 = $base_path . 'uploads/staff_profile_images/' . $staffid . '/small_' . $profile_image;
        if (file_exists($p1)) return $base_url . 'uploads/staff_profile_images/' . $staffid . '/small_' . $profile_image;
        $p2 = $base_path . 'uploads/staff_profile_images/' . $profile_image;
        if (file_exists($p2)) return $base_url . 'uploads/staff_profile_images/' . $profile_image;
        $p3 = $base_path . 'uploads/staff_profile_images/' . $staffid . '/thumb_' . $profile_image;
        if (file_exists($p3)) return $base_url . 'uploads/staff_profile_images/' . $staffid . '/thumb_' . $profile_image;
    }
    return null;
}

$conn = db_connect();

// Buscar todos os staff activos
$staff_sql = "SELECT s.staffid AS id, s.firstname AS nome, s.lastname AS apelido,
    s.email, s.phonenumber AS telefone, s.profile_image AS foto, s.landing_foto,
    s.landing_slug, s.is_admin,
    cfv_wa.value AS whatsapp
FROM tblstaff s
LEFT JOIN tblcustomfieldsvalues cfv_wa ON (cfv_wa.relid = s.staffid AND cfv_wa.fieldid = 19)
WHERE s.active = 1
ORDER BY s.staffid ASC";

$staff_result = $conn->query($staff_sql);
$staff_map = [];
while ($row = $staff_result->fetch_assoc()) {
    $foto_url = get_foto_url($row['id'], $row['landing_foto'], $row['foto']);
    $row['foto_url'] = $foto_url;
    $row['has_foto'] = !empty($foto_url);
    $staff_map[$row['id']] = $row;
}

// Buscar equipas imobiliárias
$teams_sql = "SELECT t.id, t.name, t.area, t.parent_id,
    m.staff_id, m.role
FROM tbldps_teams t
LEFT JOIN tbldps_team_members m ON m.team_id = t.id
WHERE t.area = 'imo'
ORDER BY t.id ASC, m.role ASC";

$teams_result = $conn->query($teams_sql);
$teams = [];
if ($teams_result) {
    while ($tr = $teams_result->fetch_assoc()) {
        $tid = $tr['id'];
        if (!isset($teams[$tid])) {
            $teams[$tid] = ['id' => $tid, 'name' => $tr['name'], 'area' => $tr['area'],
                'parent_id' => $tr['parent_id'], 'managers' => [], 'members' => []];
        }
        if (!empty($tr['staff_id'])) {
            if ($tr['role'] === 'manager') $teams[$tid]['managers'][] = (int)$tr['staff_id'];
            else $teams[$tid]['members'][] = (int)$tr['staff_id'];
        }
    }
}
$conn->close();

$main_team = null;
$sub_teams = [];
foreach ($teams as $t) {
    if (empty($t['parent_id'])) $main_team = $t;
    else $sub_teams[] = $t;
}

$market_map = [
    'IMO BRASIL & BSX' => ['flag' => '🇧🇷', 'label' => 'Brasil'],
    'IMO BRASIL'       => ['flag' => '🇧🇷', 'label' => 'Brasil'],
    'IMO PORTUGAL'     => ['flag' => '🇵🇹', 'label' => 'Portugal'],
    'IMO DUBAI'        => ['flag' => '🇦🇪', 'label' => 'Dubai'],
];

$ceo_id = 1;    // Ricardo Magalhães
$gestor_id = 46; // Cláudio Carvalho

// Ordenar sub-equipas: Portugal primeiro, depois Brasil, depois Dubai
$order = ['IMO PORTUGAL' => 1, 'IMO BRASIL & BSX' => 2, 'IMO BRASIL' => 2, 'IMO DUBAI' => 3];
usort($sub_teams, function($a, $b) use ($order) {
    $oa = $order[strtoupper(trim($a['name']))] ?? 99;
    $ob = $order[strtoupper(trim($b['name']))] ?? 99;
    return $oa - $ob;
});
?><!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Equipa DPS Imobiliário</title>
<meta name="description" content="Conheça a equipa DPS Imobiliário - especialistas em Portugal, Brasil e Dubai">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', system-ui, sans-serif; background: #060E1C; color: #F5F2ED; min-height: 100vh; }
.page-header { background: linear-gradient(135deg, #0A1628 0%, #0D1F3C 100%); border-bottom: 1px solid rgba(197,165,90,0.2); padding: 40px 20px 32px; text-align: center; }
.logo-area { display: flex; align-items: center; justify-content: center; gap: 14px; margin-bottom: 16px; }
.logo-area img { height: 48px; width: auto; }
.page-header h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 700; color: #F5F2ED; letter-spacing: 0.02em; }
.page-header h1 span { color: #C5A55A; }
.page-header p { color: rgba(245,242,237,0.6); font-size: 1rem; margin-top: 8px; }
.container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
.section-title { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: #C5A55A; margin-bottom: 20px; padding-bottom: 8px; border-bottom: 1px solid rgba(197,165,90,0.2); }
.leadership-section { padding: 48px 0 32px; }
.leadership-grid { display: flex; gap: 24px; justify-content: center; flex-wrap: wrap; }
.member-card { background: #0D1F3C; border: 1px solid rgba(197,165,90,0.2); border-radius: 4px; overflow: hidden; transition: border-color 0.3s, transform 0.3s; text-align: center; }
.member-card:hover { border-color: rgba(197,165,90,0.6); transform: translateY(-4px); }
.card-ceo { width: 240px; }
.card-gestor { width: 220px; }
.card-comercial { width: 180px; }
.member-photo { width: 100%; aspect-ratio: 1/1; object-fit: cover; object-position: top center; display: block; background: #0A1628; }
.member-info { padding: 14px 12px 16px; }
.member-name { font-size: 0.95rem; font-weight: 600; color: #F5F2ED; margin-bottom: 4px; line-height: 1.3; }
.member-role { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #C5A55A; margin-bottom: 10px; }
.member-contacts { display: flex; flex-direction: column; gap: 5px; }
.member-contact-link { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.75rem; color: rgba(245,242,237,0.6); text-decoration: none; transition: color 0.2s; word-break: break-all; }
.member-contact-link:hover { color: #C5A55A; }
.member-contact-link svg { flex-shrink: 0; }
.markets-section { padding: 16px 0 60px; }
.market-block { margin-bottom: 48px; }
.market-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding: 14px 20px; background: rgba(197,165,90,0.08); border: 1px solid rgba(197,165,90,0.2); border-radius: 4px; }
.market-flag { font-size: 1.6rem; }
.market-name { font-size: 1.1rem; font-weight: 700; color: #F5F2ED; }
.market-count { margin-left: auto; font-size: 0.75rem; color: rgba(245,242,237,0.4); }
.market-grid { display: flex; flex-wrap: wrap; gap: 20px; }
.page-footer { background: #0A1628; border-top: 1px solid rgba(197,165,90,0.15); padding: 24px 20px; text-align: center; font-size: 0.8rem; color: rgba(245,242,237,0.4); }
.page-footer a { color: #C5A55A; text-decoration: none; }
@media (max-width: 600px) {
  .card-ceo, .card-gestor, .card-comercial { width: 160px; }
  .leadership-grid, .market-grid { gap: 14px; }
}
</style>
</head>
<body>
<header class="page-header">
  <div class="logo-area">
    <img src="https://crm.grupo-dps.com/uploads/company/d8f78db59e2555a42c9fc952efc7c4c2.png" alt="DPS Imobiliário" onerror="this.style.display='none'">
  </div>
  <h1>A Nossa <span>Equipa</span></h1>
  <p>Especialistas em Portugal, Brasil e Dubai</p>
</header>
<main>
<div class="container">

<!-- Liderança -->
<section class="leadership-section">
  <p class="section-title">Liderança</p>
  <div class="leadership-grid">
    <?php
    $icons_email = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>';
    $icons_wa = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>';

    function render_member_card($m, $role, $css_class, $icons_email, $icons_wa) {
        if (!$m || !$m['has_foto']) return;
        $name = htmlspecialchars($m['nome'] . ' ' . $m['apelido']);
        $foto = htmlspecialchars($m['foto_url']);
        $email = htmlspecialchars($m['email'] ?? '');
        $wa = preg_replace('/[^0-9]/', '', $m['whatsapp'] ?? $m['telefone'] ?? '');
        echo "<div class=\"member-card {$css_class}\">";
        echo "<img class=\"member-photo\" src=\"{$foto}\" alt=\"{$name}\" loading=\"lazy\">";
        echo "<div class=\"member-info\">";
        echo "<div class=\"member-name\">{$name}</div>";
        echo "<div class=\"member-role\">" . htmlspecialchars($role) . "</div>";
        echo "<div class=\"member-contacts\">";
        if ($email) echo "<a class=\"member-contact-link\" href=\"mailto:{$email}\">{$icons_email} {$email}</a>";
        if ($wa) echo "<a class=\"member-contact-link\" href=\"https://wa.me/{$wa}\" target=\"_blank\" rel=\"noopener\">{$icons_wa} +{$wa}</a>";
        echo "</div></div></div>";
    }

    render_member_card($staff_map[$ceo_id] ?? null, 'CEO', 'card-ceo', $icons_email, $icons_wa);
    render_member_card($staff_map[$gestor_id] ?? null, 'Gestor de Equipa', 'card-gestor', $icons_email, $icons_wa);
    ?>
  </div>
</section>

<!-- Mercados -->
<section class="markets-section">
  <p class="section-title">Equipas por Mercado</p>
  <?php foreach ($sub_teams as $team):
      $team_name_upper = strtoupper(trim($team['name']));
      $market_info = $market_map[$team_name_upper] ?? ['flag' => '🌍', 'label' => $team['name']];
      $all_ids = array_unique(array_merge($team['managers'], $team['members']));
      $members_with_photo = [];
      foreach ($all_ids as $sid) {
          if ($sid == $ceo_id || $sid == $gestor_id) continue;
          if (isset($staff_map[$sid]) && $staff_map[$sid]['has_foto']) {
              $members_with_photo[] = $staff_map[$sid];
          }
      }
      if (empty($members_with_photo)) continue;
  ?>
  <div class="market-block">
    <div class="market-header">
      <span class="market-flag"><?= $market_info['flag'] ?></span>
      <span class="market-name"><?= htmlspecialchars($market_info['label']) ?></span>
      <span class="market-count"><?= count($members_with_photo) ?> membro<?= count($members_with_photo) != 1 ? 's' : '' ?></span>
    </div>
    <div class="market-grid">
      <?php foreach ($members_with_photo as $m):
          render_member_card($m, 'Consultor', 'card-comercial', $icons_email, $icons_wa);
      endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</section>

</div>
</main>
<footer class="page-footer">
  <p>&copy; <?= date('Y') ?> <a href="https://dpsimobiliario.pt" target="_blank">DPS Imobiliário</a> &mdash; Todos os direitos reservados</p>
</footer>
</body>
</html>
