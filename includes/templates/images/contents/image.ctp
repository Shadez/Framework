<h1>Images Uploader</h1>

<p>Select Images to Upload</p>

<div id="container">
    <div id="filelist"></div>
    <a id="pickfiles" href="javascript:;">[Select Files]</a> 
    <a id="uploadfiles" href="javascript:;">[Upload]</a>
</div>
<div id="uploaded_files"><br /></div>


<script type="text/javascript">
var cleanupList = false;
// Custom example logic
function $(id) {
	return document.getElementById(id);	
}

var uploader = new plupload.Uploader({
	runtimes : 'html5',
	browse_button : 'pickfiles',
	container: 'container',
	max_file_size : '10mb',
	url : '<?php echo $this->getUrl('upload'); ?>',
	filters : [
		{title : "Image files", extensions : "jpg,gif,png"},
		{title : "Zip files", extensions : "zip"}
	]
});

uploader.bind('FilesAdded', function(up, files) {
	for (var i in files) {
		$('filelist').innerHTML += '<div id="' + files[i].id + '">' + files[i].name + ' (' + plupload.formatSize(files[i].size) + ') <b></b></div>';
	}
});

uploader.bind('UploadComplete', function(up, files) {
	for (var i in files) {
		uploader.removeFile(files[i]);
	}

	$('filelist').innerHTML = '';

	cleanupList = true;
});

uploader.bind('UploadProgress', function(up, file) {
	$(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
});

uploader.bind('FileUploaded', function(up, file, resp) {
	if (cleanupList)
	{
		jQuery('#uploaded_files').html('<br />');

		cleanupList = false;
	}

	var r = jQuery.parseJSON(resp.response);

	if (r)
	{
		jQuery('#uploaded_files').append('<b>' + file.name + '</b>: <a href="<?php echo $this->getUrl('i/'); ?>' + r.slug + '" target="_blank">Full size</a> | <a href="<?php echo $this->getUrl('i/'); ?>' + r.slug + '/t" target="_blank">Thumbnail</a><br />');
	}
});

$('uploadfiles').onclick = function() {
	uploader.start();
	return false;
};

uploader.init();
</script>