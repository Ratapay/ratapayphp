<?php

require_once "../vendor/autoload.php";

$client = new \ratapay\ratapayphp\Client(101, 'abc', 'def', 'ghi');
$link = $client->linkAccount('user@mail.com', 'username');
echo "<pre>";
print_r($link);
echo "</pre>";
