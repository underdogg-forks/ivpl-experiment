<?php

namespace App\Modules\Projects\Controllers;

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
class ProjectsController extends \Admin_Controller
{
    /**
     * Projects constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('projects/projects');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->projects->paginate(site_url('projects/index'), $page);
        $projects = $this->projects->result();

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

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('projects');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->projects->run_validation()) {
            $this->projects->save($id);
            redirect('projects');
        }

        if ($id && ! $this->input->post('btn_submit') && ! $this->projects->prep_form($id)) {
            show_404();
        }

        $this->load->model('clients/clients');

        $this->layout->set(
            [
                'project' => $this->projects->get_by_id($id),
                'clients' => $this->clients->where('client_active', 1)->get()->result(),
            ]
        );

        $this->layout->buffer('content', 'projects/form');
        $this->layout->render();
    }

    public function view($project_id)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('projects');
        }

        $this->load->model('projects/projects');
        $project = $this->projects->get_by_id($project_id);

        if ( ! $project) {
            show_404();
        }

        $this->load->model('tasks/tasks');

        $this->layout->set([
            'project'       => $project,
            'tasks'         => $this->projects->get_tasks($project->project_id),
            'task_statuses' => $this->tasks->statuses(),
        ]);
        $this->layout->buffer('content', 'projects/view');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->load->model('tasks/tasks');
        $this->tasks->update_on_project_delete($id);

        $this->projects->delete($id);
        redirect('projects');
    }
}
