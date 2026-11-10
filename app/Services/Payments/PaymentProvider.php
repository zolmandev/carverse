<?php

namespace App\Services\Payments;

use App\Models\PaymentAttempt;
use App\Models\Transaction;

interface PaymentProvider
{
    public function initiate(Transaction $transaction, string $phone): PaymentAttempt;
}
