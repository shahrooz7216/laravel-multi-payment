<?php

namespace Omalizadeh\MultiPayment;

use Ramsey\Uuid\Uuid;
use Exception;

class Invoice
{
    protected $uuid;
    protected $amount;
    protected $transactionId;
    protected string $driverName;
    protected string $appName;

    public function __construct($amount)
    {
        $this->amount = $amount ?? 0;
        $this->uuid = Uuid::uuid4()->toString();
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setAmount($amount)
    {
        if (!is_numeric($amount)) {
            throw new Exception('Invoice amount must be an numeric.');
        }
        if (config('online-payment.convert_to_rials')) {
            $this->amount = $amount * 10;
        } else {
            $this->amount = $amount;
        }
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setTransactionId($id)
    {
        $this->transactionId = $id;
        return $this;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }
}
