# Ratapay PHP Library

A simple library to integrate laravel with your PHP Code.
Be aware that this library is in the very early stage of development, only the most fundamental features available and no thorough test has been carried out to ensure reliable functionality.

## Install

Via Composer

``` bash
$ composer require ratapay/ratapayphp
```
Thn require the autoload in your project

``` php
require_once "vendor/autoload.php";
```

## Usage

### __1. Keys and Secrets__

Before you can use this library, you need to have a Ratapay account with at least Personal level Account.

The Keys and Secrets can be obtained from the account page -> profile -> view credentials menu

### __2. Invoice__

Invoice is the primary requirement to create transaction in Ratapay. The transaction will be created with just the invoice being defined.

Instantiated with

``` php
$invoice = new \ratapay\ratapayphp\Invoice($data);
```

where <code>$data</code> is an array with key value format containing the data defined in the properties below

__Properties__

| Property         | type    | Required | Length   | Default | Note                                                                              |
|------------------|---------|----------|----------|---------|-----------------------------------------------------------------------------------|
| note             | String  | Y        | 255 Char | Null    | General note of the invoice                                                       |
| email            | String  | Y        | 64 Char  | Null    | Payer email address                                                               |
| invoice_id       | String  | Y        | 25 Char  | Null    | Merchant invoice ID                                                               |
| amount           | Integer | Y        | 8 Bytes  | Null    | Payment amount                                                                    |
| second_amount    | Integer | N        | 8 Bytes  | 0       | Total amount of each recurring payment                                            |
| first_period     | String  | N        | 4 Char   | Null    | Period between the initial payment with the first recurring payment               |
| second_period    | String  | N        | 4 Char   | Null    | Period between each recurring payment                                             |
| rebill_times     | Integer | N        | 4 Bytes  | 0       | How many recurring payment will occur                                             |
| refundable       | Boolean | N        | 1 Byte   | False   | Is the invoice will be refundable                                                 |
| refund_threshold | String  | N        | 4 Char   | Null    | How long will the invoice be refundable after successful payment                  |
| url_callback     | String  | N        | 255 Char | Null    | Merchant URL where Ratapay will send notification data about the invoice activity |
| url_success      | String  | N        | 255 Char | Null    | URL where user will be redirected after succesful payment                         |
| url_failed       | String  | N        | 255 Char | Null    | URL where user will ber redirected if the payment failed or cancelled             |

The first_period, second_period, and refund_threshold use a period format defined as:

[1-9][D/M/Y]

where the first part is the nominator and the second part is the unit in either day (D), month (M), or Year (Y)

for example a 7 day period would be 7D

__Method__

| Method           | Parameter          | Return                      | Note                                                                                   |
|------------------|--------------------|-----------------------------|----------------------------------------------------------------------------------------|
| addItem          | Item Object        | Boolean                     | Add Item to Invoice                                                                    |
| clearItem        | None               | Boolean                     | Clear Invoice items                                                                    |
| addBeneficiary   | Beneficiary Object | Boolean                     | Add Beneficiary to Invoice                                                             |
| clearBeneficiary | None               | Boolean                     | Clear Invoice beneficiaries                                                            |
| getAmount        | String             | Integer                     | Get Invoice amount, use 'first' as parameter for amount and 'second' for second_amount |
| getBeneficiaries | None               | Array of Beneficiary Object | Get all Invoice beneficiaries                                                          |
| getItems         | None               | Array of Item Object        | Get all Invoice items                                                                  |
| payload          | None               | Array of Mixed Data         | Generate array formatted Invoice data that will be submitted                           |

### __3. Item__

Item defines the content of the invoice in more detail.

Instantiated with

``` php
$item = new \ratapay\ratapayphp\Item($data);
```

where <code>$data</code> is an array with key value format containing the data defined in the properties below

__Properties__

| Property         | Type    | Required | Length   | Default | Note                                         |
|------------------|---------|----------|----------|---------|----------------------------------------------|
| id               | String  | Y        | 32 Char  | Null    | ID of the item in merchant system            |
| qty              | Integer | Y        | 4 Bytes  | 1       | Item quantity in the invoice                 |
| subtotal         | Integer | Y        | 8 Bytes  | Null    | Subtotal amount of the item with all the qty |
| name             | String  | Y        | 128 Char | Null    | Item Name                                    |
| type             | String  | N        | 64 Char  | Null    | Item Type                                    |
| category         | String  | N        | 64 Char  | Null    | Item Category                                |
| brand            | String  | N        | 64 Char  | Null    | Item Brand                                   |
| refundable       | Boolean | N        | 1 Byte   | false   | Is Item Refundable                           |
| refund_threshold | String  | N        | 4 Char   | Null    | Item Refund Period                           |

### __4. Beneficiary__

Beneficiary defines who will get the share from the invoice transaction.

Instantiated with

``` php
$beneficiary = new \ratapay\ratapayphp\Beneficiary($data);
```

where <code>$data</code> is an array with key value format containing the data defined in the properties below

__Properties__

| Property            | Type    | Required    | Length  | Default | Note                                                                  |
|---------------------|---------|-------------|---------|---------|-----------------------------------------------------------------------|
| email               | String  | Y           | 64 Char | Null    | Beneficary Email                                                      |
| name                | String  | Y           | 64 Char | Null    | Beneficary Name                                                       |
| username            | String  | N           | 64 Char | Null    | Beneficary Username in merchant system                                |
| share_amount        | Integer | Y           | 8 Bytes | Null    | Beneficary share on payment                                           |
| rebill_share_amount | Integer | N           | 8 Bytes | Null    | Beneficary share on recurring payment                                 |
| share_item_id       | Integer | Conditional | 4 Bytes | Null    | item_id related to this share, required if item is defined in invoice |

### __5. Client__

Client will process the invoice to Ratapay

Instantiated with

``` php
$client = new \ratapay\ratapayphp\Client($merchant_id, $merchant_secret, $api_key, $api_secret, $sandbox);
```

Data on each parameter except <code>$sandbox</code> can be obtained from point 1. Keys and Secrets.

Whereas the <code>$sandbox</code> is a flag to define wether to use sandbox mode or not, default is true.

To process the transaction creation just do

``` php
$result = $client->createTransaction($invoice);
```

Where <code>$invoice</code> is an invoice object which already been defined before

The <code>$result</code> will be an object which contain data as follow:

1. status: success or failed, indicating transaction creation result status
2. message: an error message of the reason why the transaction creation failed, only available if status is failed
3. payment_url: url for the payment process for the payer, only available if status is success
4. data: array of string indicating which invoice that the transaction is based on, containing: invoice_id, note, and ref as the reference number from Ratapay, only available if status is success

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/ratapay/ratapay-php/blob/master/CONTRIBUTING.md) for details.

## Credits

- [Ratapay](https://github.com/ratapay)
- [All Contributors](https://github.com/ratapay/ratapay-php/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
