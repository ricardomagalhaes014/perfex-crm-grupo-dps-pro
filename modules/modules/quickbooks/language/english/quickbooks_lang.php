<?php
$lang['quickbooks'] = 'QuickBooks® Online';
$lang['quickbooks_short'] = 'QuickBooks®';
$lang['qb_active'] = 'Active';
$lang['qb_client_id'] = 'Client ID';
$lang['qb_client_secret'] = 'Client Secret';
$lang['qb_client_id_demo'] = 'Demo Client ID';
$lang['qb_client_secret_demo'] = 'Demo Client Secret';
$lang['qb_test_mode_enabled'] = 'Enable Test Mode';
$lang['quickbooks_settings'] = $lang['quickbooks_short'] . ' Settings';

$lang['qb_general'] = 'General';
$lang['qb_config'] = '<i class="fa fa-cogs"></i> Config';
$lang['qb_reset_sync'] = '<i class="fa fa-window-restore"></i> Reset';

$lang['qb_reset_sync_help'] = 'This will remove all evidence of any synchronization in Perfex CRM, giving you an opportunity to run a clean synchronization. It is recommended for use if you want to switch from Development mode to Production mode';

$lang['requires_connection'] = 'Requires Connection';

$lang['qb_sales_tax_model'] = 'Sales Tax Model';
$lang['qb_invoice_synch'] = 'Invoice Synch';
$lang['qb_hour_of_day_perform_auto_operations_help'] = 'To help you synchronize everything "Chart of Accounts, Customers, Items, Taxes, Invoices, Payments, Expenses" with quickbooks at a given set time during the day, make sure that the cron job is properly configured in order to use this feature.';

$lang['qb_manual_cron_sync_enabled'] = 'Automatic Cron Sync Disabled';
$lang['qb_manual_cron_sync_enabled_help'] = 'Due to the quickbooks synchronization taking some time to complete. We recommend leaving this "Inactive" and setting up a CRON Job instance for your system. However, if you dont mind waiting till the synchronization is complete you may "Activate" it.';

$lang['qb_default_income_acc'] = 'Default Income Account';
$lang['qb_default_asset_acc'] = 'Default Asset Account';
$lang['qb_default_payment_acc'] = 'Default Payment Account';

$lang['qb_default_income_acc_help'] = 'This will be set as the default income account for all Items. (IncomeAccountRef). Synchronize Chart of Accounts if you cannot find your income account.';
$lang['qb_default_asset_acc_help'] = 'This will be the deposit account when a payment has been processed. (DepositToAccountRef)';
$lang['qb_default_payment_acc_help'] = 'This will be the default payment account used when an expense / invoice payment is processed.';

$company_name = empty(get_option('companyname')) ? 'Perfex' : get_option('companyname');

$lang['qb_upload_helper'] = $company_name . ' <i class="fa fa-arrow-right"></i> ' . $lang['quickbooks'];
$lang['qb_download_helper'] = $lang['quickbooks'] . ' <i class="fa fa-arrow-right"></i> ' . $company_name;
$lang['qb_upload_helper_2'] = $company_name . ' to ' . $lang['quickbooks'];
$lang['qb_download_helper_2'] = $lang['quickbooks'] . ' to ' . $company_name;
$lang['qb_sync_helper'] = 'Here we apply one-way sync from Perfex to ' . $lang['quickbooks'] . ' or vice-versa. The following symbols will guide you as you run a manual sync.<br>
<strong><i class="text-success fa fa-upload"></i> for ' . $lang['qb_upload_helper'] . '</strong>	<br>
<strong><i class="text-warning fa fa-download"></i> for ' . $lang['qb_download_helper'] . '</strong>';

//tax agency
$lang['qb_tax_agency'] = "Tax Agency";
$lang['qb_new_tax_agency'] = "New Tax Agency";
$lang['tax_agency_add_title'] = "Add Tax Agency";
$lang['tax_agency_edit_title'] = "Edit Tax Agency";

//Chart of Accounts
$lang['account_code'] = 'Account Number';
$lang['account_name'] = 'Account Name';
$lang['account_type'] = 'Type';
$lang['acc_ledger_notes'] = 'Notes';
$lang['o/p_balance'] = 'O/P Balance';
$lang['c/l_balance'] = 'C/L Balance';

//items
$lang['qb_income_account'] = 'Income Account <small>(' . $lang['quickbooks'] . ' Account)</small>';
$lang['qb_income_account_helper'] = 'Synchronize Chart of Accounts if you cannot find your income account.';

//Connected
$lang['qb_connection_success'] = 'Sucessfully connected...';
$lang['qb_connection_notice'] = '<strong><i class="fa fa-warning"></i> Note: Do not click save</strong> after connecting because the system will disconnect you. Saving ' . $lang['quickbooks'] . ' settings is considered as a change in your configurations, hence reconnection is mandatory to apply the new changes.';
$lang['qb_cron_alert'] = '"Auto Sync" This feature requires a properly configured cron job. Before activating the feature, make sure that the <a href="' . admin_url("settings?group=cronjob") . '">cron job</a> is configured as per the explanation in the documentation.';

//Auto Sync responses
$lang['qb_chart_of_acc'] = 'Chart Of Accounts';
$lang['qb_customers'] = 'Customers';
$lang['qb_tax_n_agencies'] = 'Taxes & Tax Agencies';
$lang['qb_items'] = 'Items';
$lang['qb_invoices'] = 'Invoices';
$lang['qb_payments'] = 'Payments';
$lang['qb_expenses'] = 'Expenses';

$lang['qb_chart_of_acc_sync_success'] = 'Chart of Accounts synchronization with ' . $lang['quickbooks'] . ' successful';
$lang['qb_chart_of_acc_sync_fail'] = 'Chart of Accounts synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly refresh page and try again.';

$lang['qb_clients_sync_success'] = 'Customers synchronization with ' . $lang['quickbooks'] . ' successful';
$lang['qb_clients_sync_fail'] = 'Customers synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly refresh page and try again.';
$lang['qb_clients_sync_not_found'] = 'No customers to synchronize with ' . $lang['quickbooks'];

$lang['qb_items_sync_success'] = 'Items synchronization with ' . $lang['quickbooks'] . ' successful';
$lang['qb_items_sync_fail'] = 'Some items [%s] synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly check on these items and try again.';
$lang['qb_items_sync_not_found'] = 'No Items to synchronize with ' . $lang['quickbooks'];

$lang['qb_invoices_sync_success'] = 'Invoice synchronization with ' . $lang['quickbooks'] . ' successful';
$lang['qb_invoices_sync_fail'] = 'Some invoices [%s] synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly check on these Invoices and try again.';
$lang['qb_invoices_sync_not_found'] = 'No Invoices to synchronize with ' . $lang['quickbooks'];

$lang['qb_payments_sync_success'] = 'Payments synchronization with ' . $lang['quickbooks'] . ' successful';
$lang['qb_payments_sync_fail'] = 'Some payments [%s] synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly check on these payments and try again.';
$lang['qb_payments_sync_not_found'] = 'No Payments to synchronize with ' . $lang['quickbooks'];

$lang['qb_expenses_sync_success'] = 'Expenses synchronization with ' . $lang['quickbooks'] . ' successful';
$lang['qb_expenses_sync_fail'] = 'Expenses synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly check your expenses and try again.';

$lang['qb_tax_agency_sync_fail'] = 'Tax Agencies synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly refresh page and try again.';
$lang['qb_tax_down_sync_fail'] = 'Tax Down-synchronization from ' . $lang['quickbooks'] . ' unsuccessful. Kindly refresh page and try again.';
$lang['qb_taxes_sync_success'] = 'Taxes synchronization with ' . $lang['quickbooks'] . ' successful';
$lang['qb_taxes_sync_fail'] = 'Tax synchronization with ' . $lang['quickbooks'] . ' unsuccessful. Kindly refresh page and try again.';
$lang['qb_taxes_sync_not_found'] = 'No taxes to synchronize with ' . $lang['quickbooks'];

$lang['qb_auto_cron_sync_clients_enabled'] = 'Auto Sync Clients';
$lang['qb_auto_cron_sync_items_enabled'] = 'Auto Sync Items';
$lang['qb_auto_cron_sync_taxes_enabled'] = 'Auto Sync Taxes';
$lang['qb_auto_cron_sync_invoices_enabled'] = 'Auto Sync Invoices';
$lang['qb_auto_cron_sync_payments_enabled'] = 'Auto Sync Payments';
$lang['qb_auto_cron_sync_expenses_enabled'] = 'Auto Sync Expenses';