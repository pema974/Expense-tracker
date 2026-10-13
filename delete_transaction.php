<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: transactions.php");
    exit();
}

$transaction_id = $_POST["transaction_id"] ?? 0;

if (!is_numeric($transaction_id) || $transaction_id <= 0) {
    header("Location: transactions.php");
    exit();
}

$stmt = $conn->prepare(
    "DELETE FROM transactions
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $transaction_id,
    $user_id
);

$stmt->execute();

$stmt->close();

header("Location: transactions.php");
exit();

?>