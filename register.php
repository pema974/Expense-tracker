<?php

session_start();

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];


    /* VALIDATION */

    if (empty($username) || empty($email) || empty($password)) {

        $message = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";

    } else {

        /* CHECK EMAIL */

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?"
        );

        $check->bind_param(
            "s",
            $email
        );

        $check->execute();

        $result = $check->get_result();


        if ($result->num_rows > 0) {

            $message = "Email is already registered.";

        } else {

            /* HASH PASSWORD */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* CREATE USER */

            $stmt = $conn->prepare(
                "INSERT INTO users
                    (username, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $username,
                $email,
                $hashed_password
            );


           if ($stmt->execute()) {
                        $stmt->bind_param(
                "sss",
                $username,
                $email,
                $hashed_password
            );

            if ($stmt->execute()) {

                $_SESSION["register_success"] =
                    "Account created successfully. Please log in.";

                header("Location: login.php");
                exit();

            } else {

                $message = "Registration failed.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
}
  

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Register - ExpenseTracker</title>
    <link rel="stylesheet" href="css/register.css">

</head>
<body>


<div class="register-container">

    <div class="register-card">

        <h1>ExpenseTracker</h1>

        <p class="register-subtitle">
            Create your account to start managing your finances
        </p>

        <?php if ($message != ""): ?>

            <div class="register-error">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="register-form">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="register-button">

                Create Account

            </button>

        </form>


        <p class="register-login">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </p>

    </div>

</div>


</body>




</html>