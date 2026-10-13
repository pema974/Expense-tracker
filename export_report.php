<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];


/* GET SELECTED PERIOD */

$period = $_GET["period"] ?? "all";

$period_condition = "";

if ($period == "month") {

    $period_condition =
        " AND YEAR(transaction_date) = YEAR(CURDATE())
          AND MONTH(transaction_date) = MONTH(CURDATE())";

} elseif ($period == "30days") {

    $period_condition =
        " AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

} elseif ($period == "year") {

    $period_condition =
        " AND YEAR(transaction_date) = YEAR(CURDATE())";
}


/* CSV HEADERS */

header("Content-Type: text/csv");

header(
    "Content-Disposition: attachment; filename=expense-report.csv"
);


/* OPEN CSV OUTPUT */

$output = fopen("php://output", "w");


/* CSV COLUMN HEADERS */

fputcsv($output, [
    "Date",
    "Type",
    "Category",
    "Description",
    "Amount"
]);


/* GET TRANSACTIONS */

$stmt = $conn->prepare(
    "SELECT
        transactions.transaction_date,
        transactions.type,
        categories.name AS category_name,
        transactions.description,
        transactions.amount
     FROM transactions
     INNER JOIN categories
        ON transactions.category_id = categories.id
     WHERE transactions.user_id = ?"
     . $period_condition . "
     ORDER BY transactions.transaction_date DESC,
              transactions.id DESC"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


/* WRITE TRANSACTIONS TO CSV */

while ($transaction = $result->fetch_assoc()) {

    fputcsv($output, [

        $transaction["transaction_date"],

        ucfirst($transaction["type"]),

        $transaction["category_name"],

        $transaction["description"],

        number_format(
            $transaction["amount"],
            2,
            ".",
            ""
        )

    ]);
}


$stmt->close();

fclose($output);

exit();