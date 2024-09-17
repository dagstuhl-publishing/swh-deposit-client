<?php

namespace Dagstuhl\SwhDepositClient;

use \DOMDocument;
use \DOMNode;

class SwhDepositMetadata
{
    const DEFAULT_NAMESPACE = "atom";

    const NAMESPACES = [
        "atom" => "http://www.w3.org/2005/Atom",
        "dcterms" => "http://purl.org/dc/terms/",
        "codemeta" => "https://doi.org/10.5063/SCHEMA/CODEMETA-2.0",
        "swhdeposit" => "https://www.softwareheritage.org/schema/2018/deposit",
    ];

    public ?string $namespace = null;
    public ?string $key = null;
    public array $attributes;
    public ?string $value;

    public array $children;
    public array $childrenByName;

    public function __construct(?string $key = null, array $attributes = [], ?string $value = null, array $children = [])
    {
        if($key !== null) {
            $split = explode(":", $key, 2);
            if(count($split) == 2) {
                $this->namespace = $split[0];
                $this->key = $split[1];
            } else {
                $this->namespace = static::DEFAULT_NAMESPACE;
                $this->key = $key;
            }

            if(!isset(static::NAMESPACES[$this->namespace])) {
                throw new \InvalidArgumentException("invalid namespace: {$this->namespace}");
            }
        }

        $this->attributes = $attributes;
        $this->value = $value;

        $this->children = [];
        $this->childrenByName = [];
        foreach($children as $child) {
            $this->addItem($child);
        }
    }

    public static function fromCodemetaJson(string|object $json): SwhDepositMetadata
    {
        $metadata = new SwhDepositMetadata();
        $metadata->importCodemetaJson($json);
        return $metadata;
    }

    public function getKey(): string
    {
        return "{$this->namespace}:{$this->key}";
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function add(string $key, array $attributes = [], ?string $value = null, ?array $children = []): SwhDepositMetadata
    {
        return $this->addItem(new SwhDepositMetadata($key, $attributes, $value, $children));
    }

    public function addItem(SwhDepositMetadata $item): SwhDepositMetadata
    {
        $this->children[] = $item;

        $key = $item->getKey();
        if(!isset($this->childrenByName[$key])) {
            $this->childrenByName[$key] = [];
        }
        $this->childrenByName[$key][] = $item;

        return $item;
    }

    public function exists(string $key): bool
    {
        return isset($this->childrenByName[$key]);
    }

    public function getFirst(string $key): ?SwhDepositMetadata
    {
        return $this->childrenByName[$key][0] ?? null;
    }

    public function getAll(string $key): array
    {
        return $this->childrenByName[$key] ?? [];
    }

    public function importCodemetaJson(string|object $json)
    {
        if(is_string($json)) {
            $json = json_decode($json);
        }
        foreach($json as $key => $value) {
            if(str_starts_with($key, "@")) {
                continue;
            }
            $children = is_array($value) ? $value : [ $value ];
            foreach($children as $child) {
                if(is_string($child)) {
                    $this->add("codemeta:$key", [], $child);
                } else {
                    $this->add("codemeta:$key")->importCodemetaJson($child);
                }
            }
        }
    }

    public function fillMissingMetadata()
    {
        foreach($this->getAll("codemeta:author") ?? [] as $author) {
            if(!$author->exists("codemeta:name")) {
                $givenName = $author->getFirst("codemeta:givenName")?->getValue() ?? "";
                $familyName = $author->getFirst("codemeta:familyName")?->getValue() ?? "";
                $name = trim("{$givenName} {$familyName}");
                if($name !== "") {
                    $author->add("codemeta:name", [], $name);
                }
            }
        }
    }

    public function generateDOMDocument(?DOMDocument $dom = null): DOMDocument
    {
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->formatOutput = true;

        $root = $dom->createElementNS(static::NAMESPACES[static::DEFAULT_NAMESPACE], "entry");
        $dom->appendChild($root);

        $namespaces = [];
        foreach($this->children as $child) {
            $child->addToDom($dom, $namespaces, $root);
        }

        foreach(static::NAMESPACES as $namespace => $url) {
            if($namespace !== static::DEFAULT_NAMESPACE && isset($namespaces[$namespace])) {
                $root->setAttribute("xmlns:$namespace", $url);
            }
        }

        return $dom;
    }

    private function addToDom(DOMDocument $dom, array &$namespaces, DOMNode $root)
    {
        $namespaces[$this->namespace] = true;

        $key = $this->namespace === static::DEFAULT_NAMESPACE ? $this->key : $this->namespace.":".$this->key;
        $element = $dom->createElement($key);
        $root->appendChild($element);

        foreach($this->attributes as $name => $value) {
            $element->setAttribute($name, $value);
        }

        if($this->value !== null) {
            $text = $dom->createTextNode($this->value);
            $element->appendChild($text);
        } else {
            foreach($this->children as $child) {
                $child->addToDom($dom, $namespaces, $element);
            }
        }
    }
}
