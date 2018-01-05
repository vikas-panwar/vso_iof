<?php if (DESIGN == 4) { ?>
    <div class="content single-frame">
        <div class="clearfix">
            <section class="form-layout sign-in" style='max-width:inherit;'>
                <h2><?php echo $heading; ?></h2>
                <?php echo $content; ?>
            </section>
        </div>
    </div>

    <?php
} else {
    if ((DESIGN == 1) && ($store_data_app['Store']['store_theme_id'] == 11)) {
        ?>
        <div class="ext-menu">
            <div class="ext-menu-title">
                <h4><?php echo $heading; ?></h4>
            </div>
        </div>
    <?php } ?>
    <div class="title-bar"><?php echo $heading; ?></div>
    <div class="main-container">
        <div class="inner-wrap profile no-border">
            <!--            <div class="common-title">
                            <h2><?php echo $content['StoreContent']['name']; ?></h2>
                        </div>-->
            <?php echo $content; ?>
        </div>
    </div>

    <?php
}?>