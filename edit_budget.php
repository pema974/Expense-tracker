<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];


/* GET BUDGET ID */

if (!isset($_GET["id"])) {
    header("Location: budgets.php");
    exit();
}

$budget_id = (int) $_GET["id"];


/* GET BUDGET */

$stmt = $conn->prepare(
    "SELECT
        budgets.id,
        budgets.category_id,
        budgets.amount,
        budgets.month,
        categories.name AS category_name
     FROM budgets
     INNER JOIN categories
        ON budgets.category_id = categories.id
     WHERE budgets.id = ?
     AND budgets.user_id = ?"
);

$stmt->bind_param(
    "ii",
    $budget_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $stmt->close();

    header("Location: budgets.php");
    exit();
}

$budget = $result->fetch_assoc();

$stmt->close();


/* UPDATE BUDGET */

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $amount = $_POST["amount"];
    $month = $_POST["month"] ?? "";


    if (!is_numeric($amount) || $amount <= 0) {

        $message = "Please enter a valid budget amount.";

    }  elseif (
    !preg_match('/^\d{4}-\d{2}$/', $month) ||
    (int) substr($month, 0, 4) < 2020 ||
    (int) substr($month, 0, 4) > 2100 ||
    (int) substr($month, 5, 2) < 1 ||
    (int) substr($month, 5, 2) > 12
) {

    $message = "Please select a valid month.";

    } else {

        $update_stmt = $conn->prepare(
            "UPDATE budgets
             SET amount = ?, month = ?
             WHERE id = ?
             AND user_id = ?"
        );

      $update_stmt->bind_param(
    "dsii",
    $amount,
    $month,
    $budget_id,
    $user_id
);


        if ($update_stmt->execute()) {

            $update_stmt->close();

            header("Location: budgets.php");
            exit();

        } else {

            $message = "Failed to update budget.";
        }

        $update_stmt->close();
    }
}


/* PAGE TITLE */

$page_title = "Edit Budget";

include "includes/header.php";

?>

<link rel="stylesheet" href="css/budgets.css">


<div class="budgets-container">

    <div class="dashboard-header">

        <h1>Edit Budget</h1>

        <p>
            Update your monthly budget.
        </p>

    </div>


    <?php if ($message != ""): ?>

        <div class="budget-message error">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <section class="budget-section">

        <div class="section-heading">

            <h2>
                <?= htmlspecialchars($budget["category_name"]) ?>
            </h2>

            <p>
                Update the budget amount or year.
            </p>

        </div>


        <form method="POST" class="budget-form">


            <div class="form-group">

                <label for="amount">
                    Monthly Budget
                </label>

                <input
                    type="number"
                    name="amount"
                    id="amount"
                    step="0.01"
                    min="0.01"
                    value="<?= htmlspecialchars($budget["amount"]) ?>"
                    required>

            </div>

           
        <div class="form-group">

            <label for="month">
                Budget Month
            </label>

            <input
                type="month"
                name="month"
                id="month"
                value="<?= htmlspecialchars($budget["month"]) ?>"
                min="2020-01"
                max="2100-12"
                required>

        </div>




    
        <div class="form-group">

            <label for="month">
                Budget Month
            </label>

            <input
                type="month"
                name="month"
                id="month"
                value="<?= htmlspecialchars($budget["month"]) ?>"
                min="2020-01"
                max="2100-12"
                required>

        </div>



        </form>

    </section>

</div>