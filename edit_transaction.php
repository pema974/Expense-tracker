<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];

$transaction_id = $_GET["id"] ?? 0;

if (!is_numeric($transaction_id) || $transaction_id <= 0) {
    header("Location: transactions.php");
    exit();
}


/* GET TRANSACTION */

$stmt = $conn->prepare(
    "SELECT
        id,
        category_id,
        type,
        amount,
        description,
        transaction_date
     FROM transactions
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $transaction_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$transaction = $result->fetch_assoc();

$stmt->close();


if (!$transaction) {
    header("Location: transactions.php");
    exit();
}


/* GET USER CATEGORIES */

$stmt = $conn->prepare(
    "SELECT id, name
     FROM categories
     WHERE user_id = ?
     ORDER BY name ASC"
);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$categories = $stmt->get_result();

$stmt->close();

$page_title = "Edit Transaction";

include "includes/header.php";

?>

<link rel="stylesheet" href="css/transactions.css">


<main class="transactions-container">

    <div class="dashboard-header">

        <h1>Edit Transaction</h1>

        <p>
            Update your income or expense.
        </p>

    </div>


    <div class="transaction-form">

        <h2>Transaction Details</h2>

        <form method="POST" action="update_transaction.php">

            <input
                type="hidden"
                name="transaction_id"
                value="<?= $transaction["id"] ?>">

                <form method="POST" action="update_transaction.php">


            <!-- TYPE -->

            <div class="form-group">

                <label for="type">
                    Type
                </label>

                <select
                    id="type"
                    name="type"
                    required>

                    <option
                        value="income"
                        <?= $transaction["type"] == "income" ? "selected" : "" ?>>
                        Income
                    </option>

                    <option
                        value="expense"
                        <?= $transaction["type"] == "expense" ? "selected" : "" ?>>
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

                    <?php while ($category = $categories->fetch_assoc()): ?>

                        <option
                            value="<?= $category["id"] ?>"
                            <?= $category["id"] == $transaction["category_id"] ? "selected" : "" ?>>

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
                    value="<?= htmlspecialchars($transaction["amount"]) ?>"
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
                    value="<?= htmlspecialchars($transaction["description"]) ?>"
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
                    value="<?= htmlspecialchars($transaction["transaction_date"]) ?>"
                    required>

            </div>


            <button type="submit">
                Update Transaction
            </button>

        </form>

    </div>

</main>