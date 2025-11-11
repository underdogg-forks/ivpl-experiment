<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// load the MX_Loader class
require APPPATH . 'third_party/MX/Loader.php';

#[AllowDynamicProperties]
class MY_Loader extends MX_Loader
{
    /**
     * Load model with backward compatibility for mdl_ prefix and plural forms
     * 
     * This method extends MX_Loader::model() to provide backward compatibility
     * for legacy code that accesses models using the old naming convention:
     * - mdl_ prefix (e.g., $this->mdl_clients)
     * - plural forms (e.g., $this->clients instead of $this->client)
     * 
     * @param mixed  $model       Model name or array of models
     * @param string $object_name Alias for the model object
     * @param bool   $connect     Database connection
     * @return $this
     */
    public function model($model, $object_name = null, $connect = false)
    {
        if (is_array($model)) {
            foreach ($model as $m) {
                $this->model($m, null, $connect);
            }
            return $this;
        }

        // Extract model name from path (e.g., 'clients/client' => 'client')
        $model_basename = basename($model);
        
        // Determine the alias to use
        $_alias = $object_name ?: $model_basename;
        
        // Call parent to load the model
        parent::model($model, $object_name, $connect);
        
        // Create backward compatibility aliases for legacy mdl_ usage
        $this->createLegacyAliases($_alias, $model);
        
        return $this;
    }
    
    /**
     * Create backward compatibility aliases for legacy code
     * 
     * This creates additional property references so old code continues to work:
     * - mdl_{singular} => model (e.g., $this->mdl_client => $this->client)
     * - mdl_{plural} => model (e.g., $this->mdl_clients => $this->client)
     * - {plural} => model (e.g., $this->clients => $this->client)
     * 
     * @param string $alias Current model alias
     * @param string $model_path Full model path
     */
    private function createLegacyAliases($alias, $model_path)
    {
        // Get module from path if present (e.g., 'clients/client' => 'clients')
        $parts = explode('/', $model_path);
        $module = count($parts) > 1 ? $parts[0] : null;
        
        // Map of singular to plural forms for common models
        $pluralMap = [
            'client' => 'clients',
            'invoice' => 'invoices',
            'quote' => 'quotes',
            'product' => 'products',
            'user' => 'users',
            'payment' => 'payments',
            'task' => 'tasks',
            'project' => 'projects',
            'item' => 'items',
            'setting' => 'settings',
            'version' => 'versions',
            'upload' => 'uploads',
            'custom_field' => 'custom_fields',
            'custom_value' => 'custom_values',
            'email_template' => 'email_templates',
            'client_note' => 'client_notes',
            'invoice_group' => 'invoice_groups',
            'invoice_amount' => 'invoice_amounts',
            'invoice_tax_rate' => 'invoice_tax_rates',
            'invoice_recurring' => 'invoices_recurring',
            'item_amount' => 'item_amounts',
            'payment_log' => 'payment_logs',
            'payment_method' => 'payment_methods',
            'quote_amount' => 'quote_amounts',
            'quote_item' => 'quote_items',
            'quote_item_amount' => 'quote_item_amounts',
            'quote_tax_rate' => 'quote_tax_rates',
            'tax_rate' => 'tax_rates',
            'template' => 'templates',
            'user_client' => 'user_clients',
        ];
        
        // Get the actual model instance
        if (!isset(CI::$APP->{$alias})) {
            return; // Model not loaded, skip
        }
        
        $modelInstance = CI::$APP->{$alias};
        
        // Create mdl_{singular} alias (e.g., mdl_client)
        $mdl_singular = 'mdl_' . $alias;
        if (!isset(CI::$APP->{$mdl_singular})) {
            CI::$APP->{$mdl_singular} = $modelInstance;
            $this->logDeprecation($mdl_singular, $alias);
        }
        
        // Create mdl_{plural} alias if plural form exists (e.g., mdl_clients)
        if (isset($pluralMap[$alias])) {
            $plural = $pluralMap[$alias];
            $mdl_plural = 'mdl_' . $plural;
            
            if (!isset(CI::$APP->{$mdl_plural})) {
                CI::$APP->{$mdl_plural} = $modelInstance;
                $this->logDeprecation($mdl_plural, $alias);
            }
            
            // Also create non-mdl plural alias (e.g., clients)
            if (!isset(CI::$APP->{$plural})) {
                CI::$APP->{$plural} = $modelInstance;
                $this->logDeprecation($plural, $alias);
            }
        }
    }
    
    /**
     * Log deprecation warning for legacy model access
     * 
     * @param string $old_name Legacy property name
     * @param string $new_name New property name
     */
    private function logDeprecation($old_name, $new_name)
    {
        if (ENVIRONMENT === 'development') {
            log_message('debug', sprintf(
                'DEPRECATED: Accessing model as $this->%s is deprecated. Use $this->%s instead. ' .
                'Called from %s',
                $old_name,
                $new_name,
                $this->getCallerInfo()
            ));
        }
    }
    
    /**
     * Get information about who called the model loader
     * 
     * @return string Caller file and line
     */
    private function getCallerInfo()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        
        // Find the first caller outside of this class
        foreach ($trace as $frame) {
            if (isset($frame['file']) && 
                strpos($frame['file'], 'MY_Loader.php') === false &&
                strpos($frame['file'], 'MX/Loader.php') === false) {
                return sprintf('%s:%d', basename($frame['file']), $frame['line'] ?? 0);
            }
        }
        
        return 'unknown';
    }
}
