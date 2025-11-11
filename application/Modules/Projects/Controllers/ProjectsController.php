<?php

namespace App\Modules\Projects\Controllers;

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
class ProjectsController extends AdminController
{
    /**
     * Projects constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('projects/project');
    }

    /**
     * @param int $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/projects/controllers/Projects.php
     * @legacy-function index()
     */
    public function index($page = 0)
    {
        $this->project->paginate(site_url('projects/index'), $page);
        $projects = $this->project->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_projects'),
                'filter_method'      => 'filter_projects',
                'projects'           => $projects,
            ]
        );
        $this->layout->buffer('content', 'projects/index');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/projects/controllers/Projects.php
     * @legacy-function form()
     */
    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('projects');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->project->run_validation()) {
            $this->project->save($id);
            redirect('projects');
        }

        if ($id && ! $this->input->post('btn_submit') && ! $this->project->prep_form($id)) {
            show_404();
        }

        $this->load->model('clients/client');

        $this->layout->set(
            [
                'project' => $this->project->get_by_id($id),
                'clients' => $this->client->where('client_active', 1)->get()->result(),
            ]
        );

        $this->layout->buffer('content', 'projects/form');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/projects/controllers/Projects.php
     * @legacy-function view()
     */
    public function view($project_id)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('projects');
        }

        $this->load->model('projects/project');
        $project = $this->project->get_by_id($project_id);

        if ( ! $project) {
            show_404();
        }

        $this->load->model('tasks/task');

        $this->layout->set([
            'project'       => $project,
            'tasks'         => $this->project->get_tasks($project->project_id),
            'task_statuses' => $this->task->statuses(),
        ]);
        $this->layout->buffer('content', 'projects/view');
        $this->layout->render();
    }

    /**
     * @param $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/projects/controllers/Projects.php
     * @legacy-function delete()
     */
    public function delete($id)
    {
        $this->load->model('tasks/task');
        $this->task->update_on_project_delete($id);

        $this->project->delete($id);
        redirect('projects');
    }
}
