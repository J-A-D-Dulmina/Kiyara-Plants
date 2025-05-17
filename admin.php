<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="contecnt/css/login&register.css" />
    <link rel="stylesheet" href="contecnt/css/style.css" />

    <link rel="stylesheet" href="contecnt/css/header.css" />
    <link rel="stylesheet" href="contecnt/css/footer.css" />
</head>

<body>

    <div class="overlay" id="overlay"></div>
    <section class="login-main-section" id="login-main-section">

        <div class="login-main-section-main">

            <div class="login-container-logo">
                <img class="login-container-logo-img" src="contecnt/img/kiyara Plants logo.png" alt="">
                <h3>Admin Dashboard Login</h3>
            </div>

            <div class="login-container">
                <a class="close-icon" href="index.php"><svg class="close-icon-img" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="256" height="256" viewBox="0 0 256 256" xml:space="preserve">

                        <defs>
                        </defs>
                        <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                            <path d="M 11 90 c -2.815 0 -5.63 -1.074 -7.778 -3.222 c -4.295 -4.296 -4.295 -11.261 0 -15.557 l 68 -68 c 4.297 -4.296 11.26 -4.296 15.557 0 c 4.296 4.296 4.296 11.261 0 15.557 l -68 68 C 16.63 88.926 13.815 90 11 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path d="M 79 90 c -2.815 0 -5.63 -1.074 -7.778 -3.222 l -68 -68 c -4.295 -4.296 -4.295 -11.261 0 -15.557 c 4.296 -4.296 11.261 -4.296 15.557 0 l 68 68 c 4.296 4.296 4.296 11.261 0 15.557 C 84.63 88.926 81.815 90 79 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        </g>
                    </svg></a>
                <h1 class="login-heading">Admin Panel</h1>
                <h2 class="login-heading-secanry">Log in to your Admin Account</h2>

                <form class="login-form" method="POST" action="admin.php">
                    <input type="email" class="email" name="admin_email" placeholder="Email" required>
                    <input type="password" class="password" name="admin_password" placeholder="Password" required>

                    <button type="submit" name="admin_login" id="admin_login" class="btn normal-bt-3 login-button">Login</button>

                    <?php
                    if (isset($_GET['error']) && !empty($_GET['error'])) {
                        $error_message = $_GET['error'];
                        echo '<p style="color: red; font-size: 1.2rem;">' . htmlspecialchars($error_message) . '</p>';
                    }
                    ?>
                </form>

                <div class="signup-section">
                    <p>Return to</p> <a href="index.php">Home Page</a>
                </div>

            </div>

        </div>


    </section>

    <script src="contecnt/js/login.js"></script>

</body>

<?php
include 'db_connection.php';

if (isset($_POST["admin_login"])) {
    $admin_email = $_POST["admin_email"];
    $admin_password = $_POST["admin_password"];

    $sql = "SELECT admin_id, name FROM admintb WHERE email = ? AND password = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $admin_email, $admin_password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION["admin_id"] = $row['admin_id'];
            $_SESSION["admin_name"] = $row['name'];
            header("Location: admin_main.php");
            exit();
        } else {
            $error_message = "Incorrect admin credentials. Please try again.";
            header("Location: admin.php?error=" . urlencode($error_message));
            exit();
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Database error. Please try again later.";
        header("Location: admin.php?error=" . urlencode($error_message));
        exit();
    }
}
?>

</html>
