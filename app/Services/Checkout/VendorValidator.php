<?php

namespace App\Services\Checkout;

use App\Models\Vendor;

/**
 * Valida que el vendor esté disponible y sus horarios
 */
class VendorValidator
{
    public function validate(int $vendorId): Vendor
    {
        $vendor = Vendor::find($vendorId);
        
        if (!$vendor || $vendor->status !== 'active') {
            throw new \Exception('Vendor is not available.');
        }

        // Validar horarios de atención
        $openingHours = $vendor->opening_hours ?? [];
        if (!empty($openingHours)) {
            $now = now();
            $dayKey = strtolower($now->format('l'));
            
            if (isset($openingHours[$dayKey])) {
                $hours = $openingHours[$dayKey];
                if ($hours['closed'] ?? false) {
                    throw new \Exception('Vendor is currently closed.');
                }
            }
        }

        return $vendor;
    }
}
