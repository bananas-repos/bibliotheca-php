<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2023 Johannes Keßler
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
		<a id="showNavSearch" href="#" onclick="showNavSearch();">Search</a>
	</li>
	<li role="tab" <?php if($_requestMode == 'advancedsearch') echo 'aria-selected="true"'; ?>>
		<a href="index.php?p=advancedsearch">Advanced search</a>
	</li>
<?php if(!empty($_menuManage)) { ?>
	<li role="tab"><a href="">Manage:</a></li>
	<?php foreach($_menuManage as $entry) { ?>
		<li role="tab" <?php if(!empty($_requestMode) && str_starts_with($entry['action'], $_requestMode)) echo 'aria-selected="true"'; ?>>
			<a href="index.php?p=<?php echo $entry['action']; ?>"><?php echo $entry['text']; ?></a>
		</li>
	<?php } ?>
<?php } ?>
</menu>


<dialog id="navSearchDialog" class="window">
	<div class="title-bar">
		<div class="title-bar-text">Search</div>
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
			<input type="search" placeholder="Search..." name="navSearch" autofocus>
			<input type="submit" value="Search" />
			<p><a href="index.php?p=advancedsearch">Advanced</a></p>
		</form>
	</div>
	<div class="status-bar">
		<p class="status-bar-field">Press enter to search</p>
		<p class="status-bar-field">Search within the default search field</p>
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
