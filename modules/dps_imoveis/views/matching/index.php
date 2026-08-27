<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">
<div class="row">
<div class="col-md-12">

<style>
.match-card { border-radius: 16px; background: #fff; box-shadow: 0 2px 14px rgba(0,0,0,0.07); margin-bottom: 20px; overflow: hidden; }
.match-header { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
.match-client { display: flex; align-items: center; gap: 14px; }
.match-avatar { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; color: #fff; flex-shrink: 0; }
.match-client-info strong { font-size: 15px; display: block; }
.match-client-info span { font-size: 12px; color: #999; }
.urgencia-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
.urgencia-urgente  { background: #fff0f0; color: #e03131; }
.urgencia-normal   { background: #f0f4ff; color: #3b5bdb; }
.urgencia-sem_pressa { background: #f0fdf4; color: #2f9e44; }
.match-criteria { padding: 10px 20px; background: #fafafa; border-bottom: 1px solid #f0f0f0; display: flex; flex-wrap: wrap; gap: 8px; font-size: 12px; }
.criteria-tag { background: #eee; border-radius: 20px; padding: 3px 10px; color: #555; }
.match-imoveis { padding: 16px 20px; }
.match-imoveis h6 { font-size: 12px; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 12px; }
.imovel-row { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
.imovel-row:last-child { border-bottom: none; }
.imovel-foto { width: 56px; height: 48px; border-radius: 10px; object-fit: cover; background: #eee; flex-shrink: 0; }
.imovel-foto-placeholder { width: 56px; height: 48px; border-radius: 10px; background: #eee; display: flex; align-items: center; justify-content: center; color: #bbb; flex-shrink: 0; }
.imovel-info { flex: 1; min-width: 0; }
.imovel-info strong { font-size: 13px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.imovel-info span { font-size: 12px; color: #888; }
.imovel-preco { font-size: 14px; font-weight: 700; color: #222; white-space: nowrap; }
.imovel-score { display: flex; gap: 2px; }
.score-dot { width: 8px; height: 8px; border-radius: 50%; }
.score-dot.on { background: #34C759; }
.score-dot.off { background: #e0e0e0; }
.btn-contactar { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; }
.btn-wa { background: #25D366; color: #fff; }
.btn-wa:hover { background: #1aab55; color: #fff; }
.empty-state { text-align: center; padding: 60px 20px; color: #bbb; }
.tabs-nav { display: flex; gap: 0; border-bottom: 2px solid #eee; margin-bottom: 24px; }
.tabs-nav a { padding: 10px 20px; font-size: 13px; font-weight: 500; color: #888; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.tabs-nav a.active { color: #3b5bdb; border-bottom-color: #3b5bdb; }
.tabs-nav a:hover { color: #555; }
</style>

<!-- Tabs de navegação -->
<div class="tabs-nav">
    <a href="<?= admin_url('dps_imoveis'); ?>"><i class="fa fa-home"></i> Imóveis</a>
    <a href="<?= admin_url('dps_imoveis/necessidades'); ?>"><i class="fa fa-search"></i> Necessidades</a>
    <a href="<?= admin_url('dps_imoveis/matching'); ?>" class="active"><i class="fa fa-magic"></i> Matching</a>
</div>

<div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
    <div>
        <h4 class="tw-font-bold tw-mb-1">Matching — Clientes vs Imóveis Disponíveis</h4>
        <p class="tw-text-neutral-400 tw-text-sm">Clientes com necessidades registadas que encaixam no inventário actual. Ordenados por urgência.</p>
    </div>
    <a href="<?= admin_url('dps_imoveis/nova_necessidade'); ?>" class="btn btn-primary">
        <i class="fa fa-plus"></i> Nova Necessidade
    </a>
</div>

<?php if (empty($matches)): ?>
<div class="empty-state">
    <i class="fa fa-magic fa-4x" style="color:#e0e0e0; margin-bottom:16px;"></i>
    <p style="font-size:16px; color:#aaa;">Nenhum match encontrado.</p>
    <p style="font-size:13px; color:#bbb;">Registe necessidades de clientes ou adicione imóveis aprovados ao inventário.</p>
</div>

<?php else: ?>
<p style="font-size:13px; color:#888; margin-bottom:20px;">
    <strong><?= count($matches); ?></strong> cliente(s) com imóveis compatíveis encontrados.
</p>

<?php
$cores = ['#007AFF','#34C759','#FF9500','#FF2D55','#BF5AF2','#30B0C7','#FF6B35','#AC8E68'];
$ci = 0;
foreach ($matches as $m):
    $nec  = $m['necessidade'];
    $cor  = $cores[$ci % count($cores)];
    $ci++;
    $iniciais = strtoupper(substr($nec['nome_cliente'], 0, 1));
    $urgencia_label = ['urgente' => 'Urgente', 'normal' => 'Normal', 'sem_pressa' => 'Sem pressa'][$nec['urgencia']] ?? 'Normal';
?>
<div class="match-card">
    <div class="match-header">
        <div class="match-client">
            <div class="match-avatar" style="background:<?= $cor; ?>"><?= $iniciais; ?></div>
            <div class="match-client-info">
                <strong><?= htmlspecialchars($nec['nome_cliente']); ?></strong>
                <span>
                    <?php if ($nec['contacto_cliente']): ?>
                        <i class="fa fa-phone"></i> <?= htmlspecialchars($nec['contacto_cliente']); ?>
                    <?php endif; ?>
                    <?php if ($nec['email_cliente']): ?>
                        &nbsp; <i class="fa fa-envelope"></i> <?= htmlspecialchars($nec['email_cliente']); ?>
                    <?php endif; ?>
                    <?php if ($nec['staff_nome']): ?>
                        &nbsp; · Responsável: <?= htmlspecialchars($nec['staff_nome']); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="urgencia-badge urgencia-<?= $nec['urgencia']; ?>"><?= $urgencia_label; ?></span>
            <?php if ($nec['contacto_cliente']): ?>
            <a href="https://wa.me/351<?= preg_replace('/\D/', '', $nec['contacto_cliente']); ?>" target="_blank" class="btn-contactar btn-wa">
                <i class="fa fa-whatsapp"></i> WhatsApp
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Critérios que o cliente quer -->
    <div class="match-criteria">
        <?php if ($nec['tipo']): ?>
            <span class="criteria-tag"><i class="fa fa-home"></i> <?= htmlspecialchars($nec['tipo']); ?></span>
        <?php endif; ?>
        <?php if ($nec['tipologia']): ?>
            <span class="criteria-tag"><?= htmlspecialchars($nec['tipologia']); ?></span>
        <?php endif; ?>
        <?php if ($nec['distrito']): ?>
            <span class="criteria-tag"><i class="fa fa-map-marker"></i> <?= htmlspecialchars($nec['distrito']); ?><?= $nec['cidade'] ? ', ' . htmlspecialchars($nec['cidade']) : ''; ?></span>
        <?php endif; ?>
        <?php if ($nec['preco_max']): ?>
            <span class="criteria-tag">até <?= number_format($nec['preco_max'], 0, ',', '.'); ?> €</span>
        <?php endif; ?>
        <?php if ($nec['nr_quartos_min'] !== null && $nec['nr_quartos_min'] !== ''): ?>
            <span class="criteria-tag"><?= $nec['nr_quartos_min']; ?>+ quartos</span>
        <?php endif; ?>
        <?php if ($nec['area_min']): ?>
            <span class="criteria-tag">≥ <?= $nec['area_min']; ?> m²</span>
        <?php endif; ?>
        <?php if ($nec['observacoes']): ?>
            <span class="criteria-tag" title="<?= htmlspecialchars($nec['observacoes']); ?>"><i class="fa fa-comment"></i> Obs.</span>
        <?php endif; ?>
    </div>

    <!-- Imóveis que encaixam -->
    <div class="match-imoveis">
        <h6><i class="fa fa-check-circle" style="color:#34C759;"></i> <?= count($m['matches']); ?> imóvel(eis) compatível(eis)</h6>
        <?php foreach ($m['matches'] as $im): ?>
        <?php
            $score_max = 10;
            $score_dots = min(5, round($im['match_score'] / 2));
        ?>
        <div class="imovel-row">
            <?php if ($im['foto_principal']): ?>
                <img src="<?= base_url($im['foto_principal']); ?>" class="imovel-foto" alt="">
            <?php else: ?>
                <div class="imovel-foto-placeholder"><i class="fa fa-home"></i></div>
            <?php endif; ?>

            <div class="imovel-info">
                <strong><?= htmlspecialchars($im['titulo']); ?></strong>
                <span>
                    <?php if ($im['tipologia']): ?><?= $im['tipologia']; ?> · <?php endif; ?>
                    <?php if ($im['distrito']): ?><i class="fa fa-map-marker"></i> <?= htmlspecialchars($im['distrito']); ?><?= $im['cidade'] ? ', ' . htmlspecialchars($im['cidade']) : ''; ?> · <?php endif; ?>
                    <?php if ($im['area_total']): ?><?= $im['area_total']; ?> m² · <?php endif; ?>
                    <?php if ($im['nr_quartos']): ?><?= $im['nr_quartos']; ?> quartos<?php endif; ?>
                </span>
            </div>

            <div class="imovel-score">
                <?php for ($d = 1; $d <= 5; $d++): ?>
                    <div class="score-dot <?= $d <= $score_dots ? 'on' : 'off'; ?>"></div>
                <?php endfor; ?>
            </div>

            <div class="imovel-preco">
                <?= $im['preco'] ? number_format($im['preco'], 0, ',', '.') . ' €' : '—'; ?>
            </div>

            <a href="<?= admin_url('dps_imoveis/detalhe/' . $im['id']); ?>" class="btn-contactar" style="background:#f0f4ff; color:#3b5bdb;">
                <i class="fa fa-eye"></i> Ver
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>
</div>
</div>
</div>
<?php init_tail(); ?>
</body>
</html>
