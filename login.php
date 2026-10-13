<?php

session_start();

include "db.php";

$message = "";
$message_type = "error";

if (isset($_SESSION["register_success"])) {

    $message = $_SESSION["register_success"];

    $message_type = "success";

    unset($_SESSION["register_success"]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];


    /* VALIDATION */

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";
    } else {

        /* FIND USER */

        $stmt = $conn->prepare(
            "SELECT id, username, email, password
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param(
            "s",
            $email
        );

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();


            /* VERIFY PASSWORD */

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];


                header("Location: dashboard.php");
                exit();
            } else {

                $message = "Invalid email or password.";
            }
        } else {

            $message = "Invalid email or password.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login - ExpenseTracker</title>
    <link rel="stylesheet" href="css/login.css">

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <h1>ExpenseTracker</h1>

            <p class="login-subtitle">
                Login to manage your finances
            </p>

            <?php if ($message != ""): ?>

                <div class="login-message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>

            <?php endif; ?>


            <form method="POST" class="login-form">

                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required>

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required>

                </div>


                <button
                    type="submit"
                    class="login-button">

                    Login

                </button>

            </form>


            <p class="login-register">

                Don't have an account?

                <a href="register.php">
                    Create Account
                </a>

            </p>

        </div>

    </div>

</body>

</html>