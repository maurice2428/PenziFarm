<?php

header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| RECEIVE RAW MPESA PAYLOAD
|--------------------------------------------------------------------------
*/
$callbackData = file_get_contents('php://input');

/*
|--------------------------------------------------------------------------
| LOG RAW PAYLOAD
|--------------------------------------------------------------------------
*/
file_put_contents(
    __DIR__ . '/c2b-confirmation-log.json',
    date('Y-m-d H:i:s') . PHP_EOL .
    $callbackData . PHP_EOL . PHP_EOL,
    FILE_APPEND
);

/*
|--------------------------------------------------------------------------
| DECODE JSON
|--------------------------------------------------------------------------
*/
$data = json_decode($callbackData, true);

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
$host = 'localhost';
$dbname = 'YOUR_DATABASE_NAME';
$username = 'YOUR_DATABASE_USER';
$password = 'YOUR_DATABASE_PASSWORD';

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | EXTRACT MPESA DATA
    |--------------------------------------------------------------------------
    */
    $transactionType = $data['TransactionType'] ?? null;
    $transId = strtoupper(trim($data['TransID'] ?? ''));
    $transTime = $data['TransTime'] ?? null;
    $transAmount = (float) ($data['TransAmount'] ?? 0);

    $businessShortCode = $data['BusinessShortCode'] ?? null;

    $billRefNumber = trim($data['BillRefNumber'] ?? '');
    $invoiceNumber = $billRefNumber;

    $orgAccountBalance = $data['OrgAccountBalance'] ?? null;
    $thirdPartyTransId = $data['ThirdPartyTransID'] ?? null;

    $msisdn = $data['MSISDN'] ?? null;

    $firstName = $data['FirstName'] ?? null;
    $middleName = $data['MiddleName'] ?? null;
    $lastName = $data['LastName'] ?? null;

    /*
    |--------------------------------------------------------------------------
    | INSERT INTO DATABASE
    |--------------------------------------------------------------------------
    */
    $sql = "
        INSERT INTO mpesa_c2_b_transactions (
            transaction_type,
            trans_id,
            trans_time,
            trans_amount,
            business_short_code,
            bill_ref_number,
            invoice_number,
            org_account_balance,
            third_party_trans_id,
            phone_number,
            first_name,
            middle_name,
            last_name,
            status,
            payload,
            received_at,
            created_at,
            updated_at
        ) VALUES (
            :transaction_type,
            :trans_id,
            :trans_time,
            :trans_amount,
            :business_short_code,
            :bill_ref_number,
            :invoice_number,
            :org_account_balance,
            :third_party_trans_id,
            :phone_number,
            :first_name,
            :middle_name,
            :last_name,
            :status,
            :payload,
            NOW(),
            NOW(),
            NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':transaction_type' => $transactionType,
        ':trans_id' => $transId,
        ':trans_time' => $transTime,
        ':trans_amount' => $transAmount,
        ':business_short_code' => $businessShortCode,
        ':bill_ref_number' => $billRefNumber,
        ':invoice_number' => $invoiceNumber,
        ':org_account_balance' => $orgAccountBalance,
        ':third_party_trans_id' => $thirdPartyTransId,
        ':phone_number' => $msisdn,
        ':first_name' => $firstName,
        ':middle_name' => $middleName,
        ':last_name' => $lastName,
        ':status' => 'received',
        ':payload' => json_encode($data),
    ]);

} catch (Throwable $e) {

    file_put_contents(
        __DIR__ . '/c2b-errors.log',
        date('Y-m-d H:i:s') . PHP_EOL .
        $e->getMessage() . PHP_EOL . PHP_EOL,
        FILE_APPEND
    );
}

/*
|--------------------------------------------------------------------------
| RESPONSE TO SAFARICOM
|--------------------------------------------------------------------------
*/
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Confirmation Received Successfully',
]);
