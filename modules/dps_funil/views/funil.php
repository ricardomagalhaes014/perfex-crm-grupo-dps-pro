<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dps-funnel{max-width:820px;margin:10px auto 30px;}
.dps-funnel-head{display:flex;align-items:baseline;gap:10px;margin-bottom:18px;}
.dps-funnel-head h4{margin:0;font-weight:600;}
.dps-funnel-head .muted{color:#888;font-size:13px;}
.dps-phase{margin:0 auto 8px;border-radius:12px;padding:12px 16px;background:#f6f8fa;border:1px solid #eaecef;}
.dps-phase.vip{border:2px solid #2f80c9;background:#eef5fc;}
.dps-phase-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#3a4757;margin-bottom:10px;text-transform:uppercase;letter-spacing:.03em;}
.dps-phase-title .ptot{margin-left:auto;font-size:12px;font-weight:400;color:#7a8798;}
.dps-chips{display:flex;flex-wrap:wrap;gap:8px;}
.dps-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #e3e7ec;border-radius:24px;padding:6px 13px;font-size:13px;color:#2b3440;text-decoration:none;transition:box-shadow .12s,transform .12s;}
.dps-chip:hover{box-shadow:0 3px 10px rgba(0,0,0,.10);transform:translateY(-1px);color:#2b3440;text-decoration:none;}
.dps-chip .dot{width:10px;height:10px;border-radius:50%;flex:none;box-shadow:0 0 0 1px rgba(0,0,0,.06);}
.dps-chip .cname{font-weight:500;}
.dps-chip .cnum{color:#8a97a6;font-variant-numeric:tabular-nums;}
.dps-flowarrow{text-align:center;color:#c2cad4;font-size:14px;line-height:1;margin:2px 0;}
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="dps-funnel">
                    <div class="dps-funnel-head">
                        <h4><i class="fa fa-filter"></i> Funil de Leads</h4>
                        <span class="muted"><?= number_format((int) $total_leads, 0, ',', '.'); ?> leads<?= $comercial > 0 ? ' · ' . e(get_staff_full_name($comercial)) : ' no total'; ?></span>
                        <span class="muted" style="margin-left:auto;"><a href="<?= admin_url('leads'); ?>"><i class="fa fa-th-large"></i> Ver Kanban / lista completa</a></span>
                    </div>

                    <?php if ($can_view_all) { ?>
                    <form method="get" action="<?= admin_url('dps_funil'); ?>" style="margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                        <label style="margin:0;font-size:13px;color:#5a6673;"><i class="fa fa-user-o"></i> Comercial:</label>
                        <select name="comercial" class="selectpicker" data-width="280px" data-live-search="true" onchange="this.form.submit()">
                            <option value="0"<?= $comercial == 0 ? ' selected' : ''; ?>>Todos os comerciais</option>
                            <?php foreach ($comerciais as $c) {
                                $cid = (int) $c['staffid'];
                                $cnm = trim($c['firstname'] . ' ' . $c['lastname']);
                            ?>
                            <option value="<?= $cid; ?>"<?= $comercial == $cid ? ' selected' : ''; ?>><?= e($cnm); ?> (<?= (int) $c['c']; ?>)</option>
                            <?php } ?>
                        </select>
                        <?php if ($comercial > 0) { ?>
                        <a href="<?= admin_url('dps_funil'); ?>" class="btn btn-default btn-sm">Limpar</a>
                        <?php } ?>
                    </form>
                    <?php } ?>

                    <?php $qs = $comercial > 0 ? ('?comercial=' . (int) $comercial) : ''; ?>
                    <?php $nphases = count($phases); $i = 0; ?>
                    <?php foreach ($phases as $phase) {
                        $i++;
                        $width = 100 - (($i - 1) * 5);
                        if ($width < 62) { $width = 62; }
                        $isVip = ($phase['key'] === 'vip');
                    ?>
                    <div class="dps-phase<?= $isVip ? ' vip' : ''; ?>" style="width:<?= $width; ?>%;">
                        <div class="dps-phase-title">
                            <i class="<?= e($phase['icon']); ?>"></i>
                            <span><?= $i; ?> · <?= e($phase['title']); ?></span>
                            <span class="ptot"><?= number_format((int) $phase['total'], 0, ',', '.'); ?> leads</span>
                        </div>
                        <div class="dps-chips">
                            <?php if (empty($phase['items'])) { ?>
                            <span class="muted" style="font-size:12px;color:#9aa6b2;">— sem estados —</span>
                            <?php } ?>
                            <?php foreach ($phase['items'] as $it) { ?>
                            <a class="dps-chip" href="<?= admin_url('dps_funil/estado/' . (int) $it['id']) . $qs; ?>">
                                <span class="dot" style="background:<?= e($it['color'] ?: '#cccccc'); ?>;"></span>
                                <span class="cname"><?= e($it['name']); ?></span>
                                <span class="cnum"><?= number_format((int) $it['count'], 0, ',', '.'); ?></span>
                            </a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($i < $nphases) { ?>
                    <div class="dps-flowarrow"><i class="fa fa-chevron-down"></i></div>
                    <?php } ?>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
