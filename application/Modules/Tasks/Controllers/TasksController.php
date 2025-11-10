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
class TasksController extends AdminController
{
    /**
     * Tasks constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('tasks/task');
    }

    /**
     * @param int $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/tasks/controllers/Tasks.php
     * @legacy-function index()
     */
    public function index($page = 0)
    {
        $this->task->paginate(site_url('tasks/index'), $page);
        $tasks = $this->task->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_tasks'),
                'filter_method'      => 'filter_tasks',
                'tasks'              => $tasks,
                'task_statuses'      => $this->task->statuses(),
            ]
        );
        $this->layout->buffer('content', 'tasks/index');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/tasks/controllers/Tasks.php
     * @legacy-function form()
     */
    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('tasks');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->task->run_validation()) {
            $this->task->save($id);
            redirect('tasks');
        }

        if ( ! $this->input->post('btn_submit')) {
            $prep_form = $this->task->prep_form($id);
            if ($id && ! $prep_form) {
                show_404();
            }
        }

        $this->load->model('projects/project');
        $this->load->model('tax_rates/taxrate');

        $this->layout->set(
            [
                'projects'      => $this->project->get()->result(),
                'task_statuses' => $this->task->statuses(),
                'tax_rates'     => $this->taxrates->get()->result(),
            ]
        );
        $this->layout->buffer('content', 'tasks/form');
        $this->layout->render();
    }

    /**
     * @param $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/tasks/controllers/Tasks.php
     * @legacy-function delete()
     */
    public function delete($id)
    {
        $this->task->delete($id);
        redirect('tasks');
    }
}
