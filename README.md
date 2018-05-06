# NZ Post Address Checker API client
This API client can help you to operate with NZ Post Address Checker API. You can use any PSR-16 Simple Cache with it 
to save your money on look ups number. 
## Installation
```composer require dshumkov/nzpost-client```

## Examples
The Address Checker API allows you to autocomplete and check New Zealand addresses and postcodes. It can be used within web forms or mobile apps. It’s backed by New Zealand Post’s National Postal Address Database (NPAD).

Firstly you have to get registered account https://www.nzpost.co.nz/business/developer-centre#data
And create new application there to get API credentials. 
### Auth and get the client instance 
```php
use DShumkov\NzPostClient\NzPostClient;

$clientID = 'NZPOST_CLIENT_ID';
$secret = 'NZPOST_CLIENT_SECRET';

$Client = new NzPostClient($clientID, $secret);
```

### Auth and get the client instance with PSR-16 Simple Cache
```php
use Symfony\Component\Cache\Simple\FilesystemCache;
use DShumkov\NzPostClient\NzPostClient;

$cache = new FilesystemCache();
$ttl = 31536000; // One year cache TTL

$clientID = 'NZPOST_CLIENT_ID';
$secret = 'NZPOST_CLIENT_SECRET';

$Client = new NzPostClient($clientID, $secret, $cache);
$Client->setTTL($ttl);
```
### Get suggested addresses
```php
$query = '1 Queen street';
$suggestedAddresses = $Client->suggest($query);
```
### Find an address
```php
$addressLines = [
    '1 Queen street',
    'CBD',
    'Auckland'
 ];
 $addresses = $Client->find($addressLines);
```
### Get address details by DPID
Making calls to the address details services requires passing a DPID. This can be found by calling either the find or suggest resources which return matches including DPID's.
```php
$dpid='3111226';
$addressDetails = $Client->details($dpid);
```

### The suggest partial addresses.
The suggest partial addresses service takes a partial address query and turns a list of partial address matches. 
```php
$query = 'queen';
$suggestedAddresses = $Client->suggestPartial($query);
```
### The partial address details service.
The partial address details service takes a partial's unique_id and returns detailed information about the matching partial address.
```php
$uniqId = 82868;
$response = $Client->partialDetails($uniqId);
```
