<?php

namespace App\Modules\Projects\Models;

use App\Core\ResponseModel;

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
class Project extends ResponseModel
{
    public $table = 'ip_projects';

    public $primary_key = 'ip_projects.project_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/projects/models/Mdl_project.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/projects/models/Mdl_project.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_projects.project_id');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/projects/models/Mdl_project.php
     * @legacy-function default_join()
     */
    public function default_join()
    {
        $this->db->join('ip_clients', 'ip_clients.client_id = ip_projects.client_id', 'left');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/projects/models/Mdl_project.php
     * @legacy-function get_latest()
     */
    public function get_latest()
    {
        $this->db->order_by('ip_projects.project_id', 'DESC');

        return $this;
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/projects/models/Mdl_project.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'project_name' => [
                'field' => 'project_name',
                'label' => trans('project_name'),
                'rules' => 'required',
            ],
            'client_id' => [
                'field' => 'client_id',
                'label' => trans('client'),
            ],
        ];
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/projects/models/Mdl_project.php
     * @legacy-function get_tasks()
     */
    public function get_tasks($project_id)
    {
        $result = [];

        if ( ! $project_id) {
            return $result;
        }

        $this->load->model('tasks/mdl_tasks');
        $query = $this->mdl_tasks->where('ip_tasks.project_id', $project_id)->get();

        foreach ($query->result() as $row) {
            $result[] = $row;
        }

        return $result;
    }
}
