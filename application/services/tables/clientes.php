<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tabela de Clientes + coluna "Status" manual editável
 */
return App_table::find('clients')->outputUsing(function ($params) {
    extract($params);

    $hasPermissionDelete = staff_can('delete', 'customers');

    // Opções de status (mantém em sintonia com o controller Clients::update_status)
    $statusOptions = [
        'Novo',
        'Contactado',
        'Em negociação',
        'Qualificado',
        'Sem resposta',
        'Ganhou',
        'Perdido',
    ];

    // Badge quando não for editável
    $statusBadges = [
        'Novo'           => 'label-info',
        'Contactado'     => 'label-primary',
        'Em negociação'  => 'label-warning',
        'Qualificado'    => 'label-success',
        'Sem resposta'   => 'label-default',
        'Ganhou'         => 'label-success',
        'Perdido'        => 'label-danger',
    ];

    $customFieldsColumns = [];

    // Evitar ONLY_FULL_GROUP_BY em hosts restritivos
    $this->ci->db->query("SET sql_mode = ''");

    $aColumns = [
        '1',
        db_prefix() . 'clients.userid as userid',

        // Nome da empresa
        db_prefix() . 'clients.company as company',

        // Nome do contacto principal
        'CONCAT(' . db_prefix() . 'contacts.firstname, " ", ' . db_prefix() . 'contacts.lastname) as fullname',

        // Email do contacto principal
        db_prefix() . 'contacts.email as email',

        // Telefone do cliente
        db_prefix() . 'clients.phonenumber as phonenumber',

        // Ativo/Inativo
        db_prefix() . 'clients.active',

        // Grupos do cliente (JOIN correto)
        '(SELECT GROUP_CONCAT(cg.name SEPARATOR ",")
            FROM ' . db_prefix() . 'customer_groups cg
            INNER JOIN ' . db_prefix() . 'customers_groups csg ON cg.id = csg.groupid
           WHERE csg.customer_id = ' . db_prefix() . 'clients.userid
           ORDER BY cg.name ASC) as customerGroups',

        // Data de criação
        db_prefix() . 'clients.datecreated as datecreated',

        // Nosso status manual
        db_prefix() . 'clients.status as client_status',
    ];

    $sIndexColumn = 'userid';
    $sTable       = db_prefix() . 'clients';
    $where        = [];

    if ($filtersWhere = $this->getWhereFromRules()) {
        $where[] = $filtersWhere;
    }

    $join = [
        'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'contacts.userid=' . db_prefix() . 'clients.userid AND ' . db_prefix() . 'contacts.is_primary=1',
    ];

    // Campos personalizados como colunas
    $custom_fields = get_table_custom_fields('customers');
    foreach ($custom_fields as $key => $field) {
        $selectAs               = is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key;
        $customFieldsColumns[]  = $selectAs;
        $aColumns[]             = 'ctable_' . $key . '.value as ' . $selectAs;
        $join[]                 = 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key
                                . ' ON ' . db_prefix() . 'clients.userid = ctable_' . $key . '.relid'
                                . ' AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '"'
                                . ' AND ctable_' . $key . '.fieldid=' . $field['id'];
    }

    $join = hooks()->apply_filters('customers_table_sql_join', $join);

    if (staff_cant('view', 'customers')) {
        $where[] = 'AND ' . db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')';
    }

    $aColumns = hooks()->apply_filters('customers_table_sql_columns', $aColumns);

    if (count($custom_fields) > 4) {
        @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
    }

    $result = data_tables_init(
        $aColumns,
        $sIndexColumn,
        $sTable,
        $join,
        $where,
        [
            db_prefix() . 'contacts.id as contact_id',
            db_prefix() . 'clients.zip as zip',
            'registration_confirmed',
            'vat',
        ]
    );

    $output  = $result['output'];
    $rResult = $result['rResult'];

    foreach ($rResult as $aRow) {
        $row = [];

        // Checkbox bulk
        $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['userid'] . '"><label></label></div>';

        // ID
        $row[] = $aRow['userid'];

        // Empresa / pessoa singular sem company
        $company  = e($aRow['company']);
        $isPerson = false;
        if ($company === '') {
            $company  = _l('no_company_view_profile');
            $isPerson = true;
        }

        $url = admin_url('clients/client/' . $aRow['userid']);
        if ($isPerson && $aRow['contact_id']) {
            $url .= '?contactid=' . (int)$aRow['contact_id'];
        }

        $companyHtml  = '<a href="' . $url . '" class="tw-font-medium">' . $company . '</a>';
        $companyHtml .= '<div class="row-options">';
        $companyHtml .= '<a href="' . admin_url('clients/client/' . $aRow['userid'] . ($isPerson && $aRow['contact_id'] ? '?group=contacts' : '')) . '">' . _l('view') . '</a>';

        if ((int)$aRow['registration_confirmed'] === 0 && is_admin()) {
            $companyHtml .= ' | <a href="' . admin_url('clients/confirm_registration/' . $aRow['userid']) . '" class="text-success bold">' . _l('confirm_registration') . '</a>';
        }

        if (!$isPerson) {
            $companyHtml .= ' | <a href="' . admin_url('clients/client/' . $aRow['userid'] . '?group=contacts') . '">' . _l('customer_contacts') . '</a>';
        }

        if ($hasPermissionDelete) {
            $companyHtml .= ' | <a href="' . admin_url('clients/delete/' . $aRow['userid']) . '" class="_delete">' . _l('delete') . '</a>';
        }
        $companyHtml .= '</div>';

        $row[] = $companyHtml;

        // Contacto principal
        $row[] = ($aRow['contact_id']
            ? '<a href="' . admin_url('clients/client/' . $aRow['userid'] . '?contactid=' . (int)$aRow['contact_id']) . '" target="_blank" class="tw-font-medium">' . e(trim($aRow['fullname'])) . '</a>'
            : '');

        // E-mail
        $row[] = ($aRow['email'] ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>' : '');

        // Telefone
        $row[] = ($aRow['phonenumber'] ? '<a href="tel:' . e($aRow['phonenumber']) . '">' . e($aRow['phonenumber']) . '</a>' : '');

        // Ativo / Inativo
        $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('customer_active_inactive_help') . '">
            <input type="checkbox"' . ((int)$aRow['registration_confirmed'] === 0 ? ' disabled' : '') . ' data-switch-url="' . admin_url() . 'clients/change_client_status" name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['userid'] . '" data-id="' . $aRow['userid'] . '" ' . ((int)$aRow[db_prefix() . 'clients.active'] === 1 ? 'checked' : '') . '>
            <label class="onoffswitch-label" for="' . $aRow['userid'] . '"></label>
        </div>';
        $toggleActive .= '<span class="hide">' . ((int)$aRow[db_prefix() . 'clients.active'] === 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';
        $row[] = $toggleActive;

        // Grupos
        $groupsRow = '';
        if (!empty($aRow['customerGroups'])) {
            foreach (explode(',', $aRow['customerGroups']) as $group) {
                $groupsRow .= '<span class="label label-default mleft5 customer-group-list pointer">' . e($group) . '</span>';
            }
        }
        $row[] = $groupsRow;

        // Data criada
        $row[] = e(_dt($aRow['datecreated']));

        // ===== Coluna STATUS (editável) =====
        $currentStatus = $aRow['client_status'] ?: 'Novo';

        $canEditStatus = is_admin()
            || staff_can('edit', 'customers')
            || staff_can('create', 'customers')
            || is_customer_admin($aRow['userid']);

        if ($canEditStatus) {
            $select  = '<select class="client-status-select form-control input-sm" data-id="' . (int)$aRow['userid'] . '">';
            foreach ($statusOptions as $opt) {
                $selected = ($opt === $currentStatus) ? ' selected' : '';
                $select  .= '<option value="' . e($opt) . '"' . $selected . '>' . e($opt) . '</option>';
            }
            $select .= '</select>';
            $row[] = $select;
        } else {
            $cls = $statusBadges[$currentStatus] ?? 'label-default';
            $row[] = '<span class="label ' . $cls . '">' . e($currentStatus) . '</span>';
        }

        // Campos personalizados
        foreach ($customFieldsColumns as $customFieldColumn) {
            $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
        }

        $row['DT_RowClass'] = 'has-row-options has-border-left';
        if ((int)$aRow['registration_confirmed'] === 0) {
            $row['DT_RowClass'] .= ' row-border-warning requires-confirmation';
            $row['Data_Title']  = _l('customer_requires_registration_confirmation');
            $row['Data_Toggle'] = 'tooltip';
        }
        if ((int)$aRow[db_prefix() . 'clients.active'] === 0) {
            $row['DT_RowClass'] .= ' secondary';
        }

        $row = hooks()->apply_filters('customers_table_row_data', $row, $aRow);
        $output['aaData'][] = $row;
    }

    return $output;
})
->setRules([
    App_table_filter::new('phonenumber', 'TextRule')->label(_l('clients_phone')),
    App_table_filter::new('active', 'BooleanRule')->label(_l('customer_active')),
]);