```markdown
# GS1 EPC PHP Decoder

Pure-PHP GS1 EPC decoder library (SGTIN‑96 focused, extendable).

Features
- Decode EPC provided as hex or raw bytes into structured fields.
- Convert decoded SGTIN into EPC URN (urn:epc:id:sgtin:...) and GTIN-14 string.
- Partition table handling and GTIN check digit calculation.
- Small, extendable architecture for adding other EPC schemes (SSCC, GRAI, etc.)

Installation (from Packagist — after you publish)
- Require the package:
  composer require joaopasrodrigues/gs1-epc-php

Basic usage
```php
<?php
require 'vendor/autoload.php';

use Epc\Decoder;

$hex = '3034257BF7194E4000001A85';
$decoder = new Decoder();
$result = $decoder->decodeHex($hex);

print_r($result);
```

Example output (example):
```
Array
(
    [scheme] => sgtin-96
    [header] => 48
    [filter] => 1
    [partition] => 5
    [company_prefix] => 0614141
    [item_reference] => 012345
    [serial] => 400
    [urn] => urn:epc:id:sgtin:0614141.012345.400
    [gtin14] => 00614141012345
)
```

Development & Tests
- Run tests with PHPUnit (composer require --dev phpunit/phpunit and then run phpunit or vendor/bin/phpunit)
- Add additional scheme decoders under `src/Schemes/`.

Publishing instructions
1. Create a GitHub repo `joaopasrodrigues/gs1-epc-php` and push these files.
2. (Optional) Register the repository on Packagist (https://packagist.org/) to make it installable via composer require.
3. When published on Packagist, users can add it to their project using:
   composer require joaopasrodrigues/gs1-epc-php

Notes
- Current implementation focuses on SGTIN-96 (header 0x30). Adding more schemes is straightforward: add a new class under `src/Schemes/` and register the header handling in `src/Decoder.php`.
- Ensure you add official test vectors for complete validation before production use.
```