<!-- static-banner -->
<?php echo $this->element('hquser/static_banner'); ?>
<!-- /banner -->
    <div class="location common-padding">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="common-title clearfix">
                        <span class="yello-dash"></span>
                        <h2><?php echo "Privacy Policy"; ?></h2>
                    </div>
                </div>
            </div>
           <div class="row">
                    <div class="col-xs-12">
                        <div class="row" style="padding-top: 15px;">
			    <?php
			    if (!empty($tandcData)) { ?>        
			    
                            <div><?php echo $tandcData; ?></div>
                            <?php }
?>
                        </div>
                    </div>
                </div>
        </div>
    </div>