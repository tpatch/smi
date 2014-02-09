<?php include 'header.php'; ?>
    <div class="section gallery">
    	<form>
		<?php
			// API code
			require_once '../lib/Koken.php';

			// Get album id
			// Connect to server and select databse.
		    mysql_connect("$host", "$username", "$password")or die("cannot connect"); 
		    mysql_select_db("$db_name")or die("cannot select DB");

			$gallery = $_SESSION['gallery'];
			$sql = "SELECT Id FROM koken_albums WHERE REPLACE(Title, ' ', '') = '".$gallery."'";
		    $result = mysql_query($sql);

		    if ( mysql_num_rows($result) === 1 ) {
			    while($row = mysql_fetch_assoc($result)){
				    foreach($row as $cname => $cvalue){
				        $gallery_number = $cvalue;
				    };
				};

				$koken = new Koken('http://trevorpatch.com/k/koken');
				$data = $koken->call("/albums/" . $gallery_number . "/content");
			    
			    $count = $data->counts->images;
				$counter = 1;
				$orientation = '';

				echo "<h2>{$data->album->title}</h2>";
			    foreach ( $data->content as $photo ) {
			    	if ( $photo->aspect_ratio < 1 ) {
			    		$orientation = 'vertical';
			    	} else {
			    		$orientation = 'horizontal';
			    	};

			    	if ( (($counter + 4) % 5) === 0 ) {
			    		echo "<div class='row'>";
			    	};

			    	echo "<div class='review-thumb span3'>";
			    	echo "<a href='{$photo->presets->large->url}'>";
			    	echo "<img src='{$photo->presets->medium->url}' class='" . $orientation . "' />";
				    echo "</a>";
				    echo "<input type='checkbox' value='{$photo->filename}' name='imageselection' class='check'>";
				    echo "</div>";

					if ( ($counter % 5) === 0 || $counter === $count ) {
			    		echo "</div>";
			    	};

			    	$counter++;
				};
			} else {
				echo "<h4>Sorry, we couldn't find any records for that information. <a href=''>Please try again</a>.</h4>";
			};
		?>
		<div class="submitwrap">
			<input type='submit' name='selectionssubmit' value='Submit' class='selectionssubmit' />
			<p>Select your favorite images and submit when finished</p>
		</div>
		</form>
    </div>
<?php include 'footer.php'; ?>
        