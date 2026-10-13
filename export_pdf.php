<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require "vendor/autoload.php";

include "db.php";

use Dompdf\Dompdf;

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


/* CALCULATE TOTALS */

$total_income = 0;
$total_expenses = 0;


/* BUILD TABLE ROWS */

$rows = "";

while ($transaction = $result->fetch_assoc()) {

    if ($transaction["type"] == "income") {

        $total_income += $transaction["amount"];

    } else {

        $total_expenses += $transaction["amount"];
    }

    $rows .= "
        <tr>

            <td>
                " . htmlspecialchars(
                    $transaction["transaction_date"]
                ) . "
            </td>

            <td>
                " . htmlspecialchars(
                    ucfirst($transaction["type"])
                ) . "
            </td>

            <td>
                " . htmlspecialchars(
                    $transaction["category_name"]
                ) . "
            </td>

            <td>
                " . htmlspecialchars(
                    $transaction["description"]
                ) . "
            </td>

            <td class='amount'>
                Rs. " . number_format(
                    $transaction["amount"],
                    2
                ) . "
            </td>

        </tr>
    ";
}


$stmt->close();

$balance = $total_income - $total_expenses;


/* PERIOD NAME */

$period_name = "All Time";

if ($period == "month") {

    $period_name = "This Month";

} elseif ($period == "30days") {

    $period_name = "Last 30 Days";

} elseif ($period == "year") {

    $period_name = "This Year";
}


/* PDF HTML */

$html = "

<!DOCTYPE html>

<html>

<head>

    <meta charset='UTF-8'>

    <style>

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333333;
        }

        h1 {
            text-align: center;
            margin-bottom: 5px;
        }

        .period {
            text-align: center;
            color: #666666;
            margin-bottom: 25px;
        }

        .summary {
            width: 100%;
            margin-bottom: 25px;
        }

        .summary td {
            width: 33.33%;
            padding: 12px;
            text-align: center;
            border: 1px solid #dddddd;
        }

        .summary-title {
            font-size: 11px;
            color: #666666;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }

        table.transactions {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions th {
            background: #111827;
            color: white;
            padding: 8px;
            text-align: left;
        }

        .transactions td {
            border: 1px solid #dddddd;
            padding: 7px;
        }

        .amount {
            text-align: right;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            color: #777777;
            font-size: 10px;
        }

    </style>

</head>

<body>

    <h1>ExpenseTracker</h1>

    <div class='period'>
        Financial Report — " . $period_name . "
    </div>


    <table class='summary'>

        <tr>

            <td>

                <div class='summary-title'>
                    Total Income
                </div>

                <div class='summary-value'>
                    Rs. " . number_format(
                        $total_income,
                        2
                    ) . "
                </div>

            </td>


            <td>

                <div class='summary-title'>
                    Total Expenses
                </div>

                <div class='summary-value'>
                    Rs. " . number_format(
                        $total_expenses,
                        2
                    ) . "
                </div>

            </td>


            <td>

                <div class='summary-title'>
                    Balance
                </div>

                <div class='summary-value'>
                    Rs. " . number_format(
                        $balance,
                        2
                    ) . "
                </div>

            </td>

        </tr>

    </table>


    <table class='transactions'>

        <thead>

            <tr>

                <th>Date</th>

                <th>Type</th>

                <th>Category</th>

                <th>Description</th>

                <th>Amount</th>

            </tr>

        </thead>

        <tbody>

            " . $rows . "

        </tbody>

    </table>


    <div class='footer'>
        Generated by ExpenseTracker
    </div>

</body>

</html>
";


/* CREATE PDF */

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper("A4", "portrait");

$dompdf->render();


/* DOWNLOAD PDF */

$dompdf->stream(
    "expense-report.pdf",
    [
        "Attachment" => true
    ]
);

exit();