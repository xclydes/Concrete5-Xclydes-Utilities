<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardEcUtilitiesController extends Controller{
	
	private $CURRENT_EVENT = NULL;
	private $PACKAGE = NULL;
	private $cache_actions = array(
							'flatfilecache'=>'Flat File Cache',
							 'publicviewdir'=>'Optimized Files Directory',
							 'optimizedimages'=>'Optimized Images Directory'
							 );
	
	public function handleError($error){
		$LOG = new Log($this->getPackageHandle() . ' - DashboardController', false);
		$LOG->write($error->getTraceAsString());
	}
	
	protected function getPackageID(){
		return $this->getCollectionObject()->getPackageID();
	}
	
	protected function getPackageHandle(){
		return $this->getCollectionObject()->getPackageHandle();
	}
	
	protected function getBaseAdminPath(){
		return $this->getCollectionObject()->getCollectionPath();
	}
	
	protected function getPackage(){
		if(!$this->PACKAGE){
			$this->PACKAGE = Package::getByHandle($this->getPackageHandle());
		}
		return $this->PACKAGE;
	}
	
	public function getCacheActions(){
		return $this->cache_actions;
	}
	
	public function view(){
		/* Set some variables for use in the display */
		$this->set('admin_url_base', $this->getBaseAdminPath());
		$this->set('pkgHandle', $this->getPackageHandle());
		$this->set('pkg', $this->getPackage());
	}
	
	public function clear($type = ''){
		if(!in_array($type,array_keys($this->getCacheActions()))){
			$this->redirect($this->getBaseAdminPath());
		}
		try{
			$message = '';
			switch($type){
				case 'flatfilecache':
					Loader::helper('flat_file_cache', $this->getPackageHandle());
					FlatFileCacheHelper::clearCache();
					$message = 'The "Cache" has been cleared. It will be rebuilt over time';
					break;
				case 'publicviewdir':
					Loader::model('asset_optimizer', $this->getPackageHandle());
					AssetOptimizer::emptyOutputDir();
					$message = 'The "Optimizer" directory has been cleaned. It will be regenerated over time.';
					break;
				case 'optimizedimages':
					Loader::model('image_optimizer', $this->getPackageHandle());
					ImageOptimizer::emptyOutputDir();
					$message = 'The "Images" directory has been cleaned. It will be regenerated over time.';
					break;
			}
			$this->set('message', t($message));
			$this->task = 'view';
			$this->view();
		}catch(Exception $error){
			$this->handleError($error);
		}
	}
	
	public function save_config(){
		try{
			$config_settings = $_POST;
			$pkg = Package::getByHandle($this->getPackageHandle());
			foreach($config_settings AS $cfKey=>$value){
				$pkg->saveConfig($cfKey, $value);
			}
			$this->set('message',t('Your settings have been saved.'));
		}catch(Exception $error){
			$this->handleError($error);
		}
		$this->view();
	}
}
