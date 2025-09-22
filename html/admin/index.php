<?php
require_once("../includes.php");
if (isset($_REQUEST['page'])) {
	$page = $_REQUEST['page'];
} else {
	$page = 'production';
}?>
<html>
<head>
<script src="../js/jquery.min.js"></script>
<title><?=$page?></title>
</head>
<body>

<div id="admin_menu" style="clear: both;">
<a href="index.php?page=production">ProductionType</a>
</div>

<div>
<?require_once "$page.php";?>
</div>

</body>
</html>
