<?php

namespace Omalizadeh\MultiPayment;

use Ramsey\Uuid\Uuid;
use InvalidArgumentException;

class Invoice
{
    protected $amount;
    protected string $uuid;
    protected ?string $email = null;
    protected ?string $description = null;
    protected ?string $phoneNumber = null;
    protected string $transactionId;

    public function __construct($amount)
    {
        $this->setAmount($amount);
        $this->uuid = Uuid::uuid4()->toString();
    }

    public function setAmount($amount)
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Invoice amount must be a numeric value.');
        }
        if (config('online-payment.convert_to_rials')) {
            $this->amount = $amount * 10;
        } else {
            $this->amount = $amount;
        }
        return $this;
    }

    public function setTransactionId(string $id)
    {
        $this->transactionId = $id;
        return $this;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function setPhoneNumber(string $phone)
    {
        $this->phoneNumber = $phone;
        return $this;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getCustomerInfo()
    {
        return [
            'phone' => $this->getPhoneNumber(),
            'email' => $this->getEmail(),
            'description' => $this->getDescription()
        ];
    }
}
