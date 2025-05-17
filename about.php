<?php
session_start();
include 'db_connection.php';

// Get site settings
$settings = array(
    'store_name' => 'Kiyara Plants',
    'about_us' => '<h2>About Kiyara Plants</h2>
    <p>Kiyara Plants is a premium plant shop dedicated to bringing nature into your homes and offices.</p>
    <p>Founded with a passion for plants and a vision to make plant shopping an enjoyable experience, we carefully curate our collection to offer you the highest quality plants.</p>
    <h2>Our Mission</h2>
    <p>Our mission is to help everyone experience the joy and benefits of living with plants. We believe that plants not only beautify our spaces but also contribute to our well-being by purifying the air, reducing stress, and increasing productivity.</p>
    <h2>Quality Assurance</h2>
    <p>Every plant in our collection is carefully selected, nurtured, and inspected to ensure it meets our high standards. We work directly with trusted growers who share our commitment to quality and sustainability.</p>'
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

// Include header
include 'header.php';
?>

<link rel="stylesheet" href="contecnt/css/about.css">

<div class="tital">
    <h1>About Us</h1>
</div>

<div class="main-about-section">
    <div class="main-about">
        <div class="about">
            <?php 
            // Display about us content from settings
            if (!empty($settings['about_us'])) {
                echo $settings['about_us'];
            } else {
                echo '<div class="alert alert-info">About us content has not been set up yet.</div>';
            }
            ?>
        </div>
        
        <div class="team-main">
            <h2>Our Team</h2>
            <div class="team">
                <div class="team-member">
                    <img src="contecnt/img/Users/founder.png" alt="Founder">
                    <div class="team-member-detail-main">
                        <h3>Deshan Dulmina</h3>
                        <h4>Founder</h4>
                        <p class="founder">With a deep passion for plants and sustainable living, our founder envisioned Kiyara Plants as a place where people could find joy in bringing nature into their homes.</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-member-detail-main">
                        <h3>Randi Wasana</h3>
                        <h4>Co-Founder</h4>
                        <p class="co-founder">Our co-founder brings expertise in plant care and a commitment to customer education, ensuring that every plant parent has the knowledge they need to help their plants thrive.</p>
                    </div>
                    <img src="contecnt/img/Users/Co-founder.png" alt="Co-founder">
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>

<style>
/* Fix for zoomed appearance */
.main-about-section {
    zoom: 1;
    transform: scale(1);
}
.main-about {
    max-width: 1200px;
    margin: 0 auto;
}
</style>