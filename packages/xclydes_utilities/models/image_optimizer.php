<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
try
{
	//Loader::helper('file_system', 'xclydes_utilities');
	//Load extra classes which will be needed.
	Loader::helper('flat_file_cache', 'xclydes_utilities');
}
catch(Exception $error)
{
	$LOG = new Log('image_optimizer', false);
	$LOG->write($error->getTrace());
}

class ImageOptimizer
{
	const IMG_FILE_TYPE = 'ProcessedImageFile';
	private $FILEPATH = '';
	private $WIDTH = -1;
	private $HEIGHT = -1;
	private $WRITE_TO = '';
	private $PROCESSED_IMAGE = NULL;
	private $RAW_IMAGE = NULL;
	const CONFIG_KEY = 'iomPubPath';
	
	//Define the package handle until a method of reliably retreiving it has been determined.
	private static $pkgHandle = 'xclydes_utilities';
	private static $package = NULL;
	
	public function __construct($source, $destination = '')
	{
		$this->setSource($source);
		$this->setDestination($destination);
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
		//$instance = self::getInstance();
		if(self::getPackage()){//Check to see if it is defined on the package.
			$folder_name = self::getPackage()->config(self::CONFIG_KEY);
		}
		if(!$folder_name){
			$folder_name = substr(sha1("xclydes_public-{$_SERVER['HTTP_HOST']}"), 0, 5);
		}
		$path = FlatFileCacheHelper::formatPath(DIR_FILES_UPLOADED_STANDARD . '/' . $folder_name .'/', $format);
		return FlatFileCacheHelper::createDirectory($path) ? $path : '';
	}
	
	/**
	 * Sets the path of the file to be processed.
	 * @param String $filepath The path to the image file to be optimized.
	 * @return ImageOptimizer A reference to the current object to allow chaining.
	 */
	public function setSource($filepath)
	{
		$filepath = FlatFileCacheHelper::checkFilePath($filepath, FlatFileCacheHelper::DISK_ABSOLUTE);
		if($filepath)
		{
			$this->FILEPATH = $filepath;
		}
		return $this;
	}
	
	/**
	 * Sets the path to which the optimized file should be stored.
	 * @param String $save_path The path to which the file should be stored.
	 * @return ImageOptimizer A reference to the current object to allow chaining.
	 */
	public function setDestination($save_path)
	{
		$this->WRITE_TO = FlatFileCacheHelper::formatPath($save_path, FlatFileCacheHelper::DISK_ABSOLUTE);
		return $this;
	}
	
	/**
	 * Sets the dimensions of the optimized image.
	 * @param Integer $width The width of the optimized image. Set to 0 for automatic scaling.
	 * @param Integer $height The height of the optimized image. Set to 0 for automatic scaling.
	 * @return ImageOptimizer A reference to the current object to allow chaining.
	 */
	public function setDimensions($width = FALSE, $height = FALSE)
	{
		if($width && is_numeric($width)) { $this->WIDTH = $width; }
		if($height && is_numeric($height)) { $this->HEIGHT = $height; }
		return $this;
	}
	
	/**
	 * Gets the path to via which the processed image can be accessed/served.
	 * If a save was not specified an empty string will be returned as the image
	 * not written to disk.
	 * @return String The relative path to the optimized image.
	 */
	public function getProcessedImagePath()
	{
		return FlatFileCacheHelper::formatPath($this->WRITE_TO, FlatFileCacheHelper::SITE_ABSOLUTE);
	}
	
	/**
	 * Gets the path to via which the original image can be accessed/served.
	 * @return String The relative path to the original image.
	 */
		public function getRawImagePath()
	{
		return $this->FILEPATH;
	}
	
	/**
	 * Gets the result of the image processing.
	 * @return Object An object representing the processed image.
	 */
	public function getProcessedImageData()
	{
		//return $this->PROCESSED_IMAGE;
	}
	
	/**
	 * Gets raw unprocessed image data.
	 * @return Object An object representing the unprocessed image data.
	 */
	public function getRawImageData()
	{
		//return $this->RAW_IMAGE;
	}
	
	/**
	 * @param String $single_use_path A path to which the image should be saved. 
 	 * 								  This path does no override the object's current save path.
	 *								  To do so use  setDestination.
	 */
	public function saveToDisk($single_use_path = '')
	{
		if($single_use_path){
			$true_save_path = FlatFileCacheHelper::formatPath($single_use_path, FlatFileCacheHelper::DISK_ABSOLUTE);
		}
		/*$true_save_path = 
			$single_use_path ? 
				FlatFileCacheHelper::formatPath($single_use_path, FlatFileCacheHelper::DISK_ABSOLUTE) : //!strpos($single_use_path, DIR_BASE) ? DIR_BASE . $single_use_path : $single_use_path :
				$this->WRITE_TO;*/
		if( $true_save_path )
		{
			copy($this->WRITE_TO, $true_save_path);
			//file_put_contents($true_save_path, $this->PROCESSED_IMAGE);
		}
		return $this;
	}
	
	/**
	 * Performs the actual image processing and storage.
	 * @return Object An object representing this object.
	 */
	public function processImage()
	{
		$img_src = $this->FILEPATH;
		if(FileSystemHelper::checkFilePath($img_src) == ''){ 
			return $this; 
		}
		
		if(!$img_src) { return; }
		
		$new_height = $this->HEIGHT;
		$new_width = $this->WIDTH;
		$ext = $quality = 0;
		try
		{
			$mime = @getimagesize($img_src);
			$mime = $mime['mime'];
			switch($mime){
				case 'image/gif':
					$ext='gif';
					$quality = -1;				
					break;
				case 'image/jpeg':
					$ext = 'jpeg';
					$quality = 80;			
					break;
				case 'image/png':
					$ext = 'png';
					$quality = 8;			
					break;
			}
			$cache_id =  md5("{$img_src}.{$new_width}.{$new_height}")."_{$new_width}x{$height}{$new_height}.{$ext}";
			if(!$this->WRITE_TO){
				$this->WRITE_TO = self::getPublicViewFolder(FlatFileCacheHelper::DISK_ABSOLUTE).$cache_id;
			}
			$storage_path = $this->WRITE_TO;
			$this->PROCESSED_IMAGE = FlatFileCacheHelper::get(self::IMG_FILE_TYPE, $cache_id, FlatFileCacheHelper::fileModTime($this->FILEPATH));
			if(!$this->PROCESSED_IMAGE)
			{
				$create = 'imagecreatefrom'.$ext;
				$render = 'image'.$ext;
				$img = $create($img_src);
				$this->RAW_IMAGE = $img;
				$original_width = imagesx($img);
				$original_height = imagesy($img);
				if($new_width>=5 && $new_height<1){$new_height = ($new_width/$original_width)*$original_height;}
				if($new_height>=5 && $new_width<1){$new_width = ($new_height/$original_height)*$original_width;}
				if($new_width<1 && $new_height<1)
				{
					$new_width = AL_THUMBNAIL_WIDTH;
					$new_height = ($new_width/$original_width)*$original_height;
				}
				$new_img = imagecreatetruecolor($new_width,$new_height);
				if($ext=='gif'){#Tranparency Preservation.
					$trnprt_indx = imagecolortransparent($img); 
					$trnprt_color = imagecolorsforindex($img, $trnprt_indx); // Get the original image's transparent color's RGB values       
					$trnprt_indx = imagecolorallocate($new_img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);// Allocate the same color in the new image resource
					imagefill($new_img, 0, 0, $trnprt_indx);// Completely fill the background of the new image with allocated color.      
					imagecolortransparent($new_img, $trnprt_indx);// Set the background color for new image to transparent
				}
				elseif($ext=='png'){#Tranparency Preservation.
					imagealphablending($new_img,false );
					imagesavealpha($new_img,true );
				}
				imagecopyresampled($new_img,$img,0,0,0,0,$new_width,$new_height,$original_width,$original_height);
				if($quality==-1){@$render( $new_img, $storage_path);}
				else{@$render( $new_img, $storage_path, $quality);}
				imagedestroy($new_img);
				$this->PROCESSED_IMAGE = file_get_contents($this->WRITE_TO);
				FlatFileCacheHelper::set(self::IMG_FILE_TYPE, $cache_id, $this->PROCESSED_IMAGE);
			}
			if(!file_exists($this->WRITE_TO)){
				file_put_contents($this->WRITE_TO, $this->PROCESSED_IMAGE);
			}
		}
		catch(Exception $error)
		{
			$Log = new Log('image_optimizer', false);
			$Log->write($error->getTraceAsString());
		}
		return $this;
	}

	/**
	* Removes all files which have been outputted to disk.
	* @return Boolean Whether or not the cache was sucessfully cleared.
	*/
	public static function emptyOutputDir()
	{
		if(!FlatFileCacheHelper::checkFilePath(self::getPublicViewFolder(), FlatFileCacheHelper::DISK_ABSOLUTE)) { return FALSE; }
		else
		{
			FlatFileCacheHelper::removeDirectory(self::getPublicViewFolder());
		}
	}

}