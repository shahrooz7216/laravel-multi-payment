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

    public function getAction(): string
    {
        return $this->action;
    }

    public function render()
    {
        $data = [
            "action" => $this->getAction(),
            "inputs" => $this->getInputs(),
            "method" => $this->getMethod(),
        ];

        return view('payment.redirect_to_bank', $data);
    }

    public function json($options = JSON_UNESCAPED_UNICODE)
    {
        return response()->json($data, 200, [], $options);
    }

    public function __toString()
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
