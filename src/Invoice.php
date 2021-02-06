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

    public function setAmount($amount): Invoice
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

    public function setTransactionId(string $id): Invoice
    {
        $this->transactionId = $id;
        return $this;
    }

    public function setDescription(string $description): Invoice
    {
        $this->description = $description;
        return $this;
    }

    public function setPhoneNumber(string $phone): Invoice
    {
        $this->phoneNumber = $phone;
        return $this;
    }

    public function setEmail(string $email): Invoice
    {
        $this->email = $email;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCustomerInfo(): array
    {
        return [
            'phone' => $this->getPhoneNumber(),
            'email' => $this->getEmail(),
            'description' => $this->getDescription()
        ];
    }
}
