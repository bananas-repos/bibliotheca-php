<!DOCTYPE html>
<html lang="<?php echo $I18n->twoCharLang(); ?>">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	<link type="text/css" rel="stylesheet" href="view/default/ui/css/uikit.min.css"  media="screen,projection"/>
	<link type="text/css" rel="stylesheet" href="view/default/ui/css/style.css"  media="screen,projection"/>
	<script type="text/javascript" src="view/default/ui/js/uikit.min.js"></script>
	<script type="text/javascript" src="view/default/ui/js/uikit-icons.min.js"></script>

	<meta name="author" content="https://www.bananas-playground.net/projekt/bibliotheca" />
	<title><?php echo $TemplateData['pageTitle']; ?> - Bibliotheca</title>
</head>
<body>
	<header>
		<?php require_once $ViewMenu; ?>
	</header>

	<main>
		<div class="uk-container uk-container-expand uk-margin-top">
			<?php require_once $ViewMessage; ?>
			<?php require_once $View; ?>
		</div>
	</main>

	<footer>
		<div class="uk-section uk-section-default">
			<div class="uk-container uk-container-expand">
				<p class="uk-text-muted uk-text-small">&copy; 2018 - <?php echo date('Y'); ?> <a href="https://www.bananas-playground.net/projekt/bibliotheca/" target=_blank>Bibliotheca</a></p>
			</div>
		</div>
	</footer>
</body>
</html>
