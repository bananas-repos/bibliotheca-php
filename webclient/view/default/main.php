<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	<link type="text/css" rel="stylesheet" href="view/default/ui/css/uikit.min.css"  media="screen,projection"/>
	<link type="text/css" rel="stylesheet" href="view/default/ui/css/style.css"  media="screen,projection"/>
	<script type="text/javascript" src="view/default/ui/js/uikit.min.js"></script>
	<script type="text/javascript" src="view/default/ui/js/uikit-icons.min.js"></script>

	<meta name="author" content="https://www.bananas-playground.net/projekt/bibliotheca" />
	<title>Bibliotheca</title>
</head>
<body>
	<header>
		<?php require_once $ViewMenu; ?>
	</header>

	<main>
		<div class="uk-container uk-container-expand uk-margin-top">
			<?php require_once $ViewMessage; ?>
			<?php require_once $ViewPagination; ?>
			<?php require_once $View; ?>
		</div>
	</main>

	<footer>
		<div class="uk-container uk-container-expand">
			<p>&nbsp;</p>
		</div>
	</footer>
</body>
</html>
