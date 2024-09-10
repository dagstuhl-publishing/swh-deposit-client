# SwhDepositClient

A SoftwareHeritage Deposit API Client for PHP.

## License

This project is licensed under the MIT license. See [LICENSE.md](LICENSE.md) for details.

## Install

TODO

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

// Import CodeMeta-JSON
$codemetaJson = json_decode("...");
$metadata->importCodeMetaJson($codemetaJson);
// Alternatively: $metadata = SwhDepositMetadata::fromCodeMetaJson($codemetaJson);

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
