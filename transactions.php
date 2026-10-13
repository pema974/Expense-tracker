<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];

$message = "";


/* ADD TRANSACTION */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category_id = $_POST["category_id"];
    $type = $_POST["type"];
    $amount = $_POST["amount"];
    $description = trim($_POST["description"]);
    $transaction_date = $_POST["transaction_date"];


    /* VALIDATION */

    if (
        empty($category_id) ||
        empty($type) ||
        empty($amount) ||
        empty($transaction_date)
    ) {

        $message = "Please fill in all required fields.";
    } elseif (!in_array($type, ["income", "expense"])) {

        $message = "Invalid transaction type.";
    } elseif (!is_numeric($amount) || $amount <= 0) {

        $message = "Please enter a valid amount.";
    } else {

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

            $message = "Invalid category.";
        } else {

            /* INSERT TRANSACTION */

            $stmt = $conn->prepare(
                "INSERT INTO transactions
                    (
                        user_id,
                        category_id,
                        type,
                        amount,
                        description,
                        transaction_date
                    )
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iisdss",
                $user_id,
                $category_id,
                $type,
                $amount,
                $description,
                $transaction_date
            );


            if ($stmt->execute()) {

                $message = "Transaction added successfully.";
            } else {

                $message = "Failed to add transaction.";
            }

            $stmt->close();
        }

        $check->close();
    }
}


/* GET USER CATEGORIES */
$category_stmt = $conn->prepare(
    "SELECT id, name
     FROM categories
     WHERE user_id = ?
     ORDER BY name ASC"
);

$category_stmt->bind_param(
    "i",
    $user_id
);

$category_stmt->execute();

$categories = $category_stmt->get_result();

$category_stmt->close();


/* TRANSACTION FILTERS */

$search = trim($_GET["search"] ?? "");
$filter_type = $_GET["type"] ?? "";
$filter_category = $_GET["category_id"] ?? "";


/* GET TRANSACTIONS */

$sql = "SELECT
            transactions.id,
            transactions.type,
            transactions.amount,
            transactions.description,
            transactions.transaction_date,
            categories.name AS category_name
        FROM transactions
        INNER JOIN categories
            ON transactions.category_id = categories.id
        WHERE transactions.user_id = ?";

$params = [$user_id];
$types = "i";


/* SEARCH */

if ($search != "") {

    $sql .= " AND (
                transactions.description LIKE ?
                OR categories.name LIKE ?
              )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ss";
}


/* FILTER BY TYPE */

if (in_array($filter_type, ["income", "expense"])) {

    $sql .= " AND transactions.type = ?";

    $params[] = $filter_type;

    $types .= "s";
}


/* FILTER BY CATEGORY */

if ($filter_category != "" && is_numeric($filter_category)) {

    $sql .= " AND transactions.category_id = ?";

    $params[] = $filter_category;

    $types .= "i";
}


/* ORDER */

$sql .= " ORDER BY transactions.transaction_date DESC,
                   transactions.id DESC";


/* PREPARE */

$transaction_stmt = $conn->prepare($sql);


/* BIND PARAMETERS */

$transaction_stmt->bind_param(
    $types,
    ...$params
);


/* EXECUTE */

$transaction_stmt->execute();

$transactions = $transaction_stmt->get_result();

$transaction_stmt->close();


/* TRANSACTION SUMMARY */

$total_income = 0;
$total_expense = 0;

$summary_sql = "SELECT
                    type,
                    SUM(amount) AS total
                FROM transactions
                WHERE user_id = ?
                GROUP BY type";

$summary_stmt = $conn->prepare($summary_sql);

$summary_stmt->bind_param(
    "i",
    $user_id
);

$summary_stmt->execute();

$summary_result = $summary_stmt->get_result();

while ($summary = $summary_result->fetch_assoc()) {

    if ($summary["type"] == "income") {

        $total_income = $summary["total"];

    } elseif ($summary["type"] == "expense") {

        $total_expense = $summary["total"];
    }
}

$summary_stmt->close();

$balance = $total_income - $total_expense;

/* PAGE HEADER */

$page_title = "Transactions";

include "includes/header.php";

?>


<link rel="stylesheet" href="css/transactions.css">


<main class="transactions-container">

    <div class="dashboard-header">

        <h1>Transactions</h1>

        <p>
            Add and manage your income and expenses.
        </p>

    </div>

    <div class="transaction-summary">

    <div class="summary-card income-card">

        <span class="summary-label">
            Total Income
        </span>

        <strong>
            Rs. <?= number_format($total_income, 2) ?>
        </strong>

    </div>


    <div class="summary-card expense-card">

        <span class="summary-label">
            Total Expenses
        </span>

        <strong>
            Rs. <?= number_format($total_expense, 2) ?>
        </strong>

    </div>


    <div class="summary-card balance-card">

        <span class="summary-label">
            Balance
        </span>

        <strong>
            Rs. <?= number_format($balance, 2) ?>
        </strong>

    </div>

</div>


    <?php if ($message != ""): ?>

        <p>
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>


    <!-- ADD TRANSACTION -->

    <div class="transaction-form">

        <h2>Add Transaction</h2>

        <form method="POST">


            <!-- TYPE -->

            <div class="form-group">

                <label for="type">
                    Type
                </label>

                <select
                    id="type"
                    name="type"
                    required>

                    <option value="">
                        Select Type
                    </option>

                    <option value="income">
                        Income
                    </option>

                    <option value="expense">
                        Expense
                    </option>

                </select>

            </div>


            <!-- CATEGORY -->

            <div class="form-group">

                <label for="category_id">
                    Category
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required>

                    <option value="">
                        Select Category
                    </option>

                    <?php while ($category = $categories->fetch_assoc()): ?>

                        <option value="<?= $category["id"] ?>">

                            <?= htmlspecialchars($category["name"]) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- AMOUNT -->

            <div class="form-group">

                <label for="amount">
                    Amount
                </label>

                <input
                    type="number"
                    id="amount"
                    name="amount"
                    step="0.01"
                    min="0.01"
                    placeholder="Enter amount"
                    required>

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <input
                    type="text"
                    id="description"
                    name="description"
                    placeholder="e.g. Monthly salary">

            </div>


            <!-- DATE -->

            <div class="form-group">

                <label for="transaction_date">
                    Date
                </label>

                <input
                    type="date"
                    id="transaction_date"
                    name="transaction_date"
                    value="<?= date("Y-m-d") ?>"
                    required>

            </div>


            <button type="submit">
                Add Transaction
            </button>


        </form>

    </div>


    <!-- TRANSACTION HISTORY -->

    <div>

        <h2>Transaction History</h2>


        <div class="transaction-filters">

    <form method="GET">

        <div class="filter-group">

            <label for="search">
                Search
            </label>

            <input
                type="text"
                id="search"
                name="search"
                placeholder="Search transactions..."
                value="<?= htmlspecialchars($search) ?>">

        </div>


        <div class="filter-group">

            <label for="filter_type">
                Type
            </label>

            <select
                id="filter_type"
                name="type">

                <option value="">
                    All Types
                </option>

                <option
                    value="income"
                    <?= $filter_type == "income" ? "selected" : "" ?>>
                    Income
                </option>

                <option
                    value="expense"
                    <?= $filter_type == "expense" ? "selected" : "" ?>>
                    Expense
                </option>

            </select>

        </div>


        <div class="filter-group">

            <label for="filter_category">
                Category
            </label>

            <select
                id="filter_category"
                name="category_id">

                <option value="">
                    All Categories
                </option>

                <?php
                $categories->data_seek(0);
                ?>

                <?php while ($category = $categories->fetch_assoc()): ?>

                    <option
                        value="<?= $category["id"] ?>"
                        <?= $filter_category == $category["id"] ? "selected" : "" ?>>

                        <?= htmlspecialchars($category["name"]) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <button type="submit">
            Apply Filters
        </button>


        <a href="transactions.php">
            Reset
        </a>

    </form>

</div>


        <div class="transactions-list">

            <?php if ($transactions->num_rows > 0): ?>

                <?php while ($transaction = $transactions->fetch_assoc()): ?>

                    <?php
                    $amount_sign = $transaction["type"] == "income" ? "+" : "-";

                    $amount_class = $transaction["type"] == "income"
                        ? "income-amount"
                        : "expense-amount";
                    ?>

                    <div class="transaction-item">


                        <div class="transaction-info">

                            <h3>
                                <?= htmlspecialchars(
                                    $transaction["category_name"]
                                ) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $transaction["description"]
                                        ?: "No description"
                                ) ?>
                            </p>

                            <p>
                                <?= htmlspecialchars(
                                    $transaction["transaction_date"]
                                ) ?>
                            </p>

                        </div>


                        <div class="transaction-actions">

                            <div class="transaction-amount <?= $amount_class ?>">
                                <?= $amount_sign ?>Rs. <?= number_format($transaction["amount"], 2) ?>
                            </div>

                            <div class="transaction-buttons">

                                <a
                                    href="edit_transaction.php?id=<?= $transaction["id"] ?>"
                                    class="edit-btn">
                                    Edit
                                </a>


                                <form
                                    action="delete_transaction.php"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this transaction?');">

                                    <input
                                        type="hidden"
                                        name="transaction_id"
                                        value="<?= $transaction["id"] ?>">

                                    <button
                                        type="submit"
                                        class="delete-btn">
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p>
                    No transactions yet.
                </p>

            <?php endif; ?>

        </div>

    </div>

</main>