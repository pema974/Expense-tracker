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


/* ADD CATEGORY */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);

    if (empty($name)) {

        $message = "Please enter a category name.";

    } else {

        /* CHECK DUPLICATE */

        $check = $conn->prepare(
            "SELECT id
             FROM categories
             WHERE user_id = ? AND name = ?"
        );

        $check->bind_param(
            "is",
            $user_id,
            $name
        );

        $check->execute();

        $result = $check->get_result();


        if ($result->num_rows > 0) {

            $message = "This category already exists.";

        } else {

            /* INSERT CATEGORY */

            $stmt = $conn->prepare(
                "INSERT INTO categories
                    (user_id, name)
                 VALUES (?, ?)"
            );

            $stmt->bind_param(
                "is",
                $user_id,
                $name
            );


            if ($stmt->execute()) {

                $message = "Category added successfully.";

            } else {

                $message = "Failed to add category.";

            }

            $stmt->close();
        }

        $check->close();
    }
}


/* GET CATEGORIES */

$stmt = $conn->prepare(
    "SELECT id, name, created_at
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


/* PAGE HEADER */

$page_title = "Categories";

include "includes/header.php";

?>
<link rel="stylesheet" href="css/categories.css">

<main class="categories-container">

    <div class="dashboard-header">

        <h1>Categories</h1>

        <p>
            Manage your income and expense categories.
        </p>

    </div>


    <?php if ($message != ""): ?>

        <p>
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>


    <!-- ADD CATEGORY -->

    <div class="category-form">

        <h2>Add Category</h2>

        <form method="POST">

            <input
                type="text"
                id="name"
                name="name"
                placeholder="e.g. Food"
                required
            >

            <button type="submit">
                Add Category
            </button>

        </form>

    </div>


    <!-- CATEGORY LIST -->

    <div>

        <h2>Your Categories</h2>

        <div class="categories-list">

            <?php if ($categories->num_rows > 0): ?>

                <?php while ($category = $categories->fetch_assoc()): ?>

                    <div class="category-item">

                        <strong>
                            <?= htmlspecialchars($category["name"]) ?>
                        </strong>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p>
                    No categories yet.
                </p>

            <?php endif; ?>

        </div>

    </div>

</main>