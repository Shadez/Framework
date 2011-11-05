<h1>Create Models</h1>
<a href="/tools/models">Back to Models</a>
<hr />
<h3>Select table:</h3>
<?php
$tables = $models->getTables();
if ($tables) :
?>
<select onchange="document.location.href='/tools/models/create?step=2&dbtype=<?php echo $_GET['dbtype']; ?>&table=' + this.value;">
<option value="-1">-- Select --</option>
<?php
	foreach ($tables as $t) :
?>
<option value="<?php echo $t; ?>"><?php echo $t; ?></option>
<?php endforeach; ?>
</select><br /><br />
<a href="/tools/models/create?step=2&dbtype=<?php echo $_GET['dbtype'] ?>&table=-1">Generate All</a>
<?php endif; ?>