<?php

namespace OpenPix\PhpSdk\Resources;

use OpenPix\PhpSdk\Paginator;
use OpenPix\PhpSdk\Request;
use OpenPix\PhpSdk\RequestTransport;

/**
 * Operations on statements.
 *
 * @link https://developers.openpix.com.br/api#tag/statement
 */
class Statements
{
    /**
     * Used to send HTTP requests to statements API.
     *
     * @var RequestTransport
     */
    private $requestTransport;

    /**
     * Create a new Statements instance.
     *
     * @param RequestTransport $requestTransport Used to send HTTP requests to statements API.
     */
    public function __construct(RequestTransport $requestTransport)
    {
        $this->requestTransport = $requestTransport;
    }

    /**
     * Get a list of Statements.
     *
     * ```php
     * $list = $client->statements()->list([
     *      "start" => "", // Start date used in the query. Complies with RFC 3339. Optional.
     *      "end" => "", // End date used in the query. Complies with RFC 3339. Optional.
     * ]);
     *
     * foreach ($list as $statement) {
     *     $statement["id"]; // string
     *     $statement["time"]; // datetime RFC 3339
     *     $statement["description"]; // string
     *     $statement["balance"]; // int
     *     $statement["value"]; // int
     *     $statement["transactionId"]; // string
     * }
     * ```
     *
     * @link https://developers.openpix.com.br/api#tag/statement
     *
     * @param array<string, mixed> $queryParams
     *
     * @return array<int, array<string, string|int>>.
     */
    public function list(array $queryParams): array
    {
        $request = (new Request())
            ->method("GET")
            ->path("/api/v1/statement")
            ->queryParams($queryParams);

        return $this->requestTransport->transport($request);
    }
}
