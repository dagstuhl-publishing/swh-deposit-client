<?php

namespace Dagstuhl\SwhDepositClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use \DOMDocument;

class SwhDepositClient
{
    const SUPPORTED_CONTENT_TYPES = [
        "application/zip" => "zip",
        "application/x-tar" => "tar",
    ];

    private Client $client;

    public function __construct(string $baseUrl, string $username, string $password)
    {
        $this->client = new Client([
            "base_uri" => $baseUrl,
            "auth" => [ $username, $password ],
        ]);
    }

    public function createDeposit(
        string $collectionName,
        bool $final,
        SwhDepositMetadata|DOMDocument|null $atom = null,
        ?string $contentType = null,
        mixed $archive = null,
    ): SwhDepositResponse
    {
        if($atom === null && $archive === null) {
            throw new \InvalidArgumentException("at least one of atom or archive must be provided");
        }

        if($atom instanceof SwhDepositMetadata) {
            $atom = $atom->generateDOMDocument();
        }

        if($atom !== null && $archive !== null) {
            return $this->request("POST", "/1/".$collectionName."/", [
                "headers" => [
                    "In-Progress" => $final ? "false" : "true",
                ],
                "multipart" => [
                    [
                        "name" => "atom",
                        "headers" => [
                            "Content-Type" => "application/atom+xml; charset=UTF-8",
                        ],
                        "filename" => "atom.xml",
                        "contents" => $atom->saveXML(),
                    ],
                    [
                        "name" => "file",
                        "headers" => [
                            "Content-Type" => $contentType,
                        ],
                        "filename" => static::getArchiveFilename($contentType),
                        "contents" => $archive,
                    ],
                ],
            ]);

        } else if($atom !== null) {
            return $this->request("POST", "/1/{$collectionName}/", [
                "headers" => [
                    "Content-Type" => "application/atom+xml; type=entry",
                    "In-Progress" => $final ? "false" : "true",
                ],
                "body" => $atom->saveXML(),
            ]);

        } else {
            return $this->request("POST", "/1/{$collectionName}/", [
                "headers" => [
                    "Content-Type" => $contentType,
                    "Content-Disposition" => "attachment; filename=".static::getArchiveFilename($contentType),
                    "In-Progress" => $final ? "false" : "true",
                ],
                "body" => $archive,
            ]);
        }
    }

    public function updateDepositMetadata(
        string $collectionName,
        string $depositId,
        bool $final,
        SwhDepositMetadata|DOMDocument|null $atom,
        bool $replace = false,
    ): SwhDepositResponse
    {
        if($atom instanceof SwhDepositMetadata) {
            $atom = $atom->generateDOMDocument();
        }

        return $this->request($replace ? "PUT" : "POST", "/1/{$collectionName}/{$depositId}/metadata/", [
            "headers" => [
                "Content-Type" => "application/atom+xml; type=entry",
                "In-Progress" => $final ? "false" : "true",
            ],
            "body" => $atom->saveXML(),
        ]);
    }

    public function replaceDepositMetadata(
        string $collectionName,
        string $depositId,
        bool $final,
        SwhDepositMetadata|DOMDocument|null $atom = null,
    ): SwhDepositResponse
    {
        return $this->updateDepositMetadata($collectionName, $depositId, $final, $atom, true);
    }

    public function updateDepositContent(
        string $collectionName,
        string $depositId,
        bool $final,
        ?string $contentType,
        mixed $archive,
        bool $replace = false,
    ): SwhDepositResponse
    {
        return $this->request($replace ? "PUT" : "POST", "/1/{$collectionName}/{$depositId}/media/", [
            "headers" => [
                "Content-Type" => $contentType,
                "Content-Disposition" => "attachment; filename=".static::getArchiveFilename($contentType),
                "In-Progress" => $final ? "false" : "true",
            ],
            "body" => $archive,
        ]);
    }

    public function replaceDepositContent(
        string $collectionName,
        string $depositId,
        bool $final,
        ?string $contentType,
        mixed $archive,
    ): SwhDepositResponse
    {
        return $this->updateDepositContent($collectionName, $depositId, $final, $contentType, $archive, true);
    }

    public function getStatus(string $collectionName, string $depositId): SwhDepositResponse
    {
        return $this->request("GET", "/1/{$collectionName}/{$depositId}/status/");
    }

    public function getContent(string $collectionName, string $depositId): SwhDepositResponse
    {
        return $this->request("GET", "/1/{$collectionName}/{$depositId}/content/");
    }

    public function request(string $method, string $path, array $args = []): SwhDepositResponse
    {
        try {
            $path = ltrim($path, "/");
            $response = $this->client->request($method, $path, $args);
            return new SwhDepositResponse($response);
        } catch(RequestException $ex) {
            throw new SwhDepositException($ex);
        }
    }

    private static function getArchiveFilename(string $contentType): string
    {
        $extension = static::SUPPORTED_CONTENT_TYPES[$contentType] ?? null;
        if($extension === null) {
            throw new \InvalidArgumentException("unsupported content type: {$contentType}");
        }
        return "archive.{$extension}";
    }
}
