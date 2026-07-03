<?php

namespace App\Services\Checkout;

use App\Models\Product;
use App\Models\ProductBundle;

/**
 * Procesa los items del carrito y valida disponibilidad
 */
class CartProcessor
{
    public function processItems(array $items): array
    {
        $processed = [];

        foreach ($items as $item) {
            if ($item['type'] === 'product') {
                $product = Product::find($item['id']);
                
                if (!$product || !$product->available) {
                    throw new \Exception("Product {$item['id']} is not available.");
                }
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}.");
                }

                $processed[] = [
                    'type'       => 'product',
                    'id'         => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $product->price * $item['quantity'],
                ];

            } elseif ($item['type'] === 'bundle') {
                $bundle = ProductBundle::find($item['id']);
                
                if (!$bundle || !$bundle->available) {
                    throw new \Exception("Bundle {$item['id']} is not available.");
                }

                $processed[] = [
                    'type'       => 'bundle',
                    'id'         => $bundle->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $bundle->getTotalPrice(),
                    'subtotal'   => $bundle->getTotalPrice() * $item['quantity'],
                ];
            }
        }

        return $processed;
    }
}
