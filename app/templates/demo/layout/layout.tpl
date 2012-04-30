<html>
<head>
	<title><?php echo $this->c('Layout')->getPageTitle(); ?></title>
	<?php echo $this->c('Layout')->releaseMetaTags(); ?>
	<?php echo $this->c('Layout')->releaseCss('header'); ?>
	<?php echo $this->c('Layout')->releaseJs('header'); ?>
</head>
<body>
	<?php echo $this->getRegionContents('pagecontent'); ?>
</body>
</html>