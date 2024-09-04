<?php

namespace Dagstuhl\SwhDepositClient;

use \DOMDocument;
use \DOMNode;

class SwhDepositMetadata
{
    public ?string $namespace;
    public ?string $key;
    public array $attributes;
    public ?string $value;

    public array $children;
    public array $childrenByName;

    public function __construct(?string $key = null, array $attributes = [], ?string $value = null, array $children = [])
    {
        $split = explode(":", $key, 2);
        if(count($split) == 2) {
            $this->namespace = $split[0];
            $this->key = $split[1];
        } else {
            $this->namespace = "atom";
            $this->key = $key;
        }

        $this->attributes = $attributes;
        $this->value = $value;

        $this->children = [];
        $this->childrenByName = [];
        foreach($children as $child) {
            $this->addItem($child);
        }
    }

    public static function fromCodeMetaJson(string|object $json): SwhDepositMetadata
    {
        $metadata = new SwhDepositMetadata();
        $metadata->importCodeMetaJson($json);
        return $metadata;
    }

    public function getKey(): string
    {
        return "{$this->namespace}:{$this->key}";
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

    public function importCodeMetaJson(string|object $json)
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
                    $this->add("codemeta:$key")->importCodeMetaJson($child);
                }
            }
        }
    }

    public function generateDOMDocument(?DOMDocument $dom = null): DOMDocument
    {
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->formatOutput = true;

        $root = $dom->createElementNS("http://www.w3.org/2005/Atom", "entry");
        $root->setAttribute("xmlns:dcterms", "http://purl.org/dc/terms/");
        $root->setAttribute("xmlns:codemeta", "https://doi.org/10.5063/SCHEMA/CODEMETA-2.0");
        $root->setAttribute("xmlns:swhdeposit", "https://www.softwareheritage.org/schema/2018/deposit");
        $dom->appendChild($root);

        foreach($this->children as $child) {
            $child->addToDom($dom, $root);
        }

        return $dom;
    }

    private function addToDom(DOMDocument $dom, DOMNode $root)
    {
        $key = $this->namespace === "atom" ? $this->key : $this->namespace.":".$this->key;
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
                $child->addToDom($dom, $element);
            }
        }
    }
}
