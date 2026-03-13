<?php

defined('BASEPATH') or exit('No direct script access allowed');

$this->ci->load->model('gdpr_model');
$this->ci->load->model('leads_model');
$this->ci->load->model('staff_model');
$statuses = $this->ci->leads_model->get_status();

if (is_gdpr() && get_option('gdpr_enable_consent_for_leads') == '1') {
    $consent_purposes = $this->ci->gdpr_model->get_consent_purposes();
}

$rules = [
    App_table_filter::new('name', 'TextRule')->label(_l('leads_dt_name')),
    App_table_filter::new('phonenumber', 'TextRule')->label(_l('leads_dt_phonenumber')),
    App_table_filter::new('country', 'SelectRule')->label(_l('lead_country'))->options(function ($ci) {
        return collect(get_all_countries())->map(fn ($country) => [
            'value' => $country['country_id'],
            'label' => $country['short_name'],
        ]);
    }),
    App_table_filter::new('city', 'TextRule')->label(_l('lead_city')),
    App_table_filter::new('state', 'TextRule')->label(_l('lead_state')),
    App_table_filter::new('zip', 'TextRule')->label(_l('lead_zip')),
    App_table_filter::new('is_public', 'BooleanRule')->label(_l('lead_public')),
    App_table_filter::new('lost', 'BooleanRule')->label(_l('lead_lost')),
    App_table_filter::new('junk', 'BooleanRule')->label(_l('lead_junk')),
    App_table_filter::new('lastcontact', 'DateRule')->label(_l('leads_dt_last_contact')),
    App_table_filter::new('dateadded', 'DateRule')->label(_l('date_created')),
    App_table_filter::new('dateassigned', 'DateRule')->label(_l('customer_admin_date_assigned')),
    App_table_filter::new('lead_value', 'NumberRule')->label(_l('lead_add_edit_lead_value')),
    App_table_filter::new('status', 'MultiSelectRule')->label(_l('lead_status'))->options(function () use ($statuses) {
        return collect($statuses)->map(fn ($status) => [
            'value'   => $status['id'],
            'label'   => $status['name'],
            'subtext' => $status['isdefault'] == 1 ? _l('leads_converted_to_client') : null,
        ]);
    }),
    App_table_filter::new('source', 'MultiSelectRule')->label(_l('lead_source'))->options(function ($ci) {
        return collect($ci->leads_model->get_source())->map(fn ($source) => [
            'value' => $source['id'],
            'label' => $source['name'],
        ]);
    }),
];

$rules[] = App_table_filter::new('assigned', 'SelectRule')->label(_l('leads_dt_assigned'))
    ->withEmptyOperators()
    ->emptyOperatorValue(0)
    ->isVisible(fn () => staff_can('view', 'leads'))
    ->options(function ($ci) {
        $staff = $ci->staff_model->get('', ['active' => 1]);

        return collect($staff)->map(function ($staff) {
            return [
                'value' => $staff['staffid'],
                'label' => $staff['firstname'] . ' ' . $staff['lastname'],
            ];
        })->all();
    });

if (isset($consent_purposes)) {
    $rules[] = App_table_filter::new('gdpr_content', 'SelectRule')
        ->label(_l('gdpr_consent'))
        ->options(function () use ($consent_purposes) {
            return collect($consent_purposes)->map(fn ($purpose) => [
                'value' => $purpose['id'],
                'label' => $purpose['name'],
            ]);
        })->raw(function ($value, $operator, $sql_operator) {
            return db_prefix() . 'leads.id ' . $sql_operator . ' (SELECT lead_id FROM ' . db_prefix() . 'consents WHERE purpose_id=' . $value . ' and action="opt-in" AND date IN (SELECT MAX(date) FROM ' . db_prefix() . 'consents WHERE purpose_id=' . $value . ' AND lead_id=' . db_prefix() . 'leads.id))';
        });
}

return App_table::find('leads')
    ->outputUsing(function ($params) use ($statuses) {
        extract($params);

        $lockAfterConvert      = get_option('lead_lock_after_convert_to_customer');
        $has_permission_delete = staff_can('delete', 'leads');
        $custom_fields         = get_table_custom_fields('leads');
        $customFieldsColumns   = []; // 🔹 garante que existe
        $consentLeads          = get_option('gdpr_enable_consent_for_leads');

        $aColumns = [
            '1',
            db_prefix() . 'leads.id as id',
            db_prefix() . 'leads.name as name',
        ];

        if (is_gdpr() && $consentLeads == '1') {
            $aColumns[] = '1';
        }

        $aColumns = array_merge($aColumns, [
            'company',
            db_prefix() . 'leads.email as email',
            db_prefix() . 'leads.phonenumber as phonenumber',
        
            // 🔹 Placeholder para a coluna "Notes" na tabela (alinha os índices)
            '1',
        
        //  'lead_value',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'leads.id and rel_type="lead" ORDER by tag_order ASC LIMIT 1) as tags',
            'firstname as assigned_firstname',
            db_prefix() . 'leads_status.name as status_name',
            db_prefix() . 'leads_sources.name as source_name',
            db_prefix() . 'leads.lastcontact as lastcontact',
            db_prefix() . 'leads.dateadded as dateadded',
        ]);

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'leads';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'leads.assigned',
            'LEFT JOIN ' . db_prefix() . 'leads_status ON ' . db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status',
            'JOIN ' . db_prefix() . 'leads_sources ON ' . db_prefix() . 'leads_sources.id = ' . db_prefix() . 'leads.source',
        ];

        // 🔹 JOIN para pegar a ÚLTIMA nota (rel_type = 'lead') por lead
        $lastNoteJoin  = 'LEFT JOIN (';
        $lastNoteJoin .= ' SELECT n1.* FROM ' . db_prefix() . 'notes n1';
        $lastNoteJoin .= ' INNER JOIN (';
        $lastNoteJoin .= '   SELECT rel_id, MAX(dateadded) AS max_date';
        $lastNoteJoin .= '   FROM ' . db_prefix() . 'notes';
        $lastNoteJoin .= "   WHERE rel_type = 'lead'";
        $lastNoteJoin .= '   GROUP BY rel_id';
        $lastNoteJoin .= ' ) n2 ON n1.rel_id = n2.rel_id AND n1.dateadded = n2.max_date';
        $lastNoteJoin .= " WHERE n1.rel_type = 'lead'";
        $lastNoteJoin .= ') AS last_note ON last_note.rel_id = ' . db_prefix() . 'leads.id';

        $join[] = $lastNoteJoin;

        foreach ($custom_fields as $key => $field) {
            $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
            $customFieldsColumns[] = $selectAs;
            $aColumns[]            = 'ctable_' . $key . '.value as ' . $selectAs;
            $join[]                = 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'leads.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id'];
        }

        $where = [];

        if ($filtersWhere = $this->getWhereFromRules()) {
            $where[] = $filtersWhere;
        }

        // 🔒 Restrição: só admins veem tudo, comerciais apenas as suas leads
        if (!is_admin()) {
            $where[] = 'AND assigned = ' . get_staff_user_id();
        }

        $aColumns = hooks()->apply_filters('leads_table_sql_columns', $aColumns);

        if (count($custom_fields) > 4) {
            @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
        }

        // 🔹 Campos extras, incluindo a última nota
        $additionalColumns = hooks()->apply_filters('leads_table_additional_columns_sql', [
            'junk',
            'lost',
            'color',
            'status',
            'assigned',
            'lastname as assigned_lastname',
            db_prefix() . 'leads.addedfrom as addedfrom',
            '(SELECT count(leadid) FROM ' . db_prefix() . 'clients WHERE ' . db_prefix() . 'clients.leadid=' . db_prefix() . 'leads.id) as is_converted',
            'zip',
            'last_note.description as last_note',
            'last_note.dateadded as last_note_date',
        ]);

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalColumns);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

            $hrefAttr = 'href="' . admin_url('leads/index/' . $aRow['id']) . '" onclick="init_lead(' . $aRow['id'] . ');return false;"';
            $row[]    = '<a ' . $hrefAttr . ' class="tw-font-medium">' . $aRow['id'] . '</a>';

            $nameRow = '<a ' . $hrefAttr . ' class="tw-font-medium">' . e($aRow['name']) . '</a>';

            $nameRow .= '<div class="row-options">';
            $nameRow .= '<a ' . $hrefAttr . '>' . _l('view') . '</a>';

            $locked = false;

            if ($aRow['is_converted'] > 0) {
                $locked = ((! is_admin() && $lockAfterConvert == 1) ? true : false);
            }

            if (! $locked) {
                $nameRow .= ' | <a href="' . admin_url('leads/index/' . $aRow['id'] . '?edit=true') . '" onclick="init_lead(' . $aRow['id'] . ', true);return false;">' . _l('edit') . '</a>';
            }

            if ($aRow['addedfrom'] == get_staff_user_id() || $has_permission_delete) {
                $nameRow .= ' | <a href="' . admin_url('leads/delete/' . $aRow['id']) . '" class="_delete text-danger">' . _l('delete') . '</a>';
            }
            $nameRow .= '</div>';

            $row[] = $nameRow;

            if (is_gdpr() && $consentLeads == '1') {
                $consentHTML = '<p class="bold"><a href="#" onclick="view_lead_consent(' . $aRow['id'] . '); return false;">' . _l('view_consent') . '</a></p>';
                $consents    = $this->ci->gdpr_model->get_consent_purposes($aRow['id'], 'lead');

                foreach ($consents as $consent) {
                    $consentHTML .= '<p style="margin-bottom:0px;">' . e($consent['name']) . (! empty($consent['consent_given']) ? '<i class="fa fa-check text-success pull-right"></i>' : '<i class="fa fa-remove text-danger pull-right"></i>') . '</p>';
                }
                $row[] = $consentHTML;
            }
            
            // Empresa
            $row[] = e($aRow['company']);

            // Email
            $row[] = ($aRow['email'] != ''
                ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>'
                : '');

            // Telefone (link WhatsApp)
            $row[] = ($aRow['phonenumber'] != ''
                ? '<a href="https://wa.me/' . preg_replace('/\D/', '', $aRow['phonenumber']) . '" target="_blank">' . e($aRow['phonenumber']) . '</a>'
                : '');

            // 🔹 NOVA COLUNA "Notes" (última nota com cor por idade)
            $note_text = isset($aRow['last_note']) ? $aRow['last_note'] : '';
            $note_date = isset($aRow['last_note_date']) ? $aRow['last_note_date'] : null;

            $note_class = '';
            if (!empty($note_date)) {
                $ts_note   = strtotime($note_date);
                $ts_now    = time();
                $diff_days = (int) floor(($ts_now - $ts_note) / (60 * 60 * 24));

                if ($diff_days <= 3) {
                    $note_class = 'note-fresh';       // verde
                } elseif ($diff_days <= 5) {
                    $note_class = 'note-warning-age'; // amarelo
                } else {
                    $note_class = 'note-danger-age';  // vermelho
                }
            }

            if ($note_text !== '') {
                $short_note = strip_tags($note_text);
                if (strlen($short_note) > 80) {
                    $short_note = substr($short_note, 0, 80) . '...';
                }
                $row[] = '<div class="lead-note note-item ' . $note_class . '" 
                             style="min-width:260px; max-width:520px; display:block;">'
                         . e($short_note) .
                         '</div>';

            } else {
                $row[] = '';
            }

            // Tags
            $row[] = render_tags($aRow['tags']);


            $assignedOutput = '';
            if ($aRow['assigned'] != 0) {
                $full_name = e($aRow['assigned_firstname'] . ' ' . $aRow['assigned_lastname']);

                $assignedOutput = '<a data-toggle="tooltip" data-title="' . $full_name . '" href="' . admin_url('profile/' . $aRow['assigned']) . '">' . staff_profile_image($aRow['assigned'], [
                    'staff-profile-image-small',
                ]) . '</a>';

                $assignedOutput .= '<span class="hide">' . $full_name . '</span>';
            }

            $row[] = $assignedOutput;

            $outputStatus = '';

            if ($aRow['status_name'] == null) {
                if ($aRow['lost'] == 1) {
                    $outputStatus = '<span class="label label-danger">' . _l('lead_lost') . '</span>';
                } elseif ($aRow['junk'] == 1) {
                    $outputStatus = '<span class="label label-warning">' . _l('lead_junk') . '</span>';
                }
            } else {
                if (! $locked) {
                    $outputStatus .= '<div class="dropdown inline-block">';
                    $outputStatus .= '<a href="#" class="dropdown-toggle tw-flex tw-items-center tw-gap-1 tw-flex-nowrap hover:tw-opacity-80 tw-align-middle lead-status-' . $aRow['status'] . ' label' . (empty($aRow['color']) ? ' label-default' : '') . '" style="color:' . $aRow['color'] . ';border:1px solid ' . adjust_hex_brightness($aRow['color'], 0.4) . ';background: ' . adjust_hex_brightness($aRow['color'], 0.04) . ';" id="tableLeadsStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $outputStatus .= e($aRow['status_name']);
                    $outputStatus .= '<i class="chevron"></i>';
                    $outputStatus .= '</a>';

                    $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableLeadsStatus-' . $aRow['id'] . '">';

                    foreach ($statuses as $leadChangeStatus) {
                        if ($aRow['status'] != $leadChangeStatus['id']) {
                            $outputStatus .= '<li>
                          <a href="#" onclick="lead_mark_as(' . $leadChangeStatus['id'] . ',' . $aRow['id'] . '); return false;">
                             ' . e($leadChangeStatus['name']) . '
                          </a>
                       </li>';
                        }
                    }
                    $outputStatus .= '</ul>';
                    $outputStatus .= '</div>';
                } else {
                    $outputStatus = '<span class="lead-status-' . $aRow['status'] . ' label' . (empty($aRow['color']) ? ' label-default' : '') . '" style="color:' . $aRow['color'] . ';border:1px solid ' . adjust_hex_brightness($aRow['color'], 0.4) . ';background: ' . adjust_hex_brightness($aRow['color'], 0.04) . ';">' . e($aRow['status_name']) . '</span>';
                }
            }

            $row[] = $outputStatus;

            $row[] = e($aRow['source_name']);

            $row[] = ($aRow['lastcontact'] == '0000-00-00 00:00:00' || ! is_date($aRow['lastcontact']) ? '' : '<span data-toggle="tooltip" data-title="' . e(_dt($aRow['lastcontact'])) . '" class="text-has-action is-date">' . e(time_ago($aRow['lastcontact'])) . '</span>');

            $row[] = '<span data-toggle="tooltip" data-title="' . e(_dt($aRow['dateadded'])) . '" class="text-has-action is-date">' . e(time_ago($aRow['dateadded'])) . '</span>';

            foreach ($customFieldsColumns as $customFieldColumn) {
                $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            }

            $row['DT_RowId']    = 'lead_' . $aRow['id'];
            $row['DT_RowClass'] = 'has-border-left';

            if ($aRow['assigned'] == get_staff_user_id()) {
                $row['DT_RowClass'] .= ' row-border-info';
            }

            if (isset($row['DT_RowClass'])) {
                $row['DT_RowClass'] .= ' has-row-options';
            } else {
                $row['DT_RowClass'] = 'has-row-options';
            }

            $row = hooks()->apply_filters('leads_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }

        return $output;
    })->setRules($rules);