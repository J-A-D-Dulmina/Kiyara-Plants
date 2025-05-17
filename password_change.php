
    <?php
    session_start();
    include 'db_connection.php';

    if (isset($_POST['change_password'])) {
        $customerId = $_SESSION["user_id"];
        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];

        // Retrieve the existing password from the database
        $sql = "SELECT password FROM customers WHERE customer_id = '$customerId'";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $existingPassword = $row['password'];

            // Verify if the old password matches the one in the database
            if ($oldPassword === $existingPassword) {
                // Update the password in the database
                $updateSql = "UPDATE customers SET password = '$newPassword' WHERE customer_id = '$customerId'";
                if (mysqli_query($conn, $updateSql)) {
                    echo '<script>alert("Password updated successfully!"); window.location.href = "user.php";</script>';
                    exit();
                } else {
                    echo "Error updating password: " . mysqli_error($conn);
                }
            } else {
                echo '<script>alert("Old password is incorrect!"); window.location.href = "user.php";</script>';
                exit();
            }
        } else {
            echo "Error fetching existing password: " . mysqli_error($conn);
        }
    }



    ?>