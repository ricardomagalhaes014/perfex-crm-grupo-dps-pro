<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mailflow extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('client_groups_model');
        $this->load->model('tickets_model');
        $this->load->model('leads_model');
        $this->load->model('mailflow_model');
        $this->load->model('staff_model');
        hooks()->do_action('mailflow_init');
    }

    public function index()
    {
        show_404();
    }

    public function manage()
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        $data = [];

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_sends_newsletter');

        $data['clientGroups'] = $this->client_groups_model->get_groups();
        $data['lead_statuses'] = $this->leads_model->get_status();
        $data['lead_sources'] = $this->leads_model->get_source();
        $data['staff_members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1]);
        $data['template_list'] = $this->mailflow_model->getTemplates();
        $data['sms_active_integration'] = $this->app_sms->get_active_gateway();

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mailflow', 'table'));
        }

        $this->load->view('true_manage', $data);
    }

    public function integrations()
    {
        if (!has_permission('mailflow_integrations', '', 'view')) {
            access_denied('mailflow_integrations');
        }

        $data = [];

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_manage_integrations');

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mailflow', 'integrations/table'));
        }

        $this->load->view('integrations/manage', $data);
    }

    public function sms_integrations()
    {
        if (!has_permission('mailflow_integrations', '', 'create')) {
            access_denied('mailflow_integrations');
        }

        if ($this->input->post()) {

            $this->load->model('payment_modes_model');
            $this->load->model('settings_model');

            $logo_uploaded = (handle_company_logo_upload() ? true : false);
            $favicon_uploaded = (handle_favicon_upload() ? true : false);
            $signatureUploaded = (handle_company_signature_upload() ? true : false);

            $post_data = $this->input->post();
            $tmpData = $this->input->post(null, false);

            if (isset($post_data['settings']['email_header'])) {
                $post_data['settings']['email_header'] = $tmpData['settings']['email_header'];
            }

            if (isset($post_data['settings']['email_footer'])) {
                $post_data['settings']['email_footer'] = $tmpData['settings']['email_footer'];
            }

            if (isset($post_data['settings']['email_signature'])) {
                $post_data['settings']['email_signature'] = $tmpData['settings']['email_signature'];
            }

            if (isset($post_data['settings']['smtp_password'])) {
                $post_data['settings']['smtp_password'] = $tmpData['settings']['smtp_password'];
            }

            $success = $this->settings_model->update($post_data);

            if ($success > 0) {
                set_alert('success', _l('settings_updated'));
            }

            if ($logo_uploaded || $favicon_uploaded) {
                set_debug_alert(_l('logo_favicon_changed_notice'));
            }

            redirect(admin_url('mailflow/sms_integrations'), 'refresh');
        }

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_add_integration');

        $this->load->view('integrations/sms_integration', $data);
    }

    public function create_integration($integration_id = '')
    {
        if (!has_permission('mailflow_integrations', '', 'create')) {
            access_denied('mailflow_integrations');
        }

        if ($this->input->post() && $integration_id === '') {

            $postData = $this->input->post();
            $tmpData = $this->input->post(null, false);

            unset(
                $postData['fakeusernameremembered'],
                $postData['fakepasswordremembered'],
                $postData['test_email'],
            );

            if (isset($postData['smtp_password'])) {
                $postData['smtp_password'] = $tmpData['smtp_password'];
            }

            $response = $this->mailflow_model->addIntegration($postData + ['created_at' => date('Y-m-d H:i:s')]);

            if ($response == true) {
                set_alert('success', _l('mailflow_integration_created'));
            } else {
                set_alert('warning', _l('mailflow_integration_not_created_successfully'));
            }

            redirect(admin_url('mailflow/integrations'));

        } elseif ($this->input->post() && $integration_id !== '') {

            $postData = $this->input->post();
            $tmpData = $this->input->post(null, false);

            unset(
                $postData['fakeusernameremembered'],
                $postData['fakepasswordremembered'],
                $postData['test_email'],
            );

            if (isset($postData['smtp_password'])) {
                $postData['smtp_password'] = $tmpData['smtp_password'];
            }

            $doNotUpdateIntegrationTitle = [1, 2, 3, 4];
            if (in_array($integration_id, $doNotUpdateIntegrationTitle)) {
                unset($postData['name']);
            }

            $response = $this->mailflow_model->updateIntegration($integration_id, $postData);

            if ($response == true) {
                set_alert('success', _l('mailflow_integration_updated'));
            } else {
                set_alert('warning', _l('mailflow_integration_not_updated_successfully'));
            }

            redirect(admin_url('mailflow/integrations'));
        }

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_add_integration');
        if ($integration_id) {
            $data['integration_data'] = $this->mailflow_model->getIntegration($integration_id);
        }

        $this->load->view('integrations/create', $data);
    }

    public function delete_integration($integration_id)
    {
        if (!has_permission('mailflow_integrations', '', 'delete')) {
            access_denied('mailflow_integrations');
        }

        if (!$integration_id) {
            redirect(admin_url('mailflow/integrations'));
        }

        $doNotDeleteIntegrations = [1, 2, 3, 4];
        if (in_array($integration_id, $doNotDeleteIntegrations)) {
            redirect(admin_url('mailflow/integrations'));
        }

        $response = $this->mailflow_model->deleteIntegration($integration_id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('mailflow_manage_integrations')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('mailflow_manage_integrations')));
        }

        redirect(admin_url('mailflow/integrations'));
    }

    public function scheduled_campaigns()
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        $data = [];

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_manage_schedules');

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mailflow', 'schedules/table'));
        }

        $this->load->view('schedules/manage', $data);
    }

    public function view_schedule($schedule_id)
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        $data = [];

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_manage_schedules');

        $data['newsletterData'] = $this->mailflow_model->getScheduledCampaign($schedule_id);

        $this->load->view('schedules/view', $data);
    }

    public function delete_schedule($schedule_id)
    {
        if (!has_permission('mailflow', '', 'delete')) {
            access_denied('mailflow');
        }

        if (!$schedule_id) {
            redirect(admin_url('mailflow/schedules'));
        }

        $response = $this->mailflow_model->deleteScheduledCampaign($schedule_id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('mailflow_manage_schedules')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('mailflow_manage_schedules')));
        }

        redirect(admin_url('mailflow/schedules'));
    }

    public function history()
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        $data = [];

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_newsletter_history');

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mailflow', 'table'));
        }

        $this->load->view('manage', $data);
    }

    public function view_newsletter($id)
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        $data = [];

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_newsletter_history');

        $data['newsletterData'] = $this->mailflow_model->get($id);

        $this->load->view('view', $data);
    }

    public function sendEmails()
    {
        if (!has_permission('mailflow', '', 'create')) {
            access_denied('mailflow');
        }

        $sendNewsletterTo = $this->input->post('send_newsletter_to', true);

        $customerStatus = $this->input->post('customers_status', true);
        $customerGroups = $this->input->post('customer_groups', true);
        $customerCountries = $this->input->post('customers_country', true);

        $leadGroups = $this->input->post('lead_groups', true);
        $leadSources = $this->input->post('leads_source', true);
        $leadAssignedToStaff = $this->input->post('leads_assigned_to_staff', true);
        $leadCountries = $this->input->post('leads_country', true);

        $emailSubject = $this->input->post('email_subject');
        $emailContent = $this->input->post('email_content', false);
        $smsContent = $this->input->post('sms_content', false);

        $sendCampaignSettings = $this->input->post('settings', true);
        $sendCampaignToEmails = $sendCampaignSettings['send_campaign_to_emails'];
        $sendCampaignToSMS = $sendCampaignSettings['send_campaign_to_sms'];
        $scheduleCampaign = $this->input->post('schedule_campaign', true);
        $smtpIntegration = $this->input->post('email_smtp_integration', true);

        if (empty($sendNewsletterTo)) {
            set_alert('danger', _l('mailflow_please_select_to_who_you_want_to_newsletter'));
            redirect(admin_url('mailflow/manage'));
        }

        $leadsEmails = [];
        $leadsSMS = [];
        $customerEmails = [];
        $customerSMS = [];

        if (in_array('leads', $sendNewsletterTo)) {
            $leadsEmails = $this->mailflow_model->searchLeadsEmails($leadSources, $leadAssignedToStaff, $leadCountries, $leadGroups);
            $leadsSMS = $this->mailflow_model->searchLeadsPhoneNumbers($leadSources, $leadAssignedToStaff, $leadCountries, $leadGroups);
        }

        if (in_array('customers', $sendNewsletterTo)) {
            $customerEmails = $this->mailflow_model->searchCustomersEmails($customerStatus, $customerGroups, $customerCountries);
            $customerSMS = $this->mailflow_model->searchCustomersPhoneNumbers($customerStatus, $customerGroups, $customerCountries);
        }

        $usersToSendMail = array_merge(
            $leadsEmails,
            $customerEmails
        );
        $usersToSendMail = array_filter($usersToSendMail, 'strlen');
        $usersToSendMail = array_unique($usersToSendMail);

        $phonenumbersToReceiveSMS = array_merge(
            $leadsSMS,
            $customerSMS
        );
        $phonenumbersToReceiveSMS = array_filter($phonenumbersToReceiveSMS, 'strlen');
        $phonenumbersToReceiveSMS = array_unique($phonenumbersToReceiveSMS);
        $phonenumbersToReceiveSMS = array_values($phonenumbersToReceiveSMS);

        $phonenumbersToReceiveSMS = array_filter($phonenumbersToReceiveSMS, 'mailflow_validate_phone_number');

        $phonenumbersToReceiveSMS = array_values($phonenumbersToReceiveSMS);

        $unsubscribedEmails = $this->mailflow_model->getUnsubscribedEmails();

        foreach ($unsubscribedEmails as $unsubscribedEmail) {
            $key = array_search($unsubscribedEmail, $usersToSendMail);
            if ($key !== false) {
                unset($usersToSendMail[$key]);
            }
        }
        $usersToSendMail = array_values($usersToSendMail);

        if ($sendCampaignToEmails) {
            if (empty($emailSubject)) {
                set_alert('danger', _l('mailflow_please_enter_email_subject'));
                redirect(admin_url('mailflow/manage'));
            }

            if (empty($emailContent)) {
                set_alert('danger', _l('mailflow_please_enter_email_subject'));
                redirect(admin_url('mailflow/manage'));
            }

            if (count($usersToSendMail) === 0 || empty($usersToSendMail)) {
                set_alert('danger', _l('mailflow_no_emails_found'));
                redirect(admin_url('mailflow/manage'));
            }
        }

        if ($sendCampaignToSMS) {
            if (empty($smsContent)) {
                set_alert('danger', _l('mailflow_please_enter_email_content'));
                redirect(admin_url('mailflow/manage'));
            }

            if (count($phonenumbersToReceiveSMS) === 0 || empty($phonenumbersToReceiveSMS)) {
                set_alert('danger', _l('mailflow_no_sms_found'));
                redirect(admin_url('mailflow/manage'));
            }
        }

        if (!$sendCampaignToSMS && !$sendCampaignToEmails) {
            set_alert('danger', _l('mailflow_campaign_should_be_sent_to_sms_or_email'));
            redirect(admin_url('mailflow/manage'));
        }

        if (empty($scheduleCampaign)) {

            $totalEmailsSent = 0;
            $totalEmailsFailed = 0;
            $emailsToSent = 0;

            $totalSMSSent = 0;
            $totalSMSFailed = 0;
            $smsToSend = 0;

            if ($sendCampaignToEmails) {
                $this->load->model('emails_model');
                foreach ($usersToSendMail as $email) {

                    ++$emailsToSent;

                    $unsubscribeLink = '<a href="' . base_url('mailflow/mailflowunsubscribe/opt_out/' . mailflow_encryption($email)) . '">Unsubscribe</a>';
                    $emailContent = str_replace('{{unsubscribe_link}}', $unsubscribeLink, $emailContent);

                    if (mailflow_send_email($email, $emailSubject, $emailContent, $smtpIntegration)) {

                        ++$totalEmailsSent;
                        log_activity('Campaign Email Sent [To : ' . $email . ']');

                    } else {

                        ++$totalEmailsFailed;
                        log_activity('Campaign Email Failed [To : ' . $email . ']');

                    }

                }
            }

            if ($sendCampaignToSMS) {
                foreach ($phonenumbersToReceiveSMS as $phone) {

                    ++$smsToSend;

                    if (mailflow_send_sms($phone, $smsContent)) {

                        ++$totalSMSSent;
                        log_activity('Campaign SMS Sent [To : ' . $phone . ']');

                    } else {

                        ++$totalSMSFailed;
                        log_activity('Campaign SMS Failed [To : ' . $phone . ']');

                    }
                }
            }

            $isEmailDataEqual = ($totalEmailsFailed + $totalEmailsSent) === $emailsToSent;
            $isSMSDataEqual = ($totalSMSFailed + $totalSMSSent) === $smsToSend;

            if ($sendCampaignToSMS && $isSMSDataEqual && !$sendCampaignToEmails) {

                $this->mailflow_model->add([
                    'sent_by' => get_staff_user_id(),
                    'sms_content' => $smsContent,
                    'total_sms_to_send' => $smsToSend,
                    'sms_sent' => $totalSMSSent,
                    'sms_list' => json_encode($phonenumbersToReceiveSMS),
                    'sms_failed' => $totalSMSFailed,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                log_activity('SMS Campaign Sent [Total SMS: ' . $smsToSend . ' - Total SMS Sent: ' . $totalSMSSent . ' - Total Failed SMS: ' . $totalSMSFailed . ']');

                set_alert('success', _l('mailflow_newsletter_sent_successfully') . ' ' . _l('mailflow_sms_sent') . ' - ' . $totalSMSSent . ' ' . _l('mailflow_sms_failed') . ' -' . $totalSMSFailed);
                redirect(admin_url('mailflow/history'));

            }

            if ($sendCampaignToEmails && $isEmailDataEqual && !$sendCampaignToSMS) {

                $this->mailflow_model->add([
                    'sent_by' => get_staff_user_id(),
                    'email_subject' => $emailSubject,
                    'email_content' => $emailContent,
                    'total_emails_to_send' => $emailsToSent,
                    'emails_sent' => $totalEmailsSent,
                    'email_list' => json_encode($usersToSendMail),
                    'emails_failed' => $totalEmailsFailed,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                log_activity('Email Campaign Sent [Email Subject - ' . $emailSubject . ' - Total Emails: ' . $emailsToSent . ' - Total Emails Sent: ' . $totalEmailsSent . ' - Total Failed Emails: ' . $totalEmailsFailed . ']');

                set_alert('success', _l('mailflow_newsletter_sent_successfully') . ' ' . _l('mailflow_mails_sent') . ' - ' . $totalEmailsSent . ' ' . _l('mailflow_mails_failed') . ' -' . $totalEmailsFailed);
                redirect(admin_url('mailflow/history'));

            }

            if ($sendCampaignToEmails && $isEmailDataEqual && $sendCampaignToSMS && $isSMSDataEqual) {

                $this->mailflow_model->add([
                    'sent_by' => get_staff_user_id(),
                    'email_subject' => $emailSubject,
                    'email_content' => $emailContent,
                    'sms_content' => $smsContent,
                    'total_emails_to_send' => $emailsToSent,
                    'total_sms_to_send' => $smsToSend,
                    'emails_sent' => $totalEmailsSent,
                    'sms_sent' => $totalSMSSent,
                    'email_list' => json_encode($usersToSendMail),
                    'sms_list' => json_encode($phonenumbersToReceiveSMS),
                    'emails_failed' => $totalEmailsFailed,
                    'sms_failed' => $totalSMSFailed,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                log_activity('SMS & Email Campaign Sent [Email Subject - ' . $emailSubject . ' - Total Emails: ' . $emailsToSent . ' - Total Emails Sent: ' . $totalEmailsSent . ' - Total Failed Emails: ' . $totalEmailsFailed . '] - Total SMS: ' . $smsToSend . ' - Total SMS Sent: ' . $totalSMSSent . ' - Total Failed SMS: ' . $totalSMSFailed . ']');

                set_alert('success', _l('mailflow_newsletter_sent_successfully') . ' ' . _l('mailflow_mails_sent') . ' - ' . $totalEmailsSent . ' ' . _l('mailflow_mails_failed') . ' -' . $totalEmailsFailed . ' ' . _l('mailflow_sms_sent') . ' - ' . $totalSMSSent . ' ' . _l('mailflow_sms_failed') . ' -' . $totalSMSFailed);
                redirect(admin_url('mailflow/history'));

            }

        } else {
            // Schedule Campaign
            $campaignData = [
                'scheduled_by' => get_staff_user_id(),
                'email_subject' => $emailSubject,
                'email_content' => $emailContent,
                'email_list' => json_encode($usersToSendMail),
                'email_smtp' => $smtpIntegration,
                'sms_content' => $smsContent,
                'sms_list' => json_encode($phonenumbersToReceiveSMS),
                'scheduled_to' => $scheduleCampaign,
                'campaign_status' => 0,
                'scheduled_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $campaignId = $this->mailflow_model->addScheduledCampaign($campaignData);

            set_alert('success', _l('mailflow_campaign_scheduled_successfully'));
            redirect(admin_url('mailflow/view_schedule/' . $campaignId));
        }

    }

    public function totalEmailsFound()
    {
        if (!has_permission('mailflow', '', 'create')) {
            access_denied('mailflow');
        }

        $customerStatus = $this->input->post('customers_status', true);
        $customerGroups = $this->input->post('customer_groups', true);
        $customerCountries = $this->input->post('customers_country', true);

        $leadGroups = $this->input->post('lead_groups', true);
        $leadSources = $this->input->post('leads_source', true);
        $leadAssignedToStaff = $this->input->post('leads_assigned_to_staff', true);
        $leadCountries = $this->input->post('leads_country', true);

        $totalLeads = $this->mailflow_model->searchLeadsEmails($leadSources, $leadAssignedToStaff, $leadCountries, $leadGroups);
        $totalLeadsPhoneNumbers = $this->mailflow_model->searchLeadsPhoneNumbers($leadSources, $leadAssignedToStaff, $leadCountries, $leadGroups);
        $totalCustomers = $this->mailflow_model->searchCustomersEmails($customerStatus, $customerGroups, $customerCountries);
        $totalCustomersPhoneNumbers = $this->mailflow_model->searchCustomersPhoneNumbers($customerStatus, $customerGroups, $customerCountries);
        $unsubscribedEmails = $this->mailflow_model->getUnsubscribedEmails();

        $totalLeadsPhoneNumbers = array_filter($totalLeadsPhoneNumbers, 'mailflow_validate_phone_number');
        $totalCustomersPhoneNumbers = array_filter($totalCustomersPhoneNumbers, 'mailflow_validate_phone_number');

        foreach ($unsubscribedEmails as $unsubscribedEmail) {
            $key = array_search($unsubscribedEmail, $totalLeads);
            if ($key !== false) {
                unset($totalLeads[$key]);
            }
        }

        foreach ($unsubscribedEmails as $unsubscribedEmail) {
            $key = array_search($unsubscribedEmail, $totalCustomers);
            if ($key !== false) {
                unset($totalCustomers[$key]);
            }
        }

        $totalLeads = array_values($totalLeads);
        $totalCustomers = array_values($totalCustomers);
        $totalLeadsPhoneNumbers = array_values($totalLeadsPhoneNumbers);
        $totalCustomersPhoneNumbers = array_values($totalCustomersPhoneNumbers);

        echo json_encode([
            'total_leads' => count($totalLeads),
            'total_leads_phone_numbers' => count($totalLeadsPhoneNumbers),
            'total_customers' => count($totalCustomers),
            'total_customers_phone_numbers' => count($totalCustomersPhoneNumbers),
            'leads_list' => $totalLeads,
            'leads_phone_number_list' => $totalLeadsPhoneNumbers,
            'customers_list' => $totalCustomers,
            'customers_phone_number_list' => $totalCustomersPhoneNumbers
        ]);
        die;
    }

    public function getTemplate()
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        $templateId = $this->input->post('newsletter_template');
        $templateData = $this->mailflow_model->getTemplate($templateId);

        echo json_encode([
            'template_data' => $templateData
        ]);
    }

    public function manage_templates()
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mailflow', 'templates/table'));
        }

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_templates');
        $this->load->view('templates/manage', $data);
    }

    public function create_template($template_id = '')
    {
        if (!has_permission('mailflow', '', 'create')) {
            access_denied('mailflow');
        }

        if ($this->input->post() && $template_id === '') {

            $response = $this->mailflow_model->addTemplate($this->input->post() + ['created_at' => date('Y-m-d H:i:s')]);

            if ($response == true) {
                set_alert('success', _l('mailflow_template_created_successfully'));
            } else {
                set_alert('warning', _l('mailflow_template_not_created_successfully'));
            }

            redirect(admin_url('mailflow/manage_templates'));

        } elseif ($this->input->post() && $template_id !== '') {
            $response = $this->mailflow_model->updateTemplate($template_id, $this->input->post(null, false));

            if ($response == true) {
                set_alert('success', _l('mailflow_template_updated_successfully'));
            } else {
                set_alert('warning', _l('mailflow_template_not_updated_successfully'));
            }

            redirect(admin_url('mailflow/manage_templates'));
        }

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_template');
        if ($template_id) {
            $data['template_data'] = $this->mailflow_model->getTemplate($template_id);
        }

        $this->load->view('templates/create', $data);
    }

    public function delete_template($template_id)
    {
        if (!has_permission('mailflow', '', 'delete')) {
            access_denied('mailflow');
        }

        if (!$template_id) {
            redirect(admin_url('mailflow/manage_templates'));
        }

        $response = $this->mailflow_model->deleteTemplate($template_id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('mailflow_template')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('mailflow_template')));
        }

        redirect(admin_url('mailflow/manage_templates'));
    }

    public function manage_unsubscribed_emails()
    {
        if (!has_permission('mailflow', '', 'view')) {
            access_denied('mailflow');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mailflow', 'unsubscribe/table'));
        }

        $data['title'] = _l('mailflow') . ' - ' . _l('mailflow_unsub_emails');
        $this->load->view('unsubscribe/manage', $data);
    }

    public function remove_unsubscribed($id)
    {
        if (!has_permission('mailflow', '', 'delete')) {
            access_denied('mailflow');
        }

        if (!$id) {
            redirect(admin_url('mailflow/manage_unsubscribed_emails'));
        }

        $response = $this->mailflow_model->deleteUnsubscribedEmail($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('mailflow_unsubscribed_email')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('mailflow_unsubscribed_email')));
        }

        redirect(admin_url('mailflow/manage_unsubscribed_emails'));
    }

    public function send_email_test()
    {

        if ($this->input->post()) {

            $testContent = $this->input->post('test_content');
            if (empty($testContent)) {
                $testContent = 'Test Campaign Email';
            }

            $this->load->config('email');
            // Simulate fake template to be parsed
            $template = new StdClass();
            $template->message = $testContent;
            $template->fromname = get_option('companyname') != '' ? get_option('companyname') : 'TEST';
            $template->subject = 'Email Campaign Testing';

            $template = parse_email_template($template);

            $this->email->initialize();
            if (get_option('mail_engine') == 'phpmailer') {
                $this->email->set_debug_output(function ($err) {
                    if (!isset($GLOBALS['debug'])) {
                        $GLOBALS['debug'] = '';
                    }
                    $GLOBALS['debug'] .= $err . '<br />';

                    return $err;
                });

                $this->email->set_smtp_debug(3);
            }

            $this->email->set_newline(config_item('newline'));
            $this->email->set_crlf(config_item('crlf'));

            $this->email->from(get_option('smtp_email'), $template->fromname);
            $this->email->to($this->input->post('test_email'));

            $systemBCC = get_option('bcc_emails');

            if ($systemBCC != '') {
                $this->email->bcc($systemBCC);
            }

            $this->email->subject($template->subject);
            $this->email->message($template->message);

            if ($this->email->send(true)) {
                echo json_encode(['status' => 1, 'message' => 'Seems like your SMTP settings is set correctly. Check your email now.']);
                die;
            }

            $msg = '<h1>Your SMTP settings are not set correctly here is the debug log.</h1><br />' . $this->email->print_debugger() . (isset($GLOBALS['debug']) ? $GLOBALS['debug'] : '');

            echo json_encode(['status' => 0, 'message' => $msg]);
            die;
        }
    }

    public function send_sms_test()
    {
        $phone = $this->input->post('number');
        $content = $this->input->post('content');
        $content = strip_tags($content);

        if (empty($phone)) {
            return false;
        }

        $gateway = $this->app_sms->get_active_gateway();

        if ($gateway !== false) {
            $className = 'sms_' . $gateway['id'];

            if (empty($content)) {
                $content = "Test Campaign SMS";
            }

            $message = clear_textarea_breaks($content);

            $retval = $this->{$className}->send($phone, $message);

            if ($retval) {
                echo json_encode([
                    "status" => 1,
                    "message" => _l('mailflow_test_sms_sent_successfully')
                ]);
                die;
            }

            echo json_encode([
                "status" => 0,
                "message" => _l('mailflow_test_sms_sent_failed')
            ]);
            die;
        }

        return false;
    }
}
