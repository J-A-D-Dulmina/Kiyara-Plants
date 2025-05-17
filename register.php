<?php
include 'db_connection.php';

$registrationConfirmation = '';
$registrationError = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnsubmit'])) {
    function sanitizeInput($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $username = sanitizeInput($_POST["username"]);
    $userEmail = sanitizeInput($_POST["email"]);
    $address = sanitizeInput($_POST["address"]);
    $phoneNumber = sanitizeInput($_POST["phone"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    if ($password !== $confirmPassword) {
        $registrationError = "Passwords do not match.";
    } else {
        $profileImagePath = '';

        if (isset($_FILES['profile-image']) && $_FILES['profile-image']['error'] === UPLOAD_ERR_OK) {
            $profileImagePath = "uploads/" . basename($_FILES['profile-image']['name']);

            if (!move_uploaded_file($_FILES['profile-image']['tmp_name'], $profileImagePath)) {
                $registrationError = "Sorry, there was an error uploading your file.";
                $profileImagePath = ''; // Reset the image path on failure
            }
        }

        // Proceed with database insertion
        $sql = "INSERT INTO customers (name, email, password, address, phone, profile_image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssss", $username, $userEmail, $password, $address, $phoneNumber, $profileImagePath);
            $execute = mysqli_stmt_execute($stmt);

            if ($execute) {
                $registrationConfirmation = "Registration successful! You will be redirected to <a href='login.php'>login</a> page shortly.";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 5000);
                </script>";
            } else {
                $registrationError = "Registration failed. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $registrationError = "Statement preparation failed. Please try again.";
        }
    }
}

mysqli_close($conn);
?>






<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SignUp</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="contecnt/css/login&register.css" />
    <link rel="stylesheet" href="contecnt/css/style.css" />
    <link rel="stylesheet" href="contecnt/css/footer.css" />
</head>

<body>
    <div class="overlay" id="overlay"></div>
    <section class="login-main-section" id="login-main-section">
        <div class="login-main-section-main">
            <div class="login-container-logo">
                <img class="login-container-logo-img" src="contecnt/img/kiyara Plants logo.png" alt="">
                <h3>Lorem ipsum dolor sit amet consectetur adipisicing.</h3>
            </div>
            <div class="login-container">
                <a class="close-icon" href="index.php">
                    <svg class="close-icon-img" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="256" height="256" viewBox="0 0 256 256" xml:space="preserve">

                        <defs>
                        </defs>
                        <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                            <path d="M 11 90 c -2.815 0 -5.63 -1.074 -7.778 -3.222 c -4.295 -4.296 -4.295 -11.261 0 -15.557 l 68 -68 c 4.297 -4.296 11.26 -4.296 15.557 0 c 4.296 4.296 4.296 11.261 0 15.557 l -68 68 C 16.63 88.926 13.815 90 11 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path d="M 79 90 c -2.815 0 -5.63 -1.074 -7.778 -3.222 l -68 -68 c -4.295 -4.296 -4.295 -11.261 0 -15.557 c 4.296 -4.296 11.261 -4.296 15.557 0 l 68 68 c 4.296 4.296 4.296 11.261 0 15.557 C 84.63 88.926 81.815 90 79 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        </g>
                    </svg>
                </a>
                <h1 class="login-heading">Welcome</h1>
                <h2 class="login-heading-secanry">Create your Account</h2>
                <form class="login-form" method="POST" enctype="multipart/form-data">
                    <input type="text" class="username" placeholder="Username" name="username" required>
                    <input type="email" class="password" placeholder="Email" name="email" required>
                    <input type="text" class="address" placeholder="Address" name="address" required>
                    <input type="tel" class="phone" placeholder="Phone Number" name="phone" required>
                    <input type="password" class="password" placeholder="Password" name="password" required>
                    <input type="password" class="password" placeholder="Confirm Password" name="confirm_password" required>
                    <div class="form-group">
                        <label for="profile-image" class="custom-file-upload">
                            <i class="fas fa-cloud-upload-alt"></i> <span id="file-name">Upload Profile Image</span>
                        </label>
                        <input type="file" id="profile-image" name="profile-image" onchange="displayFileName()">
                    </div>

                    <!-- Error message -->
                    <div style="color: red; font: size 1.8rem; margin-top:5%;" class="registration-error">
                        <?php echo $registrationError; ?>
                    </div>

                    <!-- Registration confirmation message -->
                    <div style="color: green; font: size 1.8rem; margin-top:5%;" class=" registration-confirmation">
                        <?php echo $registrationConfirmation; ?>
                    </div>


                    <button type="submit" class="btn normal-bt-3 login-button" name="btnsubmit" onclick="return confirm('Are you sure you want to register?')">Register</button>
                </form>
                <div class="signup-section">
                    <p>Already have an account?</p>
                    <a href="login.php">Log-in</a>
                </div>
            </div>
        </div>
    </section>


    <script>
        const overlay = document.getElementById('overlay');
        const loginMainSection = document.getElementById('login-main-section');
        overlay.addEventListener('click', () => {
            loginMainSection.style.display = 'none'; // Hide the login section
            window.location.href = 'index.html'; // Redirect to the home page
        });


        function displayFileName() {
            const fileInput = document.getElementById('profile-image');
            const fileName = document.getElementById('file-name');

            if (fileInput.files.length > 0) {
                fileName.textContent = fileInput.files[0].name;
            } else {
                fileName.textContent = 'Upload Image';
            }
        }
    </script>
</body>



</html>