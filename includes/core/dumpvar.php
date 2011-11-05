<?php

/**
 * @author Melvil (https://github.com/Melvil)
 **/
function dump($Var, $Var_s = null, $level_limit = 4)
{
	echo '<div align="left" class="debug">' . NL;
	echo dumpVar($Var, 0, $Var_s, $level_limit);
	echo '</div>';
	return true;
}

/**
 * @author Melvil (https://github.com/Melvil)
 **/
function dumpVar(&$Var, $Level = 0, $Var_s = null, $level_limit = 5)
{
	$error_strack_trace = '';
	$is_ob_ar = false;
	$Type = gettype($Var);

	if (is_array($Var))
	{
		$is_ob_ar = true;
		$Type = 'Array[' . count($Var) . ']';
	}
	if (is_object($Var)) $is_ob_ar = true;
	if ($Level == 0)
	{
		if ($Var_s) echo   NL . '<br>' . NL . '<b><span style="color:#ff0000">' . $Var_s . ' = {</span></b>';
		if ($is_ob_ar && count($Var)) echo '<pre>' . NL;
		else echo   NL . '<tt>';
		$Level_zero = 0;
	}
	if ($is_ob_ar)
	{
		
		if ($Type == 'object') echo '<span style="color:#05a209">object of</span> <span style="color:#A03000">' . get_class($Var) . '</span>';
		else echo '<span style="color:#05a209">' . $Type . '</span>';
		if ($Level > $level_limit)
		{
			if ($level_limit > 1) echo '<b>...</b> LEVEL > 5<br>' . NL;
			else echo   NL;
			return;
		}
		echo   NL;
		if ($Level == 0 || !is_object($Var))
			for (Reset($Var), $Level++; list($k, $v)=each($Var);)
			{
				for ($i = 0; $i < $Level*3; $i++) echo ' ';
				echo '<b>'.HtmlSpecialChars($k).'</b> => ';
				// if (is_object($v) || ($k === 'GLOBALS' && is_array($v))) { echo   "\n"; continue; }
				if ($k === 'GLOBALS' && is_array($v)) { echo   NL; continue; }
				dumpVar($v, $Level, null, $level_limit);
			}
	}
	else
	{
		$iss = is_string($Var);
		if ($iss && strlen($Var)>400)
			echo '('.$Type.') <span style="color:#35BBFA">strlen = '.strlen($Var).'</span>' . NL;
		else {
			echo '(' . $Type . ') ' . ($iss ? '"' : '') . '<span style="color:#0000FF">';
			if ($Type == 'boolean') echo   ($Var ? 'true' : 'false');
			else echo   HtmlSpecialChars($Var);
			echo '</span>' . ($iss ? '"' : '') . NL;
		}
	}
	if (isset($Level_zero))
	{
		if ($is_ob_ar && count($Var)) echo '</pre>'; else echo '</tt>';
		if ($Var_s) echo '<b><span style="color:#ff0000">}</span></b><br>' . NL;
	}
	return;
}
?>