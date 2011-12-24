<h1><?php echo $title; ?></h1>
<h3>DB Texts</h3>
<?php
for ($i = 0; $i < $text->getRowsCount(); ++$i)
{
	$row = $text->getRow();
	echo $row['text'] . '<hr />';
	$text->next();
}
?>