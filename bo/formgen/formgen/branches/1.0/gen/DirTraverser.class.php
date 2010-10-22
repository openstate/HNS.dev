<?php

require_once('Generator.class.php');

/*
	Class: DirTraverser
	Recursively scans through directories searching for form definition files, and parses
	those that it finds.
*/
class DirTraverser {
	// Property: $settings
	// An array with settings.
	protected $settings;
	// Property: $gen
	// A <Generator> instance used to parse form definitions.
	protected $gen;
	// Property: $total
	// A total count of form files processed
	// Property: $failed
	// A count of how many form files failed to compile
	protected $total, $failed;

	/*
		Constructor: __construct

		Parameters:
		$settings - The settings for the traverser.

		Settings:
		Valid settings for the traverser are:

		baseDir     - The directory at which to start scanning.
		fileMask    - The filemask that identifies form definition files.
		classDir    - The directory relative to the form file where class files will be written.
		className   - The name of the generated classes. The filenames are based on this as well.
		htmlDir     - The directory relative to the form file where HTML files will be written.
		htmlHeadDir - The directory relative to the form file where HTML header files will be written. If not specified,
		              the header will be written in the normal HTML file.
		actionTarget - The file that the form will be submitted to.
		actionDir   - The directory relative to the form file where action files will be written.
		actionFiles - An array of files that perform display and/or actions. The key is the name of the PHP template file,
		              the value is the name of the generated file.
		templateDir - The directory where the PHP templates can be found.

		The generated filenames classFile, actionFiles and actionTarget have a
		few format specifiers:

		%O - The object name as specified in a form template.
		%o - The object name with the first letter converted to lowercase.
		%A - The action name starting with a capital.
		%a - The action name starting with a lowercase letter.
	*/
	public function __construct($settings, $settingsFilePath) {
		// Convert filemask to regex
		$settings['fileMask'] = '/^'.str_replace(array('*', '?'), array('.*', '.'), str_replace('.', '\.', $settings['fileMask'])).'$/';

		// Make relative paths absolute
		$settings['baseDir'] = $this->convertSettingsToAbsolutePath($settings['baseDir'], $settingsFilePath);
		$settings['templateDir'] = $this->convertSettingsToAbsolutePath($settings['templateDir'], $settingsFilePath);

		$this->settings = $settings;

		$this->gen = new Generator(array(
			'classDir'     => $this->settings['classDir'],
			'className'    => $this->settings['className'],
			'actionTarget' => $this->settings['actionTarget'],
			'actionFiles'  => $this->settings['actionFiles'],
			'templateDir'  => $this->settings['templateDir']));
	}

	// Method: removeDoubleSlash
	// Changes double slashes in paths to a single one, for aesthetic purposes.
	protected function removeDoubleSlash($s) {
		return str_replace('//', '/', $s);
	}

	// Make relative paths absolute
	protected function convertSettingsToAbsolutePath($settingPath, $settingsFilePath) {
		if(is_array($settingPath)) {
			foreach($settingPath as & $item) {
				$item = $this->convertSettingsToAbsolutePath($item,$settingsFilePath);
			}
		} else {
			if (realpath($settingPath)!=$settingPath)
				$settingPath = realpath($settingsFilePath.'/'.$settingPath);
		}
		return $settingPath;
	}

	/*
		Method: doTraverse
		Processes a single directory.
		Any subdirectories that are found are processed recursively, and
		any files that match the filemask are compiled via the callback
		function.
	*/
	protected function doTraverse($baseDir, $mask, $callback) {
		if(is_array($baseDir)) {
			foreach($baseDir as $item) {
				$this->doTraverse($item, $mask, $callback);
			}
			return;
		}

		$baseDir = $this->removeDoubleSlash($baseDir);
		foreach (glob($baseDir.'/*') as $filename) {
			if (is_dir($filename))
				$this->doTraverse($filename, $mask, $callback);
			else if (preg_match($mask, basename($filename)))
				call_user_func($callback, $filename);
		}
	}

	/*
		Method: traverse
		Traverses the directory structure pointed at in the settings.
		Any encountered form definition files are compiled.
	*/
	public function traverse() {
		$this->total = 0;
		$this->success = 0;

		$this->doTraverse($this->settings['baseDir'], $this->settings['fileMask'], array($this, 'compileFile'));

		echo 'Compiled '.$this->total.' file';
		if ($this->total!=1) echo 's';
		if ($this->failed==0)
			echo ', all were successful.'.(HTMLOUTPUT ? '<br />' : "\n");
		else {
			echo ', '.$this->failed.' failed.'.(HTMLOUTPUT ? '<br />' : "\n");
			if (!HTMLOUTPUT) {
				echo 'Press enter to continue...'."\n";
				$fp = fopen('php://stdin', 'r');
				$in = fgets($fp, 10);
				fclose($fp);
			}
		}
	}

	/*
		Method: compileFile
		Generates code for a single form description file.
		If anything goes wrong while processing, a notice is printed.
	*/
	protected function compileFile($filename) {
		$dir = dirname($filename);
		chdir($dir);

		$opts = array(
			'htmlDir' =>     $dir.'/'.$this->settings['htmlDir'],
			'actionDir' =>   $dir.'/'.$this->settings['actionDir']
		);
		if (isset($this->settings['htmlHeadDir']))
			$opts['htmlHeadDir'] = $dir.'/'.$this->settings['htmlHeadDir'];
		$this->gen->setOpts($opts);

		echo 'Compiling '.$filename.'... ';
		$this->total++;
		try {
			$this->gen->setFile($filename);
			$this->gen->compileFiles();
			if (HTMLOUTPUT)
				echo '<span class="success">Complete</span>.<br />';
			else
				echo "Complete.\n";
		} catch (Exception $e) {
			if (HTMLOUTPUT)
				echo '<span class="failure">Failed</span>.<br /><span class="error">An error occurred: <span class="msg">'.$e->getMessage().'</span></span><br />';
			else
				echo 'Failed.'."\n".'An error occurred: '.$e->getMessage()."\n";
			$this->failed++;
		}
	}
}

?>