<?php
$lang['quickbooks'] = 'QuickBooks® Online';
$lang['quickbooks_short'] = 'QuickBooks®';
$lang['qb_active'] = 'Actif';
$lang['qb_client_id'] = 'Identifiant';
$lang['qb_client_secret'] = 'Identifiant secret';
$lang['qb_client_id_demo'] = 'Identifiant de démonstration';
$lang['qb_client_secret_demo'] = 'Identifiant secret de démonstration';
$lang['qb_test_mode_enabled'] = 'Activer le mode test';
$lang['quickbooks_settings'] = $lang['quickbooks_short'] . ' Settings';

$lang['qb_general'] = 'Général';
$lang['qb_config'] = '<i class="fa fa-cogs"></i> Config';
$lang['qb_reset_sync'] = '<i class="fa fa-window-restore"></i> Réinitialiser';

$lang['qb_reset_sync_help'] = 'Cela supprimera toutes les preuves de toute synchronisation dans Perfex CRM, vous donnant la possibilité d\'exécuter une synchronisation propre. Il est recommandé de l\'utiliser si vous souhaitez passer du mode Développement au mode Production';

$lang['requires_connection'] = 'Nécessite une connexion';

$lang['qb_sales_tax_model'] = 'Modèle de taxe de vente';
$lang['qb_invoice_synch'] = 'Synchronisation des factures';
$lang['qb_hour_of_day_perform_auto_operations_help'] = 'Pour vous aider à tout synchroniser "Plan comptable, Clients, Articles, Taxes, Factures, Paiements, Dépenses" avec Quickbooks à une heure donnée pendant la journée, assurez-vous que la tâche cron est correctement configurée pour utiliser cette fonction.';

$lang['qb_manual_cron_sync_enabled'] = 'Synchronisation Cron manuelle activée';
$lang['qb_manual_cron_sync_enabled_help'] = 'En raison de la synchronisation de Quickbooks qui prend du temps. Nous vous recommandons de laisser cette option "Inactive" et de configurer une instance de travail CRON pour votre système. Cependant, si cela ne vous dérange pas d\'attendre la fin de la synchronisation, vous pouvez l\'activer.';

$lang['qb_default_income_acc'] = 'Compte de revenu par défaut';
$lang['qb_default_asset_acc'] = 'Compte d\'actif par défaut';
$lang['qb_default_payment_acc'] = 'Compte de paiement par défaut';

$lang['qb_default_income_acc_help'] = 'Il sera défini comme le compte de revenus par défaut pour tous les articles. (ComptedeRevenuRef). Synchronisez le plan comptable si vous ne trouvez pas votre compte de revenu.';
$lang['qb_default_asset_acc_help'] = 'Ce sera le compte de dépôt lorsqu\'un paiement aura été traité. (DéposersurleCompteRef)';
$lang['qb_default_payment_acc_help'] = 'Ce sera le compte de paiement par défaut utilisé / factures  lors du traitement d\'un paiement de dépenses..';

$company_name = empty(get_option('companyname')) ? 'Perfex' : get_option('companyname');

$lang['qb_upload_helper'] = $company_name . ' <i class="fa fa-arrow-right"></i> ' . $lang['quickbooks'];
$lang['qb_download_helper'] = $lang['quickbooks'] . ' <i class="fa fa-arrow-right"></i> ' . $company_name;
$lang['qb_upload_helper_2'] = $company_name . ' à ' . $lang['quickbooks'];
$lang['qb_download_helper_2'] = $lang['quickbooks'] . ' à ' . $company_name;
$lang['qb_sync_helper'] = 'Ici, nous appliquons la synchronisation unidirectionnelle de Perfex à ' . $lang['quickbooks'] . ' ou vice-versa. Les symboles suivants vous guideront lors de l\'exécution d\'une synchronisation manuelle.<br>
<strong><i class="text-success fa fa-upload"></i> pour ' . $lang['qb_upload_helper'] . '</strong>	<br>
<strong><i class="text-warning fa fa-download"></i> pour ' . $lang['qb_download_helper'] . '</strong>';

//tax agency
$lang['qb_tax_agency'] = "Agence fiscale";
$lang['qb_new_tax_agency'] = "Nouvelle agence fiscale";
$lang['tax_agency_add_title'] = "Ajouter une agence fiscale";
$lang['tax_agency_edit_title'] = "Modifier l'agence fiscale";

//Chart of Accounts
$lang['account_code'] = 'Numéro de compte';
$lang['account_name'] = 'Nom du compte';
$lang['account_type'] = 'Type';
$lang['acc_ledger_notes'] = 'Remarques';
$lang['o/p_balance'] = 'Balance O/P';
$lang['c/l_balance'] = 'Balance C/L';

//items
$lang['qb_income_account'] = 'Compte de revenu <small>(' . $lang['quickbooks'] . ' Compte)</small>';

$lang['qb_income_account_helper'] = 'Synchronisez le plan comptable si vous ne trouvez pas votre compte de revenu.';

//Connected
$lang['qb_connection_success'] = 'Connecté avec succès...';
$lang['qb_connection_notice'] = '<strong><i class="fa fa-warning"></i> Remarque: ne cliquez pas sur enregistrer</strong> après la connexion car le système vous déconnectera. Économie ' . $lang['quickbooks'] . ' les paramètres sont considérés comme un changement dans vos configurations, donc la reconnexion est obligatoire pour appliquer les nouvelles modifications.';
$lang['qb_cron_alert'] = '"Synchronisation automatique" Cette fonction nécessite un travail cron correctement configuré. Avant d\'activer la fonction, assurez-vous que le <a href="' . admin_url("settings?group=cronjob") . '">cron job</a> est configuré selon l\'explication de la documentation.';

//Auto Sync responses
$lang['qb_chart_of_acc'] = 'Plan comptable';
$lang['qb_customers'] = '
Les clients';
$lang['qb_tax_n_agencies'] = 'Taxes et agences fiscales';
$lang['qb_items'] = 'Articles';
$lang['qb_invoices'] = 'Factures';
$lang['qb_payments'] = 'Paiements';
$lang['qb_expenses'] = 'Dépenses';

$lang['qb_chart_of_acc_sync_success'] = 'Synchronisation du plan comptable avec ' . $lang['quickbooks'] . ' réussi';
$lang['qb_chart_of_acc_sync_fail'] = 'Synchronisation du plan comptable avec ' . $lang['quickbooks'] . ' infructueux. Veuillez rafraîchir la page et réessayer.';

$lang['qb_clients_sync_success'] = 'Synchronisation des clients avec ' . $lang['quickbooks'] . ' réussi';
$lang['qb_clients_sync_fail'] = 'Synchronisation des clients avec ' . $lang['quickbooks'] . ' infructueux. Veuillez rafraîchir la page et réessayer.';
$lang['qb_clients_sync_not_found'] = 'Aucun client avec lequel se synchroniser ' . $lang['quickbooks'];

$lang['qb_items_sync_success'] = 'Synchronisation des éléments avec ' . $lang['quickbooks'] . ' réussi';
$lang['qb_items_sync_fail'] = 'Certains articles [%s] synchronisation des éléments avec ' . $lang['quickbooks'] . ' infructueux. Veuillez vérifier ces éléments et réessayer.';
$lang['qb_items_sync_not_found'] = 'Aucun élément à synchroniser avec ' . $lang['quickbooks'];

$lang['qb_invoices_sync_success'] = 'Synchronisation des factures avec ' . $lang['quickbooks'] . ' réussi';
$lang['qb_invoices_sync_fail'] = 'Quelques factures [%s] synchronisation des factures avec ' . $lang['quickbooks'] . ' infructueux. Veuillez vérifier ces factures et réessayer.';
$lang['qb_invoices_sync_not_found'] = 'Aucune facture à synchroniser avec ' . $lang['quickbooks'];

$lang['qb_payments_sync_success'] = 'Synchronisation des paiements avec ' . $lang['quickbooks'] . ' réussi';
$lang['qb_payments_sync_fail'] = 'Quelques paiements [%s] synchronisation des paiements avec ' . $lang['quickbooks'] . ' infructueux. Veuillez vérifier ces paiements et réessayer';
$lang['qb_payments_sync_not_found'] = 'Aucun paiement à synchroniser avec ' . $lang['quickbooks'];

$lang['qb_expenses_sync_success'] = 'Synchronisation des dépenses avec ' . $lang['quickbooks'] . ' réussi';
$lang['qb_expenses_sync_fail'] = 'Synchronisation des dépenses avec ' . $lang['quickbooks'] . ' infructueux. Veuillez vérifier vos dépenses et réessayer.';

$lang['qb_tax_agency_sync_fail'] = 'Synchronisation des agences fiscales avec ' . $lang['quickbooks'] . ' infructueux. Veuillez rafraîchir la page et réessayer.';
$lang['qb_tax_down_sync_fail'] = 'Tax-down-synchronisation de ' . $lang['quickbooks'] . ' infructueux. Veuillez rafraîchir la page et réessayer.';
$lang['qb_taxes_sync_success'] = 'Synchronisation des taxes avec ' . $lang['quickbooks'] . ' réussi';
$lang['qb_taxes_sync_fail'] = 'Synchronisation des taxes avec ' . $lang['quickbooks'] . ' infructueux. Veuillez rafraîchir la page et réessayer.';
$lang['qb_taxes_sync_not_found'] = 'Pas de taxes pour synchroniser avec ' . $lang['quickbooks'];

$lang['qb_auto_cron_sync_clients_enabled'] = 'Auto Sync Clients';
$lang['qb_auto_cron_sync_items_enabled'] = 'Auto Sync Items';
$lang['qb_auto_cron_sync_taxes_enabled'] = 'Auto Sync Taxes';
$lang['qb_auto_cron_sync_invoices_enabled'] = 'Auto Sync Invoices';
$lang['qb_auto_cron_sync_payments_enabled'] = 'Auto Sync Payments';
$lang['qb_auto_cron_sync_expenses_enabled'] = 'Auto Sync Expenses';