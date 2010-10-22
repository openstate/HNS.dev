<?php

class Gallery {
	protected $_subdir = '';
	protected $_gdAvailable = true;
	protected $_imageFolder = '';
	protected $_imageUrl = '';
	protected $_actions = array('list', 'upload', 'delete');

	protected $_error = false;

	protected $_action = 'list';


	public function __construct() {
		if (!extension_loaded('gd')) {
		   if (!dl('gd.so')) {
		       $this->_gdAvailable = FALSE;
		   }
		}
		$this->_imageUrl = '/assets/files/tinymceimages/';
		$this->_imageFolder = $_SERVER['DOCUMENT_ROOT'].$this->_imageUrl;

		if (isset($_COOKIE['instance_id'])) {
			$this->_subdir = $_COOKIE['instance_id'] . '/';
		} else {
			die('No instance available in the session');
		}

		if (!file_exists($this->_imageFolder)) {
			die('the folder : ' . $this->_imageFolder . ' could not be found on the server.');
		}

		if (!file_exists($this->_imageFolder.$this->_subdir.'/')) {
			mkdir($this->_imageFolder.$this->_subdir.'/');
		}
	}
	
	public function getBaseUrl() {
		return rtrim($this->_imageUrl.$this->_subdir, '/').'/';
	}

	public function processGet($get) {
		if (isset($get['action']) && in_array($get['action'], $this->_actions)) {
			$this->_action = $get['action'];
		}
		
		if ($this->_action == 'delete') {
			
			if ((isset($get['file']) && $get['file'] != '' && strpos($get['file'], '..') === false)) {
				if (file_exists($this->_imageFolder.$this->_subdir.'/'.$get['file'])) {
					@unlink($this->_imageFolder.$this->_subdir.'/'.$get['file']);
				}				
			}		
			header('location: gallery.php');
			die;
		}
	}

	public function processPost($post, $files) {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
		if ($this->_action == 'upload')	{
			$file = $files['photo'];
			$this->_error = false;
			$dirname = $this->_imageFolder.$this->_subdir.'/';
			//TODO: file type check
			switch($file['error']) {
				case UPLOAD_ERR_OK:
					if (file_exists($dirname.$file['name']))  {
						$this->_error = 'A file with that name already exists !';
						break;
					}
					if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $dirname.$file['name'])) {
						$this->_error = 'The uploaded file could not be moved to your personal directory.';
						break;
					}
					chmod($dirname.$file['name'], 0777);
					header('location: gallery.php');
					die;
				break;
				default:
					$this->_error = 'An error occured with the upload.';
				break;
			}
		}
	}

	public function display() {
		$method = $this->_action.'Display';
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			$this->listDisplay();
		}
	}

	public function listDisplay() {
		$dirname = $this->_imageFolder.$this->_subdir;

		$handle = opendir($dirname);
		$files = array();
		while ($file = readdir($handle)) {
			if (($file != '.') && ($file != '..')) {
				$files[] = $file;
			}
		}
		ob_start(); ?>
		<?php if(count($files) != 0): ?>
		<table class="files default wlarge form" summary="">
			<tr>
                <td class="nopadding-left">
				<?php foreach($files as $file):
					$info = getImageSize($this->_imageFolder.$this->_subdir.'/'.$file);				
					if ($info[0] > $info[1]) {
						$extra = 'width="100"';
					} else {
						$extra = 'height="100"';
					}
				?>
					<div style="float: left; margin: 5px; width: 100px; height: 100px;">
                    <img src="<?php echo $this->_imageUrl.$this->_subdir.'/'.$file; ?>" <?php echo $extra; ?> alt="Image" />
                    <a class="add icon-notext left somemargin-right" title="Voeg deze foto toe" href="javascript:void(0);" onClick="javascript:insert_image('<?php echo $file; ?>');">Toevoegen</a>
                    <a class="delete icon-notext left" title="Verwijder deze foto" href="gallery.php?action=delete&amp;file=<?php echo $file; ?>">Verwijder</a>
                    </div>
                <?php endforeach ?>
                </td>
            </tr>			
		</table>
		<?php else: ?>
			<p>Geen foto's geupload</p>
		<?php endif ?>
		<?php $result = ob_get_clean();
		echo $result;
	}

	public function uploadDisplay() {
		ob_start(); ?>
		<form name="imgupload" method="post" action="" enctype="multipart/form-data">
		<?php if ($this->_error): ?><span class="error"><?php echo $this->_error;?></span><?php endif ?>
		<table class="default wlarge form ">
			<tr>
				<th><span class="white">Jouw foto</span></th>
				<td><input type="file" name="photo" /></td>
			</tr>
			<tr>
				<th />
				<td><input type="submit" value="Versturen" /></td>
			</tr>
		</table>
		</form>
		<?php $result = ob_get_clean();
		echo $result;
	}
}

$gallery = new Gallery();
$gallery->processGet($_GET);
$gallery->processPost($_POST, $_FILES);

// -------------------------------------------------
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>PropperBox - Jouw maximale vakantiebeleving!</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="/assets/skins/propperbox/stylesheets/base.css" type="text/css" />
	<script type="text/javascript">
		function insert_image(image) {
			opener.document.forms[0].src.value = '<?php echo $gallery->getBaseUrl(); ?>'+image;
    		window.close();
		}
	</script>
</head>
    <body class="nopadding-bottom">
    	<div class="somemargin-left somemargin-right">        
            <div id="header">
                <!-- Branding -->
                <div id="branding">
                    <h1><a name="Propperbox" title="Propperbox">Propperbox</a></h1>
                    <h2>Jouw foto's!</h2>
                </div>
            </div>            
            <div class="content">            
                <div class="header dotswhite clearfix somemargin-bottom">
                    <h3 class="wide light">Beheer jouw foto's</h3>
                    <a title="Overzicht van jouw foto's" class="add white" href="gallery.php?action=list">Overzicht van jouw foto's</a>
                    <a title="Foto toevoegen" class="add white" href="gallery.php?action=upload">Foto toevoegen</a>
                </div>            
                <div class="main">
                    <?php
                    $gallery->display();
                    ?>
                </div>            
            </div>        
        </div>
    </body>
</html>