<?php

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
        echo "Not a valid entry point";
        exit(1);
}

$wgAvailableRights[] = 'unblockuser';
$wgGroupPermissions['sysop']['unblockuser'] = true;

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['UnblockUser'] = $dir . 'UnblockUser_body.php';

$wgExtensionMessagesFiles['UnblockUser'] = $dir . 'UnblockUser.i18n.php';
$wgExtensionAliasesFiles['UnblockUser'] = $dir . 'UnblockUser.alias.php';
$wgSpecialPages['UnblockUser'] = 'UnblockUser';
$wgSpecialPageGroups['UnblockUser'] = 'users';
