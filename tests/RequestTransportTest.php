<?php

namespace Tests;

use JsonException;
use OpenPix\PhpSdk\ApiErrorException;
use OpenPix\PhpSdk\UnreadableResponseException;
use OpenPix\PhpSdk\Client;
use OpenPix\PhpSdk\RequestTransport;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class RequestTransportTest extends TestCase
{
    public function testTransport(): void
    {
        $expectedResult = ["data" => "hello, world."];
        $streamMock = $this->createConfiguredMock(StreamInterface::class, [
            "getContents" => json_encode($expectedResult),
        ]);
        $responseMock = $this->createConfiguredMock(ResponseInterface::class, [
            "getBody" => $streamMock,
        ]);

        $expectedHeaders = [
            "Authorization" => "appId",
            "User-Agent" => RequestTransport::USER_AGENT,
            "platform" => "openpix-php-sdk",
            "version" => Client::SDK_VERSION,
        ];

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->exactly(count($expectedHeaders)))
            ->method("withAddedHeader")
            ->willReturnCallback(function ($header, $value) use ($requestMock, $expectedHeaders) {
                foreach ($expectedHeaders as $expectedHeader => $expectedValue) {
                    if ($header === $expectedHeader) {
                        $this->assertSame($value, $expectedValue);
                    }
                }

                return $requestMock;
            });

        $httpClientMock = $this->createMock(ClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method("sendRequest")
            ->with($requestMock)
            ->willReturn($responseMock);

        $requestTransport = new RequestTransport(
            "appId",
            "https://example.com",
            $httpClientMock,
            $this->createMock(RequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
        );

        $this->assertSame($requestTransport->transport($requestMock), $expectedResult);
    }

    public function testShouldHandleApiReturnedErrorString(): void
    {
        $message = "Nome é obrigatório.";

        $this->expectException(ApiErrorException::class);
        $this->expectExceptionMessage($message);
        $this->testApiErrorHandlingFor($message);
    }

    public function testShouldHandleApiReturnedErrorArray(): void
    {
        $message = "Cobrança não encontrada.";

        $this->expectException(ApiErrorException::class);
        $this->expectExceptionMessage($message);
        $this->testApiErrorHandlingFor(["message" => $message]);
    }

    public function testShouldHandleTruncatedResponseBody(): void
    {
        try {
            $this->transportResponseBody('{"charge": {"correlationID": "abc"', 200);
            $this->fail("Expected " . UnreadableResponseException::class . " to be thrown.");
        } catch (UnreadableResponseException $unreadableResponseException) {
            $this->assertSame("Response body could not be decoded.", $unreadableResponseException->getMessage());
            $this->assertSame(200, $unreadableResponseException->getStatusCode());
            $this->assertInstanceOf(JsonException::class, $unreadableResponseException->getPrevious());
        }
    }

    public function testShouldHandleNonObjectResponseBody(): void
    {
        try {
            $this->transportResponseBody('"ok"', 200);
            $this->fail("Expected " . UnreadableResponseException::class . " to be thrown.");
        } catch (UnreadableResponseException $unreadableResponseException) {
            $this->assertSame("Response body is not an object.", $unreadableResponseException->getMessage());
            $this->assertSame(200, $unreadableResponseException->getStatusCode());
            $this->assertNull($unreadableResponseException->getPrevious());
        }
    }

    private function transportResponseBody(string $body, int $statusCode): void
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock
            ->method("withAddedHeader")
            ->willReturn($requestMock);

        $responseMock = $this->createConfiguredMock(ResponseInterface::class, [
            "getBody" => $this->createConfiguredMock(StreamInterface::class, [
                "getContents" => $body,
            ]),
            "getStatusCode" => $statusCode,
        ]);

        $httpClientMock = $this->createMock(ClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method("sendRequest")
            ->with($requestMock)
            ->willReturn($responseMock);

        $requestTransport = new RequestTransport(
            "appId",
            "https://example.com",
            $httpClientMock,
            $this->createMock(RequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
        );

        $requestTransport->transport($requestMock);
    }

    /**
     * @param string|array{message: string} $error
     */
    private function testApiErrorHandlingFor($error): void
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock
            ->method("withAddedHeader")
            ->willReturn($requestMock);

        $responseMock = $this->createConfiguredMock(ResponseInterface::class, [
            "getBody" => $this->createConfiguredMock(StreamInterface::class, [
                "getContents" => json_encode(["error" => $error]),
            ]),
            "getStatusCode" => 400,
            "getReasonPhrase" => "Bad request",
        ]);

        $httpClientMock = $this->createMock(ClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method("sendRequest")
            ->with($requestMock)
            ->willReturn($responseMock);

        $requestTransport = new RequestTransport(
            "appId",
            "https://example.com",
            $httpClientMock,
            $this->createMock(RequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
        );

        $requestTransport->transport($requestMock);
    }
}
