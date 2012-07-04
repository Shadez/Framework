<?php if (!isset($appCrash) || !$appCrash) return; ?>
<!--
	Original File: Yii Framework Exception Page
	Package: Yii Framework 1.1.8
-->

<!DOCTYPE html PUBLIC
	"-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Exception</title>

<style type="text/css">
/*<![CDATA[*/
html,body,div,span,applet,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,a,abbr,acronym,address,big,cite,code,del,dfn,em,font,img,ins,kbd,q,s,samp,small,strike,strong,sub,sup,tt,var,b,u,i,center,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td{border:0;outline:0;font-size:100%;vertical-align:baseline;background:transparent;margin:0;padding:0;}
body{line-height:1;}
ol,ul{list-style:none;}
blockquote,q{quotes:none;}
blockquote:before,blockquote:after,q:before,q:after{content:none;}
:focus{outline:0;}
ins{text-decoration:none;}
del{text-decoration:line-through;}
table{border-collapse:collapse;border-spacing:0;}

body {
	font: normal 9pt "Verdana";
	color: #000;
	background: #fff;
}

h1 {
	font: normal 18pt "Verdana";
	color: #f00;
	margin-bottom: .5em;
}

h2 {
	font: normal 14pt "Verdana";
	color: #800000;
	margin-bottom: .5em;
}

h3 {
	font: bold 11pt "Verdana";
}

pre {
	font: normal 11pt Menlo, Consolas, "Lucida Console";
}

pre span.error {
	display: block;
	background: #fce3e3;
}

pre span.ln {
	color: #999;
	padding-right: 0.5em;
	border-right: 1px solid #ccc;
}

pre span.error-ln {
	font-weight: bold;
}

.container {
	margin: 1em 4em;
}

.version {
	color: gray;
	font-size: 8pt;
	border-top: 1px solid #aaa;
	padding-top: 1em;
	margin-bottom: 1em;
}

.message {
	color: #000;
	padding: 1em;
	font-size: 11pt;
	background: #f3f3f3;
	-webkit-border-radius: 10px;
	-moz-border-radius: 10px;
	border-radius: 10px;
	margin-bottom: 1em;
	line-height: 160%;
}

.source {
	margin-bottom: 1em;
}

.code pre {
	background-color: #ffe;
	margin: 0.5em 0;
    padding: 0.5em;
    line-height: 125%;
    border: 1px solid #eee;
}

.source .file {
	margin-bottom: 1em;
	font-weight: bold;
}

.traces {
	margin: 2em 0;
}

.trace {
	margin: 0.5em 0;
	padding: 0.5em;
}

.trace.app {
	border: 1px dashed #c00;
}

.trace .number {
	text-align: right;
	width: 2em;
	padding: 0.5em;
}

.trace .content {
	padding: 0.5em;
}

.trace .plus,
.trace .minus {
	display:inline;
	vertical-align:middle;
	text-align:center;
	border:1px solid #000;
	color:#000;
	font-size:10px;
	line-height:10px;
	margin:0;
	padding:0 1px;
	width:10px;
	height:10px;
}

.trace.collapsed .minus,
.trace.expanded .plus,
.trace.collapsed pre {
	display: none;
}

.trace-file {
	cursor: pointer;
	padding: 0.2em;
}

.trace-file:hover {
	background: #f0ffff;
}
/*]]>*/
</style>
</head>

<body>
<div class="container">
	<h1>Exception (<?php echo $appCrash->getType(); ?>)</h1>

	<p class="message">
		<?php echo $appCrash->getMessage(); ?> 	</p>

	<div class="source">
		<p class="file"><?php
		$eLine = $appCrash->getException()->getLine();
		echo $appCrash->getException()->getFile() . '(' . $eLine . ')'; ?></p>
<?php
$lines = $appCrash->getExceptionInfo('lines');
if ($lines) :
?>
<div class="code">
<pre>
<?php
foreach ($lines as $line)
{
	if (($line['line'] + 1) == $eLine)
		echo '<span class="error"><span class="ln error-ln">' . $line['line'] . '</span>' . $line['content'] . '</span>';
	else
		echo '<span class="ln">' . $line['line'] . '</span>' . $line['content'] . NL;
}
?>
</pre></div>
<?php endif; ?>
</div>

	<div class="traces">
		<h2>Stack Trace</h2>
				<table style="width:100%;">
<?php
$trace = $appCrash->getTrace();
$size = sizeof($trace);
if ($trace)
{
	for ($i = 0; $i < $size; ++$i)
	{
		echo '<tr class="trace core collapsed">' . NL .
		'<td class="number">#' . $i . '</td>' . NL .
		'<td class="content">' . NL .
		'<div class="trace-file">' . NL .
		'<div class="plus">+</div><div class="minus">-</div>' . NL .
		'&nbsp;' . (!empty($trace[$i]['file']) ? $trace[$i]['file'] : 'unknown file') . '(' . (!empty($trace[$i]['line']) ? $trace[$i]['line'] : 'unknown line') . '): ';
		if (isset($trace[$i]['class']))
			echo '<strong>' . $trace[$i]['class'] . '</strong>' . $trace[$i]['type'];
		echo '<strong>' . $trace[$i]['function'] . '</strong>()' . NL .
		'<div class="code"><pre>' . NL;
		if (!empty($trace[$i]['lineContents']))
		{
			foreach ($trace[$i]['lineContents'] as $line)
			{
				if (($line['line'] + 1) == $trace[$i]['line'])
					echo '<span class="error"><span class="ln error-ln">' . $line['line'] . '</span>' . $line['content'] . '</span>';
				else
					echo '<span class="ln">' . $line['line'] . '</span>' . $line['content'] . NL;
			}
		}
		echo '</pre></div></td></tr>' . NL;
	}
}
?>
</table>
	</div>
	<div class="version">
		<?php echo date('d.m.Y H:i'); ?>
	</div>
</div>

<script type="text/javascript">
/*<![CDATA[*/
var traceReg = new RegExp("(^|\\s)trace-file(\\s|$)");
var collapsedReg = new RegExp("(^|\\s)collapsed(\\s|$)");

var e = document.getElementsByTagName("div");
for(var j=0,len=e.length;j<len;j++){
	if(traceReg.test(e[j].className)){
		e[j].onclick = function(){
			var trace = this.parentNode.parentNode;
			if(collapsedReg.test(trace.className))
				trace.className = trace.className.replace("collapsed", "expanded");
			else
				trace.className = trace.className.replace("expanded", "collapsed");
		}
	}
}
/*]]>*/
</script>

</body>
</html>