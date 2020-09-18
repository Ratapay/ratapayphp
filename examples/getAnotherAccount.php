<?php

require_once "../vendor/autoload.php";

$client = new \ratapay\ratapayphp\Client(101, 'abc', 'def', 'ghi');
$acc = $client->getAccount(
    [
        'email' => 'user@mail.com'
    ]
);
echo "<pre>";
print_r($acc);
echo "</pre>";
