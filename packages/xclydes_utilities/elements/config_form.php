<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
$default_paths = $pkg->getDefaultPaths();
?>	
	<!-- Start Asset Optimizer -->	
    <div id="assetoptimizer-options" class="ccm-module">
        <h1><span><?php echo t('Asset Optimizer');?></span></h1>
        <div class="ccm-dashboard-inner">
        <form method="post" id="assetoptimizer-form" action="<?php echo $form->action($admin_url_base, 'save_config'); ?>">
			        <!--<h4><?php echo t('Settings'); ?></h4>
			        <br />-->
			        <?php echo $form->label('aomPubPath', t('Directory Path'));?> : <br />
			        <?php echo $form->text('aomPubPath', $pkg->config('aomPubPath'), array('style'=>"width:90%;"));?>
			        <div class="ccm-dashboard-description">
			        	<p><?php echo t('The name of the folder to be used for storing files generated by the');?> <strong><?php echo t('Asset Optimizer');?></strong>.<br />
			        	<?php echo t('This folder can found inside the current');?> <strong><?php echo t('Concrete5 Files').' ('.REL_DIR_FILES_UPLOADED.'/)';?></strong> <?php echo t('directory');?><br />
			        	<strong><?php echo t('Default');?>: </strong><?php echo $default_paths['assetoptimizermodel'];?><br />
			        	</p>
			        </div>
			        <!--<?php echo $form->label('aomOverrideCore', t('Handle Core Assets'));?> :
			        <?php echo $form->select('aomOverrideCore', array('FALSE'=>'Disabled', 'TRUE'=>'Enabled'), $pkg->config('aomOverrideCore'));?> <br />
			        <div class="ccm-dashboard-description">
			        	<p>
							<?php echo t('Whether or not the <strong>Asset Optimizer</strong> should attempt to include core <strong>Concrete5</strong> files.');?>.<br />
                            <strong><?php echo t('Default');?>: </strong><?php echo "Disabled";?><br />
                        </p>
			        </div>-->
			        <a href="<?php echo View::url($admin_url_base, 'clear', 'publicviewdir');?>"><?php echo t('Empty Directory');?></a>
	        <hr />
			<?php 
				echo $core_ui->submit(t('Save Settings'), 'assetoptimizer-form');
			?>
        </form>	
        <br class="clear" />
        </div>
    </div>
	<!-- End Asset Optimizer -->

	<!-- Start Flat File Cache -->
    <div id="flatfilecache-options" class="ccm-module">
        <h1><span><?php echo t('Flat File Cache Helper');?></span></h1>
        <div class="ccm-dashboard-inner">
            <form method="post" id="flatfilecache-form" action="<?php echo $form->action($admin_url_base, 'save_config'); ?>">
			       <!--<h4><?php echo t('Settings'); ?></h4>
			        <br />--> 
			        <?php echo $form->label('ffchPath', t('Cache Path'));?> : <br />
			        <?php echo $form->text('ffchPath', $pkg->config('ffchPath'), array('style'=>"width:90%;"));?>
			        <div class="ccm-dashboard-description">
			        	<p><?php echo t('The name of the folder to be used for storing files generated by the');?> <strong><?php echo t('Flat File Cache Helper');?></strong>.<br />
			        	<?php echo t('This folder can found inside the current');?> <strong><?php echo t('Concrete5 Files').' ('.REL_DIR_FILES_UPLOADED.'/)';?></strong> <?php echo t('directory');?><br />
			        	<strong><?php echo t('Default');?>: </strong><?php echo $default_paths['flatfilecachehelper'];?><br />
			        	</p>
			        </div>
	        		<a href="<?php echo View::url($admin_url_base, 'clear', 'flatfilecache');?>"><?php echo t('Clear Cache');?></a>
	        <hr />
			<?php 
				echo $core_ui->submit(t('Save Settings'), 'flatfilecache-form');
			?>
            </form>		
        <br class="clear" />
        </div>
    </div>
	<!-- End Flat File Cache -->

	<!-- Start Image Optimizer -->	
    <div id="imageoptimizer-options" class="ccm-module">
        <h1><span><?php echo t('Image Optimizer');?></span></h1>
        <div class="ccm-dashboard-inner">
            <form method="post" id="imageoptimizer-form" action="<?php echo $form->action($admin_url_base, 'save_config'); ?>">
                <!--<h4><?php echo t('Settings'); ?></h4>
                <br />-->
                <?php echo $form->label('iomPubPath', t('Directory Path'));?> : <br />
                <?php echo $form->text('iomPubPath', $pkg->config('iomPubPath'), array('style'=>"width:90%;"));?>
                <div class="ccm-dashboard-description">
                    <p><?php echo t('The name of the folder to be used for storing files generated by the');?> <strong><?php echo t('Image Optimizer');?></strong>.<br />
                    <?php echo t('This folder can found inside the current');?> <strong><?php echo t('Concrete5 Files').' ('.REL_DIR_FILES_UPLOADED.'/)';?></strong> <?php echo t('directory');?><br />
                    <strong><?php echo t('Default');?>: </strong><?php echo $default_paths['imageoptimizermodel'];?><br />
                    </p>
                </div>
                <a href="<?php echo View::url($admin_url_base, 'clear', 'optimizedimages');?>"><?php echo t('Empty Directory');?></a>
        <hr />
        <?php 
            echo $core_ui->submit(t('Save Settings'), 'imageoptimizer-form');
        ?>
            </form>	
        <br class="clear" />
        </div>
    </div>
	<!-- End Image Optimizer -->
