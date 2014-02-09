<?php 
    session_start();

    if ( (!isset($_SESSION['auth']) || $_SESSION['auth'] === 0) && strpos($_SERVER['SCRIPT_NAME'], 'login.php') == false ) {
        header("location: /k/koken/_review/login.php");
    } else if ( isset($_SESSION['auth']) && $_SESSION['auth'] === 1 && strpos($_SERVER['SCRIPT_NAME'], 'displaygallery.php') == false ) {
        header("location: /k/koken/_review/displaygallery.php");
    };

    $host = "mysql11.hostek.com";
    $username = "tpatch";
    $password = "tpfe_7281";
    $db_name = "koken";
?>


<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Scott Michael Photography | Client Portal Login</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width">

        <link rel="stylesheet" href="../_styles/normalize.css">
        <link rel="stylesheet" href="../_styles/bootstrap.min.css">
        <link rel="stylesheet" href="../_styles/styles.css">
        <script src="../_scripts/vendor/modernizr-2.6.1.min.js"></script>
    </head>
    <body class="clientlogin">
        <!--[if lt IE 7]>
            <p class="chromeframe">You are using an outdated browser. <a href="http://browsehappy.com/">Upgrade your browser today</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to better experience this site.</p>
        <![endif]-->