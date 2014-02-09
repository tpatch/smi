<?php include 'header.php'; ?>
    <div class="section login">
		<?php
			if ( strlen($_SESSION['gallery']) > 1 ) {
				header("location: /k/koken/_review/displaygallery.php?" . $_SESSION['qs']);
				echo 'You are now logged in. Please wait...';
			} else {
				echo 'Please be sure you used the correct keyword to view your gallery.';
			};
		?>
    </div>
<?php include 'footer.php'; ?>