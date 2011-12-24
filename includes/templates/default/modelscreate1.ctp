<h1>Create Models</h1>
<a href="/tools/models">Back to Models</a>
<hr />
<h3>Select table:</h3>
<?php
$tables = $models->getTables();
if ($tables) :
?>
<select onchange="document.location.href='<?php echo $this->getUrl('tools/models/create?step=2&dbtype=' . $_GET['dbtype']); ?>&table=' + this.value;">
<option value="-1">-- Select --</option>
<?php
	foreach ($tables as $t) :
?>
<option value="<?php echo $t; ?>"><?php echo $t; ?></option>
<?php endforeach; ?>
</select><br /><br />
<a href="<?php echo $this->getUrl('tools/models/create?step=2&dbtype=' . $_GET['dbtype']); ?>&table=-1">Generate All</a>
<?php endif; ?>