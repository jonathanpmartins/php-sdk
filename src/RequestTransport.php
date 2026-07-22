<?php

namespace OpenPix\PhpSdk;

use JsonException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Transports HTTP requests to the API, adding data such as the Authorization and
 * User-Agent header, API path (like /api/v1) and handling errors and decoding responses.
 */
class RequestTransport
{
    /**
     * User Agent.
     */
    public const USER_AGENT = "openpix-php-sdk";

    /**
     * Underlying HTTP client.
     *
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * Request factory passed to API `Request` builder.
     *
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * Stream factory passed to API `Request` builder.
     *
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * Application ID.
     *
     * @var string
     */
    private $appId;

    /**
     * Base URI of all requests handled by RequestTransport.
     *
     * @var string
     */
    private $baseUri;

    /**
     * Create a new RequestTransport instance.
     *
     * @link https://developers.openpix.com.br/docs/apis/api-getting-started
     *
     * @param string $appId Application ID.
     * @param string $baseUri Base URI of all requests handled by RequestTransport.
     * @param ?ClientInterface $httpClient PSR-18 HTTP Client. Is automatically
     * discovered with HTTPlug.
     * @param ?RequestFactoryInterface $requestFactory PSR-17 RequestFactory. It is automatically discovered with HTTPlug.
     * @param ?StreamFactoryInterface $streamFactory PSR-17 StreamFactory. It is automatically discovered with HTTPlug.
     */
    public function __construct(
        string $appId,
        string $baseUri,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->appId = $appId;
        $this->baseUri = $baseUri;
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Send the request to API.
     *
     * @param Request|RequestInterface $request
     *
     * @return array<string, mixed> Decoded response data.
     */
    public function transport($request): array
    {
        if (! ($request instanceof RequestInterface)) {
            $request = $request->build($this->baseUri, $this->requestFactory, $this->streamFactory);
        }

        $request = $this->withRequestDefaultParameters($request);

        $response = $this->httpClient->sendRequest($request);

        return $this->hydrateResponse($response);
    }

    /**
     * Get base URI of API.
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * Add default headers like Authorization and `platform`.
     */
    private function withRequestDefaultParameters(RequestInterface $request): RequestInterface
    {
        return $request->withAddedHeader("User-Agent", self::USER_AGENT)
            ->withAddedHeader("Authorization", $this->appId)
            ->withAddedHeader("version", Client::SDK_VERSION)
            ->withAddedHeader("platform", "openpix-php-sdk");
    }

    /**
     * Decode response data.
     *
     * @return array<string, mixed>
     */
    private function hydrateResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        // A 204 has no body by definition, so there is nothing to decode.
        if ($statusCode === 204) {
            return [];
        }

        try {
            $contents = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new UnreadableResponseException(
                "Response body could not be decoded.",
                $statusCode,
                $jsonException
            );
        }

        if (!is_array($contents)) {
            throw new UnreadableResponseException(
                "Response body is not an object.",
                $statusCode
            );
        }

        if (!empty($contents["error"])) {
            throw new ApiErrorException($this->errorMessage($contents["error"]), $statusCode, $contents);
        }

        // An error status without the `error` key is still an error: returning it
        // as data would make a rejected request indistinguishable from a successful one.
        if ($statusCode >= 400) {
            throw new ApiErrorException($this->statusErrorMessage($response, $contents), $statusCode, $contents);
        }

        return $contents;
    }

    /**
     * Extract the message of the `error` key of a response body.
     *
     * @param mixed $error
     */
    private function errorMessage($error): string
    {
        if (is_array($error)) {
            $error = $error["message"] ?? $error["description"] ?? json_encode($error);
        }

        return is_string($error) ? $error : (string) json_encode($error);
    }

    /**
     * Build a message for an error status whose body has no `error` key.
     *
     * @param array<string, mixed> $contents
     */
    private function statusErrorMessage(ResponseInterface $response, array $contents): string
    {
        foreach (["message", "description", "errorMessage"] as $key) {
            if (isset($contents[$key]) && is_string($contents[$key]) && $contents[$key] !== "") {
                return $contents[$key];
            }
        }

        $reasonPhrase = $response->getReasonPhrase();

        return "API responded with status " . $response->getStatusCode()
            . ($reasonPhrase === "" ? "." : " (" . $reasonPhrase . ").");
    }
}
