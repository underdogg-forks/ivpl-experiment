<?php

namespace App\Libraries;

/**
 * Settings Cache Service
 * Implements Dynamic Programming through memoization of settings
 * Reduces database queries by caching settings within request lifecycle
 * Follows Single Responsibility Principle (SRP)
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
class SettingsCache
{
    /**
     * Cache for settings values (Dynamic Programming)
     */
    private static array $cache = [];

    /**
     * Get a setting value with memoization
     * Dynamic Programming: Avoids repeated database queries
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value
     */
    public static function get(string $key, $default = null)
    {
        // Early return if cached (Dynamic Programming)
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // Get setting value using existing helper
        $value = get_setting($key);

        // Use default if value is null or empty string
        if ($value === null || $value === '') {
            $value = $default;
        }

        // Cache the result
        self::$cache[$key] = $value;

        return $value;
    }

    /**
     * Get multiple settings at once
     * More efficient than multiple individual calls
     * Dynamic Programming: Batch loading with memoization
     *
     * @param array $keys Array of setting keys
     * @return array Associative array of settings
     */
    public static function getMultiple(array $keys): array
    {
        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = self::get($key);
        }

        return $settings;
    }

    /**
     * Get currency settings bundle
     * DRY: Commonly used together, fetch as a unit
     *
     * @return array Currency settings
     */
    public static function getCurrencySettings(): array
    {
        static $currencySettings = null;

        // Early return if already loaded (Dynamic Programming)
        if ($currencySettings !== null) {
            return $currencySettings;
        }

        $currencySettings = [
            'symbol' => self::get('currency_symbol', '$'),
            'placement' => self::get('currency_symbol_placement', 'before'),
            'decimal_point' => self::get('decimal_point', '.'),
        ];

        return $currencySettings;
    }

    /**
     * Check if a boolean setting is enabled
     * Simplifies boolean checks with caching
     *
     * @param string $key The setting key
     * @return bool Whether setting is enabled
     */
    public static function isEnabled(string $key): bool
    {
        $value = self::get($key);

        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * Set a value in cache (without persisting to database)
     * Useful for temporary overrides within request
     *
     * @param string $key The setting key
     * @param mixed $value The value to cache
     * @return void
     */
    public static function set(string $key, $value): void
    {
        self::$cache[$key] = $value;
    }

    /**
     * Clear the cache
     * Useful for testing or when settings change
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
    }

    /**
     * Warm up cache with commonly used settings
     * Dynamic Programming: Preload frequently accessed values
     * Call this early in request lifecycle for better performance
     *
     * @return void
     */
    public static function warmUp(): void
    {
        $commonSettings = [
            'currency_symbol',
            'currency_symbol_placement',
            'decimal_point',
            'einvoicing',
            'mark_quotes_sent_pdf',
            'legacy_calculation',
        ];

        self::getMultiple($commonSettings);
    }
}
