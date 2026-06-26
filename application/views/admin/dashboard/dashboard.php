<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="screen-options-area"></div>
    <div class="screen-options-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="tw-w-5 tw-h-5 ltr:tw-mr-1 rtl:tw-ml-1">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>

        <?= _l('dashboard_options'); ?>
    </div>
    <div class="content">
        <div class="row">
            <?php $this->load->view('admin/includes/alerts'); ?>

            <?php hooks()->do_action('before_start_render_dashboard_content'); ?>

            <div class="clearfix"></div>

            <!-- DPS Quick Shortcuts -->
            <div class="col-md-12" style="padding: 20px 15px 0;">
                <style>
                    .dps-shortcuts-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
                        gap: 14px;
                        padding: 20px;
                        background: #ffffff;
                        border-radius: 20px;
                        box-shadow: 0 2px 16px rgba(0,0,0,0.07);
                        margin-bottom: 4px;
                    }
                    .dps-shortcut-item {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 8px;
                        text-decoration: none !important;
                        cursor: pointer;
                        transition: transform 0.15s ease, opacity 0.15s ease;
                    }
                    .dps-shortcut-item:hover {
                        transform: scale(1.08);
                        opacity: 0.9;
                        text-decoration: none !important;
                    }
                    .dps-shortcut-item:active {
                        transform: scale(0.96);
                    }
                    .dps-shortcut-icon {
                        width: 60px;
                        height: 60px;
                        border-radius: 15px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
                    }
                    .dps-shortcut-icon svg {
                        width: 28px;
                        height: 28px;
                        color: #fff;
                        stroke: #fff;
                    }
                    .dps-shortcut-label {
                        font-size: 11px;
                        font-weight: 500;
                        color: #555;
                        text-align: center;
                        line-height: 1.3;
                        max-width: 80px;
                    }
                    .dps-shortcuts-title {
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.8px;
                        color: #999;
                        margin-bottom: 12px;
                        padding-left: 2px;
                    }
                    @media (max-width: 576px) {
                        .dps-shortcuts-grid {
                            grid-template-columns: repeat(4, 1fr);
                            gap: 10px;
                            padding: 15px;
                        }
                        .dps-shortcut-icon {
                            width: 52px;
                            height: 52px;
                        }
                    }
                </style>
                <div class="dps-shortcuts-title">Atalhos rápidos</div>
                <div class="dps-shortcuts-grid">
                    <!-- Novo Lead -->
                    <a href="<?= admin_url('leads/create'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #34C759, #28a745);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Novo Lead</span>
                    </a>
                    <!-- Novo Cliente -->
                    <a href="<?= admin_url('clients/create'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #007AFF, #0056cc);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Novo Cliente</span>
                    </a>
                    <!-- Nova Tarefa -->
                    <a href="<?= admin_url('tasks'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #FF9500, #e07b00);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Tarefas</span>
                    </a>
                    <!-- Imóveis -->
                    <a href="<?= admin_url('dps_imoveis'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #5856D6, #4a48c0);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Imóveis</span>
                    </a>
                    <!-- WhatsApp -->
                    <a href="<?= admin_url('dps_whatsapp'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #25D366, #1aab55);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">WhatsApp</span>
                    </a>
                    <!-- Reuniões -->
                    <a href="<?= admin_url('dps_meetings'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #FF2D55, #cc0033);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Reuniões</span>
                    </a>
                    <!-- Faturas -->
                    <a href="<?= admin_url('invoices'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #30B0C7, #1a8fa3);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Faturas</span>
                    </a>
                    <!-- Equipas -->
                    <a href="<?= admin_url('dps_teams'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #FF6B35, #e05520);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Equipas</span>
                    </a>
                    <!-- Projetos -->
                    <a href="<?= admin_url('projects'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #64D2FF, #0099cc);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Projetos</span>
                    </a>
                    <!-- Relatórios -->
                    <a href="<?= admin_url('reports'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #AC8E68, #8B6A40);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Relatórios</span>
                    </a>
                    <!-- Orçamentos -->
                    <a href="<?= admin_url('estimates'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #BF5AF2, #9b2de0);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.598 4.5 4.698V18a2.25 2.25 0 002.25 2.25h10.5A2.25 2.25 0 0019.5 18V4.698c0-1.1-.807-1.998-1.907-2.126A48.234 48.234 0 0012 2.25z" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Orçamentos</span>
                    </a>
                    <!-- Suporte -->
                    <a href="<?= admin_url('tickets'); ?>" class="dps-shortcut-item">
                        <div class="dps-shortcut-icon" style="background: linear-gradient(135deg, #FF6961, #cc3333);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796a3.765 3.765 0 00-2.528 0m4.796 4.796c-.181.506-.475.982-.88 1.388a3.736 3.736 0 01-1.388.88m2.268-2.268l4.138 3.448m0 0a9.027 9.027 0 01-1.306 1.652c-.51.51-1.064.944-1.652 1.306m0 0l-3.448-4.138m3.448 4.138a9.014 9.014 0 01-9.424 0m5.976-4.138a3.765 3.765 0 01-2.528 0m0 0a3.736 3.736 0 01-1.388-.88 3.737 3.737 0 01-.88-1.388m2.268 2.268L7.288 19.67m0 0a9.024 9.024 0 01-1.652-1.306 9.027 9.027 0 01-1.306-1.652m0 0l4.138-3.448M4.33 16.712a9.014 9.014 0 010-9.424m4.138 5.976a3.765 3.765 0 010-2.528m0 0c.181-.506.475-.982.88-1.388a3.736 3.736 0 011.388-.88m-2.268 2.268L4.33 7.288m6.406 1.18L7.288 4.33m0 0a9.024 9.024 0 00-1.652 1.306A9.025 9.025 0 004.33 7.288" /></svg>
                        </div>
                        <span class="dps-shortcut-label">Suporte</span>
                    </a>
                </div>
            </div>
            <!-- /DPS Quick Shortcuts -->

            <div class="col-md-12 mtop20" data-container="top-12">
                <?php render_dashboard_widgets('top-12'); ?>
            </div>

            <?php hooks()->do_action('after_dashboard_top_container'); ?>

            <div class="col-md-6" data-container="middle-left-6">
                <?php render_dashboard_widgets('middle-left-6'); ?>
            </div>
            <div class="col-md-6" data-container="middle-right-6">
                <?php render_dashboard_widgets('middle-right-6'); ?>
            </div>

            <?php hooks()->do_action('after_dashboard_half_container'); ?>

            <div class="col-md-8" data-container="left-8">
                <?php render_dashboard_widgets('left-8'); ?>
            </div>
            <div class="col-md-4" data-container="right-4">
                <?php render_dashboard_widgets('right-4'); ?>
            </div>

            <div class="clearfix"></div>

            <div class="col-md-4" data-container="bottom-left-4">
                <?php render_dashboard_widgets('bottom-left-4'); ?>
            </div>
            <div class="col-md-4" data-container="bottom-middle-4">
                <?php render_dashboard_widgets('bottom-middle-4'); ?>
            </div>
            <div class="col-md-4" data-container="bottom-right-4">
                <?php render_dashboard_widgets('bottom-right-4'); ?>
            </div>

            <?php hooks()->do_action('after_dashboard'); ?>
        </div>
    </div>
</div>
<script>
    app.calendarIDs = '<?= json_encode($google_ids_calendars); ?>';
</script>
<?php init_tail(); ?>
<?php $this->load->view('admin/utilities/calendar_template'); ?>
<?php $this->load->view('admin/dashboard/dashboard_js'); ?>
</body>

</html>