<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2024 Johannes Keßler
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

/**
 * A plain and simple setup for bibliotheca
 * It creates the config file based on the .default file.
 * It creates the default database tables.
 * It self deletes after a setup is complete to reduce some security risks
 */

mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
error_reporting(-1); // E_ALL & E_STRICT
ini_set('display_errors',true);
date_default_timezone_set('Europe/Berlin');

# check request
$_urlToParse = filter_var($_SERVER['QUERY_STRING'],FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
if(!empty($_urlToParse)) {
	if(preg_match('/[\p{C}\p{M}\p{Sc}\p{Sk}\p{So}\p{Zl}\p{Zp}]/u',$_urlToParse) === 1) {
		die('Malformed request. Make sure you know what you are doing.');
	}
}

$TemplateData = array();

if(!is_dir('../config') || !is_writeable('../config')) {
	die('Missing correct write permissions for ../config dir');
}
if(!is_dir('../storage') || !is_writeable('../storage')) {
	die('Missing correct write permissions for ../storage dir');
}
if(!is_dir('../systemout') || !is_writeable('../systemout')) {
	die('Missing correct write permissions for ../systemout dir');
}
if(!is_writeable(getcwd())) {
	die('Missing correct write/delete permissions for the setup folder');
}

$configStep = 'ready';
$configFile = '../config/config.php';
$configFileSkeleton = '../config/config.php.default';
$configBrainz = '../config/config-musicbrainz.php';
$configImdb = '../config/config-imdbweb.php';
if(!file_exists($configFile)) {
	if(file_exists($configFileSkeleton) && is_readable($configFileSkeleton)) {
		$configStep = 'config';
	}
	else {
		die('Missing needed skeleton config files. Make sure all the files are unpacked correctly.');
	}
}

if(file_exists($configFile)) {
	$configStep = 'database';
}

if(isset($_GET['done'])) {
	$configStep = 'ready';
}

switch($configStep) {
	case 'config':
		$TemplateData['headline'] = '1. Main config file';
		$TemplateData['body'] = 'More special options can be directly edited in the config files themself. Only the basics are covered here.';
		$TemplateData['footer'] = 'Next - default database tables';
		$TemplateData['result'] = array();

		$data = array(
			'timezone' => 'Europe/Berlin',
			'dbhost' => '127.0.01',
			'dbuser' => 'user',
			'dbprefix' => 'bib',
			'dbname' => '',
			'dbpassword' => '',
			'pathabsolute' => ''
		);

		if(isset($_POST['submitForm'])) {
			$fCheck = false;
			foreach ($data as $k=>$v) {
				if(isset($_POST[$k])) {
					$_t = trim($_POST[$k]);
					if(!empty($_t)) {
						$data[$k] = $_t;
						$fCheck = true;
					}
					else {
						$fCheck = false;
						break;
					}
				}
				else {
					$fCheck = false;
					break;
				}
			}

			if(!empty($fCheck)) {
				$configFileSkeletonString = file_get_contents($configFileSkeleton);

				$configFileString = str_replace('~timezone~',$data['timezone'],$configFileSkeletonString);
				$configFileString = str_replace('~dbhost~',$data['dbhost'],$configFileString);
				$configFileString = str_replace('~dbuser~',$data['dbuser'],$configFileString);
				$configFileString = str_replace('~dbprefix~',$data['dbprefix'],$configFileString);
				$configFileString = str_replace('~dbname~',$data['dbname'],$configFileString);
				$configFileString = str_replace('~dbpassword~',$data['dbpassword'],$configFileString);
				$configFileString = str_replace('~pathabsolute~',$data['pathabsolute'],$configFileString);

				if(file_put_contents($configFile, $configFileString)) {
					copy($configBrainz.'.default', $configBrainz);
					copy($configImdb.'.default', $configImdb);
					header('Location: index.php');
					exit();
				}
				else {
					$TemplateData['result']['status'] = 'ERROR';
					$TemplateData['result']['message'] = 'Config file could not be written. Please check your permission';
				}

			}
			else {
				$TemplateData['result']['status'] = 'ERROR';
				$TemplateData['result']['message'] = 'Please provide input for all the fields';
			}
		}

		$TemplateData['bodyForm'] = stepConfigForm($data);
	break;

	case 'database':
		$TemplateData['headline'] = '2. Database';
		$TemplateData['body'] = 'Check and setup of default tables';
		$TemplateData['bodyForm'] = '';
		$TemplateData['footer'] = 'Next - Additional tools';
		$TemplateData['result'] = array();

		if(isset($_POST['submitForm'])) {
			if(isset($_POST['resetConfig']) && !empty($_POST['resetConfig'])) {
				unlink($configFile);
				header('Location: index.php');
			}
		}

		require_once $configFile;

		$DB = new mysqli(DB_HOST, DB_USERNAME,DB_PASSWORD, DB_NAME);
		$driver = new mysqli_driver();
		$driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
		$_conCheck = false;
		if ($DB->connect_errno) {
			$TemplateData['result']['status'] = 'ERROR';
			$TemplateData['result']['message'] = 'Can not connect to database: '.$DB->connect_error;
			$TemplateData['bodyForm'] = stepDBRest();
		}
		else {
			$_conCheck = true;
			$DB->set_charset("utf8mb4");
			$DB->query("SET collation_connection = 'utf8mb4_unicode_ci'");
		}

		if(!empty($_conCheck)) {
			try {
				$query = $DB->query("SELECT * FROM information_schema.tables WHERE table_schema = '".DB_NAME."' AND table_name = '".DB_PREFIX."_sys_fields' LIMIT 1");
				if($query !== false && $query->num_rows < 1) {
					$TemplateData['bodyForm'] = stepDBCreateTables();
				}
				else {
					$TemplateData['result']['status'] = 'ERROR';
					$TemplateData['result']['message'] = 'Existing DB Tables found! Setup already complete';
					clearSetup();
				}
			}
			catch (Exception $e) {
				$TemplateData['result']['status'] = 'ERROR';
				$TemplateData['result']['message'] = 'Can not run query: '.$e->getMessage();
			}
		}

		if(isset($_POST['submitForm'])) {
			if(isset($_POST['createTables']) && !empty($_POST['createTables'])) {
				$sqlSkeleton = file_get_contents('./bibliotheca.sql.default');
				file_put_contents('./bibliotheca.sql', str_replace('#REPLACEME#',DB_PREFIX,$sqlSkeleton));

				try {
					$sqlScript = file('./bibliotheca.sql');
					$queryStr = '';
					foreach ($sqlScript as $line)	{

						$startWith = substr(trim($line), 0 ,2);
						$endWith = substr(trim($line), -1 ,1);

						if (empty($line) || $startWith == '--' || $startWith == '/*' || $startWith == '//') {
							continue;
						}

						$queryStr .= $line;
						if ($endWith == ';') {
							$DB->query($queryStr);
							$queryStr = '';
						}
					}

					header("Location: index.php?done=1");
				}
				catch (Exception $e) {
					$TemplateData['result']['status'] = 'ERROR';
					$TemplateData['result']['message'] = 'Can not create needed tables: '.$e->getMessage();
					$TemplateData['body'] = 'Please check your DB Settings, files and start over. To restart the setup remove all non .default config files and upload the /setup folder again.';
				}
			}
		}

	break;

	case 'ready':
	default:
		$TemplateData['headline'] = 'Complete';
		$TemplateData['body'] = 'All done. This file is now gone. To restart the setup remove all non .default config files and upload the /setup folder gain.';
		$TemplateData['bodyForm'] = '';
		$TemplateData['footer'] = 'cya';
		$TemplateData['result'] = array();
		clearSetup();
}

header('Content-type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="author" content="https://www.bananas-playground.net/projekt/bibliotheca/" />
	<title>Setup - Bibliotheca</title>
</head>
<body>
	<header style="border-bottom: 1px solid gray;">
		<h1><?php echo $TemplateData['headline']; ?></h1>
		<?php if(!empty($TemplateData['result'])) { ?>
		<p>
			Result: <?php echo $TemplateData['result']['status']; ?><br />
			<?php echo $TemplateData['result']['message']; ?>
		</p>
		<?php } ?>
	</header>
	<main>
		<p><?php echo $TemplateData['body']; ?></p>
		<form method="post" action="">
			<?php echo $TemplateData['bodyForm']; ?>
			<p><button type="submit" name="submitForm">Save</button></p>
		</form>
	</main>
	<footer style="border-top: 1px solid gray;">
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
function stepConfigForm(array $data): string {
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
	$absPath = str_replace("setup", "", $absPath);

	return <<<RET
<p>
	Select your timezone<br />
	<select name="timezone">
		{$timeZoneOptions}
	</select>
</p>
<p>
	The absolute path to this installation on your webspace. Current <b>{$absPath}</b> should be the right one.<br />
	Make sure there is a / at the end<br />
	<input type="text" name="pathabsolute" value="{$data['pathabsolute']}" size="50">
</p>
<p>
	Database Hostname<br />
	<input type="text" name="dbhost" value="{$data['dbhost']}">
</p>
<p>
	Database User<br />
	<input type="text" name="dbuser" value="{$data['dbuser']}">
</p>
<p>
	Database Password for the given user<br />
	<input type="password" name="dbpassword" value="">
</p>
<p>
	Database name<br />
	<input type="text" name="dbname" value="{$data['dbname']}">
</p>
<p>
	Database table prefix. Default is <b>bib</b>. A _ is added automatically.<br />
	<input type="text" name="dbprefix" value="{$data['dbprefix']}">
</p>
RET;
}

/**
 * Database step
 *
 * @return string
 */
function stepDBRest(): string {
	return <<<RET
<p>
	Reset the config file to input the correct DB values: 
	<input type="checkbox" name="resetConfig" value="1" />
</p>
RET;
}

/**
 * database step
 *
 * @return string
 */
function stepDBCreateTables(): string {
	return <<<RET
<p>
	Warning: Existing data can be lost if the config is wrong!<br />
	Create tables: <input type="checkbox" name="createTables" value="1" />	
</p>
RET;
}

function clearSetup(): void {
	array_map('unlink', glob("../setup/*"));
	rmdir('../setup');
}
