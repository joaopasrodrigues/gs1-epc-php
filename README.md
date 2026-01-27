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


# Using the code:

```   
composer require joaopasrodrigues/gs1-epc-php
```



#Basic usage

##Decoding EPCs
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


##Encoding



###How to Use the Factory

######PHP
```
use Gs1Epc\Epc;

// Encode an SGTIN (GTIN-14)
$hexSgtin = Epc::fromBarcode('01', '00614141123452', '6789', 7);
echo "SGTIN Hex: $hexSgtin\n";

// Encode an SSCC
$hexSscc = Epc::fromBarcode('00', '106141411234567890', '0', 7);
echo "SSCC Hex: $hexSscc\n";

```
######Summary of Logic
Centralized: One class (Epc) to rule them all.

Smart Parsing: Handles the weirdness of SSCC (Extension Digit) and GRAI internally.

Standards Compliant: Uses the 96-bit partition tables for all major GS1 keys.

####
##Summary Table of Schemes used for encoding
|Scheme |GS1 Key|Binary Header|Reference Part|Serial Part|
|----------|----------|-------|--------------|------|
|SGTIN-96|GTIN|0x30|Item Reference|38 bits|
|SSCC-96|SSCC|0x31|Serial Reference|24 bits (zeros at end)|
|SGLN-96|GLN|0x32|Location Reference|38 bits (Extension)|
|GRAI-96|GRAI|0x33|Asset Type|38 bits|
|GIAI-96|GIAI|0x34|Individual Asset Ref|Variable (up to 82 bits)|


#Development & Tests
- Run tests with PHPUnit (composer require --dev phpunit/phpunit and then run phpunit or vendor/bin/phpunit)













