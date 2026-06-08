<?php
namespace App\Models\Discounts;

use App\Support\Logger;

/**
 * Factory para resolver la estrategia de descuento correcta basada en el tipo.
 */
class DiscountStrategyFactory
{
    private static array $strategies = [
        'percentage' => PercentageStrategy::class,
        'fixed_amount' => FixedAmountStrategy::class,
        'bogo' => BogoStrategy::class,
        'first_purchase' => FirstPurchaseStrategy::class,
        'free_delivery' => FreeDeliveryStrategy::class,
    ];

    public static function resolve(string $type): DiscountStrategy
    {
        if (!isset(self::$strategies[$type])) {
            app(\App\Support\Logger::class)->log("Unknown discount type '{$type}'", 'warning');
            throw new \InvalidArgumentException("Unknown discount type: {$type}");
        }

        return new self::$strategies[$type]();
    }
}
