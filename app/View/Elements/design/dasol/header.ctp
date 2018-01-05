<header class="custom-header">
    <div class="white-bg">
        <div class="main-container">
            <div class="log-in-header clearfix">
                <div class="log-head-right">
                    <ul class="account-access">
                        <?php if (AuthComponent::User()) { ?>
                            <li><a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Welcome, <?php echo ucfirst($_SESSION['Auth']['User']['fname']); ?></a>
                                <ul class="dropdown-menu dropdown-cart" role="menu">
                                    <li><?php echo $this->Html->link('Delivery Addresses', array('controller' => 'users', 'action' => 'deliveryAddress', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                    <?php if (is_array($nzsafe_data_app)) { ?>
                                        <li><?php echo $this->Html->link('My Billing Information', array('controller' => 'users', 'action' => 'myBillingInfo', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                    <?php }
                                    ?>
                                    <li><?php echo $this->Html->link('Profile', array('controller' => 'users', 'action' => 'myProfile', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                    <li> <?php echo $this->Html->link(__('My Favorites & Orders'), array('controller' => 'orders', 'action' => 'myOrders', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                    <li><?php echo $this->Html->link(__('My Coupons'), array('controller' => 'coupons', 'action' => 'myCoupons', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                    <?php if ($store_data_app['Store']['is_booking_open'] == 1) { ?>
                                        <li><?php echo $this->Html->link(__('My Reservations'), array('controller' => 'pannels', 'action' => 'myBookings', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                    <?php } ?>
                                    <li><?php echo $this->Html->link(__('My Reviews'), array('controller' => 'pannels', 'action' => 'myReviews', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                </ul>
                            </li>
                            <li><span>|</span></li>
                            <li><?php echo $this->Html->link('<i class="fa fa-power-off"></i> Logout', array('controller' => 'users', 'action' => 'logout'), array('escape' => false)); ?></li>
                            <?php
                        } else if (!AuthComponent::User() && $this->Session->read('GuestUser.name')) {
                            $gName = $this->Session->read('GuestUser.name');
                            ?>
                            <li><a href="javascript:void(0);">Welcome , <?php echo $gName; ?></a></li>
                            <li><span>|</span></li>
                            <li><?php echo $this->Html->link('<i class="fa fa-power-off"></i> Logout', array('controller' => 'users', 'action' => 'logout'), array('escape' => false)); ?></li>
                        <?php } else {
                            ?>
                            <li><a href="#" data-toggle="modal" data-target="#login-modal">Login</a></li>
                            <li><span>|</span></li>
                            <li><?php echo $this->Html->link('Sign Up', array('controller' => 'users', 'action' => 'registration'), array('escape' => false, 'class' => "")); ?></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <div class="o-grid__item">
                <button  id="vt-hambug" class="c-hamburger c-hamburger--htx " >
                    <span>toggle menu</span>
                </button>
            </div>

        </div>
    </div>
    <?php if (KEYWORD == 'IOF-D4-H') { ?>
        <div class="black-bg vt-header theme-bg-2">
            <div class="main-container">
                <div class="main-header-menu">

                    <?php if ($store_data_app['Store']['is_store_logo'] == 1) { ?>
                        <div class="logo logo-title">
                            <h1><a href="/users/login" class="store-name"><?php echo $store_data_app['Store']['store_name']; ?></a></h1>
                        <?php } else {
                            ?>
                            <div class="logo">
                                <?php if ($store_data_app['Store']['logotype'] == 1) {
                                    ?>
                                    >
                                    <a href="/users/login" class="restaurant-logo-square"><?php echo $this->Html->image('/storeLogo/' . $store_data_app['Store']['store_logo']); ?></a>

                                <?php } elseif ($store_data_app['Store']['logotype'] == 2) {
                                    ?>

                                    <a href="/users/login" class="restaurant-logo-rectangle"><?php echo $this->Html->image('/storeLogo/' . $store_data_app['Store']['store_logo']); ?></a>

                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if (KEYWORD != 'IOF-D3-H' && KEYWORD != 'IOF-D4-H') { ?>
            <div class="black-bg vt-header theme-bg-2">
                <div class="main-container">
                    <div class="main-header-menu">

                        <?php if ($store_data_app['Store']['is_store_logo'] == 1) { ?>
                            <div class="logo logo-title">
                                <h1><a href="/users/login" class="store-name"><?php echo $store_data_app['Store']['store_name']; ?></a></h1>
                            <?php } else {
                                ?>
                                <div class="logo">
                                    <?php if ($store_data_app['Store']['logotype'] == 1) {
                                        ?>

                                        <a href="/users/login" class="restaurant-logo-square"><?php echo $this->Html->image('/storeLogo/' . $store_data_app['Store']['store_logo']); ?></a>


                                    <?php } elseif ($store_data_app['Store']['logotype'] == 2) {
                                        ?>

                                        <a href="/users/login" class="restaurant-logo-rectangle"><?php echo $this->Html->image('/storeLogo/' . $store_data_app['Store']['store_logo']); ?></a>

                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <div class="menu-right">
                                <nav class="CTA-menu">
                                    <div class="navbar-header">
                                        <button type = "button" class = "navbar-toggle"
                                                data-toggle = "collapse" data-target = "#example-navbar-collapse">
                                            <span class = "sr-only">Toggle navigation</span>
                                            <span class = "icon-bar"></span>
                                            <span class = "icon-bar"></span>
                                            <span class = "icon-bar"></span>
                                        </button>
                                    </div>
                                    <div class="collapse navbar-collapse custom-colapse" id = "example-navbar-collapse">
                                        <a href="javascript:void(0)" class="menu-control"><i class="fa fa-chevron-down"></i></a>
                                        <?php /*(if (KEYWORD == 'IOF-D1-H' || KEYWORD == 'IOF-D1-V') { ?><?php pr('header');?>
                                            <ul class="main-menu-list menu-inr-left">
                                                <li class="<?php if (($this->params['controller'] == 'users' || $this->params['controller'] == 'Users') && ($this->params['action'] == 'login')) echo 'active'; ?>"><a href="/users/login">Home</a></li>
                                                <?php if ($store_data_app['Store']['review_page'] == 1) { ?>
                                                    <li class="<?php if (($this->params['controller'] == 'pannels' || $this->params['controller'] == 'Pannels') && ($this->params['action'] == 'allReviews')) echo 'active'; ?>"> <?php echo $this->Html->link('Reviews', array('controller' => 'pannels', 'action' => 'allReviews')); ?></li>
                                                <?php } ?>

                                                <?php if (!empty($store_data_app) && $store_data_app['Store']['deal_page'] == 2) { ?>
                                                    <li class="<?php if ($this->params['controller'] == 'deals') echo 'active'; ?>"> <?php echo $this->Html->link('Deals', array('controller' => 'deals', 'action' => 'index')); ?></li>
                                                <?php } ?>

                                                <?php if ($store_data_app['Store']['is_booking_open'] == 1 && AuthComponent::User()) { ?>
                                                    <li class="<?php if (($this->params['controller'] == 'pannels' || $this->params['controller'] == 'Pannels') && ($this->params['action'] == 'myBookings')) echo 'active'; ?>"><?php echo $this->Html->link(__('Reservations'), array('controller' => 'pannels', 'action' => 'myBookings', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                                <?php } ?>

                                            </ul>
                                            <ul class="main-menu-list menu-inr-right">
                                                <li class='<?php if (($this->params['controller'] == 'products' || $this->params['controller'] == 'Menus') && ($this->params['action'] == 'items')) echo 'active'; ?>'>
                                                    <a href="/products/items/<?php echo $this->Encryption->encode($this->Session->read('store_id')); ?>/<?php echo $this->Encryption->encode($this->Session->read('merchant_id')); ?>">Menu</a>
                                                </li>
                                                <?php
                                                if (!empty($store_data_app['StoreContent'])) {
                                                    foreach ($store_data_app['StoreContent'] as $content) {
                                                        if ($content['page_position'] == 1) {
                                                            ?>
                                                            <li class='<?php if (($this->params['controller'] == 'pannels') && ($this->params['action'] == 'staticContent') && ($this->params['pass'][3] == $content['name'])) echo 'active'; ?>'><?php echo $this->Html->link($content['name'], array('controller' => 'pannels', 'action' => 'staticContent', $this->Encryption->encode($this->Session->read('store_id')), $this->Encryption->encode($this->Session->read('merchant_id')), $this->Encryption->encode($content['id']), $content['name'])); ?></li>
                                                            <?php
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if ($store_data_app['Store']['is_not_photo'] == 0) { ?>  
                                                    <li class="<?php if (($this->params['controller'] == 'pannels') && ($this->params['action'] == 'orderImages')) echo 'active'; ?>"><?php echo $this->Html->link('Gallery', array('controller' => 'pannels', 'action' => 'orderImages', $this->Encryption->encode($this->Session->read('store_id')), $this->Encryption->encode($this->Session->read('merchant_id')))); ?></li><?php } ?>
                                                <li class='<?php if (($this->params['controller'] == 'users' || $this->params['controller'] == 'Users') && ($this->params['action'] == 'storeLocation')) echo 'active'; ?>'><a href="/users/storeLocation/<?php echo $this->Encryption->encode($this->Session->read('store_id')); ?>/<?php echo $this->Encryption->encode($this->Session->read('merchant_id')); ?>">Store Info</a></li>
                                            </ul>
                                        <?php }else {*/ ?>
                                            <ul class="main-menu-list menu-inr-left">
                                                <?php
                                                if (!empty($store_data_app['StoreContent'])) {
                                                    foreach ($store_data_app['StoreContent'] as $content) {
                                                        if ($content['page_position'] == 1) {
                                                            if ($content['name'] == 'HOME') {
                                                                ?>
                                                                <li class = "<?php if (($this->params['controller'] == 'users' || $this->params['controller'] == 'Users') && ($this->params['action'] == 'login')) echo 'active'; ?>"><a href = "/users/login">Home</a></li>
                                                                <?php
                                                            }elseif ($content['name'] == 'REVIEWS') {
                                                                if ($store_data_app['Store']['review_page'] == 1) {
                                                                    ?>
                                                                    <li class="<?php if (($this->params['controller'] == 'pannels' || $this->params['controller'] == 'Pannels') && ($this->params['action'] == 'allReviews')) echo 'active'; ?>"> <?php echo $this->Html->link('Reviews', array('controller' => 'pannels', 'action' => 'allReviews')); ?></li>
                                                                    <?php
                                                                }
                                                            }elseif ($content['name'] == 'DEALS') {
                                                                if ($store_data_app['Store']['deal_page'] == 2) {
                                                                    ?>
                                                                    <li class="<?php if (($this->params['controller'] == 'deals' || $this->params['controller'] == 'Deals') && ($this->params['action'] == 'index')) echo 'active'; ?>"> <?php echo $this->Html->link('Deals', array('controller' => 'deals', 'action' => 'index')); ?></li>
                                                                    <?php
                                                                }
                                                            }elseif ($content['name'] == 'RESERVATIONS') {
                                                                if (AuthComponent::User() && !empty($store_data_app['Store']['is_booking_open'])) {
                                                                    ?>
                                                                    <li class="<?php if (($this->params['controller'] == 'pannels' || $this->params['controller'] == 'Pannels') && ($this->params['action'] == 'myBookings')) echo 'active'; ?>"><?php echo $this->Html->link(__('Reservations'), array('controller' => 'pannels', 'action' => 'myBookings', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                                                                    <?php
                                                                }
                                                            }elseif ($content['name'] == 'STORE INFO') {
                                                                ?>
                                                                <li class='<?php if (($this->params['controller'] == 'users' || $this->params['controller'] == 'Users') && ($this->params['action'] == 'storeLocation')) echo 'active'; ?>'><a href="/users/storeLocation/<?php echo $this->Encryption->encode($this->Session->read('store_id')); ?>/<?php echo $this->Encryption->encode($this->Session->read('merchant_id')); ?>">Store Info</a></li>
                                                            <?php }elseif ($content['name'] == 'MENU') {
                                                                ?>
                                                                <li class='<?php if (($this->params['controller'] == 'users' || $this->params['controller'] == 'products') && ($this->params['action'] == 'items')) echo 'active'; ?>'><a href="/products/items/<?php echo $this->Encryption->encode($this->Session->read('store_id')); ?>/<?php echo $this->Encryption->encode($this->Session->read('merchant_id')); ?>">Menu</a></li>
                                                                <?php
                                                            }elseif ($content['name'] == 'GALLERY') {
                                                                if ($store_data_app['Store']['is_not_photo'] == 1) {
                                                                    ?>
                                                                    <li class="<?php if (($this->params['controller'] == 'pannels') && ($this->params['action'] == 'orderImages')) echo 'active'; ?>"><?php echo $this->Html->link('Gallery', array('controller' => 'pannels', 'action' => 'orderImages', $this->Encryption->encode($this->Session->read('store_id')), $this->Encryption->encode($this->Session->read('merchant_id')))); ?></li>
                                                                    <?php
                                                                }
                                                            }else {
                                                                if ($content['name'] != 'PLACE ORDER' && $content['name'] != 'PHOTO') {
                                                                    ?>
                                                                    <li class='<?php if (($this->params['controller'] == 'pannels') && ($this->params['action'] == 'staticContent') && ($this->params['pass'][3] == $content['name'])) echo 'active'; ?>'><?php echo $this->Html->link($content['name'], array('controller' => 'pannels', 'action' => 'staticContent', $this->Encryption->encode($this->Session->read('store_id')), $this->Encryption->encode($this->Session->read('merchant_id')), $this->Encryption->encode($content['id']), $content['name'])); ?></li>
                                                                    <?php
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($store_data_app['StoreContent'])) { ?>
                                                    <?php
                                                    $m = 0;
                                                    foreach ($store_data_app['StoreContent'] as $content) {
                                                        if ($content['page_position'] == 3) {
                                                            if ($m == 0) {
                                                                ?>
                                                                <li>
                                                                    <a href="javascript:void(0)">MORE INFO</a>
                                                                    <ul class="list-style-none">
                                                                    <?php }
                                                                    ?>
                                                                    <li class='<?php if (($this->params['controller'] == 'pannels') && ($this->params['action'] == 'staticContent') && ($this->params['pass'][3] == $content['name'])) echo 'active'; ?>'><?php echo $this->Html->link($content['name'], array('controller' => 'pannels', 'action' => 'staticContent', $this->Encryption->encode($this->Session->read('store_id')), $this->Encryption->encode($this->Session->read('merchant_id')), $this->Encryption->encode($content['id']), $content['name'])); ?>
                                                                    </li>
                                                                    <?php
                                                                    $m++;
                                                                }
                                                            }
                                                            ?>
                                                        </ul>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                            </ul>
                                        <?php //} ?>
                                        <?php $cartlink = "/products/items/" . $encrypted_storeId . "/" . $encrypted_merchantId; ?>
                                        <a href="<?php echo $cartlink; ?>" class="ordr-btn theme-bg-1">Order Online</a>
                                    </div>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            </header>

            <script>
                $(document).ready(function () {
                    $('.menu-control').on('click', function () {
                        $('.main-menu-list').toggleClass('open');
                    });
                });

                /*$(document).mouseup(function (e)
                 {
                 var container = $(".vt-header");
                 
                 if (!container.is(e.target) // if the target of the click isn't the container...
                 && container.has(e.target).length === 0) // ... nor a descendant of the container
                 {
                 $('.vt-header').removeClass('open');
                 }
                 });*/
            </script>
