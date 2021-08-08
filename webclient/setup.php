<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2021 Johannes Keßler
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

mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
ini_set('error_reporting',-1); // E_ALL & E_STRICT
ini_set('display_errors',true);
date_default_timezone_set('Europe/Berlin');

# check request
$_urlToParse = filter_var($_SERVER['QUERY_STRING'],FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
if(!empty($_urlToParse)) {
	if(preg_match('/[\p{C}\p{M}\p{Sc}\p{Sk}\p{So}\p{Zl}\p{Zp}]/u',$_urlToParse) === 1) {
		die('Malformed request. Make sure you know what you are doing.');
	}
}

$TemplateData = array();

$configStep = 'ready';
$configFile = './config/config.php';
if(file_exists($configFile) && is_readable($configFile)) {
	$configStep = 'config';
}

switch($configStep) {
	case 'config':
		$TemplateData['headline'] = '1. Main config file';
		$TemplateData['body'] = 'More special options can be directly edited in the config files themself. Only the basics are covered here.';
		$TemplateData['footer'] = 'Next - additional tools';

		$data = array(
			'timezone' => 'Europe/Berlin'
		);

		$TemplateData['bodyForm'] = stepConfig($data);
	break;

	case 'ready':
	default:
		$TemplateData['headline'] = 'Complete';
		$TemplateData['body'] = 'All done. This file is now gone. To restart the setup remove all non .default config files and upload the setup.php file again.';
		$TemplateData['bodyForm'] = '';
		$TemplateData['footer'] = 'cya';
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="author" content="https://www.bananas-playground.net/projekt/bibliotheca" />
	<title>Setup - Bibliotheca</title>
</head>
<body>
	<header>
		<h1><?php echo $TemplateData['headline']; ?></h1>
	</header>
	<main>
		<p><?php echo $TemplateData['body']; ?></p>
		<form method="post" action="">
			<?php echo $TemplateData['bodyForm']; ?>
			<p><button type="submit" name="submitForm">Save</button></p>
		</form>
	</main>
	<footer>
		<p><?php echo $TemplateData['footer']; ?></p>
		<p>&copy; 2018 - <?php echo date('Y'); ?> <a href="https://www.bananas-playground.net/projekt/bibliotheca/" target=_blank>Bibliotheca</a></p>
	</footer>
</body>
</html>
<?php
/**
 * all the "methods" for the steps.
 * KISS
 */

/**
 * Step config
 *
 * @param array $data
 * @return string
 */
function stepConfig(array $data): string {
	$timeZones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
	$timeZoneOptions = '';
	foreach ($timeZones as $tz) {
		$selected = '';
		if($tz == $data['timezone']) {
			$selected = 'selected="selected"';
		}
		$timeZoneOptions .= "<option ".$selected." value='".$tz."'>".$tz."</option>";
	}
	$absPath = getcwd();

	return <<<RET
<p>
	Select your timezone<br />
	<select name="timezone">
		{$timeZoneOptions}
	</select>
</p>
<p>
	The absolute path to this installation on your webspace. Current <b>{$absPath}</b> should be the right one.<br />
	<input type="text" name="pathabsolute" value="" size="50">
</p>
<p>
	Database Hostname<br />
	<input type="text" name="dbhost" value="">
</p>
<p>
	Database User<br />
	<input type="text" name="dbuser" value="">
</p>
<p>
	Database Password for the given user<br />
	<input type="text" name="dbpassword" value="">
</p>
<p>
	Database name<br />
	<input type="text" name="dbname" value="">
</p>
<p>
	Database table prefix. Default is <b>bib</b>. A _ is added automatically.<br />
	<input type="text" name="dbprefix" value="">
</p>
RET;
}
