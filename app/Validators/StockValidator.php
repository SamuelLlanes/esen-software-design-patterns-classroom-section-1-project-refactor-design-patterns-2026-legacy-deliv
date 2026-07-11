<?php

namespace App\Validators;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBundle;

class StockValidator extends OrderValidationHandler
{
    public function handle(Order $order): void
    {
        if (!$order->items || $order->items->isEmpty()) {
            throw new \Exception('Order has no items.');
        }

        foreach ($order->items as $item) {
            if ($item->item_type === 'product') {
                $product = Product::find($item->item_id);

                if (!$product) {
                    throw new \Exception("Product ID {$item->item_id} no longer exists.");
                }

                if (!$product->available) {
                    throw new \Exception("Product '{$product->name}' is no longer available.");
                }

                if ($product->stock < $item->quantity) {
                    throw new \Exception(
                        "Insufficient stock for '{$product->name}': " .
                        "{$product->stock} available, {$item->quantity} requested."
                    );
                }

            } elseif ($item->item_type === 'bundle') {
                $bundle = ProductBundle::find($item->item_id);

                if (!$bundle) {
                    throw new \Exception("Bundle ID {$item->item_id} no longer exists.");
                }

                if (!$bundle->available) {
                    throw new \Exception("Bundle '{$bundle->name}' is not available.");
                }
            }
        }

        $this->next($order);
    }
}
