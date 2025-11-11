<?php

namespace App\Modules\Tasks\Controllers;

use App\Core\AdminController;

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
class TasksAjaxController extends AdminController
{
    /**
     * @param null|int $invoice_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/tasks/controllers/Ajax.php
     * @legacy-function modal_task_lookups()
     */
    public function modal_task_lookups($invoice_id = null)
    {
        $default_item_tax_rate = get_setting('default_item_tax_rate');
        $data                  = [
            'default_item_tax_rate' => $default_item_tax_rate !== '' ?: 0,
            'tasks'                 => [],
        ];

        if ( ! empty($invoice_id)) {
            $this->load->model('tasks/task');
            $data['tasks'] = $this->task->get_tasks_to_invoice($invoice_id);
        }

        $this->layout->load_view('tasks/modal_task_lookups', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/tasks/controllers/Ajax.php
     * @legacy-function process_task_selections()
     */
    public function process_task_selections()
    {
        $this->load->model('tasks/task');

        $tasks = $this->task->where_in('task_id', $this->input->post('task_ids'))->get()->result();
        foreach ($tasks as $task) {
            $task->task_price = format_amount($task->task_price);
        }

        echo json_encode($tasks);
    }
}
