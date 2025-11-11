<?php

namespace App\Core;

/**
 * Abstract base class for document controllers (Invoices, Quotes, etc.)
 * Implements common functionality to reduce code duplication (DRY principle)
 * Follows Single Responsibility Principle (SRP) by separating concerns
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
abstract class DocumentController extends AdminController
{
    /**
     * Get the document type (invoice, quote, etc.)
     */
    abstract protected function getDocumentType(): string;

    /**
     * Get the custom table name for this document type
     */
    abstract protected function getCustomTableName(): string;

    /**
     * Get the ID field name for custom fields
     */
    abstract protected function getCustomFieldIdName(): string;

    /**
     * Get the value field name for custom fields
     */
    abstract protected function getCustomValueFieldName(): string;

    /**
     * Load custom field values for a document
     * Implements memoization (Dynamic Programming) to avoid redundant queries
     *
     * @param object $model The document model
     * @param int $documentId The document ID
     * @return array Custom fields and values
     */
    protected function loadCustomFields(object $model, int $documentId): array
    {
        // Early return if no custom fields exist (Early Return principle)
        $customTableName = $this->getCustomTableName();
        $customFields = $this->customfields->by_table($customTableName)->get()->result();

        if (empty($customFields)) {
            return [
                'custom_fields' => [],
                'custom_values' => [],
            ];
        }

        $fields = $this->getCustomModel()->by_id($documentId)->get()->result();
        $customValues = $this->buildCustomValuesArray($customFields);

        $this->mapCustomFieldValues($model, $customFields, $fields);

        return [
            'custom_fields' => $customFields,
            'custom_values' => $customValues,
        ];
    }

    /**
     * Build custom values array with memoization
     * Dynamic Programming: Cache values to avoid repeated queries
     */
    protected function buildCustomValuesArray(array $customFields): array
    {
        static $customValuesCache = [];

        $customValues = [];
        foreach ($customFields as $customField) {
            // Early return if not a custom value field
            if (!in_array($customField->custom_field_type, $this->customvalues->custom_value_fields())) {
                continue;
            }

            $cacheKey = $customField->custom_field_id;

            // Dynamic Programming: Use cached value if available
            if (!isset($customValuesCache[$cacheKey])) {
                $customValuesCache[$cacheKey] = $this->customvalues
                    ->get_by_fid($customField->custom_field_id)
                    ->result();
            }

            $customValues[$customField->custom_field_id] = $customValuesCache[$cacheKey];
        }

        return $customValues;
    }

    /**
     * Map custom field values to model form values
     * Extracted from duplicated code in view() methods (DRY principle)
     */
    protected function mapCustomFieldValues(object $model, array $customFields, array $fields): void
    {
        $customFieldIdName = $this->getCustomFieldIdName();
        $customValueFieldName = $this->getCustomValueFieldName();

        foreach ($customFields as $cfield) {
            foreach ($fields as $fvalue) {
                if ($fvalue->{$customFieldIdName} == $cfield->custom_field_id) {
                    $model->set_form_value(
                        'custom[' . $cfield->custom_field_id . ']',
                        $fvalue->{$customValueFieldName}
                    );
                    break; // Early return from inner loop
                }
            }
        }
    }

    /**
     * Check if multiple admin users exist
     * Extracted common logic with memoization (DRY + Dynamic Programming)
     */
    protected function hasMultipleAdminUsers(): bool
    {
        static $multipleAdmins = null;

        // Dynamic Programming: Return cached result if available
        if ($multipleAdmins !== null) {
            return $multipleAdmins;
        }

        $result = $this->db
            ->from('ip_users')
            ->where(['user_type' => 1, 'user_active' => 1])
            ->select_sum('user_type')
            ->get()
            ->row();

        $multipleAdmins = ($result->user_type ?? 0) > 1;

        return $multipleAdmins;
    }

    /**
     * Get the custom model instance
     * Template method pattern - subclasses must implement
     */
    abstract protected function getCustomModel(): object;

    /**
     * Load common models required for document viewing
     * Follows Interface Segregation Principle (ISP) - only load what's needed
     */
    protected function loadDocumentViewModels(): void
    {
        $this->load->model([
            'tax_rates/mdl_tax_rate',
            'units/mdl_units',
            'custom_fields/mdl_custom_field',
            'custom_values/mdl_custom_value',
            'upload/mdl_uploads',
        ]);

        $this->load->helper(['custom_values', 'dropzone', 'e-invoice']);
    }

    /**
     * Build common view data for documents
     * Reduces duplication and follows SRP
     */
    protected function buildCommonViewData(object $document, array $items): array
    {
        return [
            'einvoice' => get_einvoice_usage($document, $items),
            'change_user' => $this->hasMultipleAdminUsers(),
            'units' => $this->unit->get()->result(),
            'tax_rates' => $this->taxrates->get()->result(),
            'custom_js_vars' => [
                'currency_symbol' => get_setting('currency_symbol'),
                'currency_symbol_placement' => get_setting('currency_symbol_placement'),
                'decimal_point' => get_setting('decimal_point'),
            ],
            'legacy_calculation' => config_item('legacy_calculation'),
        ];
    }
}
