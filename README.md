# Ratapay PHP Library

A simple library to integrate laravel with your PHP Code.
Be aware that this library is in the very early stage of development, only the most fundamental features available and no thorough test has been carried out to ensure reliable functionality.

## Install

Via Composer

``` bash
$ composer require ratapay/ratapayphp
```
Then require the autoload in your project

``` php
require_once "vendor/autoload.php";
```

The above require usually not necessary when using a framework such as Laravel. As the framework already has their package loader in place.

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
| name             | String  | N        | 64 Char  | Null    | Payer name                                                               |
| paysystem        | String  | N        | 16 Char  | Null    | Payment System ID which will be used for this invoice                             |
| second_amount    | Integer | N        | 8 Bytes  | 0       | Total amount of each recurring payment                                            |
| first_period     | String  | N        | 4 Char   | Null    | Period between the initial payment with the first recurring payment               |
| second_period    | String  | N        | 4 Char   | Null    | Period between each recurring payment                                             |
| rebill_times     | Integer | N        | 4 Bytes  | 0       | How many recurring payment will occur                                             |
| refundable       | Boolean | N        | 1 Byte   | False   | Is the invoice will be refundable                                                 |
| refund_threshold | String  | N        | 4 Char   | Null    | How long will the invoice be refundable after successful payment                  |
| url_callback     | String  | N        | 255 Char | Null    | Merchant URL where Ratapay will send notification data about the invoice activity |
| url_success      | String  | N        | 255 Char | Null    | URL where user will be redirected after succesful payment                         |
| url_failed       | String  | N        | 255 Char | Null    | URL where user will ber redirected if the payment failed or cancelled             |
| expired_time       | String  | N        | 32 Char | Null    | Time the invoice expired in iso 8601 format, e.g. 2022-01-01T10:00:00+07:00|

The first_period, second_period, and refund_threshold use a period format defined as:

[1-9][D/M/Y]

where the first part is the nominator and the second part is the unit in either day (D), month (M), or Year (Y)

for example a 7 day period would be 7D

To see the list of available Payment System ID (paysystem) send a GET request to https://api.ratapay.co.id/v2/gateway/list for production or https://dev.ratapay.co.id/v2/gateway/list for sandbox

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

Client will process the request to Ratapay

Instantiated with

``` php
$client = new \ratapay\ratapayphp\Client($merchant_id, $merchant_secret, $api_key, $api_secret, $sandbox);
```

Data on each parameter except <code>$sandbox</code> can be obtained from point 1. Keys and Secrets.

Whereas the <code>$sandbox</code> is a flag to define wether to use sandbox mode or not, default is true.

#### __A. Creating Transaction__

``` php
$result = $client->createTransaction($invoice);
```

Where <code>$invoice</code> is an invoice object which already been defined before

The <code>$result</code> will be an object which contain data as follow:

1. <code>status</code> : success or failed, indicating transaction creation result status
2. <code>message</code> : an error message of the reason why the transaction creation failed, only available if status is failed
3. <code>payment_url</code> : url for the payment process for the payer, only available if status is success
4. <code>data</code> : array of string indicating which invoice that the transaction is based on, containing: invoice_id, note, and ref as the reference number from Ratapay, only available if status is success

#### __B. Listing Transaction__

``` php
$result = $client->listTransaction($reference = '', $invoice_id = '', $creation_time = [], $paid_time = [], $offset = 0, $limit = 5);
```
| Property            | Type    | Required    |  Default | Note   |
|---------------------|---------|-------------|--------- |--------|
| reference           | String  | N           | ''       | Transaction reference code |
| invoice_id          | String  | N           | ''       | Merchant Invoice ID |
| creation_time       | String  | N           | []       | range of transaction creation time in seconds [start_time, end_time] example [1622540704, 1623663904]|
| paid_time           | Integer | N           | []     | range of transaction paid time in seconds [start_time, end_time] example [1622540704, 1623663904]|
| offset              | Integer | N           | 0     | listing offset|
| limit               | Integer | N           | 5     | listing limit, maximum 30 |

The <code>$result</code> will be an object which contain data as follow:

1. <code>status</code> : success or failed, indicating transaction listing result status
2. <code>list</code> : list of retrieved transaction data
3. <code>count</code> : total count of transaction records with specified conditions
4. <code>totalAmount</code> : total amount of transaction records retrieved

#### __C. Execute Split__
Normally, the refundable invoice fund will be split automatically if it is already past the refund threshold. However, if there is need to split it earlier, this function can be used.

``` php
$result = $client->confirmSplit($reference, $item_ids = []);
```
| Property            | Type    | Required    |  Default | Note   |
|---------------------|---------|-------------|--------- |--------|
| reference           | String  | Y           | ''       | Transaction reference code |
| item_ids           | Array  | N           | []       | List of specific invoice item id to be splitted |

If item_ids is specified, only item matched item will be splitted.

#### __D. Extend Refund__
It can be used to extend the refund threshold.

``` php
$result = $client->extendRefund($reference, $period, $item_ids = []);
```
| Property            | Type    | Required    |  Default | Note   |
|---------------------|---------|-------------|--------- |--------|
| reference           | String  | Y           | ''       | Transaction reference code |
| period           | String  | Y           | ''       | How long is the extension, format [1-9][D/M/Y], e.g. 7D |
| item_ids           | Array  | N           | []       | List of specific invoice item id to extend its refund threshold |

If item_ids is specified, only item matched item will have its refund threshold extended.

#### __E. Execute Refund__
It can be used to execute refund before refund threshold.

``` php
$result = $client->confirmRefund($reference, $params);
```
| Property            | Type    | Required    |  Default | Note   |
|---------------------|---------|-------------|--------- |--------|
| reference           | String  | Y           | ''       | Transaction reference code |
| params           | Array  | N           | null       | Specify refund rules |

If doing full refund, the params is not used. But if doing partial refund, the params should be filled using these structures:
- params structure to refund partially for the whole invoice
``` php
params = [
        [0] => [
            'type' => {% or $}
            'value' => {value}
        ]
]
```

- params structure to refund partially for each specific item
``` php
params = [
        {item_id_1} => [
            'type' => {% or $} // optional, default $, will be ignored if specified but using qty
            'value' => {value}, // required if no qty specified
            'qty' => {qty} // required if no value specified
        ]
        {item_id_n} => [
            'type' => {% or $}, // optional, default $, will be ignored if specified but using qty
            'value' => {value}, // required if no qty specified
            'qty' => {qty} // required if no value specified
        ]
]
```

#### __F. Getting Own Account Info__

``` php
$result = $client->getAccount();
```

The <code>$result</code> will be an object which contain data as follow:

1. <code>status</code> : success or failed, indicating account info fetching status
2. <code>account</code> : account info details

#### __G. Getting Another Account Info__

``` php
$result = $client->getAccount(['email' => $email]);
```
The <code>$result</code> will be an object which contain data as follow:

1. <code>status</code> : success or failed, indicating account info fetching status
2. <code>account</code> : account info details if request status is success
3. <code>error</code> : short info about the error
3. <code>message</code> : long error message info

If the <code>error</code> is 'none' or 'waiting' then you have to ask user consent to link their account to your merchant data by executing following method

``` php
$result = $client->linkAccount($email, $username);
```
<code>$username</code> is optional, it can be used to track the user if your system identify user by their username and the user change their email.
The <code>$result</code> will be an object which contain data as follow:

1. <code>status</code> : success or failed, indicating account info fetching status
2. <code>link</code> : link to ratapay consent page to approve the linkage
3. <code>error</code> : short info about the error
3. <code>message</code> : long error message info

## Sandbox

When using sandbox mode, the payment can be simulated by visiting [Sandbox Payment Simulation Page](https://dev.ratapay.co.id/simulate).

Enter the payment reference number then click pay to simulate the transaction payment.

The default payment method will use Ratapay balance, so if the tested account did not have balance in their account, the payment will fail. Hence, open the payment instruction first by visiting <code>payment_url</code> defined in transaction creation result, then choose the preferred payment method before attempting to simulate the payment.

## Callback

Ratapay sends a POST callback on certain event, one of those is a succesful payment, which contains these data

| property | type   | note                                 |
|----------|--------|--------------------------------------|
| data     | String | A json encoded data of the callback  |
| hash     | String | Hash of the data                     |

To verify the data againts the hash, use <code>hash_hmac</code> function with sha256 algo and merchant secret as the hash key.

example:

```php
$valid = hash_equals($_POST['hash'], hash_hmac('sha256', $_POST['data'], $merchant_key));
```

The data contains following information
| Property       | Type    | Note                                        |
|----------------|---------|---------------------------------------------|
| action         | Integer | Callback action type = 1                    |
| invoice_id     | String  | Invoice ID from merchant system             |
| paysystem      | String  | Payment channel used to pay the transaction |
| amount         | Integer | Amount of payment                           |
| unique_code    | Integer | Unique code applied to payment              |
| gateway_charge | Integer | Gateway charge applied to payment           |
| merchant_id    | Integer | Merchant ID                                 |
| ref            | String  | Transaction reference number                |

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
