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
        protected ?string $webhook = null,
        protected ?int $sessionTimeout = null,
        protected ?int $merchantId = null,
    ) {
    }

    public function generatePayment(array $payload): array
    {
        $response = $this->request('v2/generate_payment', array_filter([
            'success_link' => $payload['success_link'] ?? $this->successLink,
            'fail_link' => $payload['fail_link'] ?? $this->failLink,
            'accept_card' => $payload['accept_card'] ?? $this->cardPayment,
            'image_url' => $payload['image_url'] ?? $this->imageUrl,
            'webhook' => $payload['webhook'] ?? $this->webhook,
            'session_timeout_secs' => $payload['session_timeout_secs'] ?? $this->sessionTimeout,
        ], fn ($value) => ! is_null($value)) + $payload);

        return $this->decodeResponse($response, 'payment generation');
    }

    public function verifyPayment(string|int $paymentId): array
    {
        $response = $this->request('v2/verify_payment/'.rawurlencode((string) $paymentId), method: 'get');

        return $this->decodeResponse($response, 'payment verification');
    }

    public function refund(string $paymentId): array
    {
        $response = $this->request('v2/refund_payment', ['payment_id' => $paymentId]);
        $decoded = $this->decodeResponse($response, 'refund');

        // Refund errors may come back as {"status": "error", ...}; never report them as refunded.
        if (($decoded['status'] ?? null) === 'error') {
            throw new FlouciException('Flouci refund failed: '.($decoded['message'] ?? 'unknown error'), $response);
        }

        return $decoded;
    }

    public function transactionHistory(array $query = []): array
    {
        $query += array_filter(['merchant_id' => $this->merchantId]);

        if (! isset($query['merchant_id'])) {
            throw new FlouciException('Flouci merchant id is missing. Set FLOUCI_MERCHANT_ID or pass merchant_id.');
        }

        $response = $this->request('developers/history', $query, method: 'get');

        return $this->decodeResponse($response, 'transaction history');
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
