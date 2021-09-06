<?php

namespace Omalizadeh\MultiPayment;

class RedirectionForm
{
    protected string $method;
    protected array $inputs;
    protected string $action;

    public function __construct(string $action, array $inputs = [], string $method = 'POST')
    {
        $this->action = $action;
        $this->inputs = $inputs;
        $this->method = $method;
    }

    public function getUrl(): string
    {
        return $this->action;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function toJsonResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->toArray());
    }

    public function toArray(): array
    {
        return $this->getData();
    }

    public function render()
    {
        return view('multipayment::gateway_redirect', $this->toArray());
    }

    protected function getData(): array
    {
        return [
            "action" => $this->getUrl(),
            "inputs" => $this->getInputs(),
            "method" => $this->getMethod(),
        ];
    }
}
