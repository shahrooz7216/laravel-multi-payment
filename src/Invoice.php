<?php

namespace Omalizadeh\MultiPayment;

use Ramsey\Uuid\Uuid;
use InvalidArgumentException;

class Invoice
{
    protected float $amount;
    protected string $uuid;
    protected ?int $userId = null;
    protected ?string $token = null;
    protected ?string $email = null;
    protected ?string $invoiceId = null;
    protected ?string $description = null;
    protected ?string $phoneNumber = null;
    protected ?string $transactionId = null;

    public function __construct(float $amount, ?string $transactionId = null)
    {
        $this->setAmount($amount);
        $this->uuid = Uuid::uuid4()->toString();
        if (!empty($transactionId)) {
            $this->setTransactionId($transactionId);
        }
    }

    /**
     * @param $amount
     * @return $this
     */
    public function setAmount($amount): Invoice
    {
        if (config('multipayment.convert_to_rials')) {
            $this->amount = $amount * 10;
        } else {
            $this->amount = $amount;
        }

        return $this;
    }

    /**
     * @param  string  $id
     * @return $this
     */
    public function setTransactionId(string $id): Invoice
    {
        $this->transactionId = $id;
        return $this;
    }

    /**
     * @param  string  $token
     * @return $this
     */
    public function setToken(string $token): Invoice
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @param  string  $description
     * @return $this
     */
    public function setDescription(string $description): Invoice
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param  string  $phone
     * @return $this
     */
    public function setPhoneNumber(string $phone): Invoice
    {
        $this->phoneNumber = $phone;
        return $this;
    }

    /**
     * @param  string  $email
     * @return $this
     */
    public function setEmail(string $email): Invoice
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param  int  $userId
     * @return $this
     */
    public function setUserId(int $userId): Invoice
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @param  string  $invoiceId
     * @return $this
     */
    public function setInvoiceId(string $invoiceId): Invoice
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return float|int
     */
    public function getAmountInTomans()
    {
        return $this->amount / 10;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getInvoiceId(): string
    {
        if (empty($this->invoiceId)) {
            $this->invoiceId = crc32($this->getUuid()).random_int(0, 99999);
        }
        return $this->invoiceId;
    }

    /**
     * @return array
     */
    public function getCustomerInfo(): array
    {
        return [
            'user_id' => $this->getUserId(),
            'phone' => $this->getPhoneNumber(),
            'email' => $this->getEmail()
        ];
    }
}
