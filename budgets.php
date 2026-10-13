<?php

session_start();

/* LOGIN PROTECTION */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];
/* ADD BUDGET */

$message = "";
$message_type = "error";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category_id = (int) $_POST["category_id"];
    $amount = $_POST["amount"];
   $budget_month = (int) $_POST["budget_month"];
$month = (int) $_POST["month"];


    /* VALIDATION */

    if ($category_id <= 0) {

        $message = "Please select a category.";

    } elseif (!is_numeric($amount) || $amount <= 0) {

        $message = "Please enter a valid budget amount.";

    } elseif ($budget_month < 1 || $budget_month > 12) {

    $message = "Please select a valid month.";

} elseif ($month < 2020 || $month > 2100) {

    $message = "Please enter a valid year.";

    } else {


        /* CHECK CATEGORY BELONGS TO USER */

        $category_check = $conn->prepare(
            "SELECT id
             FROM categories
             WHERE id = ?
             AND user_id = ?"
        );

        $category_check->bind_param(
            "ii",
            $category_id,
            $user_id
        );

        $category_check->execute();

        $category_result = $category_check->get_result();


        if ($category_result->num_rows == 0) {

            $message = "Invalid category.";

        } else {


            /* CHECK EXISTING BUDGET */

            $budget_check = $conn->prepare(
                "SELECT id
 FROM budgets
 WHERE user_id = ?
 AND category_id = ?
 AND budget_month = ?
 AND month = ?"
            );

            $budget_check->bind_param(
    "iiii",
    $user_id,
    $category_id,
    $budget_month,
    $month
);

            $budget_check->execute();

            $budget_result = $budget_check->get_result();


            if ($budget_result->num_rows > 0) {

                $message =
                    "A budget already exists for this category and year.";

            } else {


                /* INSERT BUDGET */

                $budget_stmt = $conn->prepare(
                    "INSERT INTO budgets
    (user_id, category_id, budget_month, amount, month)
 VALUES (?, ?, ?, ?, ?)"
                );

$budget_stmt->bind_param(
    "iidii",
    $user_id,
    $category_id,
    $budget_month,
    $amount,
    $month
);


                if ($budget_stmt->execute()) {

                    $message = "Budget added successfully.";
                    $message_type = "success";

                } else {

                    $message = "Failed to add budget.";
                }

                $budget_stmt->close();
            }

            $budget_check->close();
        }

        $category_check->close();
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


/* PAGE TITLE */

$page_title = "Budgets";

include "includes/header.php";

?>

<link rel="stylesheet" href="css/budgets.css">


<div class="budgets-container">
    <?php if ($message != ""): ?>

    <div class="budget-message <?= $message_type ?>">

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>

    <div class="dashboard-header">

        <h1>Budget Management</h1>

        <p>
            Set and manage your monthly spending limits.
        </p>

    </div>


    <section class="budget-section">

        <div class="section-heading">

            <h2>Create Monthly Budget</h2>

            <p>
                Set a spending limit for a category.
            </p>

        </div>


        <form method="POST" class="budget-form">

            <div class="form-group">

                <label for="category_id">
                    Category
                </label>

                <select
                    name="category_id"
                    id="category_id"
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
                    placeholder="Enter budget amount"
                    required>

            </div>

<div class="form-group">

    <label for="budget_month">
        Month
    </label>

    <select
        name="budget_month"
        id="budget_month"
        required>

        <option value="">Select Month</option>

        <option value="1">January</option>
        <option value="2">February</option>
        <option value="3">March</option>
        <option value="4">April</option>
        <option value="5">May</option>
        <option value="6">June</option>
        <option value="7">July</option>
        <option value="8">August</option>
        <option value="9">September</option>
        <option value="10">October</option>
        <option value="11">November</option>
        <option value="12">December</option>

    </select>

</div>


<div class="form-group">

    <label for="month">
        Year
    </label>

    <input
        type="number"
        name="month"
        id="month"
        value="<?= date("Y") ?>"
        min="2020"
        max="2100"
        required>

</div>


            <button type="submit">
                Add Budget
            </button>

        </form>

    </section>


    <section class="budget-section">

        <div class="section-heading">

            <h2>Your Budgets</h2>

            <p>
                Your monthly category spending limits.
            </p>

        </div>

<?php

/* GET USER BUDGETS */

$budget_stmt = $conn->prepare(
    "SELECT
        budgets.id,
        budgets.amount,
        budgets.budget_month,
        budgets.month,
        categories.name AS category_name
     FROM budgets
     INNER JOIN categories
        ON budgets.category_id = categories.id
     WHERE budgets.user_id = ?
     ORDER BY budgets.month DESC,
              budgets.budget_month DESC,
              categories.name ASC"
);

$budget_stmt->bind_param(
    "i",
    $user_id
);

$budget_stmt->execute();

$budgets = $budget_stmt->get_result();

?>

<div class="budget-list">

    <?php if ($budgets->num_rows > 0): ?>

        <?php while ($budget = $budgets->fetch_assoc()): ?>

            <div class="budget-card">

                <div class="budget-card-info">

                    <h3>
                        <?= htmlspecialchars($budget["category_name"]) ?>
                    </h3>

                  <p>
    <?= date(
        "F",
        mktime(
            0,
            0,
            0,
            $budget["budget_month"],
            1
        )
    ) ?>

    <?= htmlspecialchars($budget["month"]) ?>
</p>

                </div>

               <div class="budget-actions">

    <div class="budget-amount">

        Rs.
        <?= number_format($budget["amount"], 2) ?>

    </div>

    <div class="budget-buttons">

        <a
            href="edit_budget.php?id=<?= $budget["id"] ?>"
            class="edit-budget">

            Edit

        </a>

        <form
            method="POST"
            action="delete_budget.php"
            onsubmit="return confirm('Are you sure you want to delete this budget?');">

            <input
                type="hidden"
                name="id"
                value="<?= $budget["id"] ?>">

            <button
                type="submit"
                class="delete-budget">

                Delete

            </button>

        </form>

    </div>

</div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty-budget">

            <p>
                No budgets created yet.
            </p>

        </div>

    <?php endif; ?>

</div>

<?php

$budget_stmt->close();

?>

    </section>

</div>