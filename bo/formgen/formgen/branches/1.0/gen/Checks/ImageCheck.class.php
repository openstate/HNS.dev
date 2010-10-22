<?php

require_once('RegexCheck.class.php');

/*
	Class: ImageCheck
	Check that validates if uploaded files have specific extensions.

	See the manual, <Image check> for its use.
*/
class ImageCheck extends RegexCheck {
	protected $validOptions = array();
	protected $regex = '/\.(bmp|gif|jpg|jpeg|png)$/i';
	protected $formEl;

	protected $errorMsgName = 'image';
}

CheckFactory::register('image', 'ImageCheck');
$GLOBALS['errormsg']['image'] = 'Invalid image file';

?>