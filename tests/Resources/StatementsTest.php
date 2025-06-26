<?php

namespace Resources;

use OpenPix\PhpSdk\Request;
use OpenPix\PhpSdk\RequestTransport;
use OpenPix\PhpSdk\Resources\Accounts;
use OpenPix\PhpSdk\Resources\Statements;
use PHPUnit\Framework\TestCase;

final class StatementsTest extends TestCase
{
    public function testList(): void
    {
        $accounts = [
            [
                "account" => [
                    "accountId" => "356a192b7913b04c54574d18c28d46e6395428ab",
                    "isDefault" => true,
                    "balance" => [
                        "total" => 129430,
                        "blocked" => 0,
                        "available" => 129430,
                    ],
                ],
            ],
            [
                "account" => [
                    "accountId" => "77de68daecd823babbb58edb1c8e14d7106e83bb",
                    "isDefault" => true,
                    "balance" => [
                        "total" => 3129430,
                        "blocked" => 0,
                        "available" => 3129430,
                    ],
                ],
            ]
        ];

        $requestTransportMock = $this->createMock(RequestTransport::class);
        $requestTransportMock->expects($this->once())
            ->method("transport")
            ->willReturnCallback(function (Request $request) use ($accounts) {
                $this->assertSame("GET", $request->getMethod());
                $this->assertSame("/api/v1/statement", $request->getPath());
                $this->assertSame($request->getBody(), null);
                $this->assertSame($request->getQueryParams(), []);

                return $accounts;
            });

        $partners = new Statements($requestTransportMock);

        $result = $partners->list([]);

        $this->assertSame($result, $accounts);
    }
}
