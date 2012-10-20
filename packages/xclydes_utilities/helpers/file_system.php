<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * @author Xclydes
 * A simple class which allows for common filesystem tasks to be performed.
 */
class FileSystemHelper
{

	const DISK_ABSOLUTE = 'disk absolute';
	const SITE_ABSOLUTE = 'site absolute';
	const RELATIVE = 'relative';
	
	/**
	 * Determines the current environment and returns the appropiate directory seperator.
	 * @return String The End Of Line Character that best suits the environment.
	 */
	final public static function EOL()
	{
		return (DIRECTORY_SEPARATOR == '/') ? "\n" : "\r\n";
	}
	
	/**
	* Determines the last modified time of the filepath submitted. If the file cannot be located 
	* the time returned will be 0.
	* @return Integer A unix timestamp representing the last time the file was modified.
	*/
	final public static function fileModTime($filepath)
	{
		$mtime = 0;
		$filepath = self::checkFilePath($filepath);
		if($filepath)
		{
			$mtime = filemtime($filepath);
		}
		return $mtime;
	}
	
	/**
	 * Creates the folders necessary to ensure the existence of the requested path.
	 * @param String $filepath The filepath to be created.
	 */
	final public static function createDirectory($filepath)
	{
		$created = FALSE;
		try 
		{
			if(file_exists($filepath)) { $created = TRUE; }
			if(!$created){
				$directories = explode('/',self::unixFilePath($filepath));
				$path = substr($filepath, 0,1) == '/' ? '/' : '';
				foreach($directories as $directory)
				{
					$path = self::unixFilePath($path == '' ? "{$directory}" : "{$path}/{$directory}");
					if(!file_exists($path))
					{
						@mkdir($path, 0777);
					}
					$created = TRUE;
				}
			}
		} 
		catch (Exception $error) 
		{
			self::handleError($error);
		}
		return $created;
	}
	
	/**
	 * Ensures the inputted filepath is using '/' only and there are no duplicate slashes.
	 * @param String $filepath The filepath to be corrected.
	 * @return String The corrected filepath
	 */
	final public static function unixFilePath($filepath)
	{
		return preg_replace(array('/\\+/','/\/+/'), array('/','/'), $filepath);
	}
	
	/**
	* Retrieves the directory section of the inputted string.
	* @param String $filepath The path to be checked.
	* @return String The directories leading to the filepath specified.
	*/
	final public static function getFilePath($file_path, $return_format = self::RELATIVE){
		$file_path = self::unixFilePath($file_path);
		$file_path = substr($file_path,0, strrpos($file_path,'/')+1);
		/*If a specific return format is requested, honor it. */
		if($return_format){
			$file_path = self::formatPath($file_path, $return_format);
		}
		return $file_path;
	}
	
	/**
	* Retreives the file extension, if any, from the inputted string.
	* @param String file_path to be checked.
	* @return String The extension which was detected from the inputted string.
	*/
	final public static function getFileExtension($file_path, $include_period = FALSE){
		$ext = '';
		try{
			if(strrpos($file_path,'.')){
				$truncate_index = strrpos($file_path,'.');
				if($include_period){
					$truncate_index = $truncate_index - 1;
				}
				$ext = substr($file_path,$truncate_index);
			}
		}catch(Exception $error){
		}
		return $ext;
	}
	
	/**
	* Takes a string and ensures it is properly adjusted to reflect a location in a certain format.
	* @param String $return_format The format which is needed. Same as the class constants.
	* @return String The formatted string.
	*/
	final public static function formatPath($path, $return_format = self::RELATIVE){
		/*If the path is empty, do not continue.*/
		if(!$path) { return $path; }
		/*Ensure the path is sanitized. and fully unix compliant to reduce manipulation errors.*/
		$path = self::unixFilePath($path);
		switch($return_format){
			/*If an absolute (site root) path is requested.*/
			case self::SITE_ABSOLUTE :
				$path = self::formatPath($path, self::RELATIVE);
				if(DIR_REL != ''){
					if(strpos($path, DIR_REL) !== 0){
						$path = DIR_REL . '/' . $path;
					}
				}
				if(substr($path,0,1) != '/'){
					$path = "/{$path}";
				}
				break;
			/*If an absolute (disk root) path is requested.*/
			case self::DISK_ABSOLUTE :
				if(strpos($path, DIR_BASE) === FALSE){
						$cleaned_base = DIR_BASE;
						if(DIR_REL != ''){
							if(strpos($path, DIR_REL) === 0){
								$cleaned_base = substr($cleaned_base,0, -strlen(DIR_REL)); 
							}
						}
					$path = $cleaned_base . '/' . $path;
				}
				break;			
			/* If a relative (script) path is requested.*/
			case self::RELATIVE :
			default:
				if(!((strpos($path, DIR_BASE) === FALSE))){
					$path = substr($path, (strlen(DIR_BASE)+1));
				}
				if(substr($path, 0, 1) == '/'){
					while(substr($path, 0, 1) == '/'){
						$path = substr($path, 1);
					}
				}
				break;
		}
		return self::unixFilePath($path);
	}
	
	/**
	 * Ensures the inputed filepath/filename exists. Otherwise an empty string will be returned.
	 * @param String $filename The path to be checked.
	 */
	final public static function checkFilePath($filename, $return_format = self::RELATIVE)
	{
		$path = '';
		try
		{
			/*Check to see if the filename passed in exists as is.*/
			if(file_exists($filename)){
				$path = $filename;
			}
			/*If it doesnt, so check to see if it can be resolved absolutely.*/
			elseif(file_exists(DIR_BASE . '/' . $filename)){ 
				$path = DIR_BASE . '/' . $filename;
			}
			/*If it cannot be found absolutely, it may be an issue of the path script being
			run from a sub directory which occurs in the DIR_BASE and the filename.*/
			elseif(DIR_REL){
				$based_filename = substr(DIR_BASE, 0, -strlen(DIR_REL)+1) . '/' . $filename;
				if(file_exists($based_filename)){
					$path = $based_filename;
				}
			}
		}
		catch(Exception $error)
		{
			self::handleError($error);
		}
		/*If a specific return format is requested, honor it. */
		if($return_format){
			$path = self::formatPath($path, $return_format);
		}
		return self::unixFilePath($path);
	}

	/**
	 * Removes all files and folders listed located at the path specified, as well
	 * as the root directory.
	 * Credit To :- http://www.ozzu.com/programming-forum/php-delete-directory-folder-t47492.html#p240584
	 * @return Boolean Indicates whether or not the action was performed.
	 */
	final public static function removeDirectory($dirname)
	{
		try 
		{
			if (is_dir($dirname))
			{
				$dir_handle = opendir($dirname);	
			}
			if (!$dir_handle)
			{
				return false;
			}
			while($file = readdir($dir_handle)) {
				if ($file != '.' && $file != '..') {
					if (!is_dir($dirname . '/'   .$file))
					{
						unlink($dirname . '/' . $file);
					}
					else
					{
						self::removeDirectory($dirname . '/' . $file);
					}
				}
			}
			closedir($dir_handle);
			rmdir($dirname);
		}
		catch (Exception $error) 
		{
			self::handleError($error);
			return FALSE;
		}	
		return TRUE;
	}
	
	/**
	* An error logging mechanism to the handle exceptions which may be thrown in
	* in this class.
	*/
	private static function handleError($error)
	{
			$LOG = new Log('filesystem_helper', false);
			$LOG->write($error->getTraceAsString());
	}
}
