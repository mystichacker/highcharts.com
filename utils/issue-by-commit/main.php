<?php
	ini_set('display_errors', 'on');

	session_start();

	require_once('Git.php');

	try {
		$repo = Git::open(dirname(__FILE__) . '/../../');
		$branches = $repo->list_branches();
	} catch (Exception $e) {
		$error = "Error connecting to the local git repo <b>highcharts.com</b>. Make sure git is running.<br/><br>" . $e->getMessage();
	}


	$commit = @$_GET['commit'];
	$tempDir = sys_get_temp_dir();

	// Defaults
	if (!@$_SESSION['branch']) {
		$_SESSION['after'] = strftime('%Y-%m-%d', mktime() - 30 * 24 * 3600);
		$_SESSION['before'] = strftime('%Y-%m-%d', mktime());
		$_SESSION['branch'] = 'master';
	}

	if (@$_POST['branch']) {
		try {
			$_SESSION['branch'] = @$_POST['branch'];
			$_SESSION['after'] = @$_POST['after'];
			$_SESSION['before'] = @$_POST['before'];
			$activeBranch = $repo->active_branch();
			$repo->checkout($_SESSION['branch']);
			$repo->run('log > ' . $tempDir . '/log.txt --format="%H %ci %s" ' .
				'--first-parent --after={' . $_SESSION['after'] . '} --before={' . $_SESSION['before'] . '}');
			$repo->checkout($activeBranch);


			$commitsKey = join(array($_SESSION['branch'],$_SESSION['after'],$_SESSION['before']), ',');
		} catch (Exception $e) {
			$error = $e->getMessage();
		}
	}

	// handle input data
	if (@$_POST['html']) {
		$_SESSION['html'] = stripslashes($_POST['html']);
	}
	if (@$_POST['js']) {
		$_SESSION['js'] = stripslashes($_POST['js']);
	}


	// Get demo code
	$html = isset($_SESSION['html']) ? $_SESSION['html'] : file_get_contents('demo.html');
	$js = isset($_SESSION['js']) ? $_SESSION['js'] : file_get_contents('demo.js');


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<style type="text/css">
		textarea {
			font-family: monospace;
			color: green;
		}
		</style>
		<?php if (@$_POST['branch']) : ?>
		<script>
			window.onload = function () {
				var commitsKey = "<?php echo $commitsKey ?>";

				// If we have loaded a new branch or new dates, update commits frame
				//if (window.parent.commitsKey) {
					window.parent.frames[0].location.reload();
				//}

				window.parent.commitsKey = commitsKey;
			}
		</script>
		<? endif; ?>
	</head>
	
	<body>
		
		
<?php if ($commit) {
	printf($html, $commit, $commit, $commit, $commit, $commit, $commit, $commit, $commit, $commit, $commit);

		
	echo "<script>$js</script>";	
	echo '<hr/>';
	echo "<a target='_blank' href='https://github.com/highslide-software/highcharts.com/commit/$commit'>View commit ". substr($commit, 0, 8) ."</a>";

} else { ?>

<?php if (@$error) { 
	echo "<pre style='margin: 2em; padding: 2em; background: red; color: white; border-radius: 5px'>$error</pre>";
} ?>


<form method="post" action="main.php">
<b>Paste HTML</b> here (including framework and Highcharts, use %s for commit):<br/>
<textarea name="html" rows="6" style="width: 100%"><?php echo $html; ?></textarea>
	
<br/>
<b>Paste JS</b> here:<br/>
<textarea name="js" rows="30" style="width: 100%"><?php echo $js; ?></textarea><br/>

Load commits in <b>branch</b>
<select name="branch">
<?php 
foreach ($branches as $branchOption) {
	$selected = ($branchOption == $_SESSION['branch']) ? 'selected="selected"' : '';
	echo "<option value='$branchOption' $selected>$branchOption</option>\n";
}
?>
</select>

from
<input type="text" name="after" value="<?php echo $_SESSION['after'] ?>" />
to
<input type="text" name="before" value="<?php echo $_SESSION['before'] ?>" />

<br/>
<br/>

<input type="submit" value="Submit"/>

	<br/>
	<br/>
</form>
<?php } ?>
		
	</body>
</html>