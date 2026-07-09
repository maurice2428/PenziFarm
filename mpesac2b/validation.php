<?php

header('Content-Type: application/json');

$callbackData = file_get_contents('php://input');

/*
|--------------------------------------------------------------------------
| OPTIONAL LOGGING
|--------------------------------------------------------------------------
*/
file_put_contents(
    __DIR__ . '/c2b-validation-log.json',
    date('Y-m-d H:i:s') . PHP_EOL .
    $callbackData . PHP_EOL . PHP_EOL,
    FILE_APPEND
);

/*
|--------------------------------------------------------------------------
| RESPONSE TO SAFARICOM
|--------------------------------------------------------------------------
*/
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Accepted',
]);
