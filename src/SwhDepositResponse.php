<?php

namespace Dagstuhl\SwhDepositClient;

use Carbon\CarbonImmutable;
use Psr\Http\Message\ResponseInterface;
use \DOMDocument;
use \DOMXPath;

class SwhDepositResponse
{
    private ResponseInterface $response;
    private string $responseBody;
    private ?DOMDocument $document = null;
    private ?DOMXPath $xpath = null;

    private ?string $depositId = null;
    private ?CarbonImmutable $depositDate = null;
    private ?string $depositArchive = null;
    private ?SwhDepositStatus $depositStatus = null;
    private ?string $depositSwhId = null;
    private ?string $depositSwhIdContext = null;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        $this->responseBody = $response->getBody()->getContents();

        $document = new DOMDocument();
        if($document->loadXML($this->responseBody, LIBXML_NOERROR)) {
            $this->document = $document;
            $this->xpath = new DOMXPath($this->document);
            $this->xpath->registerNamespace("app", "http://www.w3.org/2007/app");
            $this->xpath->registerNamespace("atom", "http://www.w3.org/2005/Atom");
            $this->xpath->registerNamespace("sword", "http://purl.org/net/sword/terms/");
            $this->xpath->registerNamespace("dcterms", "http://purl.org/dc/terms/");
            $this->xpath->registerNamespace("codemeta", "https://doi.org/10.5063/SCHEMA/CODEMETA-2.0");
            $this->xpath->registerNamespace("swhdeposit", "https://www.softwareheritage.org/schema/2018/deposit");

            $this->depositId = $this->get("//atom:entry/swhdeposit:deposit_id");
            if(($date = $this->get("//atom:entry/swhdeposit:deposit_date")) !== null) {
                $this->depositDate = CarbonImmutable::parse($date);
            }
            $this->depositArchive = $this->get("//atom:entry/swhdeposit:deposit_archive");
            if(($status = $this->get("//atom:entry/swhdeposit:deposit_status")) !== null) {
                $this->depositStatus = SwhDepositStatus::from($status);
            }
            $this->depositSwhId = $this->get("//atom:entry/swhdeposit:deposit_swh_id");
            $this->depositSwhIdContext = $this->get("//atom:entry/swhdeposit:deposit_swh_id_context");
        }
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getResponseStatus(): int
    {
        return $this->response->getStatusCode();
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getDocument(): ?DOMDocument
    {
        return $this->document;
    }

    public function getXPath(): ?DOMXPath
    {
        return $this->xpath;
    }

    public function getDepositId(): ?string
    {
        return $this->depositId;
    }

    public function getDepositDate(): ?CarbonImmutable
    {
        return $this->depositDate;
    }

    public function getDepositArchive(): ?string
    {
        return $this->depositArchive;
    }

    public function getDepositStatus(): ?SwhDepositStatus
    {
        return $this->depositStatus;
    }

    public function getDepositSwhId(): ?string
    {
        return $this->depositSwhId;
    }

    public function getDepositSwhIdContext(): ?string
    {
        return $this->depositSwhIdContext;
    }

    public function get(string $xpath): ?string
    {
        if($this->xpath === null) {
            return null;
        }

        return $this->xpath->query($xpath)->item(0)?->textContent;
    }

    public function getAll(string $xpath): array
    {
        if($this->xpath === null) {
            return [];
        }

        $res = [];
        foreach($this->xpath->query($xpath) as $node) {
            $res[] = $node->textContent;
        }
        return $res;
    }
}
