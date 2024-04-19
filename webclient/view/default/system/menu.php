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
<nav class="uk-container uk-container-expand uk-navbar-container" uk-navbar>
	<div class="uk-navbar-left">
		<ul class="uk-navbar-nav">
			<li class="uk-parent">
				<a href=""><?php echo $I18n->t('menu.lv1.show'); ?></a>
				<div class="uk-navbar-dropdown">
					<ul class="uk-nav uk-navbar-dropdown-nav">
						<?php foreach($_menuShow as $entry) { ?>
							<li>
								<a href="index.php?p=<?php echo $entry['action']; ?>">
									<span class="uk-icon uk-margin-small-right" uk-icon="icon: <?php echo $entry['icon']; ?>"></span>
									<?php echo $entry['text']; ?>
								</a>
							</li>
						<?php } ?>
					</ul>
				</div>
			</li>
			<?php if(!empty($_menuManage)) { ?>
			<li class="uk-parent">
				<a href=""><?php echo $I18n->t('menu.lv1.manage'); ?></a>
				<div class="uk-navbar-dropdown">
					<ul class="uk-nav uk-navbar-dropdown-nav">
						<?php foreach($_menuManage as $entry) { ?>
							<li>
								<a href="index.php?p=<?php echo $entry['action']; ?>">
									<span class="uk-icon uk-margin-small-right" uk-icon="icon: <?php echo $entry['icon']; ?>"></span>
									<?php echo $entry['text']; ?>
								</a>
							</li>
						<?php } ?>
					</ul>
				</div>
			</li>
			<?php } ?>
			<li>
				<a href="index.php?p=auth">
					<?php if($Doomguy->isSignedIn() === true) {
                        echo $I18n->t('auth.logout');
                    }
                    else { echo $I18n->t('global.login'); } ?>
				</a>
			</li>
		</ul>
	</div>
	<div class="uk-navbar-right">
		<div>
			<a class="uk-navbar-toggle" uk-search-icon href="#"></a>
			<div class="uk-drop" uk-drop="mode: click; pos: left-center; offset: 0">
				<form class="uk-search uk-search-navbar uk-width-1-1" method="get" action="index.php">
					<?php
						if(isset($TemplateData['navSearchAction'])) {
							foreach($TemplateData['navSearchAction'] as $param=>$pValue) {
								echo '<input type="hidden" name="'.$param.'" value="'.$pValue.'" />';
							}
						}
					?>
					<input class="uk-search-input" type="search" placeholder="<?php echo $I18n->t('menu.search'); ?>" name="navSearch" autofocus>
					<small><a href="index.php?p=advancedsearch"><?php echo $I18n->t('menu.search.advanced'); ?></a></small>
				</form>
			</div>
		</div>
	</div>
</nav>
