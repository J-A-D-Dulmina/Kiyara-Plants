<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="stylesheet" href="contecnt/css/user.css" />
    <link rel="stylesheet" href="contecnt/css/style.css" />

    <link rel="stylesheet" href="contecnt/css/header.css" />
    <link rel="stylesheet" href="contecnt/css/footer.css" />

</head>

<body>

    <?php include 'header.php'; ?>


    <section class="user-acount-secttion">



        <div class="user-acount-secttion-main">


            <div class="user-nv-main">
                <img class="logo-image" src="contecnt/img/kiyara Plants logo.png" alt="">
                <nav class="user-nec-list">
                    <li><a class="usernev-link account-dt" href="user.php">Account Details</a></li>
                    <li><a class="usernev-link shoping-histry" href="usershopinghistry.php">Shoping Histry</a></li>

                </nav>
            </div>

            <div class=" detail-section">

                <div class="editable-form-box">

                    <!-- <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data"> -->
                    <form method="post" action="change_details.php" enctype="multipart/form-data">

                        <div class="profile-image-box">
                            <input type="file" name="user_image" id="user_image" style="display: none;">
                            <label class="user_image" for="user_image">
                                <img class="profile-image" id="selected-image" src="<?php echo $userImage; ?>" alt="User Profile Image">
                            </label>
                        </div>


                        <div class="editable-form-box-main">

                            <div class="editable-form">
                                <div class="form-field">
                                    <div class="label">Name</div>
                                    <div class="value-flex">
                                        <div class="value" id="name-value"><?php echo $customerName; ?></div>
                                        <input name="customerName" id="name-input" class="text-input" type="text" value="<?php echo $customerName; ?>" style="display: none;">
                                        <div id="edit-name" class="icon edit-icon">✏️</div>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <div class="label">Email</div>
                                    <div class="value-flex">
                                        <div class="value" id="email-value"><?php echo $email; ?></div>
                                        <input name="email" id="email-input" class="text-input" type="email" value="<?php echo $email; ?>" style="display: none;">
                                        <div id="edit-email" class="icon edit-icon">✏️</div>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <div class="label">Age</div>
                                    <div class="value-flex">
                                        <div class="value" id="age-value"><?php echo $age; ?></div>
                                        <input placeholder="Add Age" name="age" id="age-input" class="text-input" type="number" value="<?php echo $age; ?>" style="display: none;">
                                        <div id="edit-age" class="icon edit-icon">✏️</div>
                                    </div>
                                </div>

                                <button class="buton-submit" type="submit" name="user-detail-up" id="user-detail-up" onclick="return confirm('Are you sure you want to update your details?')">Update</button>

                            </div>

                            <div class="editable-form">
                                <!-- Mobile Number -->
                                <div class="form-field">
                                    <div class="label">Mobile Number</div>
                                    <div class="value-flex">
                                        <div class="value" id="mobile-value"><?php echo $phone; ?></div>
                                        <input name="phone" id="mobile-input" class="text-input" type="text" value="<?php echo $phone; ?>" style="display: none;">
                                        <div id="edit-mobile" class="icon edit-icon">✏️</div>
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="form-field">
                                    <div class="label">Address</div>
                                    <div class="value-flex">
                                        <div class="value" id="address-value"><?php echo $address; ?></div>
                                        <input name="address" id="address-input" class="text-input" type="text" value="<?php echo $address; ?>" style="display: none;">
                                        <div id="edit-address" class="icon edit-icon">✏️</div>
                                    </div>
                                </div>

                                <!-- Postal Code -->
                                <div class="form-field">
                                    <div class="label">Postal Code</div>
                                    <div class="value-flex">
                                        <div class="value" id="postal-value"><?php echo $postal_code; ?> </div>
                                        <input name="postal_code" id="postal-input" class="text-input" type="text" value="<?php echo $postal_code; ?>" style="display: none;">
                                        <div id="edit-postal" class="icon edit-icon">✏️</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>


                    <div class="sing-out-and-edit">

                        <!-- <button class="buton-submit" type="submit">Update</button> -->

                        <form method="post" action="password_change.php">
                            <div class="password-section-main">
                                <h2>Change Password</h2>
                                <div class="password-section-sub">
                                    <div class="password-section-sub-fill">
                                        <label for="old_password">Old Password</label>
                                        <input type="password" id="old_password" name="old_password" required>
                                    </div>
                                    <div class="password-section-sub-fill">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" required>
                                    </div>
                                </div>
                                <input class="buton-change" type="submit" name="change_password" value="Change">
                            </div>
                        </form>


                        <form id="logoutForm" method="post" action="logout.php">
                            <button class="buton-log-out" type="submit" name="logout" onclick="return confirmLogout()">Sign Out</button>
                        </form>
                    </div>



                </div>
            </div>


        </div>


    </section>


    <?php include 'footer.php'; ?>

    <script src="contecnt/js/user.js"></script>





</body>




</html>