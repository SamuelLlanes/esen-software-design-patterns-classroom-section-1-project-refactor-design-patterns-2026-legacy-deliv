<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Discounts\DiscountStrategyFactory;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'type', 'value', 'min_order_amount', 'max_discount_amount',
                           'valid_from', 'valid_to', 'max_uses', 'current_uses', 'vendor_id'];

    protected $casts = ['valid_from' => 'datetime', 'valid_to' => 'datetime'];

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function orders() { return $this->belongsToMany(Order::class, 'order_discounts'); }

    /**
     * Aplica el descuento a una orden usando el Strategy Pattern.
     * La lógica específica de cada tipo de descuento está encapsulada en su respectiva estrategia.
     */
    public function apply(Order $order): float
    {
        // Paso 1: Validaciones comunes
        if (!$this->isValid()) {
            return 0.0;
        }

        if (!$this->isApplicableToOrder($order)) {
            return 0.0;
        }

        // Paso 2: Resolver la estrategia específica del tipo de descuento
        $strategy = DiscountStrategyFactory::resolve($this->type);

        // Paso 3: Calcular el descuento usando la estrategia
        return $strategy->calculate($order, $this->getDiscountData());
    }

    /**
     * Valida que el descuento esté activo en fecha y usos.
     */
    private function isValid(): bool
    {
        if (\now() < $this->valid_from || \now() > $this->valid_to) {
            return false;
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Valida que el descuento sea aplicable a la orden específica.
     */
    private function isApplicableToOrder(Order $order): bool
    {
        if ($this->min_order_amount !== null && $order->subtotal < $this->min_order_amount) {
            return false;
        }

        if ($this->vendor_id !== null && $this->vendor_id !== $order->vendor_id) {
            return false;
        }

        return true;
    }

    /**
     * Retorna los datos necesarios para calcular el descuento.
     */
    private function getDiscountData(): array
    {
        return [
            'value' => $this->value,
            'max_discount_amount' => $this->max_discount_amount,
        ];
    }
}

