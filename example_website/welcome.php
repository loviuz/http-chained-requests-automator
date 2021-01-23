<?php
session_start();

$your_email = "dude@dudelang.com";

// Redirect to login if not logged in
if( !$_SESSION['logged_in'] ){
    header("Location: /mysite");
    exit();
}

// Set a cookie if email passed via GET are your
if( $_GET['email'] == $your_email ){
    setcookie("flag2", 'wow_second_flag', time()+3600);
}

?>
<html>
    <head>
        <title>Welcome dude!</title>
    </head>

    <body>
        <p>Hi dude!</p>
        <p>Your email is <?php echo $your_email; ?>!</p>
        <p>Congratulations, the flag is: Sup3rS3cr3tFl4g</p>
        <p>The second flag is in the cookies :-)</p>
    </body>
</html>