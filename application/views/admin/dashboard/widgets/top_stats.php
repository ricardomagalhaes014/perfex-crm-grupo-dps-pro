<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget relative" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('quick_stats'); ?>">
    <div class="widget-dragger"></div>
    <style>
        .dps-stat-card {
            background: #fff;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 16px;
            height: 100%;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            text-decoration: none !important;
            color: inherit !important;
        }
        .dps-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            text-decoration: none !important;
            color: inherit !important;
        }
        .dps-stat-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dps-stat-icon-wrap svg {
            width: 26px;
            height: 26px;
            stroke: #fff;
        }
        .dps-stat-body {
            flex: 1;
            min-width: 0;
        }
        .dps-stat-label {
            font-size: 12px;
            font-weight: 500;
            color: #999;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dps-stat-numbers {
            display: flex;
            align-items: baseline;
            gap: 4px;
        }
        .dps-stat-main {
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
            color: #1a1a1a;
        }
        .dps-stat-total {
            font-size: 14px;
            color: #bbb;
            font-weight: 500;
        }
        .dps-stat-bar {
            height: 4px;
            background: #f0f0f0;
            border-radius: 2px;
            margin-top: 10px;
            overflow: hidden;
        }
        .dps-stat-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 1s cubic-bezier(.4,0,.2,1);
            width: 0%;
        }
        .dps-stat-col {
            margin-bottom: 16px;
        }
    </style>

    <?php
      $initial_column = 'col-lg-3';
      if (!is_staff_member() && ((staff_cant('view', 'invoices') && staff_cant('view_own', 'invoices') && (get_option('allow_staff_view_invoices_assigned') == 0
        || (get_option('allow_staff_view_invoices_assigned') == 1 && !staff_has_assigned_invoices()))))) {
          $initial_column = 'col-lg-6';
      } elseif (!is_staff_member() || (staff_cant('view', 'invoices') && staff_cant('view_own', 'invoices') && (get_option('allow_staff_view_invoices_assigned') == 1 && !staff_has_assigned_invoices()) || (get_option('allow_staff_view_invoices_assigned') == 0 && (staff_cant('view', 'invoices') && staff_cant('view_own', 'invoices'))))) {
          $initial_column = 'col-lg-4';
      }
    ?>

    <div class="row">
        <?php if (staff_can('view', 'invoices') || staff_can('view_own', 'invoices') || (get_option('allow_staff_view_invoices_assigned') == '1' && staff_has_assigned_invoices())) { ?>
        <?php
          $total_invoices                          = total_rows(db_prefix() . 'invoices', 'status NOT IN (5,6)' . (staff_cant('view', 'invoices') ? ' AND ' . get_invoices_where_sql_for_staff(get_staff_user_id()) : ''));
          $total_invoices_awaiting_payment         = total_rows(db_prefix() . 'invoices', 'status NOT IN (2,5,6)' . (staff_cant('view', 'invoices') ? ' AND ' . get_invoices_where_sql_for_staff(get_staff_user_id()) : ''));
          $percent_inv = $total_invoices > 0 ? round(($total_invoices_awaiting_payment * 100) / $total_invoices) : 0;
        ?>
        <div class="quick-stats-invoices col-xs-12 col-md-6 col-sm-6 <?= $initial_column; ?> dps-stat-col">
            <a href="<?= admin_url('invoices'); ?>" class="dps-stat-card">
                <div class="dps-stat-icon-wrap" style="background: linear-gradient(135deg, #FF9500, #e07b00);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                </div>
                <div class="dps-stat-body">
                    <div class="dps-stat-label"><?= _l('invoices_awaiting_payment'); ?></div>
                    <div class="dps-stat-numbers">
                        <span class="dps-stat-main"><?= $total_invoices_awaiting_payment; ?></span>
                        <span class="dps-stat-total">/ <?= $total_invoices; ?></span>
                    </div>
                    <div class="dps-stat-bar">
                        <div class="dps-stat-bar-fill not-dynamic" style="background: linear-gradient(90deg, #FF9500, #FFc340);" data-percent="<?= $percent_inv; ?>"></div>
                    </div>
                </div>
            </a>
        </div>
        <?php } ?>

        <?php if (is_staff_member()) { ?>
        <?php
          $where = '';
          if (!is_admin()) {
              $where .= '(addedfrom = ' . get_staff_user_id() . ' OR assigned = ' . get_staff_user_id() . ')';
          }
          $total_leads = total_rows(db_prefix() . 'leads', ($where == '' ? 'junk=0' : $where .= ' AND junk=0'));
          if ($where == '') { $where .= 'status=1'; } else { $where .= ' AND status=1'; }
          $total_leads_converted = total_rows(db_prefix() . 'leads', $where);
          $percent_leads = $total_leads > 0 ? round(($total_leads_converted * 100) / $total_leads) : 0;
        ?>
        <div class="quick-stats-leads col-xs-12 col-md-6 col-sm-6 <?= $initial_column; ?> dps-stat-col">
            <a href="<?= admin_url('leads'); ?>" class="dps-stat-card">
                <div class="dps-stat-icon-wrap" style="background: linear-gradient(135deg, #34C759, #28a745);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                </div>
                <div class="dps-stat-body">
                    <div class="dps-stat-label"><?= _l('leads_converted_to_client'); ?></div>
                    <div class="dps-stat-numbers">
                        <span class="dps-stat-main"><?= $total_leads_converted; ?></span>
                        <span class="dps-stat-total">/ <?= $total_leads; ?></span>
                    </div>
                    <div class="dps-stat-bar">
                        <div class="dps-stat-bar-fill not-dynamic" style="background: linear-gradient(90deg, #34C759, #7ddf8a);" data-percent="<?= $percent_leads; ?>"></div>
                    </div>
                </div>
            </a>
        </div>
        <?php } ?>

        <?php
          $_where = '';
          $project_status = get_project_status_by_id(2);
          if (staff_cant('view', 'projects')) {
              $_where = 'id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')';
          }
          $total_projects = total_rows(db_prefix() . 'projects', $_where);
          $where_p = ($_where == '' ? '' : $_where . ' AND ') . 'status = 2';
          $total_projects_in_progress = total_rows(db_prefix() . 'projects', $where_p);
          $percent_proj = $total_projects > 0 ? round(($total_projects_in_progress * 100) / $total_projects) : 0;
        ?>
        <div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 <?= $initial_column; ?> dps-stat-col">
            <a href="<?= admin_url('projects'); ?>" class="dps-stat-card">
                <div class="dps-stat-icon-wrap" style="background: linear-gradient(135deg, #007AFF, #0056cc);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
                </div>
                <div class="dps-stat-body">
                    <div class="dps-stat-label"><?= _l('projects') . ' ' . $project_status['name']; ?></div>
                    <div class="dps-stat-numbers">
                        <span class="dps-stat-main"><?= $total_projects_in_progress; ?></span>
                        <span class="dps-stat-total">/ <?= $total_projects; ?></span>
                    </div>
                    <div class="dps-stat-bar">
                        <div class="dps-stat-bar-fill not-dynamic" style="background: linear-gradient(90deg, #007AFF, #64b5ff);" data-percent="<?= $percent_proj; ?>"></div>
                    </div>
                </div>
            </a>
        </div>

        <?php
          $_where_t = '';
          if (staff_cant('view', 'tasks')) {
              $_where_t = db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ')';
          }
          $total_tasks = total_rows(db_prefix() . 'tasks', $_where_t);
          $where_t = ($_where_t == '' ? '' : $_where_t . ' AND ') . 'status != ' . Tasks_model::STATUS_COMPLETE;
          $total_not_finished_tasks = total_rows(db_prefix() . 'tasks', $where_t);
          $percent_tasks = $total_tasks > 0 ? round(($total_not_finished_tasks * 100) / $total_tasks) : 0;
        ?>
        <div class="quick-stats-tasks col-xs-12 col-md-6 col-sm-6 <?= $initial_column; ?> dps-stat-col">
            <a href="<?= admin_url('tasks'); ?>" class="dps-stat-card">
                <div class="dps-stat-icon-wrap" style="background: linear-gradient(135deg, #BF5AF2, #9b2de0);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="dps-stat-body">
                    <div class="dps-stat-label"><?= _l('tasks_not_finished'); ?></div>
                    <div class="dps-stat-numbers">
                        <span class="dps-stat-main"><?= $total_not_finished_tasks; ?></span>
                        <span class="dps-stat-total">/ <?= $total_tasks; ?></span>
                    </div>
                    <div class="dps-stat-bar">
                        <div class="dps-stat-bar-fill not-dynamic" style="background: linear-gradient(90deg, #BF5AF2, #d98ff7);" data-percent="<?= $percent_tasks; ?>"></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script>
    (function() {
        document.querySelectorAll('.dps-stat-bar-fill.not-dynamic').forEach(function(el) {
            var pct = parseFloat(el.getAttribute('data-percent')) || 0;
            setTimeout(function() { el.style.width = pct + '%'; }, 200);
        });
    })();
    </script>
</div>
