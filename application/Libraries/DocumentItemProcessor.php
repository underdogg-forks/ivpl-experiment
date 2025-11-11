<?php

namespace App\Libraries;

/**
 * Document Item Processor
 * Implements Dynamic Programming for optimized item calculations
 * Follows Single Responsibility Principle (SRP) - handles only item processing
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
class DocumentItemProcessor
{
    /**
     * Cache for unit names (Dynamic Programming)
     */
    private static array $unitNamesCache = [];

    /**
     * Cache for standardized amounts (Dynamic Programming)
     */
    private static array $standardizedAmountsCache = [];

    /**
     * CodeIgniter instance
     */
    private object $ci;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Standardize amount with memoization
     * Dynamic Programming: Cache results for identical input values
     *
     * @param mixed $amount The amount to standardize
     * @return float Standardized amount
     */
    public function standardizeAmount($amount): float
    {
        // Early return for null/empty (Early Return principle)
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        // Convert to string for cache key
        $cacheKey = (string) $amount;

        // Return cached value if available (Dynamic Programming)
        if (isset(self::$standardizedAmountsCache[$cacheKey])) {
            return self::$standardizedAmountsCache[$cacheKey];
        }

        // Standardize and cache
        $standardized = standardize_amount($amount);
        self::$standardizedAmountsCache[$cacheKey] = $standardized;

        return $standardized;
    }

    /**
     * Get unit name with memoization
     * Dynamic Programming: Cache unit name lookups
     *
     * @param int|null $unitId The unit ID
     * @param float $quantity The quantity
     * @return string Unit name
     */
    public function getUnitName(?int $unitId, float $quantity): string
    {
        // Early return for null unit ID (Early Return principle)
        if ($unitId === null) {
            return '';
        }

        $cacheKey = $unitId . '_' . $quantity;

        // Return cached value if available (Dynamic Programming)
        if (isset(self::$unitNamesCache[$cacheKey])) {
            return self::$unitNamesCache[$cacheKey];
        }

        // Ensure unit model is loaded
        if (!isset($this->ci->unit)) {
            $this->ci->load->model('units/mdl_units');
        }

        $unitName = $this->ci->unit->get_name($unitId, $quantity);
        self::$unitNamesCache[$cacheKey] = $unitName;

        return $unitName;
    }

    /**
     * Process item data with standardization
     * DRY: Consolidates common item processing logic
     *
     * @param object $item The item object to process
     * @return object Processed item
     */
    public function processItemData(object $item): object
    {
        // Standardize numeric fields with memoization
        $item->item_quantity = $this->standardizeAmount($item->item_quantity ?? 0);
        $item->item_price = $this->standardizeAmount($item->item_price ?? 0);
        $item->item_discount_amount = $item->item_discount_amount
            ? $this->standardizeAmount($item->item_discount_amount)
            : null;

        // Set product fields with null coalescing
        $item->item_product_id = $item->item_product_id ?? null;
        $item->item_product_unit_id = $item->item_product_unit_id ?? null;

        // Get unit name with memoization
        $item->item_product_unit = $this->getUnitName(
            $item->item_product_unit_id,
            $item->item_quantity
        );

        return $item;
    }

    /**
     * Calculate items subtotal with early optimization
     * Dynamic Programming: Optimized calculation
     *
     * @param array $items Array of items
     * @return float Subtotal amount
     */
    public function calculateItemsSubtotal(array $items): float
    {
        // Early return for empty items (Early Return principle)
        if (empty($items)) {
            return 0.0;
        }

        $subtotal = 0.0;

        foreach ($items as $item) {
            // Early continue if no item name (Early Return principle)
            if (empty($item->item_name)) {
                continue;
            }

            $quantity = $this->standardizeAmount($item->item_quantity ?? 0);
            $price = $this->standardizeAmount($item->item_price ?? 0);

            $subtotal += $quantity * $price;
        }

        return $subtotal;
    }

    /**
     * Build global discount array
     * DRY: Extracted from duplicated code in Invoice and Quote Ajax controllers
     *
     * @param float $discountPercent Discount percentage
     * @param float $discountAmount Discount amount
     * @param float $itemsSubtotal Items subtotal (for amount-based discounts)
     * @return array Global discount configuration
     */
    public function buildGlobalDiscount(
        float $discountPercent,
        float $discountAmount,
        float $itemsSubtotal
    ): array {
        // Standardize inputs with memoization
        $amount = $this->standardizeAmount($discountAmount);
        $percent = $this->standardizeAmount($discountPercent);

        return [
            'amount' => $amount,
            'percent' => $percent,
            'item' => 0.0, // Updated by reference in amount calculations
            'items_subtotal' => $itemsSubtotal,
        ];
    }

    /**
     * Validate and normalize discount inputs
     * Prevents multiple discount types (business rule enforcement)
     * Early Return: Returns normalized values immediately
     *
     * @param float $discountPercent Discount percentage
     * @param float $discountAmount Discount amount
     * @return array Normalized discount values
     */
    public function normalizeDiscounts(float $discountPercent, float $discountAmount): array
    {
        // Business rule: Only one discount type allowed
        // If both are set, percent takes precedence (set amount to 0)
        if ($discountPercent && $discountAmount) {
            $discountAmount = 0.0;
        }

        return [
            'percent' => $discountPercent,
            'amount' => $discountAmount,
        ];
    }

    /**
     * Clear memoization caches
     * Useful for testing or long-running processes
     */
    public static function clearCache(): void
    {
        self::$unitNamesCache = [];
        self::$standardizedAmountsCache = [];
    }
}
