<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class XclydesUtilitiesPackage extends Package {

	protected $pkgHandle = 'xclydes_utilities';
	protected $appVersionRequired = '5.1.0';
	protected $pkgVersion = '1.0.7';

	public function getPackageDescription() {
		return t('A collection of various ulitities, and models.');
	}

	public function getPackageName() {
		return t('Xclydes Utilities');
	}

	public function getDefaultPaths(){
		$site_key_string = sha1("xclydes_cache-{$_SERVER['HTTP_HOST']}");
		$default_paths['flatfilecachehelper'] = substr( $site_key_string, 0, 7) ;
		$default_paths['assetoptimizermodel'] = substr( $site_key_string, 8, 7) ;
		$default_paths['imageoptimizermodel'] = substr( $site_key_string, 15, 7) ;
		return $default_paths;
	}
	
	public function install() {
		$pkg = parent::install();
		if($pkg){
			
			/*Save Default Settings */
			$default_paths = $this->getDefaultPaths();
			$pkg->saveConfig('ffchPath', $default_paths['flatfilecachehelper']);
			$pkg->saveConfig('aomPubPath', $default_paths['assetoptimizermodel']);
			$pkg->saveConfig('iomPubPath', $default_paths['imageoptimizermodel']);
			$pkg->saveConfig('aomOverrideCore', FALSE);
			$this->createDashboardBase($pkg);		
			/* Add DashBoard Controls */
			Loader::model('single_page');
			$utilites_root = SinglePage::add('/dashboard/ec/utilities', $pkg); 
			$utilites_root->update(array('cName'=>t('Utility Pack'), 'cDescription'=>t('Configure the utility pack.')));
		}
	}
	
	public function createDashboardBase($pkg){
		Loader::model('single_page');
		$xclydes_root = SinglePage::add('/dashboard/ec', $pkg);
		if($xclydes_root){
			$xclydes_root->update(array('cName'=>t('EC Packages'), 'cDescription'=>t('Fine tune specific packages.')));
		}
	}

}