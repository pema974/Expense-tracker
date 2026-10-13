<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];


/* ONLY ALLOW POST */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: transactions.php");
    exit();
}


/* GET FORM DATA */

$transaction_id = $_POST["transaction_id"] ?? 0;
$category_id = $_POST["category_id"] ?? 0;
$type = $_POST["type"] ?? "";
$amount = $_POST["amount"] ?? "";
$description = trim($_POST["description"] ?? "");
$transaction_date = $_POST["transaction_date"] ?? "";


/* VALIDATION */

if (
    !is_numeric($transaction_id) ||
    $transaction_id <= 0 ||
    empty($category_id) ||
    empty($type) ||
    empty($amount) ||
    empty($transaction_date)
) {
    die("Please fill in all required fields.");
}


if (!in_array($type, ["income", "expense"])) {
    die("Invalid transaction type.");
}


if (!is_numeric($amount) || $amount <= 0) {
    die("Please enter a valid amount.");
}


/* CHECK TRANSACTION BELONGS TO USER */

$check = $conn->prepare(
    "SELECT id
     FROM transactions
     WHERE id = ? AND user_id = ?"
);

$check->bind_param(
    "ii",
    $transaction_id,
    $user_id
);

$check->execute();

$result = $check->get_result();

if ($result->num_rows == 0) {
    $check->close();

    die("Transaction not found.");
}

$check->close();


/* CHECK CATEGORY BELONGS TO USER */

$check = $conn->prepare(
    "SELECT id
     FROM categories
     WHERE id = ? AND user_id = ?"
);

$check->bind_param(
    "ii",
    $category_id,
    $user_id
);

$check->execute();

$result = $check->get_result();

if ($result->num_rows == 0) {
    $check->close();

    die("Invalid category.");
}

$check->close();


/* UPDATE TRANSACTION */

$stmt = $conn->prepare(
    "UPDATE transactions
     SET
        category_id = ?,
        type = ?,
        amount = ?,
        description = ?,
        transaction_date = ?
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "isdssii",
    $category_id,
    $type,
    $amount,
    $description,
    $transaction_date,
    $transaction_id,
    $user_id
);


if ($stmt->execute()) {

    $stmt->close();

    header("Location: transactions.php");
    exit();

} else {

    $stmt->close();

    die("Failed to update transaction.");
}

?>