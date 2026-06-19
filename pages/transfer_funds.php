<?php
// transfer_funds.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'debug' => []];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception('Invalid input.');

    $userId      = intval($input['user_id'] ?? 0);
    $fromAccount = intval($input['from_account'] ?? 0);
    $toAccount   = intval($input['to_account'] ?? 0);
    $amount      = floatval($input['amount'] ?? 0);
    $date        = $input['date'] ?? date('Y-m-d');

    if (!$userId || !$fromAccount || !$toAccount || $amount <= 0) {
        throw new Exception('Missing or invalid transfer data.');
    }

    $provinceId = 11;   // always 11
    $categoryId = 3;    // always 3
    $itemId     = 210;  // always 210
    $unitId     = 1;    // always 1 (each)

    // -------------------------
    // Get account names
    // -------------------------
    $stmtAcc = $pdo->prepare("SELECT AccountID, AccountName FROM accounts WHERE AccountID IN (:from, :to)");
    $stmtAcc->execute([':from' => $fromAccount, ':to' => $toAccount]);
    $accountsData = $stmtAcc->fetchAll(PDO::FETCH_KEY_PAIR); 
    // FETCH_KEY_PAIR gives [AccountID => AccountName]

    $fromName = $accountsData[$fromAccount] ?? "Account $fromAccount";
    $toName   = $accountsData[$toAccount] ?? "Account $toAccount";

    // Build nice comment
    $commentDebit  = "Transfer $amount to $toName";
    $commentCredit = "Transfer $amount from $fromName";

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO transactions
            (UserID, AccountID, TypeID, Date, Quantity, Price, Tax, Comment, ProvinceID, CategoryID, ItemID, UnitID)
        VALUES
            (:userId, :accountId, :typeId, :date, :quantity, :price, :tax, :comment, :provinceId, :categoryId, :itemId, :unitId)
    ");

    // Debit
    $debitParams = [
        ':userId'     => $userId,
        ':accountId'  => $fromAccount,
        ':typeId'     => 1,
        ':date'       => $date,
        ':quantity'   => 1,
        ':price'      => -1 * $amount,
        ':tax'        => 0,
        ':comment'    => $commentDebit,
        ':provinceId' => $provinceId,
        ':categoryId' => $categoryId,
        ':itemId'     => $itemId,
        ':unitId'     => $unitId
    ];
    $response['debug']['debit'] = $debitParams;
    $stmt->execute($debitParams);

    // Credit
    $creditParams = [
        ':userId'     => $userId,
        ':accountId'  => $toAccount,
        ':typeId'     => 1,
        ':date'       => $date,
        ':quantity'   => 1,
        ':price'      => $amount,
        ':tax'        => 0,
        ':comment'    => $commentCredit,
        ':provinceId' => $provinceId,
        ':categoryId' => $categoryId,
        ':itemId'     => $itemId,
        ':unitId'     => $unitId
    ];
    $response['debug']['credit'] = $creditParams;
    $stmt->execute($creditParams);

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Transfer completed successfully.';

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = $e->getMessage();
    $response['debug']['exception'] = $e->getMessage();
}

echo json_encode($response);
