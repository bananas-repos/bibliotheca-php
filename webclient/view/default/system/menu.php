<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2020 Johannes Keßler
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
<nav class="uk-navbar-container" uk-navbar>
	<div class="uk-navbar-left">
		<ul class="uk-navbar-nav">
			<li class="uk-parent">
				<a href="">Show</a>
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
				<a href="">Manage</a>
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
					<?php if($Doomguy->isSignedIn() === true) { echo "Logout"; } else { echo "Login"; } ?>
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
					<input class="uk-search-input" type="search" placeholder="Search..." name="navSearch" autofocus>
					<small><a href="index.php?p=advancedsearch">Advanced</a></small>
				</form>
			</div>
		</div>
	</div>
</nav>
