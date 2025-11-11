<?php

namespace App\Libraries;

/**
 * Status Filter Strategy Interface
 * Implements Open/Closed Principle - open for extension, closed for modification
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
interface StatusFilterStrategyInterface
{
    /**
     * Apply status filter to a model
     *
     * @param object $model The model to filter
     * @param string $status The status to filter by
     * @return void
     */
    public function apply(object $model, string $status): void;
}
