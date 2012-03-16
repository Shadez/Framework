<!doctype html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title><?php echo $this->c('Layout')->getPageTitle(); ?></title>

<?php
echo $this->c('Document')->releaseCss('header');
echo $this->c('Document')->releaseJs('header');
?>
</head>
<body>

<?php echo $this->region('pagecontent'); ?>
</body>
</html>