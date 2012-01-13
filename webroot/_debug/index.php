<?php

/**
 * Copyright (C) 2009-2012 Shadez <https://github.com/Shadez>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 **/

if(isset($_GET['clearLog'])) {
    @file_put_contents('tmp.dbg', null);
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
<title>Debug Log</title>
</head>
<body>
	<a href="index.php?clearLog">Clear log</a> | <a href="">Reload</a>
	<br />
	<hr />
	<?php @include('tmp.dbg'); ?>

</body>
</html>