<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	<link type="text/css" rel="stylesheet" href="view/98/ui/css/98.min.css" />
	<link type="text/css" rel="stylesheet" href="view/98/ui/css/style.css" />

	<meta name="author" content="https://www.bananas-playground.net/projekt/bibliotheca" />
	<title><?php echo $TemplateData['pageTitle']; ?> - Bibliotheca</title>
</head>
<body>
	<header>
		<?php require_once $ViewMenu; ?>
	</header>

	<div class="window" role="tabpanel">
		<main class="window-body">
			<?php require_once $ViewMessage; ?>
			<?php require_once $View; ?>
		</main>
		<footer class="status-bar">
			<p class="status-bar-field"><a href="https://www.bananas-playground.net/projekt/bibliotheca/" target=_blank>Bibliotheca</a></p>
			<p class="status-bar-field">&copy; 2018 - <?php echo date('Y'); ?></p>
		</footer>
	</div>

</body>
</html>
