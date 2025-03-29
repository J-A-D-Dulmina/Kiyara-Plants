<?php
// Include database connection and settings if not already included
if (!isset($settings)) {
    include_once 'db_connection.php';
    
    // Get site settings
    $settings = array(
        'store_name' => 'Kiyara Plants',
        'store_email' => 'info@kiyaraplants.com',
        'store_phone' => '+94 77 123 4567',
        'social_facebook' => '',
        'social_twitter' => '',
        'social_instagram' => '',
    );

    // Check if settings table exists and get settings
    $settings_check = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
    if (mysqli_num_rows($settings_check) > 0) {
        $settings_query = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
        if ($settings_query && mysqli_num_rows($settings_query) > 0) {
            $db_settings = mysqli_fetch_assoc($settings_query);
            // Update settings with database values
            foreach ($db_settings as $key => $value) {
                if (array_key_exists($key, $settings) && !empty($value)) {
                    $settings[$key] = $value;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="contecnt/css/footer.css" />
</head>

<body>
    <footer class="footer">
        <div class="footer-main-part">
            <div class="footer-grid">
                <div class="header-secandry">
                    <?php if (!empty($settings['store_logo'])): ?>
                    <img class="logo-secandry" alt="<?php echo htmlspecialchars($settings['store_name']); ?> Logo" src="<?php echo htmlspecialchars($settings['store_logo']); ?>" />
                    <?php else: ?>
                    <img class="logo-secandry" alt="Kiyara Plants Logo" src="contecnt/img/kiyara Plants logo.png" />
                    <?php endif; ?>

                    <img class="footer-main-img" src="contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092324.999.png" alt="">

                </div>

                <div class="footer-main">


                    <nav class="main-nav">
                        <ul class="secandry-nav-list">
                            <li><a class="secandry-nav-link" href="shop.php">Shop</a></li>
                            <li><a class="secandry-nav-link" href="cart.php">Cart</a></li>
                            <li><a class="secandry-nav-link" href="about.php">About</a></li>
                            <li><a class="secandry-nav-link" href="contact.php">Contact Us</a></li>
                        </ul>
                    </nav>
                    <div>
                        <h1>Stay In The Loop With Special Offers,
                            Plant-Parenting Tips, And More.
                        </h1>
                    </div>

                    <div class="email-container">
                        <form action="newsletter_subscribe.php" method="post">
                            <input class="email-text-box" type="email" id="email" name="email" placeholder="Enter your email" required><br><br>

                            <button class="email-submit-bt" type="submit">
                                <svg class="email-submit-bt-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="auto" height="auto" viewBox="0 0 256 256" xml:space="preserve">

                                    <defs>
                                    </defs>
                                    <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                        <polygon points="38.08,51.92 89,0.99 55.98,89 " style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: #287b7b;  opacity: 1;" transform="  matrix(1 0 0 1 0 0) " />
                                        <polygon points="38.08,51.92 1,34.02 89,0.99 " style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: #4fc4c3 ;  opacity: 1;" transform="  matrix(1 0 0 1 0 0) " />
                                        <path d="M 89.994 0.935 c -0.005 -0.088 -0.022 -0.174 -0.05 -0.258 c -0.011 -0.032 -0.021 -0.063 -0.035 -0.094 c -0.049 -0.107 -0.11 -0.209 -0.196 -0.295 c -0.086 -0.086 -0.188 -0.147 -0.295 -0.196 c -0.032 -0.014 -0.063 -0.024 -0.096 -0.035 c -0.082 -0.028 -0.166 -0.044 -0.252 -0.049 C 89.037 0.005 89.007 -0.001 88.976 0 c -0.108 0.003 -0.217 0.019 -0.322 0.058 L 0.649 33.083 c -0.375 0.141 -0.629 0.491 -0.647 0.891 s 0.204 0.772 0.564 0.946 l 36.769 17.745 l 17.745 36.77 C 55.246 89.781 55.597 90 55.98 90 c 0.015 0 0.03 0 0.045 -0.001 c 0.4 -0.019 0.751 -0.273 0.892 -0.647 L 89.942 1.346 C 89.981 1.24 89.997 1.131 90 1.022 C 90.001 0.992 89.995 0.964 89.994 0.935 z M 85.032 3.553 L 37.879 50.706 L 3.54 34.135 L 85.032 3.553 z M 55.865 86.461 l -16.572 -34.34 L 86.445 4.969 L 55.865 86.461 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(72,71,77); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                    </g>
                                </svg>
                            </button>
                        </form>



                    </div>

                </div>



                <div class="footer-socail-icon">
                    <?php if (!empty($settings['social_facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['social_facebook']); ?>" target="_blank" class="fb-icon">
                    <?php else: ?>
                    <div class="fb-icon">
                    <?php endif; ?>
                        <svg class="socail-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="256" height="256" viewBox="0 0 256 256" xml:space="preserve">

                            <defs>
                            </defs>
                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: #287b7b; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                <path d="M 45 0 C 20.147 0 0 20.147 0 45 c 0 24.853 20.147 45 45 45 c 24.853 0 45 -20.147 45 -45 C 90 20.147 69.853 0 45 0 z M 57.971 28.304 h -4.676 c -3.693 0 -4.411 1.755 -4.411 4.329 v 5.677 h 8.834 l -1.178 8.916 h -7.656 V 70 h -9.199 V 47.226 h -7.656 V 38.31 h 7.656 V 31.75 c 0 -7.627 4.647 -11.749 11.431 -11.749 c 2.29 -0.01 4.578 0.108 6.855 0.353 V 28.304 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: beige;  opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            </g>
                        </svg>
                    <?php if (!empty($settings['social_facebook'])): ?>
                    </a>
                    <?php else: ?>
                    </div>
                    <?php endif; ?>


                    <?php if (!empty($settings['social_twitter'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['social_twitter']); ?>" target="_blank" class="twiter-icon">
                    <?php else: ?>
                    <div class="twiter-icon">
                    <?php endif; ?>
                        <svg class="socail-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="256" height="256" viewBox="0 0 256 256" xml:space="preserve">

                            <defs>
                            </defs>
                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                <path d="M 45 0 C 20.147 0 0 20.147 0 45 c 0 24.853 20.147 45 45 45 c 24.853 0 45 -20.147 45 -45 C 90 20.147 69.853 0 45 0 z M 64.882 34.804 c 0.02 0.44 0.029 0.882 0.029 1.326 c 0 13.555 -10.318 29.187 -29.187 29.187 c -5.794 0 -11.185 -1.698 -15.725 -4.608 c 0.803 0.095 1.619 0.142 2.447 0.142 c 4.806 0 9.229 -1.64 12.741 -4.391 c -4.49 -0.084 -8.277 -3.049 -9.583 -7.125 c 0.625 0.12 1.268 0.185 1.928 0.185 c 0.936 0 1.843 -0.126 2.704 -0.361 c -4.693 -0.941 -8.23 -5.088 -8.23 -10.057 c 0 -0.045 0 -0.088 0.002 -0.131 c 1.383 0.769 2.964 1.231 4.646 1.283 c -2.754 -1.838 -4.565 -4.98 -4.565 -8.539 c 0 -1.879 0.507 -3.64 1.389 -5.156 c 5.059 6.207 12.619 10.289 21.144 10.718 c -0.176 -0.751 -0.266 -1.534 -0.266 -2.339 c 0 -5.663 4.594 -10.258 10.26 -10.258 c 2.95 0 5.616 1.247 7.488 3.241 c 2.337 -0.46 4.531 -1.315 6.514 -2.49 c -0.767 2.395 -2.393 4.405 -4.511 5.675 c 2.075 -0.248 4.053 -0.798 5.891 -1.615 C 68.626 31.55 66.885 33.357 64.882 34.804 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            </g>
                        </svg>
                    <?php if (!empty($settings['social_twitter'])): ?>
                    </a>
                    <?php else: ?>
                    </div>
                    <?php endif; ?>


                    <?php if (!empty($settings['social_instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['social_instagram']); ?>" target="_blank" class="inster-icon">
                    <?php else: ?>
                    <div class="inster-icon">
                    <?php endif; ?>
                        <svg class="socail-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="256" height="256" viewBox="0 0 256 256" xml:space="preserve">

                            <defs>
                            </defs>
                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                <path d="M 60.961 31.655 c 0 -1.437 -1.165 -2.602 -2.602 -2.602 c -1.437 0 -2.602 1.165 -2.602 2.602 c 0 1.437 1.165 2.602 2.602 2.602 C 59.797 34.256 60.961 33.092 60.961 31.655 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                <path d="M 45 33.079 c -6.584 0 -11.921 5.337 -11.921 11.921 c 0 1.646 0.334 3.214 0.937 4.64 c 0.603 1.426 1.476 2.711 2.555 3.789 c 2.157 2.157 5.138 3.492 8.43 3.492 c 3.292 0 6.272 -1.334 8.43 -3.492 c 1.079 -1.079 1.952 -2.363 2.555 -3.789 c 0.603 -1.426 0.937 -2.994 0.937 -4.64 C 56.921 38.416 51.584 33.079 45 33.079 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                <path d="M 45 0 C 20.147 0 0 20.147 0 45 c 0 24.853 20.147 45 45 45 c 24.853 0 45 -20.147 45 -45 C 90 20.147 69.853 0 45 0 z M 70 55.238 C 70 63.391 63.391 70 55.238 70 H 34.762 C 26.609 70 20 63.391 20 55.238 V 34.762 c 0 -3.057 0.929 -5.897 2.521 -8.253 C 25.174 22.582 29.666 20 34.762 20 h 20.477 c 5.095 0 9.588 2.582 12.241 6.508 C 69.071 28.864 70 31.704 70 34.762 V 55.238 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            </g>
                        </svg>
                    <?php if (!empty($settings['social_instagram'])): ?>
                    </a>
                    <?php else: ?>
                    </div>
                    <?php endif; ?>

                    <div class=" Linkind-icon"><svg class="socail-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="256" height="256" viewBox="0 0 256 256" xml:space="preserve">

                            <defs>
                            </defs>
                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                <path d="M 45 0 C 20.147 0 0 20.147 0 45 c 0 24.853 20.147 45 45 45 c 24.853 0 45 -20.147 45 -45 C 90 20.147 69.853 0 45 0 z M 31.187 69.956 H 20.822 V 36.617 h 10.365 V 69.956 z M 26.005 32.062 c -3.32 0 -6.005 -2.692 -6.005 -6.007 c 0 -3.318 2.685 -6.011 6.005 -6.011 c 3.313 0 6.005 2.692 6.005 6.011 C 32.01 29.37 29.317 32.062 26.005 32.062 z M 70 69.956 H 59.643 V 53.743 c 0 -3.867 -0.067 -8.84 -5.385 -8.84 c -5.392 0 -6.215 4.215 -6.215 8.562 v 16.491 H 37.686 V 36.617 h 9.939 v 4.559 h 0.141 c 1.383 -2.622 4.764 -5.385 9.804 -5.385 C 68.063 35.791 70 42.694 70 51.671 V 69.956 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            </g>
                        </svg>
                    </div>
                </div>

            </div>

        </div>



        <div class="bottom-copyrights">
            <div>Copyright &copy;<?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['store_name']); ?> | Designed By Deshan Dulmina</div>
            <div class="bottom-copyrights-PT">
                <a href="privacy_policy.php">Privacy Policy</a>
                <a href="terms.php">Terms & Services</a>
            </div>
        </div>

    </footer>
</body>

</html>