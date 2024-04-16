<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2023 Johannes Keßler
 *
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see http://www.gnu.org/licenses/gpl-3.0.
 */

## optional context action
# the key has to match the column contextaction of the menuentry
# $_collection comes from the $Viewscript
$_contextActions = array();
if(!empty($_collection)) {
	$_contextActions['collection'] = $_collection;
}

$_menuShow = $Gorenest->get('show');
$_menuManage = $Gorenest->get('manage', false, $_contextActions);
?>

<menu role="tablist">
	<?php foreach($_menuShow as $entry) { ?>
		<li role="tab" <?php if($_requestMode == $entry['action']) echo 'aria-selected="true"'; ?>>
			<a href="index.php?p=<?php echo $entry['action']; ?>"><?php echo $entry['text']; ?></a>
		</li>
	<?php } ?>
	<li role="tab">
		<a href="index.php?p=auth">
			<?php if($Doomguy->isSignedIn() === true) { echo "Logout"; } else { echo "Login"; } ?>
		</a>
	</li>
	<li role="tab">
		<a id="showNavSearch" href="#" onclick="showNavSearch();"><?php echo $I18n->t('menu.search'); ?></a>
	</li>
	<li role="tab" <?php if($_requestMode == 'advancedsearch') echo 'aria-selected="true"'; ?>>
		<a href="index.php?p=advancedsearch"><?php echo $I18n->t('menu.search.advanced'); ?></a>
	</li>
<?php if(!empty($_menuManage)) { ?>
	<li role="tab"><a href=""><?php echo $I18n->t('menu.lv1.manage'); ?>:</a></li>
	<?php foreach($_menuManage as $entry) { ?>
		<li role="tab" <?php if(!empty($_requestMode) && str_starts_with($entry['action'], $_requestMode)) echo 'aria-selected="true"'; ?>>
			<a href="index.php?p=<?php echo $entry['action']; ?>"><?php echo $entry['text']; ?></a>
		</li>
	<?php } ?>
<?php } ?>
</menu>


<dialog id="navSearchDialog" class="window">
	<div class="title-bar">
		<div class="title-bar-text"><?php echo $I18n->t('menu.search'); ?></div>
		<div class="title-bar-controls">
			<button aria-label="Close" id="closeNavSearchDialog"></button>
		</div>
	</div>
	<div class="window-body">
		<form method="get" action="index.php">
			<?php
			if(isset($TemplateData['navSearchAction'])) {
				foreach($TemplateData['navSearchAction'] as $param=>$pValue) {
					echo '<input type="hidden" name="'.$param.'" value="'.$pValue.'" />';
				}
			}
			?>
			<input type="search" placeholder="<?php echo $I18n->t('menu.search'); ?>" name="navSearch" autofocus>
			<input type="submit" value="<?php echo $I18n->t('menu.search'); ?>" />
			<p><a href="index.php?p=advancedsearch"><?php echo $I18n->t('menu.search.advanced'); ?></a></p>
		</form>
	</div>
	<div class="status-bar">
		<p class="status-bar-field"><?php echo $I18n->t('menu.search.enter'); ?></p>
		<p class="status-bar-field"><?php echo $I18n->t('menu.search.within'); ?></p>
	</div>
</dialog>

<script>
	const navSearchDialog = document.getElementById('navSearchDialog');
	const closeNavSearchDialog = document.getElementById('closeNavSearchDialog');

	function showNavSearch() {
		navSearchDialog.showModal();
	}

	closeNavSearchDialog.addEventListener('click', () => {
		navSearchDialog.close();
	});
</script>
