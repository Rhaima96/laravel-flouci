<?php

namespace Flouci\Laravel;

use Flouci\Laravel\Exceptions\FlouciException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FlouciClient
{
    public function __construct(
        protected ?string $publicKey,
        protected ?string $privateKey,
        protected string $baseUrl,
        protected ?string $successLink = null,
        protected ?string $failLink = null,
        protected ?bool $cardPayment = null,
        protected ?string $imageUrl = null,
        protected ?HttpFactory $http = null,
        protected int $timeout = 15,
    ) {
    }

    public function generatePayment(array $payload): array
    {
        $response = $this->request('v2/generate_payment', array_filter([
            'success_link' => $payload['success_link'] ?? $this->successLink,
            'fail_link' => $payload['fail_link'] ?? $this->failLink,
            'accept_card' => $payload['accept_card'] ?? $this->cardPayment,
            'image_url' => $payload['image_url'] ?? $this->imageUrl,
        ], fn ($value) => ! is_null($value)) + $payload);

        return $this->decodeResponse($response, 'payment generation');
    }

    public function verifyPayment(string|int $paymentId): array
    {
        $response = $this->request('v2/verify_payment/'.rawurlencode((string) $paymentId), method: 'get');

        return $this->decodeResponse($response, 'payment verification');
    }

    protected function request(string $uri, array $payload = [], string $method = 'post'): Response
    {
        $this->guardCredentials();

        $client = ($this->http ?? Http::getFacadeRoot())
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->withToken($this->publicKey.':'.$this->privateKey);

        try {
            $response = $client->{$method}(ltrim($uri, '/'), $payload);
        } catch (ConnectionException $exception) {
            throw new FlouciException('Flouci request failed: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new FlouciException('Flouci request failed: '.$response->status().' '.$response->body(), $response);
        }

        return $response;
    }

    protected function decodeResponse(Response $response, string $action): array
    {
        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new FlouciException("Unable to decode Flouci {$action} response.", $response);
        }

        return $decoded;
    }

    protected function guardCredentials(): void
    {
        if (blank($this->publicKey) || blank($this->privateKey)) {
            throw new FlouciException('Flouci credentials are missing. Set FLOUCI_PUBLIC_KEY and FLOUCI_PRIVATE_KEY.');
        }
    }
}
