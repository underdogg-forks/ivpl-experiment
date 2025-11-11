<?php

namespace App\Libraries;

/**
 * Invoice Status Filter Strategy
 * Implements Strategy Pattern for invoice status filtering
 * Follows Open/Closed Principle and Single Responsibility Principle
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
class InvoiceStatusFilterStrategy implements StatusFilterStrategyInterface
{
    /**
     * Status method mapping for Dynamic Programming optimization
     * Avoids repeated string operations
     */
    private const STATUS_METHODS = [
        'draft' => 'is_draft',
        'sent' => 'is_sent',
        'viewed' => 'is_viewed',
        'paid' => 'is_paid',
        'overdue' => 'is_overdue',
    ];

    /**
     * Apply status filter to invoice model
     * Replaces switch statement with strategy pattern (OCP)
     *
     * @param object $model The invoice model
     * @param string $status The status to filter by
     * @return void
     */
    public function apply(object $model, string $status): void
    {
        // Early return if status is 'all' (Early Return principle)
        if ($status === 'all') {
            return;
        }

        // Early return if status method doesn't exist
        if (!isset(self::STATUS_METHODS[$status])) {
            return;
        }

        $method = self::STATUS_METHODS[$status];

        // Call the appropriate method dynamically
        if (method_exists($model, $method)) {
            $model->{$method}();
        }
    }

    /**
     * Get available statuses
     * Memoized for performance (Dynamic Programming)
     */
    public function getAvailableStatuses(): array
    {
        static $statuses = null;

        if ($statuses === null) {
            $statuses = array_keys(self::STATUS_METHODS);
        }

        return $statuses;
    }
}
