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
 *  along with this program. If not, see http://www.gnu.org/licenses/gpl-3.0
 */

if(isset($TemplateData['message']['content'])) {
	$cssClass = 'primary';
	if(isset($TemplateData['message']['status'])) {
		switch($TemplateData['message']['status']) {
			case 'error':
				$cssClass = 'danger';
			break;
			case 'warning':
				$cssClass = 'warning';
			break;
			case 'success':
				$cssClass = 'success';
			break;

			case 'info':
			default:

		}
	}
?>
<div class="uk-alert-<?php echo $cssClass; ?>" uk-alert>
	<p><?php echo $TemplateData['message']['content']; ?></p>
</div>
<?php } ?>
