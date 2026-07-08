<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">
    <div class="app-launcher">
        <div class="app-launcher-grid">
            <?php foreach ($tiles as $tile) { ?>
            <a class="app-tile app-tile-color-<?= (int) $tile['color']; ?>"
                href="<?= e($tile['href']); ?>"
                title="<?= e($tile['name']); ?>">
                <span class="app-tile-icon">
                    <i class="<?= e($tile['icon']); ?>"></i>
                    <?php if ($tile['badge'] !== null) { ?>
                    <span class="app-tile-badge"><?= e($tile['badge']); ?></span>
                    <?php } ?>
                </span>
                <span class="app-tile-label"><?= e($tile['name']); ?></span>
            </a>
            <?php } ?>
        </div>
    </div>
</div>
