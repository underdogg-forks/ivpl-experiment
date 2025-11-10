#!/usr/bin/env php
<?php

/**
 * Update Model References Script
 * 
 * This script updates all $this->load->model() calls to use new PSR-4 model names
 * and updates all model property references
 */

class ModelReferenceUpdater
{
    private string $basePath;
    private array $log = [];
    
    // Mapping of old model names to new PSR-4 class names
    private array $modelMapping = [
        'mdl_clients' => ['Clients', 'Clients'],
        'mdl_client_notes' => ['ClientNotes', 'Clients'],
        'mdl_custom_fields' => ['CustomFields', 'Custom_fields'],
        'mdl_client_custom' => ['ClientCustom', 'Custom_fields'],
        'mdl_invoice_custom' => ['InvoiceCustom', 'Custom_fields'],
        'mdl_payment_custom' => ['PaymentCustom', 'Custom_fields'],
        'mdl_quote_custom' => ['QuoteCustom', 'Custom_fields'],
        'mdl_user_custom' => ['UserCustom', 'Custom_fields'],
        'mdl_custom_values' => ['CustomValues', 'Custom_values'],
        'mdl_email_templates' => ['EmailTemplates', 'Email_templates'],
        'mdl_families' => ['Families', 'Families'],
        'mdl_import' => ['Import', 'Import'],
        'mdl_invoice_groups' => ['InvoiceGroups', 'Invoice_groups'],
        'mdl_invoices' => ['Invoices', 'Invoices'],
        'mdl_invoice_amounts' => ['InvoiceAmounts', 'Invoices'],
        'mdl_invoice_sumex' => ['InvoiceSumex', 'Invoices'],
        'mdl_invoice_tax_rates' => ['InvoiceTaxRates', 'Invoices'],
        'mdl_invoices_recurring' => ['InvoicesRecurring', 'Invoices'],
        'mdl_item_amounts' => ['ItemAmounts', 'Invoices'],
        'mdl_items' => ['Items', 'Invoices'],
        'mdl_templates' => ['Templates', 'Invoices'],
        'mdl_payment_methods' => ['PaymentMethods', 'Payment_methods'],
        'mdl_payments' => ['Payments', 'Payments'],
        'mdl_payment_logs' => ['PaymentLogs', 'Payments'],
        'mdl_products' => ['Products', 'Products'],
        'mdl_projects' => ['Projects', 'Projects'],
        'mdl_quotes' => ['Quotes', 'Quotes'],
        'mdl_quote_amounts' => ['QuoteAmounts', 'Quotes'],
        'mdl_quote_item_amounts' => ['QuoteItemAmounts', 'Quotes'],
        'mdl_quote_items' => ['QuoteItems', 'Quotes'],
        'mdl_quote_tax_rates' => ['QuoteTaxRates', 'Quotes'],
        'mdl_reports' => ['Reports', 'Reports'],
        'mdl_sessions' => ['Sessions', 'Sessions'],
        'mdl_settings' => ['Settings', 'Settings'],
        'mdl_versions' => ['Versions', 'Settings'],
        'mdl_setup' => ['Setup', 'Setup'],
        'mdl_tasks' => ['Tasks', 'Tasks'],
        'mdl_tax_rates' => ['TaxRates', 'Tax_rates'],
        'mdl_units' => ['Units', 'Units'],
        'mdl_uploads' => ['Uploads', 'Upload'],
        'mdl_user_clients' => ['UserClients', 'User_clients'],
        'mdl_users' => ['Users', 'Users'],
    ];
    
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }
    
    public function update(): void
    {
        echo "=== Model Reference Updater ===\n\n";
        
        // Update controllers
        echo "Updating controller files...\n";
        $this->updateControllers();
        
        echo "\n=== Update Summary ===\n";
        echo "Total operations: " . count($this->log) . "\n";
        
        file_put_contents('model_update_log.txt', implode("\n", $this->log));
    }
    
    private function updateControllers(): void
    {
        $controllersPath = $this->basePath . '/application/modules/*/Controllers/*.php';
        $files = glob($controllersPath);
        
        foreach ($files as $file) {
            $this->updateFile($file);
        }
    }
    
    private function updateFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Update $this->load->model() calls
        foreach ($this->modelMapping as $oldName => $newInfo) {
            list($newClass, $module) = $newInfo;
            $lowerNewClass = strtolower($newClass);
            
            // Update load->model calls - use lowercase for the alias
            $content = preg_replace(
                "/\\\$this->load->model\('$oldName'\)/",
                "\$this->load->model('" . strtolower($module) . "/" . $lowerNewClass . "')",
                $content
            );
            
            // Also handle module-prefixed loads
            $content = preg_replace(
                "/\\\$this->load->model\('[^']*\/$oldName'\)/",
                "\$this->load->model('" . strtolower($module) . "/" . $lowerNewClass . "')",
                $content
            );
        }
        
        // Update property references from $this->mdl_* to $this->*
        foreach ($this->modelMapping as $oldName => $newInfo) {
            list($newClass, $module) = $newInfo;
            $lowerNewClass = strtolower($newClass);
            
            // Update $this->mdl_something to $this->something
            $content = preg_replace(
                "/\\\$this->$oldName/",
                "\$this->" . $lowerNewClass,
                $content
            );
        }
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->log("Updated: " . basename(dirname($filePath)) . '/' . basename($filePath));
        }
    }
    
    private function log(string $message): void
    {
        $this->log[] = $message;
        echo "  ✓ $message\n";
    }
}

// Run updater
$updater = new ModelReferenceUpdater(__DIR__);
$updater->update();

echo "\nModel references updated!\n";
