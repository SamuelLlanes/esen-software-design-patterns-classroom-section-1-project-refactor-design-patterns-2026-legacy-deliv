<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['customer_id', 'vendor_id', 'courier_id', 'status',
                           'subtotal', 'discount_total', 'delivery_fee', 'total',
                           'delivery_address', 'notes'];

    public function customer()   { return $this->belongsTo(Customer::class); }
    public function vendor()     { return $this->belongsTo(Vendor::class); }
    public function courier()    { return $this->belongsTo(Courier::class); }
    public function items()      { return $this->hasMany(OrderItem::class); }
    public function discounts()  { return $this->belongsToMany(Discount::class, 'order_discounts'); }
    public function payment()    { return $this->hasOne(Payment::class); }
    public function notifications() { return $this->hasMany(Notification::class, 'recipient_id')
        ->where('recipient_type', 'customer'); }

    public function transitionTo(string $newStatus): void
    {
        $currentState = $this->getState();

        if (!$currentState->canTransitionTo($newStatus)) {
            throw new \Exception("Cannot transition from '{$this->status}' to '{$newStatus}'.");
        }

        $this->status = $newStatus;
        // Persist here to avoid bugs when callers forget to save after transition.
        $this->save();
    }

    /**
     * Return an OrderState instance representing the current status.
     */
    public function getState(): \App\Models\OrderState
    {
        switch ($this->status) {
            case 'created':
                return new \App\Models\States\PendingState();
            case 'paid':
                return new \App\Models\States\PaidState();
            case 'delivered':
                return new \App\Models\States\DeliveredState();
            case 'cancelled':
                return new \App\Models\States\CancelledState();
            default:
                throw new \Exception("Unknown current status: {$this->status}");
        }
    }

    /** Execute a command against this order (Command pattern). */
    public function executeCommand(\App\Models\Commands\OrderCommand $command): void
    {
        $command->execute($this);
    }

    public function validateOrder(): bool
    {
        // Validar customer
        if (!$this->customer) {
            throw new \Exception('Customer not found.');
        }
        if (!$this->customer->verified) {
            throw new \Exception('Customer account is not verified.');
        }

        // Validar vendor
        if (!$this->vendor) {
            throw new \Exception('Vendor not found.');
        }
        if ($this->vendor->status !== 'active') {
            throw new \Exception('Vendor is not currently active.');
        }

        // Validar items
        if (!$this->items || $this->items->isEmpty()) {
            throw new \Exception('Order has no items.');
        }

        foreach ($this->items as $item) {
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
                        "Insufficient stock for '{$product->name}': {$product->stock} available, {$item->quantity} requested."
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

        // Validar dirección de entrega
        if (empty($this->delivery_address)) {
            throw new \Exception('Delivery address is required.');
        }
        if (strlen($this->delivery_address) < 10) {
            throw new \Exception('Delivery address is too short to be valid.');
        }

        // Validar montos
        if ($this->subtotal <= 0) {
            throw new \Exception('Order subtotal must be greater than zero.');
        }
        if ($this->total < 0) {
            throw new \Exception('Order total cannot be negative.');
        }
        if ($this->subtotal < 5.00) {
            throw new \Exception('Minimum order amount is $5.00.');
        }

        // Validar payment (si existe)
        if ($this->payment && $this->payment->status === 'failed') {
            throw new \Exception('Associated payment has failed.');
        }

        // Validar descuentos aplicados
        foreach ($this->discounts as $discount) {
            if (now() > $discount->valid_to) {
                throw new \Exception("Discount '{$discount->code}' has expired.");
            }
            if ($discount->vendor_id && $discount->vendor_id !== $this->vendor_id) {
                throw new \Exception("Discount '{$discount->code}' is not valid for this vendor.");
            }
        }

        app(\App\Support\Logger::class)->log("Order {$this->id} validated successfully.");
        return true;
    }

    public function notify(string $event): void
    {
        app(\App\Services\Notifications\OrderNotificationDispatcher::class)
            ->dispatch($this, $event);

        $this->dispatchSideEffects($event);
    }

    public function dispatchSideEffects(string $event): void
    {
        $inventoryService = new \App\Services\InventoryService();
        $auditService     = new \App\Services\AuditService();
        $metricsService   = new \App\Services\MetricsService();

        if ($event === 'created') {
            $inventoryService->reserveStock($this);
            $auditService->log('order.created', $this->id, $this->toArray());
            $metricsService->increment('orders.created');

        } elseif ($event === 'paid') {
            $auditService->log('order.paid', $this->id, ['amount' => $this->total]);
            $metricsService->increment('orders.paid');
            $metricsService->gauge('revenue', $this->total);

        } elseif ($event === 'accepted') {
            $auditService->log('order.accepted', $this->id);

        } elseif ($event === 'preparing') {
            $metricsService->timing('order.preparation_start', now()->timestamp);

        } elseif ($event === 'ready') {
            $metricsService->timing('order.ready', now()->timestamp);

        } elseif ($event === 'picked_up') {
            $auditService->log('order.picked_up', $this->id);

        } elseif ($event === 'delivered') {
            $inventoryService->confirmDelivery($this);
            $auditService->log('order.delivered', $this->id);
            $metricsService->increment('orders.delivered');

        } elseif ($event === 'cancelled') {
            $inventoryService->releaseStock($this);
            $auditService->log('order.cancelled', $this->id);
            $metricsService->increment('orders.cancelled');

        } elseif ($event === 'refunded') {
            $auditService->log('order.refunded', $this->id, ['amount' => $this->total]);
            $metricsService->increment('orders.refunded');
        }
    }
}
