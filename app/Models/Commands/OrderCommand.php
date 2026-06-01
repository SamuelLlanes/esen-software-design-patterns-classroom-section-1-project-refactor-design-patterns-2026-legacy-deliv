<?php

namespace App\Models\Commands;

use App\Models\Order;

interface OrderCommand
{
    public function execute(Order $order): void;
}
