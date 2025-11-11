<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * CodeIgniter CRUD Model 2
 * A base model providing CRUD, pagination and validation.
 *
 * @author        Jesse Terry
 * @copyright     Copyright (c) 2012-2013, Jesse Terry
 *
 * @see          http://developer13.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
#[AllowDynamicProperties]
class MY_Model extends CI_Model
{
    public $table;
    public $primary_key;
    
    /** @var int */
    public $default_limit = 15;
    
    public $page_links;
    public $query;
    
    /** @var array */
    public $form_values = [];
    
    public $validation_errors;
    public $total_rows;
    public $date_created_field;
    public $date_modified_field;
    
    /** @var array */
    public $native_methods = [
        'select', 'select_max', 'select_min', 'select_avg', 'select_sum',
        'join', 'where', 'or_where', 'where_in', 'or_where_in',
        'where_not_in', 'or_where_not_in', 'like', 'or_like',
        'not_like', 'or_not_like', 'group_by', 'distinct',
        'having', 'or_having', 'order_by', 'limit',
    ];
    
    /** @var int */
    public $total_pages = 0;
    
    /** @var int */
    public $current_page;
    
    /** @var int */
    public $next_page;
    
    /** @var int */
    public $previous_page;
    
    /** @var int */
    public $offset;
    
    /** @var int */
    public $next_offset;
    
    /** @var int */
    public $previous_offset;
    
    /** @var int */
    public $last_offset;
    
    /** @var int */
    public $id;
    
    /** @var array */
    public $filter = [];
    
    /** @var string */
    protected $default_validation_rules = 'validation_rules';
    
    /** @var array */
    protected $validation_rules;

    /**
     * Magic method for delegating calls to database query builder
     * Also handles filter_* methods for applying filters
     *
     * @param string $name Method name
     * @param array  $arguments Method arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        // Early return for filter methods
        if (mb_substr($name, 0, 7) === 'filter_') {
            $this->filter[] = [mb_substr($name, 7), $arguments];
            return $this;
        }
        
        // Delegate to database query builder
        call_user_func_array([$this->db, $name], $arguments);
        return $this;
    }

    /**
     * Execute query and return result
     *
     * @param bool $include_defaults Whether to include default query settings
     * @return $this
     */
    public function get($include_defaults = true)
    {
        if ($include_defaults) {
            $this->set_defaults();
        }

        $this->run_filters();
        $this->query = $this->db->get($this->table);
        $this->filter = [];

        return $this;
    }

    /**
     * Paginate results with configurable pagination
     *
     * @param string $base_url Base URL for pagination links
     * @param int    $offset   Starting offset
     * @param int    $uri_segment URI segment for page number
     */
    public function paginate($base_url, $offset = 0, $uri_segment = 3)
    {
        $this->load->helper('url');
        $this->load->library('pagination');

        $this->offset = $offset;
        $default_list_limit = $this->mdl_settings->setting('default_list_limit');
        $per_page = $default_list_limit ?: $this->default_limit;

        $this->set_defaults();
        $this->run_filters();

        $this->db->limit($per_page, $this->offset);
        $this->query = $this->db->get($this->table);

        $this->total_rows = $this->db->query('SELECT FOUND_ROWS() AS num_rows')->row()->num_rows;
        $this->total_pages = ceil($this->total_rows / $per_page);
        $this->previous_offset = $this->offset - $per_page;
        $this->next_offset = $this->offset + $per_page;
        $this->last_offset = ($this->total_pages * $per_page) - $per_page;

        $config = [
            'base_url'   => $base_url,
            'total_rows' => $this->total_rows,
            'per_page'   => $per_page,
        ];

        if ($this->config->item('pagination_style')) {
            $config = array_merge($config, $this->config->item('pagination_style'));
        }

        $this->pagination->initialize($config);
        $this->page_links = $this->pagination->create_links();
    }

    /**
     * Save record to database (insert or update)
     *
     * @param int|null   $id       Record ID for update, null for insert
     * @param array|null $db_array Data to save
     * @return int Record ID
     */
    public function save($id = null, $db_array = null)
    {
        $db_array = $db_array ?: $this->db_array();
        $datetime = date('Y-m-d H:i:s');

        // Insert new record
        if (!$id) {
            $this->setTimestamps($db_array, $datetime, true);
            $this->db->insert($this->table, $db_array);
            return $this->db->insert_id();
        }

        // Update existing record
        $this->setTimestamps($db_array, $datetime, false);
        $this->db->where($this->primary_key, $id);
        $this->db->update($this->table, $db_array);

        return $id;
    }

    /**
     * Set created and modified timestamps (DRY principle)
     *
     * @param array|object $data Data array or object
     * @param string       $datetime Current datetime
     * @param bool         $is_insert Whether this is an insert operation
     */
    private function setTimestamps(&$data, $datetime, $is_insert)
    {
        $is_array = is_array($data);
        
        if ($is_insert && $this->date_created_field) {
            $is_array 
                ? $data[$this->date_created_field] = $datetime
                : $data->{$this->date_created_field} = $datetime;
        }
        
        if ($this->date_modified_field) {
            $is_array
                ? $data[$this->date_modified_field] = $datetime
                : $data->{$this->date_modified_field} = $datetime;
        }
    }

    /**
     * Build database array from POST data based on validation rules
     *
     * @return array
     */
    public function db_array()
    {
        $db_array = [];
        $validation_rules = $this->{$this->validation_rules}();

        foreach ($this->input->post() as $key => $value) {
            if (array_key_exists($key, $validation_rules)) {
                $db_array[$key] = $value;
            }
        }

        return $db_array;
    }

    /**
     * Delete record by ID
     *
     * @param int $id Record ID to delete
     */
    public function delete($id)
    {
        $this->db->where($this->primary_key, $id);
        $this->db->delete($this->table);
    }

    /**
     * Get query result as array of objects
     *
     * @return mixed
     */
    public function result()
    {
        return $this->query->result();
    }

    /**
     * Get single row as object
     *
     * @return mixed
     */
    public function row()
    {
        return $this->query->row();
    }

    /**
     * Get query result as array of arrays
     *
     * @return mixed
     */
    public function result_array()
    {
        return $this->query->result_array();
    }

    /**
     * Get single row as array
     *
     * @return mixed
     */
    public function row_array()
    {
        return $this->query->row_array();
    }

    /**
     * Get number of rows in result
     *
     * @return mixed
     */
    public function num_rows()
    {
        return $this->query->num_rows();
    }

    /**
     * Prepare form with existing record data
     *
     * @param int|null $id Record ID
     * @return bool|null
     */
    public function prep_form($id = null)
    {
        // Early return if POST data exists or no ID provided
        if ($_POST || !$id) {
            return !$id || null;
        }

        $row = $this->get_by_id($id);
        
        // Early return if no record found
        if (!$row) {
            return false;
        }

        foreach ($row as $key => $value) {
            $this->form_values[$key] = $value;
        }

        return true;
    }

    /**
     * Get single record by ID
     *
     * @param int $id Record ID
     * @return mixed
     */
    public function get_by_id($id)
    {
        return $this->where($this->primary_key, $id)->get()->row();
    }

    /**
     * Run form validation
     *
     * @param string|null $validation_rules Validation rules method name
     * @return mixed
     */
    public function run_validation($validation_rules = null)
    {
        $validation_rules = $validation_rules ?: $this->default_validation_rules;

        // Store POST values
        foreach (array_keys($_POST) as $key) {
            $this->form_values[$key] = $this->input->post($key);
        }

        // Early return if validation rules method doesn't exist
        if (!method_exists($this, $validation_rules)) {
            return null;
        }

        $this->validation_rules = $validation_rules;
        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->{$validation_rules}());
        
        $run = $this->form_validation->run();
        $this->validation_errors = validation_errors();

        return $run;
    }

    /**
     * Get form value by key
     *
     * @param string $key     Field key
     * @param bool   $escape  Whether to escape HTML
     * @return mixed|string
     */
    public function form_value($key, $escape = false)
    {
        $value = $this->form_values[$key] ?? '';
        return $escape ? htmlspecialchars($value) : $value;
    }

    /**
     * Set form value
     *
     * @param string $key   Field key
     * @param mixed  $value Field value
     */
    public function set_form_value($key, $value)
    {
        $this->form_values[$key] = $value;
    }

    /**
     * Set record ID
     *
     * @param int $id Record ID
     */
    public function set_id($id)
    {
        $this->id = $id;
    }

    /**
     * Apply default query builder methods
     *
     * @param array $exclude Methods to exclude
     */
    private function set_defaults($exclude = [])
    {
        $native_methods = $this->native_methods;

        // Remove excluded methods
        foreach ($exclude as $unset_method) {
            $key = array_search($unset_method, $native_methods, true);
            if ($key !== false) {
                unset($native_methods[$key]);
            }
        }

        // Call default_* methods if they exist
        foreach ($native_methods as $native_method) {
            $default_method = 'default_' . $native_method;
            if (method_exists($this, $default_method)) {
                $this->{$default_method}();
            }
        }
    }

    /**
     * Execute stored filters
     */
    private function run_filters()
    {
        foreach ($this->filter as $filter) {
            call_user_func_array([$this->db, $filter[0]], $filter[1]);
        }

        // Clear filters after running (single use)
        $this->filter = [];
    }
}
