<?php

namespace App\Libraries;

/**
 * Custom Field Service
 * Implements Single Responsibility Principle (SRP) - handles only custom field operations
 * Uses Dynamic Programming for memoization and caching
 * Follows Dependency Inversion Principle (DIP) through constructor injection
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
class CustomFieldService
{
    /**
     * Cache for custom fields by table name (Dynamic Programming)
     */
    private static array $customFieldsCache = [];

    /**
     * Cache for custom values by field ID (Dynamic Programming)
     */
    private static array $customValuesCache = [];

    /**
     * CodeIgniter instance
     */
    private object $ci;

    /**
     * Constructor - Dependency Injection
     */
    public function __construct()
    {
        $this->ci = &get_instance();

        // Ensure required models are loaded
        if (!isset($this->ci->customfields)) {
            $this->ci->load->model('custom_fields/mdl_custom_field');
        }

        if (!isset($this->ci->customvalues)) {
            $this->ci->load->model('custom_values/mdl_custom_value');
        }
    }

    /**
     * Get custom fields for a table with memoization
     * Dynamic Programming: Avoids redundant database queries
     *
     * @param string $tableName The table name
     * @return array Custom fields
     */
    public function getCustomFieldsByTable(string $tableName): array
    {
        // Early return if cached (Dynamic Programming)
        if (isset(self::$customFieldsCache[$tableName])) {
            return self::$customFieldsCache[$tableName];
        }

        $customFields = $this->ci->customfields
            ->by_table($tableName)
            ->get()
            ->result();

        // Cache the result
        self::$customFieldsCache[$tableName] = $customFields;

        return $customFields;
    }

    /**
     * Get custom values for a field ID with memoization
     * Dynamic Programming: Avoids redundant database queries
     *
     * @param int $fieldId The field ID
     * @return array Custom values
     */
    public function getCustomValuesByFieldId(int $fieldId): array
    {
        // Early return if cached (Dynamic Programming)
        if (isset(self::$customValuesCache[$fieldId])) {
            return self::$customValuesCache[$fieldId];
        }

        $values = $this->ci->customvalues
            ->get_by_fid($fieldId)
            ->result();

        // Cache the result
        self::$customValuesCache[$fieldId] = $values;

        return $values;
    }

    /**
     * Build custom values array for all fields
     * Optimized with memoization (Dynamic Programming)
     *
     * @param array $customFields Array of custom field objects
     * @return array Custom values indexed by field ID
     */
    public function buildCustomValuesArray(array $customFields): array
    {
        $customValues = [];
        $customValueFields = $this->ci->customvalues->custom_value_fields();

        foreach ($customFields as $customField) {
            // Early return if not a custom value field (Early Return principle)
            if (!in_array($customField->custom_field_type, $customValueFields)) {
                continue;
            }

            // Use memoized getter
            $customValues[$customField->custom_field_id] = $this->getCustomValuesByFieldId(
                $customField->custom_field_id
            );
        }

        return $customValues;
    }

    /**
     * Map custom field values to model form values
     * Extracted from duplicated code (DRY principle)
     *
     * @param object $model The model to set values on
     * @param array $customFields Array of custom field definitions
     * @param array $fieldValues Array of field value objects
     * @param string $fieldIdKey The key name for field ID in values
     * @param string $fieldValueKey The key name for field value in values
     * @return void
     */
    public function mapFieldValuesToModel(
        object $model,
        array $customFields,
        array $fieldValues,
        string $fieldIdKey,
        string $fieldValueKey
    ): void {
        // Early return if no custom fields (Early Return principle)
        if (empty($customFields)) {
            return;
        }

        // Build lookup map for O(n) complexity instead of O(n²)
        // Dynamic Programming: Trade space for time
        $fieldValueMap = [];
        foreach ($fieldValues as $fvalue) {
            $fieldValueMap[$fvalue->{$fieldIdKey}] = $fvalue->{$fieldValueKey};
        }

        // Map values to model
        foreach ($customFields as $cfield) {
            if (isset($fieldValueMap[$cfield->custom_field_id])) {
                $model->set_form_value(
                    'custom[' . $cfield->custom_field_id . ']',
                    $fieldValueMap[$cfield->custom_field_id]
                );
            }
        }
    }

    /**
     * Load complete custom field data for a document
     * DRY: Consolidates all custom field loading logic
     *
     * @param object $model The document model
     * @param object $customModel The custom fields model for this document type
     * @param int $documentId The document ID
     * @param string $tableName The custom table name
     * @param string $fieldIdKey The field ID key name
     * @param string $fieldValueKey The field value key name
     * @return array Array with 'custom_fields' and 'custom_values' keys
     */
    public function loadDocumentCustomFields(
        object $model,
        object $customModel,
        int $documentId,
        string $tableName,
        string $fieldIdKey,
        string $fieldValueKey
    ): array {
        // Get custom fields with memoization
        $customFields = $this->getCustomFieldsByTable($tableName);

        // Early return if no custom fields
        if (empty($customFields)) {
            return [
                'custom_fields' => [],
                'custom_values' => [],
            ];
        }

        // Get field values for this document
        $fieldValues = $customModel->by_id($documentId)->get()->result();

        // Build custom values array with memoization
        $customValues = $this->buildCustomValuesArray($customFields);

        // Map values to model
        $this->mapFieldValuesToModel($model, $customFields, $fieldValues, $fieldIdKey, $fieldValueKey);

        return [
            'custom_fields' => $customFields,
            'custom_values' => $customValues,
        ];
    }

    /**
     * Clear cache - useful for testing or when data changes
     * Follows good cache management practices
     */
    public static function clearCache(): void
    {
        self::$customFieldsCache = [];
        self::$customValuesCache = [];
    }
}
