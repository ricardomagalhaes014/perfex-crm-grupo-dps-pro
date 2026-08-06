<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/*
 * Seletor de estado.
 *
 * Os cartões aqui em baixo já filtram por estado, mas ninguém percebe que são
 * botões — parecem um resumo. Este seletor faz exactamente o mesmo, dito por
 * palavras. Pedido do dono (06/08/2026).
 *
 * Escreve na mesma variável que os cartões (extra.tasksRules), portanto os dois
 * caminhos não se atropelam: escolher aqui acende o cartão correspondente.
 */
$dps_resumo = tasks_summary_data(($rel_id ?? null), ($rel_type ?? null));
?>
<div class="tw-mb-3 tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
    <label for="dps-filtro-estado-tarefas" class="tw-mb-0 tw-text-sm tw-text-neutral-600">
        <?= _l('task_status'); ?>:
    </label>
    <select id="dps-filtro-estado-tarefas" class="form-control input-sm" style="width:auto;min-width:230px;"
        @change="extra.tasksRules = $event.target.value ? JSON.parse($event.target.value) : undefined">
        <option value=""><?= _l('task_list_all'); ?></option>
        <?php foreach ($dps_resumo as $dps_estado) { ?>
        <option value="<?= app\services\utilities\Js::from($tasks_table->findRule('status')->setValue([$dps_estado['status_id']])); ?>">
            <?= e($dps_estado['name']); ?> (<?= e($dps_estado['total_tasks']); ?>)
        </option>
        <?php } ?>
    </select>
</div>
<div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-3 lg:tw-grid-cols-5 tw-gap-2">
    <?php foreach (tasks_summary_data(($rel_id ?? null), ($rel_type ?? null)) as $summary) { ?>
    <button type="button"
        @click="extra.tasksRules = <?= app\services\utilities\Js::from($tasks_table->findRule('status')->setValue([$summary['status_id']])); ?>"
        class="tw-bg-white tw-border tw-border-solid tw-border-neutral-300/80 tw-shadow-sm tw-py-2 tw-px-3.5 tw-rounded-lg tw-text-sm hover:tw-bg-neutral-100 tw-text-neutral-600 hover:tw-text-neutral-600 focus:tw-text-neutral-600 text-left odd:last:tw-col-span-2 md:odd:last:tw-col-auto">
        <span class="tw-font-semibold tw-mr-1 rtl:tw-ml-1">
            <?= e($summary['total_tasks']); ?>
        </span>
        <span
            style="color:<?= e($summary['color']); ?>">
            <?= e($summary['name']); ?>
        </span>
        <span class="tw-text-sm tw-text-neutral-800 tw-block">
            <span
                class="tw-text-neutral-500"><?= _l('home_my_tasks'); ?>:</span>
            <?= e($summary['total_my_tasks']); ?>
        </span>
    </button>
    <?php } ?>
</div>