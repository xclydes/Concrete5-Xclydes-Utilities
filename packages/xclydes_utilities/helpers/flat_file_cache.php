<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

try
{
	//Define the package handle until a method of reliably retreiving it has been determined.
	$pkgHandle = 'xclydes_utilities';
	Loader::helper('file_system', $pkgHandle);
}
catch(Exception $error)
{
	$LOG = new Log('flat_file_cache_helper', false);
	$LOG->write($error->getTraceAsString());
}


/**
 * @author Xclydes
 *
 */
class FlatFileCacheHelper extends FileSystemHelper
{
	const CACHE_EXTENSION = '.cache';
	const CONFIG_KEY = 'ffchPath';
	//Define the package handle until a method of reliably retreiving it has been determined.
	private static $pkgHandle = 'xclydes_utilities';
	private static $package = NULL;
	
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
	* Gets the which is used for the store of cache files.
	* @param String $format The way the path should be formatted. @see FileSystemHelper constants.
	* @return String The formatted path.
	*/
	public static function getBaseCachePath($path_format = parent::RELATIVE)
	{
		if(self::getPackage()){//Check to see if it is defined on the package.
			$folder_name = self::$package->config(self::CONFIG_KEY);
		}
		if(!$folder_name){//It is not, so generate one.
			$folder_name = substr(sha1("xclydes_cache-{$_SERVER['HTTP_HOST']}"), 13, 7);
		}
		return parent::formatPath(DIR_FILES_UPLOADED_STANDARD . '/' . $folder_name,  parent::RELATIVE);
	}
	
	/**
	* An error logging mechanism to the handle exceptions which may be thrown in
	* in this class.
	*/
	private static function handleError($error)
	{
			$LOG = new Log('Xclydes Utilities - Flat File Cache (Helper)', false);
			$LOG->write($error->getTraceAsString());
	}

	/**
	 * Adds a file to the cache. Essentially writes the file to disk.
	 * @param String $id A unique string used to identify the object.
	 * @param unknown_type $object The object to be saved.
	 */
	public static function set($type, $id, $object)
	{
		try
		{
			if(!self::cachePathExists(TRUE)) { return FALSE; }
			$abs_filename = self::getCacheFilename($id,$type);
			file_put_contents($abs_filename, serialize($object));
		}
		catch(Exception $error)
		{
			self::handleError($error->getTraceAsString());
		}
	}
	
	/**
	 * Retrieves the selected file from the file currently stored.
	 * @param String_type $id The unique ID by which this object can be found. 
	 * @return String The contents of the file as read from disk.
	 */
	public static function get($type, $id, $newer_than = 0)
	{
		$contents = '';
		try
		{
			if(!self::cachePathExists(TRUE)) { return FALSE; }
			$abs_filename = self::getCacheFilename($id,$type);
			if(file_exists($abs_filename))
			{
				$last_modified = filemtime($abs_filename);
				if($last_modified >= $newer_than)
				{
					$contents = unserialize(file_get_contents($abs_filename));
				}
				else
				{
					try
					{
						unlink($abs_filename);
					}
					catch(Exception $error)
					{
						$LOG = new Log('flat_file_cache_helper', false);
						$LOG->write($error->getTraceAsString());
					}
				}
			}
		}
		catch(Exception $error)
		{
			self::handleError($error->getTraceAsString());
		}
		return $contents;
	}
	
	/**
	 * Gets the path to the cache directory.
	 * @param Boolean $absolute_path Whether or not the path should be absolute, or URL relative.
	 * @return String A string representing the path to the cache directory.
	 */
	public static function getCachePath($absolute_path = false)
	{
		$format = $absolute_path ? parent::DISK_ABSOLUTE : parent::RELATIVE;
		//$path = parent::formatPath(self::getBaseCachePath(), $format);
		return parent::formatPath(self::getBaseCachePath(), $format);
	}
	
	/**
	* Generates the name filename, including path, which will be used for caching
	* the object based on the parameters.
	* @param String $id The unique identifier of the file.
	* @param String $type The type of file it is.
	* @param String $path_format The way in which the returned path should be formatted.
	* @return String The path and name which the file would be stored at.
	*/
	public static function getCacheFilename($id, $type = 'UNKNOWN', $path_format = parent::DISK_ABSOLUTE)
	{
		$filename =  self::getBaseCachePath() . '/' .sha1("{$type}-{$id}").self::CACHE_EXTENSION;
		$file_path = parent::formatPath($filename, $path_format);
		return $file_path;
	}
	
	/**
	 * Checks to see if the cache directory exists.
	 * If not it can be automatically created. 
	 * @param Boolean $auto_create Whether or not the directory should be created.
	 * @return Boolean Whether or not the director exists.
	 */
	public static function cachePathExists($auto_create = FALSE)
	{
		$base_cache_path = self::getCachePath(TRUE);
		$exists = file_exists($base_cache_path); 
		if(!$exists && $auto_create)
		{
			parent::createDirectory($base_cache_path);
			$exists = file_exists($base_cache_path); 
		}
		return $exists;
	}
	
	/**
	 * Removes all files which are currently cached.
	 * @return Boolean Whether or not the cache was sucessfully cleared.
	 */
	public static function clearCache()
	{
		if(!self::cachePathExists(TRUE)) { return FALSE; }
		else
		{
			parent::removeDirectory(self::getCachePath(TRUE));
		}
	}
}