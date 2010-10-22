<?php

# This file was automatically generated by the MediaWiki installer.
# If you make manual changes, please keep track in case you need to
# recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# http://www.mediawiki.org/wiki/Manual:Configuration_settings

# If you customize your file layout, set $IP to the directory that contains
# the other MediaWiki files. It will be used as a base to locate files.
if( defined( 'MW_INSTALL_PATH' ) ) {
	$IP = MW_INSTALL_PATH;
} else {
	$IP = dirname( __FILE__ );
}

$path = array( $IP, "$IP/includes", "$IP/languages" );
set_include_path( implode( PATH_SEPARATOR, $path ) . PATH_SEPARATOR . get_include_path() );

require_once( "$IP/includes/DefaultSettings.php" );

# If PHP's memory limit is very low, some operations may fail.
# ini_set( 'memory_limit', '20M' );

if ( $wgCommandLineMode ) {
	if ( isset( $_SERVER ) && array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
		die( "This script must be run from the command line\n" );
	}
}
## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename         = "HNS.dev";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs please see:
## http://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath       = "/w";
$wgScriptExtension  = ".php";

## UPO means: this is also a user preference option

$wgEnableEmail      = true;
$wgEnableUserEmail  = true; # UPO

$wgEmergencyContact = "no-reply@hns-dev.nl";
$wgPasswordSender = "no-reply@hns-dev.nl";

$wgEnotifUserTalk = true; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = true;

# Postgres specific settings
$wgDBport           = "5432";
$wgDBmwschema       = "mediawiki";
$wgDBts2schema      = "public";

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = array();

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads       = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.utf8";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
# $wgHashedUploadDirectory = false;

## If you have the appropriate support software installed
## you can enable inline LaTeX equations:
$wgUseTeX           = false;

$wgLocalInterwiki   = strtolower( $wgSitename );

$wgLanguageCode = "en";

$wgSecretKey = "f776b232518f64cbc2e0f5da0d0b9023a8e31e7cc0dcebb8af7409ea5a55daa3";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook':
$wgDefaultSkin = 'gumax';

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
# $wgEnableCreativeCommonsRdf = true;
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";
# $wgRightsCode = ""; # Not yet used

$wgDiff3 = "/usr/bin/diff3";

# When you make changes to this configuration file, this will make
# sure that cached pages are cleared.
$wgCacheEpoch = max( $wgCacheEpoch, gmdate( 'YmdHis', @filemtime( __FILE__ ) ) );

$wgScript = "$wgScriptPath/index.php";
$wgRedirectScript = "$wgScriptPath/redirect.php";
$wgArticlePath = "/$1";
$wgUsePathInfo = true;

$wgLogo = '/w/skins/common/images/logo.gif';
$wgFavicon = '/favicon.ico';

## Database settings
$dbSettings         = require(dirname(__FILE__).'/../../privates/database.private.php');
$wgDBtype           = "postgres";
$wgDBserver         = $dbSettings[0]['host'];
$wgDBname           = $dbSettings[0]['database'];
$wgDBuser           = $dbSettings[0]['user'];
$wgDBpassword       = $dbSettings[0]['pass'];

define('NS_REDIRECT', 100);
define('NS_ISSUE', 102);
define('NS_ISSUE_TALK', 103);
define('NS_TRANSCLUDE', 104);
define('NS_HNS_PROJECT', 106);
define('NS_HNS_PROJECT_TALK', 107);

$wgExtraNamespaces[NS_REDIRECT] = 'Redirect';
$wgExtraNamespaces[NS_ISSUE] = 'Issue';
$wgExtraNamespaces[NS_ISSUE_TALK] = 'Issue_talk';
$wgExtraNamespaces[NS_TRANSCLUDE] = 'Transclude';
$wgExtraNamespaces[NS_HNS_PROJECT] = 'Project';
$wgExtraNamespaces[NS_HNS_PROJECT_TALK] = 'Project_talk';

$wgNamespaceProtection[NS_ISSUE] = array('editissue');
$wgGroupPermissions['sysop']['editissue'] = true;

$wgNamespaceProtection[NS_HNS_PROJECT] = array('editproject');
$wgGroupPermissions['sysop']['editproject'] = true;


require_once("$IP/extensions/GeSHiCodeTag.php");
require_once("$IP/extensions/GuardTag.php");

require_once( "$IP/extensions/UnblockUser/UnblockUser.php" );
require_once( "$IP/extensions/DeleteUser/DeleteUser.php" );
require_once( "$IP/extensions/Transclude/Transclude.php" );

$wgxAdminUser = 'Admin';

$wgMinimalPasswordLength = 15;

function isValidPassword($password, &$result, $user) {
	global $wgMinimalPasswordLength;
	$result = strlen($password) >= $wgMinimalPasswordLength && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
	return (boolean) $result;
}

$wgHooks['isValidPassword'][] = 'isValidPassword';

$wgAllowExternalImagesFrom = array('http://wiki.hnsdev.gl/', 'http://wiki.hns-dev.accepteproject.nl/');
