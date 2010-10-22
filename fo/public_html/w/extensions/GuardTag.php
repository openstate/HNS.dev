<?php

if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'efGuardParserInit';
} else {
	$wgExtensionFunctions[] = 'efGuardParserInit';
}

function efGuardParserInit() {
	global $wgParser;
	$wgParser->setHook( 'guard', 'efGuardRender' );
	return true;
}
 
function efGuardRender( $input, $args, &$parser ) {
	global $wgUser;
	$show = array();
	if (array_key_exists('anon', $args)) $show[] = (boolean) $args['anon'] == $wgUser->isAnon();
	if (array_key_exists('user', $args)) $show[] = $wgUser->mName == $args['user'];
	if (array_key_exists('group', $args)) $show[] = in_array($args['group'], $wgUser->mGroups);
	if (array_key_exists('right', $args)) $show[] = in_array($args['rights'], $wgUser->mRights);
	if (count($show))
		if (@$args['join'] == 'or') {
			if (!array_reduce($show, create_function('$a,$b', 'return $a || $b;'), false)) return '';
		} else {
			if (!array_reduce($show, create_function('$a,$b', 'return $a && $b;'), true)) return '';
		}
	return $parser->recursiveTagParse($input);
}
