<?php include 'header.php'; ?>
    <div class="section login">
    	<img src="../_styles/images/logo.png" alt="Scott Michael Images" />
        <h2>Client Review Login</h2>
        <form name="clientlogin" method="post" action="logincheck.php" id="clientlogin">
            <input name="username" type="text" id="username" placeholder="Username">
            <input name="password" type="password" id="password" placeholder="Password">
            <input name="keyword" type="text" id="keyword" placeholder="Keyword">
            <input type="submit" name="submit" value="Login" id="login">
        </form>
    </div>
<?php include 'footer.php'; ?>
        