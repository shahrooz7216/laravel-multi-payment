<?php

namespace Omalizadeh\MultiPayment;

class RedirectionForm
{
    protected string $method;
    protected array $inputs;
    protected string $action;

    /**
     * @param  string  $action
     * @param  array  $inputs
     * @param  string  $method
     */
    public function __construct(string $action, array $inputs = [], string $method = 'POST')
    {
        $this->action = $action;
        $this->inputs = $inputs;
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Returns a json response where form fields are wrapped in data key.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toJsonResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'data' => $this->toArray()
        ]);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->getData();
    }

    /**
     * Renders a view that redirects to payment gateway automatically.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view()
    {
        return view('multipayment::gateway_redirect', $this->toArray());
    }

    /**
     * @deprecated
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('multipayment::gateway_redirect', $this->toArray());
    }

    /**
     * @return array
     */
    protected function getData(): array
    {
        return [
            'action' => $this->getUrl(),
            'inputs' => $this->getInputs(),
            'method' => $this->getMethod(),
        ];
    }
}
