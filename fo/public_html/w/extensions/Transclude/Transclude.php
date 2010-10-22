<?php

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
        echo "Not a valid entry point";
        exit(1);
}

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['Transclude'] = $dir . 'Transclude_body.php';
$wgSpecialPages['Transclude'] = 'Transclude';
