<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo;

use App\Bridge\OpenMeteo\Exception\ApiException;
use App\Bridge\OpenMeteo\Exception\TransportException;
use App\Bridge\OpenMeteo\Request\ForecastRequest;
use App\Bridge\OpenMeteo\Response\ForecastResponse;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Thin client for the Open-Meteo Weather Forecast API (GET /v1/forecast).
 *
 * The $openMeteoClient argument is autowired from the `open_meteo.client` scoped client
 * configured in config/packages/framework.yaml.
 */
final readonly class OpenMeteoClient
{
    public function __construct(
        private HttpClientInterface $openMeteoClient,
    ) {
    }

    /**
     * @throws ApiException       when the API reports an error (HTTP error status or {"error": true} body)
     * @throws TransportException when the request fails at the network/transport level
     */
    public function forecast(ForecastRequest $request): ForecastResponse
    {
        try {
            $response = $this->openMeteoClient->request('GET', '/v1/forecast', [
                'query' => $request->toQuery(),
            ]);

            /** @var array<string, mixed> $data */
            $data = $response->toArray(throw: false);
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Open-Meteo request failed: '.$e->getMessage(), 0, $e);
        } catch (JsonException $e) {
            throw new ApiException('Open-Meteo returned a non-JSON response: '.$e->getMessage(), 0, $e);
        }

        try {
            $statusCode = $response->getStatusCode();
        } catch (HttpExceptionInterface $e) {
            throw new TransportException('Open-Meteo request failed: '.$e->getMessage(), 0, $e);
        }

        // Open-Meteo signals errors with {"error": true, "reason": "..."} (HTTP 400) in addition to the status code.
        if (true === ($data['error'] ?? false) || $statusCode >= 400) {
            $reason = \is_string($data['reason'] ?? null) ? $data['reason'] : 'unknown error';

            throw new ApiException(\sprintf('Open-Meteo API error (HTTP %d): %s', $statusCode, $reason), $statusCode);
        }

        // A successful multi-coordinate request returns a JSON array; this basic client supports single locations only.
        if (!\array_key_exists('latitude', $data)) {
            throw new ApiException('Unexpected response shape; multi-coordinate responses are not supported.', $statusCode);
        }

        return ForecastResponse::fromArray($data);
    }
}
