<?php

require_once "../vendor/autoload.php";

$client = new \ratapay\ratapayphp\Client(101, 'abc', 'def', 'ghi');
$acc = $client->registerAccount(
    [
        'email' => 'user@mail.com',
        'name' => 'user name',
        'password' => 'user password'
    ]
);
echo "<pre>";
print_r($acc);
echo "</pre>";
