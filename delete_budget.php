<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];


/* ONLY ACCEPT POST */

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: budgets.php");
    exit();
}


/* GET BUDGET ID */

$budget_id = (int) ($_POST["id"] ?? 0);

if ($budget_id <= 0) {
    header("Location: budgets.php");
    exit();
}


/* DELETE USER'S BUDGET */

$stmt = $conn->prepare(
    "DELETE FROM budgets
     WHERE id = ?
     AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $budget_id,
    $user_id
);

$stmt->execute();

$stmt->close();


/* RETURN TO BUDGETS */

header("Location: budgets.php");
exit();