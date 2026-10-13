<?php

if (!isset($page_title)) {
    $page_title = "ExpenseTracker";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($page_title) ?> - ExpenseTracker</title>

    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/transactions.css">

  
    


</head>

<body>

<header class="navbar">

    <div class="navbar-container">

        <a href="dashboard.php" class="logo">
            ExpenseTracker
        </a>

        <nav class="nav-links">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="transactions.php">
                Transactions
            </a>

            <a href="categories.php">
                Categories
            </a>

             <a href="reports.php">
                Reports
            </a>



            <a href="logout.php">
                Logout
            </a>

        </nav>

    </div>

</header>