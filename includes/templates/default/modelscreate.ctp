<h1>Create Models</h1>
<a href="<?php echo $this->getUrl('tools/models'); ?>">Back to Models</a>
<hr />
<h3>Select DB type:</h3>
<?php
$db = $models->getDatabases();
if ($db) :
	foreach ($db as $d) :
?>
<a href="<?php echo $this->getUrl('tools/models/create?step=1&dbtype=' . $d); ?>"><?php echo $d; ?></a><br />
<?php endforeach; endif; ?>