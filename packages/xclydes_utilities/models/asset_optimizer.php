<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
try
{
	//Define the package handle until a method of reliably retreiving it has been determined.
	$pkgHandle = 'xclydes_utilities';
	Loader::helper('flat_file_cache', $pkgHandle);
}
catch(Exception $error)
{
	$LOG = new Log('asset_optimizer', false);
	$LOG->write($error->getTraceAsString());
}

/**
 * @author Xclydes
 * Provides a set of functions which allow for the optimization of various filetypes.
 * Currently Supports :- CSS, JS, Images (PNG, JPG/JPEG, GIF) 
 */
class AssetOptimizer {
	
	private $CSS = array();
	private $JS = array();
	private $NEW_JS_FILE = 0;
	private $NEW_CSS_FILE = 0;
	private static $INSTANCE = NULL;
	const CSS_FILE_TYPE = 'CSSFile';
	const JS_FILE_TYPE = 'JavaScriptFile';
	const CONFIG_KEY = 'aomPubPath';
	
	//Define the package handle until a method of reliably retreiving it has been determined.
	private static $pkgHandle = 'xclydes_utilities';
	private static $package = NULL;
	
	
	// getInstance() grabs one instance of the view w/the singleton pattern
	public static function getInstance() {
		static $INSTANCE;
		if (!isset($INSTANCE)) {
			$cl = __CLASS__;
			$INSTANCE = new $cl;
		}
		return $INSTANCE;
	}		

	/*
	 * Gets a reference to the package in which this file is included.
	 * @return Package the package in which this file can be found. 
	 */
	public static function getPackage(){
		if(self::$pkgHandle && !self::$package){
			self::$package = Package::getByHandle(self::$pkgHandle);
		}
		return self::$package;
	}
	
	/**
	* Gets the path to default output folder.
	* @param String $format The way the path should be formatted. @see FileSystemHelper constants.
	* @return String The formatted path.
	*/
	public static function getPublicViewFolder($format = FlatFileCacheHelper::RELATIVE){
		$instance = self::getInstance();
		if(self::getPackage()){//Check to see if it is defined on the package.
			$folder_name = self::getPackage()->config(self::CONFIG_KEY);
		}
		if(!$folder_name){
			$folder_name = substr(sha1("xclydes_public-{$_SERVER['HTTP_HOST']}"), 9, 6);
		}
		$path = FlatFileCacheHelper::formatPath(DIR_FILES_UPLOADED_STANDARD . '/' . $folder_name .'/', $format);
		return FlatFileCacheHelper::createDirectory($path) ? $path : '';
	}
	
	/**
	 * Processes the specified CSS file if can be found at the path specified.
	 * @param String $filename The filename/file path of the CSS file to be processed.
	 * @param Boolean $store Whether or not the processed file should be saved for compiling later.
	 * @param Boolean $preprocessed Whether or not the file specified is already optimized.
	 * @return String An string representing the processed contents of the requested file. 
	 */
	public static function addCSS($filename, $store = TRUE, $preprocessed = FALSE)
	{
		$instance = self::getInstance();
		$filename = FlatFileCacheHelper::checkFilePath($filename, FlatFileCacheHelper::RELATIVE);
		$file_path = FlatFileCacheHelper::getFilePath($filename, FlatFileCacheHelper::SITE_ABSOLUTE);
		if(!$filename) { return ''; }
		try
		{
			$cache_id = md5($filename);//Create a unique id to identify this file.
			$mod_time = FlatFileCacheHelper::fileModTime($filename);
			if(isset($instance->CSS[$filename])){//See if the file was previously loaded
				$contents = $instance->CSS[$filename];
			}
			else{//The file was not previously loaded.
				$contents = FlatFileCacheHelper::get(self::CSS_FILE_TYPE, $cache_id, $mod_time);//Attempt to retrieve the file from cache.
			}
			if(!$contents)//The file doesn't exist. Load , optimize, cache, and store this file for later use.
			{
				$contents = file_get_contents($filename); //Read the contents of the file.
				if(!$preprocessed){
					$contents = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $contents);//Remove comments.
					$contents = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    ',': ',', '), array('', '', '', '', '', '', '',':',','), $contents);//Remove tabs, spaces, newlines, etc.
					$contents = strtr($contents,array('url('=>"url({$file_path}",'url("'=>"url(\"{$file_path}",'url(\''=>"url('{$file_path}" ));
				}
				FlatFileCacheHelper::set(self::CSS_FILE_TYPE, $cache_id, $contents);//The file has been processed. Cache it for future reference.
			}
			if($store && !isset($instance->CSS[$filename])) //If the file is to be referenced later, save it. 
			{ 
				//If this file was modified more recently than the last flagged file
				if($instance->NEW_CSS_FILE < $mod_time){
					//Update the flag.
					$instance->NEW_CSS_FILE = $mod_time;
				}
				$instance->CSS[$filename] = $contents;
			}
			return $contents;//Allow a copy of the optimized CSS to be captured external to this method.
		}
		catch(Exception $error)
		{
			$LOG = new Log('asset_optimizer', false);
			$LOG->write($error->getTraceAsString());
			return '';
		}
	}
	
	/**
	 * Processes the specified JavaScript file if can be found at the path specified.
	 * @param String $filename The filename/file path of the JavaScript file to be processed.
	 * @param Boolean $store Whether or not the processed file should be saved for compiling later.
	 * @param Boolean $preprocessed Whether or not the file specified is already optimized.
	 * @return String An string representing the processed file contents. 
	 */
	public static function addJS($filename, $store = TRUE, $preprocessed = FALSE)
	{
		try
		{
			$instance = self::getInstance();
			$filename = FlatFileCacheHelper::checkFilePath($filename);
			$cache_id = md5($filename);//Create a unique id to identify this file.
			$mod_time = FlatFileCacheHelper::fileModTime($filename);
			//echo 'JS:- '.$filename.', '.$mod_time.', '.$cache_id.'<br />';
			if(isset($instance->JS[$filename])){//See if the file was previously loaded
				$contents = $instance->JS[$filename];
			}
			else{//The file was not previously loaded.
				$contents = FlatFileCacheHelper::get(self::JS_FILE_TYPE, $cache_id, $mod_time);//Attempt to retrieve the file from cache.
			}
			if(!$contents)//The file doesn't exist. Load , optimize, cache, and store this file for later use.
			{
				Loader::library('jsmin','xclydes_utilities');
				if(!$filename || !class_exists('Jsmin')) { return; }
				$contents = file_get_contents($filename); //Read the contents of the file.
				if(!$preprocessed)
				{
					$contents = Jsmin::minify($contents);
				}
				//Modified to use JSMin as it was more compatible than JavaScriptPacker.
				//Loader::library('java_script_packer','xclydes_utilities');
				//if(!$filename || !class_exists('JavaScriptPacker')) { return; }
				//$contents = file_get_contents($filename); //Read the contents of the file.
				//if(!$preprocessed)
				//{
				//	$packer = new JavaScriptPacker($contents);
				//	$contents = $packer->pack();
				//}
				FlatFileCacheHelper::set(self::JS_FILE_TYPE, $cache_id, $contents);//The file has been processed. Cache it for future reference.
			}
			if($store && !isset($instance->$JS[$filename])) //If the file is to be referenced later, save it. 
			{ 
				//If this file was modified more recently than the last flagged file.
				if($instance->NEW_JS_FILE < $mod_time){
					//Update the flag.
					$instance->NEW_JS_FILE = $mod_time;
				}
				$instance->JS[$filename] = $contents;
			}
			return $contents;//Allow a copy of the optimized CSS to be captured external to this method..
		}
		catch(Exception $error)
		{
			$LOG = new Log('asset_optimizer', false);
			$LOG->write($error->getTraceAsString());
		}
	}
	
	/**
	 * Optimizes an image and returns a reference to the optimized file.
	 * @param String $file_path The filename/filepath of the image file to be processed.
	 * @param Integer $width The width of the new image. 0 indicates auto calculate.
	 * @param Integer $height The height of the new image. 0 indicates auto calculate.
	 * @param String $store_to The filename to which the image should be stored.
	 */
	public static function optimizeIMG($file_path, $width = 0, $height = 0, $store_to = '')
	{
		try
		{
			$instance = self::getInstance();
			Loader::model('image_optimizer', 'xclydes_utilities');
			$ext = FlatFileCacheHelper::getFileExtension($file_path, FALSE);
			//$store_path = $store_to ? $store_to : $instance->getPublicViewFolder() . '/' . md5($file_path)."_{$width}x{$height}{$ext}" ;
			$optimized_img = new ImageOptimizer($file_path/*, $store_path*/);
			$optimized_img->setDimensions($width, $height);
			$optimized_img->processImage();
			return FlatFileCacheHelper::formatPath($optimized_img->getProcessedImagePath(), FlatFileCacheHelper::SITE_ABSOLUTE);
		}
		catch(Exception $error)
		{
			$LOG = new Log('asset_optimizer', false);
			$LOG->write($error->getTraceAsString());
		}
	}
	
	/**
	 * Takes all processed CSS files, adds them to a single file on disk for serving.
	 * @return string A path from which this file can be accessed .
	 */
	public static function compileCSS($unique_key = '')
	{
		$instance = self::getInstance();
		$obj_name = implode('-', array_keys($instance->CSS)) . ' - CompressedCSS' . $unique_key;
		$filename = $instance->getPublicViewFolder().md5($obj_name).'.css';
		$save_path = FlatFileCacheHelper::formatPath($filename, FlatFileCacheHelper::DISK_ABSOLUTE);
		if(empty($instance->CSS)) { return ''; } 
		try
		{
			$compressed = FlatFileCacheHelper::get($instance->CSS_FILE_TYPE, $obj_name, $instance->NEW_CSS_FILE);
			if(!$compressed)//The cache was stale or this file have never been saved before.
			{
				//$compressed = implode(FlatFileCacheHelper::EOL(), $instance->CSS);
				$compressed = implode('', $instance->CSS);
				FlatFileCacheHelper::set(self::CSS_FILE_TYPE, $obj_name, $compressed);
				if(file_exists($save_path)){ unlink($save_path); }
			}
			if(!file_exists($save_path)){
				file_put_contents($save_path, $compressed);
			}
			return FlatFileCacheHelper::formatPath($filename, FlatFileCacheHelper::SITE_ABSOLUTE);
		}
		catch(Exception $error)
		{
			$LOG = new Log('asset_optimizer', false);
			$LOG->write($error->getTraceAsString());
		}
	}
	
	/**
	 * Takes all processed JS files, adds them to a single file on disk for serving.
	 * @return string A path from which this file can be accessed .
	 */
	public static function compileJS($unique_key = '')
	{
		$instance = self::getInstance();
		$obj_name = $_SERVER['HTTP_HOST'] . implode('-', array_keys($instance->JS)) . ' - CompressedJS' . $unique_key;
		$filename = $instance->getPublicViewFolder().md5($obj_name).'.js';
		$save_path = FlatFileCacheHelper::formatPath($filename, FlatFileCacheHelper::DISK_ABSOLUTE);
		if(empty($instance->JS)) { return ''; } 
		try
		{
			$compressed = FlatFileCacheHelper::get($instance->JS_FILE_TYPE, $obj_name, $instance->NEW_JS_FILE);
			if(!$compressed)//The cache was stale or this file have never been saved before.
			{
				//$compressed = implode(FlatFileCacheHelper::EOL(), $instance->JS);
				$compressed = implode('', $instance->JS);
				FlatFileCacheHelper::set(self::JS_FILE_TYPE, $obj_name, $compressed);
				if(file_exists($save_path)){ unlink($save_path); }
			}
			if(!file_exists($save_path)){
				file_put_contents($save_path, $compressed);
			}
			return FlatFileCacheHelper::formatPath($filename, FlatFileCacheHelper::SITE_ABSOLUTE);
		}
		catch(Exception $error)
		{
			$LOG = new Log('asset_optimizer', false);
			$LOG->write($error->getTraceAsString());
		}
	}
	
	/**
	* Removes all files which have been outputted to disk.
	* @return Boolean Whether or not the cache was sucessfully cleared.
	*/
	public static function emptyOutputDir()
	{
		if(!FlatFileCacheHelper::checkFilePath(self::getInstance()->getPublicViewFolder(), FlatFileCacheHelper::DISK_ABSOLUTE)) { return FALSE; }
		else
		{
			FlatFileCacheHelper::removeDirectory(self::getInstance()->getPublicViewFolder());
		}
	}

	/*
	* Adds items of a certain type to the current listing of files from the 
	* View object.
	* @param String $type The type of objects to add
	*/
	private static function addFromView($type){
		try{
			$view_object = View::getInstance();
			$existing_css = $instance->$CSS;
			$existing_js = $instance->$JS;
			$instance->$CSS = array();
			$instance->$JS = array();
			foreach($view_object->getHeaderItems() as $element){
				if(!$element->file){
					continue;
				}
				$file_path = FlatFileCacheHelper::formatPath($element->file, FlatFileCacheHelper::DISK_ABSOLUTE);
				$file_path = substr($file_path, 0, -strlen(substr($file_path, strpos($file_path, '?'))) );
				if ($element instanceof CSSOutputObject && $type == $instance->CSS_FILE_TYPE) {
					$instance->addCSS($file_path, TRUE, TRUE);
				}
				if ($element instanceof JavaScriptOutputObject && $type == $instance->JS_FILE_TYPE) { 
					$instance->addJS($file_path, TRUE, TRUE);
				}
			}
			$instance->$CSS = array_merge($instance->$CSS, $existing_css);
			$instance->$JS = array_merge($instance->$JS, $existing_js);
		}
		catch(Exception $error)
		{
			$LOG = new Log('asset_optimizer', false);
			$LOG->write($error->getTraceAsString());
		}
	}
}