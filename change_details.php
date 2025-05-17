<?php
session_start();
include '/db_connection.ph';

if (isset($_POST['user-detail-up'])) {
    $customerId = $_SESSION["user_id"];
    $customerName = $_POST['customerName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $age = $_POST['age'];
    $postal_code = $_POST['postal_code'];

    // Check if an image has been uploaded
    if (!empty($_FILES['user_image']['name'])) {
        // Handle image upload
        if ($_FILES['user_image']['error'] === UPLOAD_ERR_OK) {
            $image_name = $_FILES['user_image']['name'];
            $temp_name = $_FILES['user_image']['tmp_name'];
            $image_folder = "uploads/";

            if (move_uploaded_file($temp_name, $image_folder . $image_name)) {
                $image_path = $image_folder . $image_name;
            } else {
                echo "Error: Image upload failed";
                exit();
            }
        } else {
            echo "Error: Image upload failed";
            exit();
        }
    } else {
        // If no image uploaded, set image_path to NULL or keep the existing image path in the database
        // Modify the SQL query to update all fields except the profile_image
        $image_path = NULL; // You might also set it to the existing image path from the database
    }

    // Construct the SQL query to update user details
    $sql = "UPDATE customers SET 
        name = '$customerName',
        email = '$email',
        phone = '$phone',
        address = '$address',
        age = '$age',
        postal_code = '$postal_code'";

    // Append image path update to the query if an image was uploaded
    if ($image_path !== NULL) {
        $sql .= ", profile_image = '$image_path'";
    }

    $sql .= " WHERE customer_id = '$customerId'";

    if (mysqli_query($conn, $sql)) {
        echo '<script>alert("Details updated successfully!"); window.location.href = "user.php";</script>';
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
