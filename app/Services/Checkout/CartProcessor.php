<?php

namespace App\Services\Checkout;

use App\Models\Product;
use App\Models\ProductBundle;
use App\Orders\OrderItemInterface;
use App\Orders\SimpleItem;
use App\Orders\ComboItem;

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

    /**
     * COMPOSITE PATTERN — versión refactorizada de processItems().
     *
     * Retorna un array de OrderItemInterface en lugar de arrays crudos.
     * Tanto productos simples (SimpleItem) como bundles (ComboItem) se
     * tratan de forma uniforme — sin if/elseif de tipo en el llamador.
     *
     * @param  array  $items  Array de ['type' => ..., 'id' => ..., 'quantity' => ...]
     * @return OrderItemInterface[]
     */
    public function processItemsAsComposite(array $items): array
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

                $processed[] = new SimpleItem(
                    $product->name,
                    (float) $product->price,
                    (int) $item['quantity'],
                );

            } elseif ($item['type'] === 'bundle') {
                $bundle = ProductBundle::find($item['id']);

                if (!$bundle || !$bundle->available) {
                    throw new \Exception("Bundle {$item['id']} is not available.");
                }

                // Construir ComboItem con sus productos como SimpleItems
                $combo = new ComboItem(
                    $bundle->name,
                    (int) $item['quantity'],
                    (float) $bundle->discount_percentage,
                );

                $bundleProducts = $bundle->products()->withPivot('quantity')->get();
                foreach ($bundleProducts as $product) {
                    $combo->add(new SimpleItem(
                        $product->name,
                        (float) $product->price,
                        (int) $product->pivot->quantity,
                    ));
                }

                // Bundles hijos anidados
                $childBundles = $bundle->childBundles()->withPivot('quantity')->get();
                foreach ($childBundles as $childBundle) {
                    $innerCombo = new ComboItem(
                        $childBundle->name,
                        (int) $childBundle->pivot->quantity,
                        (float) $childBundle->discount_percentage,
                    );

                    $childProducts = $childBundle->products()->withPivot('quantity')->get();
                    foreach ($childProducts as $childProduct) {
                        $innerCombo->add(new SimpleItem(
                            $childProduct->name,
                            (float) $childProduct->price,
                            (int) $childProduct->pivot->quantity,
                        ));
                    }

                    $combo->add($innerCombo);
                }

                $processed[] = $combo;
            }
        }

        return $processed;
    }
}
