<?php
/** \file
* \brief Contains setup code for the User Merge and Delete Extension.
*/

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
        echo "Not a valid entry point";
        exit(1);
}

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['DeleteUser'] = $dir . 'DeleteUser_body.php';

$wgExtensionMessagesFiles['DeleteUser'] = $dir . 'DeleteUser.i18n.php';
$wgExtensionAliasesFiles['DeleteUser'] = $dir . 'DeleteUser.alias.php';
$wgSpecialPages['DeleteUser'] = 'DeleteUser';
$wgSpecialPageGroups['DeleteUser'] = 'users';

$wgDeleteUserProtectedGroups = array( "sysop" );

# Add a new log type
$wgLogTypes[]                         = 'deleteuser';
$wgLogNames['deleteuser']              = 'deleteuser-logpage';
$wgLogHeaders['deleteuser']            = 'deleteuser-logpagetext';
$wgLogActions['deleteuser/mergeuser']  = 'deleteuser-success-log';
$wgLogActions['deleteuser/deleteuser'] = 'deleteuser-userdeleted-log';
