[![SWH](https://archive.softwareheritage.org/badge/swh:1:dir:92742c9c9cbb1eb3ddca8c5fd0b72a22e82f28d4/)](https://archive.softwareheritage.org/swh:1:dir:92742c9c9cbb1eb3ddca8c5fd0b72a22e82f28d4;origin=https://github.com/dagstuhl-publishing/swh-deposit-client;visit=swh:1:snp:f987590d89934bb2ce036a8374fa3f04a9c94fc5;anchor=swh:1:rev:82625c08d614224624afefce6ee9b2b138cbc464)


# SwhDepositClient

A SoftwareHeritage Deposit API Client for PHP.

## License

This project is licensed under the MIT license. See [LICENSE.md](LICENSE.md) for details.

## Install

```
composer require dagstuhl/swh-deposit-client
```

## Example

```php
<?PHP
use Dagstuhl\SwhDepositClient\SwhDepositClient;
use Dagstuhl\SwhDepositClient\SwhDepositMetadata;

// Create a client with authorization parameters
$client = new SwhDepositClient("https://deposit.staging.swh.network/", "username", "password");

// Create the metadata of a deposit
$metadata = new SwhDepositMetadata();
$metadata->add("title", [], "Awesome Project");
$metadata->add("author", [], "Yannick Schillo");

// Import Codemeta-JSON
$codemetaJson = json_decode("...");
$metadata->importCodemetaJson($codemetaJson);
// Alternatively: $metadata = SwhDepositMetadata::fromCodemetaJson($codemetaJson);

$depositMetadata = $metadata->add("swhdeposit:deposit");
$createOrigin = $depositMetadata->add("swhdeposit:create_origin");
$createOrigin->add("swhdeposit:origin", [ "url" => "https://example.com/yannick-schillo/awesome-project/" ]);

// Open the archive
$archive = fopen("path/to/archive.zip", "r");
// In Laravel: $archive = Storage::readStream("path/to/archive.zip");

// Create the deposit
$res = $client->createDeposit("mycollection", true, $metadata, "application/zip", $archive);
$depositId = $res->getDepositId();

// Query the status of the deposit
$res = $client->getStatus("mycollection", $depositId);
var_dump($res->getDepositId());
var_dump($res->getDepositStatus());
var_dump($res->getDepositSwhId());
var_dump($res->getDepositSwhIdContext());
```
