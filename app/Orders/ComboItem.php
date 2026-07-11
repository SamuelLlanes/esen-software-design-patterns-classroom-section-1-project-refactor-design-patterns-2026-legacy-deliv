<?php

namespace App\Orders;

class ComboItem implements OrderItemInterface
{
    /** @var OrderItemInterface[] */
    private array $children = [];

    public function __construct(
        private string $name,
        private int    $quantity      = 1,
        private float  $discountPct   = 0.0,
    ) {}

    public function add(OrderItemInterface $item): void
    {
        $this->children[] = $item;
    }

    public function remove(OrderItemInterface $item): void
    {
        $this->children = array_values(
            array_filter($this->children, fn($child) => $child !== $item)
        );
    }

    /** @return OrderItemInterface[] */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getTotal(): float
    {
        $subtotal = 0.0;

        foreach ($this->children as $child) {
            $subtotal += $child->getTotal();
        }

        if ($this->discountPct > 0.0) {
            $subtotal = $subtotal * (1 - $this->discountPct / 100);
        }

        return $subtotal * $this->quantity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getDiscountPct(): float
    {
        return $this->discountPct;
    }
}
