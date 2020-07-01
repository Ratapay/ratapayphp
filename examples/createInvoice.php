<?php

require_once "../vendor/autoload.php";

$item = new \ratapay\ratapayphp\Item([
    'id' => 'item1',
    'qty' => 1,
    'subtotal' => 100000,
    'name' => 'Item 1',
    'type' => 'Digital',
    'category' => "cat",
    'brand' => 'brd',
    'refundable' => true,
    'refund_threshold' => '7D'
]);

$item2 = new \ratapay\ratapayphp\Item([
    'id' => 'item2',
    'qty' => 1,
    'subtotal' => 150000,
    'name' => 'Item 2',
    'type' => 'Digital',
    'category' => "cat",
    'brand' => 'brd',
    'refundable' => true,
    'refund_threshold' => '8D'
]);

$beneficiary = new \ratapay\ratapayphp\Beneficiary([
    'email' => 'jv1@mail.com',
    'name' => 'name',
    'username' => 'username',
    'share_amount' => 50000,
    'rebill_share_amount' => null,
    'share_item_id' => 'item1',
    'tier' => 1
]);

$beneficiary2 = new \ratapay\ratapayphp\Beneficiary([
    'email' => 'jv2@mail.com',
    'name' => 'name',
    'username' => 'username',
    'share_amount' => 50000,
    'rebill_share_amount' => null,
    'share_item_id' => 'item2',
    'tier' => 1
]);

$invoice = new \ratapay\ratapayphp\Invoice([
    'email' => 'buyer@mail.com',
    'note' => 'Tes Inv 1',
    'invoice_id' => 'invt2',
    'amount' => 250000,
    'url_callback' => 'https://mysite.com/callback',
    'url_success' => 'https://mysite.com/success',
    'url_failed' => 'https://mysite.com/failed'
]);

$invoice->addItem($item);
$invoice->addItem($item2);
$invoice->addBeneficiary($beneficiary);
$invoice->addBeneficiary($beneficiary2);

$client = new \ratapay\ratapayphp\Client(101, 'abc', 'def', 'ghi');
$invoice = $client->createInvoice($invoice);
echo $invoice->payment_url;
// header('Location: ' . $invoice->payment_url);
