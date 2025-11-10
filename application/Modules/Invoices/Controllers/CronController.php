<?php

namespace App\Modules\Invoices\Controllers;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author      InvoicePlane Developers & Contributors
 * @copyright   Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license     https://invoiceplane.com/license.txt
 * @link        https://invoiceplane.com
 */

#[AllowDynamicProperties]
class CronController extends \Base_Controller
{
    /**
     * @param string|null $cron_key
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Cron.php
     * @legacy-function recur()
     */
    public function recur($cron_key = null)
    {
        // Check the provided cron key
        if ($cron_key != get_setting('cron_key')) {
            log_message('error', '[Cron Recurring Invoices] Wrong cron key provided! ' . $cron_key);
            show_error(trans('wrong_cron_key_provided'), 500);
            exit('Wrong cron key!');
        }

        $this->load->model([
            'invoices/mdl_invoice_recurring',
            'invoices/mdl_invoices',
            'invoices/mdl_invoice_amount',
        ]);
        $this->load->helper('mailer');

        // Gather a list of recurring invoices to generate
        $invoices_recurring = $this->invoice_recurring->active()->get()->result();
        $recurInfo          = [];
        foreach ($invoices_recurring as $invoice_recurring) {
            $recurInfo = [
                'invoice_id'           => $invoice_recurring->invoice_id,
                'client_id'            => $invoice_recurring->client_id,
                'invoice_group_id'     => $invoice_recurring->invoice_group_id,
                'invoice_status_id'    => $invoice_recurring->invoice_status_id,
                'invoice_number'       => $invoice_recurring->invoice_number,
                'invoice_recurring_id' => $invoice_recurring->invoice_recurring_id,
                'recur_start_date'     => $invoice_recurring->recur_start_date,
                'recur_end_date'       => $invoice_recurring->recur_end_date,
                'recur_frequency'      => $invoice_recurring->recur_frequency,
                'recur_next_date'      => $invoice_recurring->recur_next_date,
                'recur_status'         => $invoice_recurring->recur_status,
            ];

            if (IP_DEBUG) {
                log_message('debug', '[Cron Recurring Invoices] Recurring Info: ' . json_encode($recurInfo, JSON_PRETTY_PRINT));
            }

            // This is the original invoice id
            $source_id = $invoice_recurring->invoice_id;

            // This is the original invoice
            $invoice = $this->invoice->get_by_id($source_id);

            // Automatic calculation mode
            if (get_setting('einvoicing')) {
                $this->load->helper('e-invoice');
                // Only for shift legacy_calculation mode
                get_einvoice_usage($invoice, [], false);
            }

            // Create the new invoice
            $db_array = [
                'client_id'                => $invoice->client_id,
                'payment_method'           => $invoice->payment_method,
                'invoice_date_created'     => $invoice_recurring->recur_next_date,
                'invoice_date_due'         => $this->invoice->get_date_due($invoice_recurring->recur_next_date),
                'invoice_group_id'         => $invoice->invoice_group_id,
                'user_id'                  => $invoice->user_id,
                'invoice_number'           => $this->invoice->get_invoice_number($invoice->invoice_group_id),
                'invoice_url_key'          => $this->invoice->get_url_key(),
                'invoice_terms'            => $invoice->invoice_terms,
                'invoice_discount_amount'  => $invoice->invoice_discount_amount,
                'invoice_discount_percent' => $invoice->invoice_discount_percent,
            ];

            // This is the new invoice id
            $target_id = $this->invoice->create($db_array, false);
            if (IP_DEBUG) {
                log_message('debug', '[Cron Recurring Invoices] Recurring Invoice with id ' . $target_id . ' was created');
            }

            // Copy the original invoice to the new invoice
            $this->invoice->copy_invoice($source_id, $target_id, false);
            if (IP_DEBUG) {
                log_message('debug', '[Cron Recurring Invoices] Recurring Invoice with sourceId ' . $source_id . ' was copied to id ' . $target_id);
            }

            // Update the next recur date for the recurring invoice
            $this->invoice_recurring->set_next_recur_date($invoice_recurring->invoice_recurring_id);
            if (IP_DEBUG) {
                log_message('debug', '[Cron Recurring Invoices] Next Recurring date was set');
            }

            // Email the new invoice if applicable
            if (get_setting('automatic_email_on_recur') && mailer_configured()) {
                $new_invoice = $this->invoice->get_by_id($target_id);

                // Set the email body, use default email template if available
                $this->load->model('email_templates/emailtemplate');

                $email_template_id = get_setting('email_invoice_template');
                if ( ! $email_template_id) {
                    log_message('error', '[Cron Recurring Invoices] No email template set in the system settings!');
                    continue;
                }

                $email_template = $this->emailtemplates->where('email_template_id', $email_template_id)->get();
                if ($email_template->num_rows() == 0) {
                    log_message('error', '[Cron Recurring Invoices] No email template set in the system settings!');
                    continue;
                }

                $tpl = $email_template->row();

                // Prepare the attachments
                $this->load->model('upload/uploads');
                $attachment_files = $this->upload->get_invoice_uploads($target_id);

                // Prepare the body
                $body = $tpl->email_template_body;
                if (mb_strlen($body) != mb_strlen(strip_tags($body))) {
                    $body = htmlspecialchars_decode($body, ENT_COMPAT);
                } else {
                    $body = htmlspecialchars_decode(nl2br($body), ENT_COMPAT);
                }

                $from = empty($tpl->email_template_from_email) ?
                    [$invoice->user_email, ''] :
                    [$tpl->email_template_from_email, $tpl->email_template_from_name];

                $subject = empty($tpl->email_template_subject) ?
                    trans('invoice') . ' #' . $new_invoice->invoice_number :
                    $tpl->email_template_subject;

                $pdf_template = $tpl->email_template_pdf_template;
                $to           = $invoice->client_email;
                $cc           = $tpl->email_template_cc;
                $bcc          = $tpl->email_template_bcc;

                $email_invoice = email_invoice($target_id, $pdf_template, $from, $to, $subject, $body, $cc, $bcc, $attachment_files);

                if ($email_invoice) {
                    $this->invoice->mark_sent($target_id);
                } else {
                    log_message('error', '[Cron Recurring Invoices] Invoice ' . $target_id . 'could not be sent. Please review your Email settings.');
                }
            } else {
                log_message('error', '[Cron Recurring Invoices] Automatic_email_on_recur was not set or mailer was not configured');
            }
        }

        if (IP_DEBUG) {
            log_message('debug', '[Cron Recurring Invoices] ' . count($invoices_recurring) . ' recurring invoices processed');
        }
    }
}
