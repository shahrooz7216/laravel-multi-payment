<?php

namespace Omalizadeh\MultiPayment;

class RedirectionForm
{
    protected $method;
    protected $inputs;
    protected $action;

    public function __construct(string $action, array $inputs = [], string $method = 'POST')
    {
        $this->action = $action;
        $this->inputs = $inputs;
        $this->method = $method;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function getUrl(): string
    {
        return $this->action;
    }

    public function getData(): array
    {
        return [
            "action" => $this->getUrl(),
            "inputs" => $this->getInputs(),
            "method" => $this->getMethod(),
        ];
    }

    public function toJson()
    {
        return $this->getData();
    }

    public function render()
    {
        return view('multipayment::gateway_redirect', $this->getData());
    }

    public function toJsonResponse()
    {
        return response()->json($this->toJson());
    }
}
