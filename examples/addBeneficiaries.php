<?php

require_once "../vendor/autoload.php";

$items = array(
    [
        'id' => 'food1',
        'qty' => 1,
        'subtotal' => 25000,
        'name' => 'Special Fried Noodle',
        'type' => 'Noodle',
        'category' => "Noodle",
        'brand' => 'MyFood',
        'refundable' => true,
        'refund_threshold' => '1D'
    ],
    [
        'id' => 'drink1',
        'qty' => 1,
        'subtotal' => 10000,
        'name' => 'Sweet Tea',
        'type' => 'Tea',
        'category' => "Tea",
        'brand' => 'MyFood',
        'refundable' => true,
        'refund_threshold' => '1D'
    ],
    [
        'id' => 'delivery',
        'qty' => 1,
        'subtotal' => 15000,
        'name' => 'Delivery',
        'type' => 'Delivery',
        'category' => "Delivery",
        'brand' => 'Delivery',
        'refundable' => true,
        'refund_threshold' => '1D'
    ],
);

$beneficiaries = array(
    [
        'email' => 'jv1@mail.com',
        'name' => 'name',
        'username' => 'username',
        'share_amount' => 25000,
        'rebill_share_amount' => null,
        'share_item_id' => 'food1',
        'tier' => 1
    ],
    [
        'email' => 'jv2@mail.com',
        'name' => 'name',
        'username' => 'username',
        'share_amount' => 10000,
        'rebill_share_amount' => null,
        'share_item_id' => 'drink1',
        'tier' => 1
    ]
);

$invoice = new \ratapay\ratapayphp\Invoice([
    'email' => 'buyer@mail.com',
    'note' => 'Food Order #123',
    'invoice_id' => 'FO123',
    'amount' => 50000,
    'paysystem' => 'QRIS',
    'url_callback' => 'https://mysite.com/callback',
    'url_success' => 'https://mysite.com/success',
    'url_failed' => 'https://mysite.com/failed',
    'expired_time' => date('c', strtotime('+3 hours')),
    'refundable' => 1,
    'refund_threshold' => '1D',
]);

$invoice->addItems($items);
$invoice->addBeneficiaries($beneficiaries);

$client = new \ratapay\ratapayphp\Client(101, 'abc', 'def', 'ghi');
$invoice = $client->createTransaction($invoice);

// After some other process
// Add new beneficiaries

$newBeneficiaries = array(
    [
        'email' => 'driver1@mail.com',
        'name' => 'driver 1',
        'username' => 'driver1',
        'share_amount' => 15000,
        'share_item_id' => 'delivery',
        'tier' => 1
    ]
);
$res = $client->addBeneficiaries($invoice->data->ref, $newBeneficiaries);

echo "<pre>";
print_r($res);
echo "</pre>";