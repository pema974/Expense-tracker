<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];


/* TOTAL INCOME */

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total_income
     FROM transactions
     WHERE user_id = ? AND type = 'income'"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_income = $row["total_income"];

$stmt->close();


/* TOTAL EXPENSES */

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total_expenses
     FROM transactions
     WHERE user_id = ? AND type = 'expense'"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_expenses = $row["total_expenses"];

$stmt->close();


/* BALANCE */

$balance = $total_income - $total_expenses;

/* RECENT TRANSACTIONS */

$recent_stmt = $conn->prepare(
    "SELECT
        transactions.id,
        transactions.type,
        transactions.amount,
        transactions.description,
        transactions.transaction_date,
        categories.name AS category_name
     FROM transactions
     INNER JOIN categories
        ON transactions.category_id = categories.id
     WHERE transactions.user_id = ?
     ORDER BY transactions.transaction_date DESC,
              transactions.id DESC
     LIMIT 5"
);

$recent_stmt->bind_param(
    "i",
    $user_id
);

$recent_stmt->execute();

$recent_transactions = $recent_stmt->get_result();

$recent_stmt->close();

?>
<?php

$page_title = "Dashboard";

include "includes/header.php";
?>
<link rel="stylesheet" href="css/dashboard.css">



<body>

  <main class="dashboard-container">

    <div class="dashboard-header">

        <h1>Dashboard</h1>

        <p>
            Welcome back,
            <?= htmlspecialchars($username) ?>!
        </p>

    </div>


    <div class="summary-grid">


        <!-- INCOME -->

        <div class="summary-card income-card">

            <h3>Total Income</h3>

            <p class="amount">
                Rs. <?= number_format($total_income, 2) ?>
            </p>

        </div>


        <!-- EXPENSES -->

        <div class="summary-card expense-card">

            <h3>Total Expenses</h3>

            <p class="amount">
                Rs. <?= number_format($total_expenses, 2) ?>
            </p>

        </div>


        <!-- BALANCE -->

        <div class="summary-card balance-card">

            <h3>Current Balance</h3>

            <p class="amount">
                Rs. <?= number_format($balance, 2) ?>
            </p>

        </div>
    </div>

    <!-- RECENT TRANSACTIONS -->

<section class="recent-transactions">

    <div class="section-header">

        <div>
            <h2>Recent Transactions</h2>

            <p>
                Your latest financial activity.
            </p>
        </div>

        <a href="transactions.php" class="view-all-btn">
            View All
        </a>

    </div>


    <div class="recent-list">

        <?php if ($recent_transactions->num_rows > 0): ?>

            <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>

                <?php

                $is_income = $transaction["type"] == "income";

                $amount_sign = $is_income ? "+" : "-";

                $amount_class = $is_income
                    ? "recent-income"
                    : "recent-expense";

                ?>

                <div class="recent-item">

                    <div class="recent-info">

                        <div class="recent-category">

                            <?= htmlspecialchars(
                                $transaction["category_name"]
                            ) ?>

                        </div>

                        <div class="recent-description">

                            <?= htmlspecialchars(
                                $transaction["description"]
                                    ?: "No description"
                            ) ?>

                        </div>

                        <div class="recent-date">

                            <?= htmlspecialchars(
                                $transaction["transaction_date"]
                            ) ?>

                        </div>

                    </div>


                    <div class="recent-amount <?= $amount_class ?>">

                        <?= $amount_sign ?>Rs.
                        <?= number_format(
                            $transaction["amount"],
                            2
                        ) ?>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="no-transactions">

                <p>No transactions yet.</p>

                <a href="transactions.php">
                    Add your first transaction
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>

</main>


    <hr>


    <p>

        <a href="logout.php">
            Logout
        </a>

    </p>

</body>

</html>