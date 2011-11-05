<h1>Create Models</h1>
<a href="/tools/models">Back to Models</a>
<hr />
<h3>Select DB type:</h3>
<?php
$db = $models->getDatabases();
if ($db) :
	foreach ($db as $d) :
?>
<a href="/tools/models/create?step=1&dbtype=<?php echo $d; ?>"><?php echo $d; ?></a><br />
<?php endforeach; endif; ?>