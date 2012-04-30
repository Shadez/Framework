<html>
<head>
	<style>
	<!--
		 html {margin:0; padding:0 } 
		 body {margin:0; padding:0; width:100%; height:100% }
		 td { text-align:center; vertical-align:middle }
	-->
	</style>
</head>
<body>
	<table width="100%" height="100%">
	<tr>
		<td>
			<p>Sorry, but action you've requested was crashed with critical error: "<strong><?php echo $appCrash->getMessage() . ' (' . $appCrash->getType() . ')'; ?></strong>".</p>
			<p>If you can, please, contact with site administrator and tell him about this error.</p>
		</td>
	</tr>
</table>
</body>
</html>