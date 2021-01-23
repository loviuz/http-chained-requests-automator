<?php
session_start();
unset( $_SESSION['logged_in'] );

// Login check and redirect to welcome page
if( $_POST['username'] == 'admin' && $_POST['password'] == 's3cr3t' ){
    $_SESSION['logged_in'] = true;
    header("Location: /mysite/welcome.php");
    exit();
}
?>

<html>
    <head>
        <title>Login</title>
    </head>

    <body>
        <form method="post">
            <input type="username" name="username">
            <input type="password" name="password">
            <button type="submit">LOGIN</button>
        </form>
    </body>
</html>