<?php

namespace Omalizadeh\MultiPayment;

use Ramsey\Uuid\Uuid;
use InvalidArgumentException;

class Invoice
{
    protected $amount;
    protected string $uuid;
    protected ?int $userId = null;
    protected ?string $referenceId;
    protected ?string $token = null;
    protected ?string $email = null;
    protected ?string $invoiceId = null;
    protected ?string $transactionId;
    protected ?string $cardNo = null;
    protected ?string $description = null;
    protected ?string $phoneNumber = null;

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
        if (config('multipayment.convert_to_rials')) {
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

    public function setToken(string $token): Invoice
    {
        $this->token = $token;
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

    public function setCardNo(string $cardNo): Invoice
    {
        $this->cardNo = $cardNo;
        return $this;
    }

    public function setReferenceId(string $referenceId): Invoice
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function setUserId(int $userId): Invoice
    {
        $this->userId = $userId;
        return $this;
    }

    public function setInvoiceId(string $invoiceId): Invoice
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getAmountInTomans()
    {
        return $this->amount / 10;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getCardNo(): ?string
    {
        return $this->cardNo;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getInvoiceId(): ?string
    {
        if (empty($this->invoiceId)) {
            $this->invoiceId = crc32($this->getUuid()) . rand(0, 99999);
        }
        return $this->invoiceId;
    }

    public function getCustomerInfo(): array
    {
        return [
            'user_id' => $this->getUserId(),
            'phone' => $this->getPhoneNumber(),
            'email' => $this->getEmail()
        ];
    }
}
