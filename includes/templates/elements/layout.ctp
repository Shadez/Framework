<!doctype html>
<html>
<head>
<title><?php echo $this->c('Layout')->getPageTitle(); ?></title>
</head>
<body>
<?php
if ($this->issetRegion('left')) echo $this->region('left');
if ($this->issetRegion('pagecontent')) echo $this->region('pagecontent');
?>
</body>
</html>
