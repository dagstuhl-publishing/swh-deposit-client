<?php

namespace Dagstuhl\SwhDepositClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use \Exception;

class SwhDepositException extends RequestException
{
    private SwhDepositResponse $swhDepositResponse;

    public function __construct(RequestException $ex)
    {
        $this->swhDepositResponse = SwhDepositResponse::fromResponse($ex->getResponse());
        parent::__construct($ex->message, $ex->getRequest(), $ex->getResponse(), $ex);
    }

    public function getSwhDepositResponse(): SwhDepositResponse
    {
        return $this->swhDepositResponse;
    }

}
