<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";
$user_id = $_SESSION["user_id"];
$period = $_GET["period"] ?? "all";
$period_condition = "";
$period_name = "All Time";
$period_description = "All your recorded transactions.";

if ($period == "month") {

    $period_name = "This Month";
    $period_description = "Your transactions for the current month.";

} elseif ($period == "30days") {

    $period_name = "Last 30 Days";
    $period_description = "Your transactions from the last 30 days.";

} elseif ($period == "year") {

    $period_name = "This Year";
    $period_description = "Your transactions for the current year.";
}

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


/* ================================
   TOTAL INCOME
================================ */
$income_stmt = $conn->prepare(

    "SELECT COALESCE(SUM(amount), 0) AS total_income

     FROM transactions

     WHERE user_id = ?

     AND type = 'income'"
     . $period_condition

);

$income_stmt->bind_param(
    "i",
    $user_id
);

$income_stmt->execute();

$income_result = $income_stmt->get_result();

$income_row = $income_result->fetch_assoc();

$total_income = $income_row["total_income"];

$income_stmt->close();


/* ================================
   TOTAL EXPENSES
================================ */

$expense_stmt = $conn->prepare(

    "SELECT COALESCE(SUM(amount), 0) AS total_expenses

     FROM transactions

     WHERE user_id = ?

     AND type = 'expense'"
     . $period_condition

);
$expense_stmt->bind_param(
    "i",
    $user_id
);

$expense_stmt->execute();

$expense_result = $expense_stmt->get_result();

$expense_row = $expense_result->fetch_assoc();

$total_expenses = $expense_row["total_expenses"];

$expense_stmt->close();


/* ================================
   BALANCE
================================ */

$balance = $total_income - $total_expenses;


/* ================================
   EXPENSES BY CATEGORY
================================ */

$category_stmt = $conn->prepare(
    "SELECT
        categories.name AS category_name,
        SUM(transactions.amount) AS total
     FROM transactions
     INNER JOIN categories
        ON transactions.category_id = categories.id
     WHERE transactions.user_id = ?
     AND transactions.type = 'expense'"
     . $period_condition . "
     GROUP BY categories.id, categories.name
     ORDER BY total DESC"
);

$category_stmt->bind_param(
    "i",
    $user_id
);

$category_stmt->execute();

$category_expenses = $category_stmt->get_result();

$category_stmt->close();


/* ================================
   MONTHLY EXPENSES
================================ */

$monthly_stmt = $conn->prepare(
    "SELECT
        DATE_FORMAT(transaction_date, '%Y-%m') AS month,
        SUM(amount) AS total
     FROM transactions
     WHERE user_id = ?
     AND type = 'expense'"
     . $period_condition . "
     GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
     ORDER BY month DESC
     LIMIT 12"
);

$monthly_stmt->bind_param(
    "i",
    $user_id
);

$monthly_stmt->execute();

$monthly_expenses = $monthly_stmt->get_result();

$monthly_stmt->close();

/* ================================
   CHART DATA
================================ */

$chart_stmt = $conn->prepare(
    "SELECT
        categories.name AS category_name,
        SUM(transactions.amount) AS total
     FROM transactions
     INNER JOIN categories
        ON transactions.category_id = categories.id
     WHERE transactions.user_id = ?
     AND transactions.type = 'expense'"
     . $period_condition . "
     GROUP BY categories.id, categories.name
     ORDER BY total DESC"
);

$chart_stmt->bind_param(
    "i",
    $user_id
);

$chart_stmt->execute();

$chart_result = $chart_stmt->get_result();

$chart_labels = [];
$chart_values = [];

while ($chart = $chart_result->fetch_assoc()) {

    $chart_labels[] = $chart["category_name"];

    $chart_values[] = (float) $chart["total"];
}

$chart_stmt->close();


/* ================================
   PAGE HEADER
================================ */

$page_title = "Reports";

include "includes/header.php";

?>

<link rel="stylesheet" href="css/reports.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<main class="reports-container">


    <!-- PAGE HEADER -->

  <div class="dashboard-header">

    <h1>
        Financial Report — <?= htmlspecialchars($period_name) ?>
    </h1>

    <p>
        <?= htmlspecialchars($period_description) ?>
    </p>

</div>

<div class="report-actions">

    <div class="report-filter">

        <form method="GET">

            <label for="period">
                Report Period
            </label>

            <select
                name="period"
                id="period"
                onchange="this.form.submit()">

                <option value="all"
                    <?= $period == "all" ? "selected" : "" ?>>
                    All Time
                </option>

                <option value="month"
                    <?= $period == "month" ? "selected" : "" ?>>
                    This Month
                </option>

                <option value="30days"
                    <?= $period == "30days" ? "selected" : "" ?>>
                    Last 30 Days
                </option>

                <option value="year"
                    <?= $period == "year" ? "selected" : "" ?>>
                    This Year
                </option>

            </select>

        </form>

    </div>

    <a
        href="export_report.php?period=<?= urlencode($period) ?>"
        class="export-button">

        ↓ Export CSV

    </a>

    <a
    href="export_pdf.php?period=<?= urlencode($period) ?>"
    class="export-button pdf-button">

    📄 Export PDF

</a>

<button
    type="button"
    class="export-button print-button"
    onclick="window.print()">

    🖨️ Print Report

</button>

</div>
 


    <!-- SUMMARY -->

    <div class="report-summary">

        <div class="report-card income-card">

            <span>
                Total Income
            </span>

            <strong>
                Rs. <?= number_format($total_income, 2) ?>
            </strong>

        </div>


        <div class="report-card expense-card">

            <span>
                Total Expenses
            </span>

            <strong>
                Rs. <?= number_format($total_expenses, 2) ?>
            </strong>

        </div>


        <div class="report-card balance-card">

            <span>
                Current Balance
            </span>

            <strong>
                Rs. <?= number_format($balance, 2) ?>
            </strong>

        </div>

    </div>


    <!-- EXPENSES BY CATEGORY -->

    <section class="report-section">

        <div class="section-heading">

            <h2>
                Expenses by Category
            </h2>

            <p>
                See where your money is going.
            </p>

        </div>


        <div class="category-report">

            <?php if ($category_expenses->num_rows > 0): ?>

                <?php while ($category = $category_expenses->fetch_assoc()): ?>

                    <?php

                    $percentage = $total_expenses > 0
                        ? ($category["total"] / $total_expenses) * 100
                        : 0;

                    ?>

                    <div class="category-report-item">

                        <div class="category-report-header">

                            <strong>
                                <?= htmlspecialchars(
                                    $category["category_name"]
                                ) ?>
                            </strong>

                            <span>
                                Rs.
                                <?= number_format(
                                    $category["total"],
                                    2
                                ) ?>
                            </span>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?= $percentage ?>%;">
                            </div>

                        </div>


                        <small>
                            <?= number_format($percentage, 1) ?>%
                        </small>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-report">

                    <p>
                        No expense data available yet.
                    </p>

                    <a href="transactions.php">
                        Add a transaction
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- MONTHLY EXPENSES -->

    <section class="report-section">

        <div class="section-heading">

         <h2>
    Monthly Expenses — <?= htmlspecialchars($period_name) ?>
</h2>

<p>
    Spending information for <?= htmlspecialchars(strtolower($period_name)) ?>.
</p>
        </div>

        <!-- EXPENSE CHART -->

<section class="report-section chart-section">

    <div class="section-heading">

        <h2>
            Expense Overview
        </h2>

        <p>
            Visual breakdown of your expenses by category.
        </p>

    </div>

    <div class="chart-container">

        <canvas id="expenseChart"></canvas>

    </div>

</section>


        <div class="monthly-report">

            <?php if ($monthly_expenses->num_rows > 0): ?>

                <?php while ($month = $monthly_expenses->fetch_assoc()): ?>

                    <div class="monthly-item">

                        <span>
                            <?= htmlspecialchars(
                                $month["month"]
                            ) ?>
                        </span>

                        <strong>
                            Rs.
                            <?= number_format(
                                $month["total"],
                                2
                            ) ?>
                        </strong>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-report">

                    <p>
                        No monthly expense data available yet.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>


</main>

<script>

    const chartLabels = <?= json_encode($chart_labels) ?>;

    const chartValues = <?= json_encode($chart_values) ?>;

    const chartCanvas = document.getElementById("expenseChart");


    if (chartValues.length > 0) {

        const ctx = chartCanvas
            .getContext("2d");

        new window.Chart(ctx, {

            type: "doughnut",

            data: {

                labels: chartLabels,

                datasets: [
                    {
                        data: chartValues
                    }
                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        position: "bottom"
                    }

                }

            }

        });

    } else {

        chartCanvas.style.display = "none";

        chartCanvas.parentElement.innerHTML =
            '<div class="empty-report">' +
            '<p>No expense data available for the chart.</p>' +
            '<a href="transactions.php">Add a transaction</a>' +
            '</div>';

    }

</script>