<?php
$storeId = $this->Session->read('store_id');
$url = HTTP_ROOT;
$imageurl = HTTP_ROOT . 'storeLogo/' . $store_data_app['Store']['store_logo'];
?>
<div class="main-container">
    <?php echo $this->Session->flash(); ?>
    <div class="inner-wrap common-tabs mySavedOrders">
        <div class="common-title">
            <h3><?php echo __('Favorite & Order History'); ?></h3>
        </div>
        <div class="form-section">
            <!-- TABS -->
            <ul class="resp-tabs-list">
                <li><?php echo $this->Html->link(__('My Favorites'), array('controller' => 'orders', 'action' => 'myFavorites', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                <li><?php echo $this->Html->link(__('My Orders'), array('controller' => 'orders', 'action' => 'myOrders', $encrypted_storeId, $encrypted_merchantId)); ?></li>
                <li class="active"><?php echo $this->Html->link(__('My Saved Orders'), array('controller' => 'orders', 'action' => 'mySavedOrders', $encrypted_storeId, $encrypted_merchantId)); ?></li>
            </ul>
            <!-- /TABS -->
            <?php echo $this->Form->create('Orders', array('url' => array('controller' => 'orders', 'action' => 'mySavedOrders'), 'id' => 'AdminId', 'type' => 'post','class'=> ' clearfix tab-form')); ?>
            <?php echo $this->element('userprofile/filter_store'); ?>
            <div class="col-lg-4">
                <?php echo $this->Form->button('Search', array('type' => 'submit', 'class' => '')); ?>
                <?php echo $this->Html->link('Clear', array('controller' => 'orders', 'action' => 'mySavedOrders', 'clear'), array('class' => '')); ?>
            </div>
            <?php echo $this->Form->end(); ?>
            <div class="inner-div">
                <?php echo $this->element('pagination'); ?>
                <!-- FORM VIEW -->
                <div class="resp-tabs-container">
                    <?php
                    if (!empty($myOrders)) {
                        foreach ($myOrders as $orders) {
                            ?>
                            <div class="repeat-deatil">                	
                                <div class="resp-tabs-frame">
                                    <div class="order-history-detail clearfix">
                                        <div class="order-history-detail-rt">  
                                            <?php
                                            $desc = '';
                                            $offers = '';
                                            $result = '';
                                            foreach ($orders['OrderItem'] as $order) {
                                                $desc = $order['quantity'] . ' ' . @$order['Size']['size'] . ' ' . @$order['Type']['name'] . ' ' . $order['Item']['name'];
                                                if (!empty($order['OrderOffer'])) {
                                                    foreach ($order['OrderOffer'] as $offer) {
                                                        $offers .= $offer['quantity'] . 'X' . $offer['Item']['name'] . '&nbsp;';
                                                    }
                                                }
                                                if (!empty($offers)) {
                                                    $result .= $desc . ' ( Offer : ' . $offers . '), ';
                                                } else {
                                                    $result .= $desc . ', ';
                                                }
                                                $offers = '';
                                                $desc = '';
                                            }
                                            ?>
                                            <?php
                                            $strDomainUrl = $_SERVER['HTTP_HOST'];
                                            $strShareLink = "https://www.facebook.com/sharer/sharer.php?u=" . $strDomainUrl;
                                            ?>
                                            <a href="#" onclick='window.open("<?php echo $strShareLink; ?>", "", "width=500, height=300");'>
                                                <?php echo $this->Html->image('fb-share-button.png', array('alt' => 'fbshare')); ?>
                                            </a>
                                            <span style="display: inline-block; margin: 0px 5px; vertical-align: text-top;">
                                                <a target="blank" href= "http://twitter.com/share?text=I saved <?php echo $result; ?> from <?php echo $_SESSION['storeName']; ?>&url=<?php echo $url; ?>&via=<?php echo $_SESSION['storeName']; ?>"><?php echo $this->Html->image('tw-share-button.png', array('alt' => 'twshare')); ?> </a>
                                            </span>
                                            <?php
                                            if (!empty($storeId)) {
                                                if ($orders['Order']['store_id'] == $storeId) {
                                                    echo $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-cart-plus')) . 'Order Now', 'javascript:void(0);', array('class' => 'reorder orderColor', 'name' => $this->Encryption->encode($orders['Order']['id']), 'escape' => false));
                                                    ?>
                                                    <span class="seprator-bar">|</span> <?php echo $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-trash-o')) . 'Delete', array('controller' => 'orders', 'action' => 'deleteSaveOrder', $encrypted_storeId, $encrypted_merchantId, $this->Encryption->encode($orders['Order']['id'])), array('confirm' => __('Are you sure you want to delete this saved order?'), 'class' => 'delete orderColor', 'escape' => false)); ?>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div class="order-history-detail-lt">
                                            <p> <span>Order Id: <?php echo $orders['Order']['order_number']; ?></span><span class="seprator-bar">|</span><span>Cost: $<?php echo $orders['Order']['amount'] - $orders['Order']['coupon_discount']; ?></span></p>
                                            <p> <?php
                                                if ($orders['Order']['seqment_id'] == 1) {
                                                    echo 'Dine-In';
                                                } elseif ($orders['Order']['seqment_id'] == 2) {
                                                    echo 'PickUp';
                                                } elseif ($orders['Order']['seqment_id'] == 3) {
                                                    echo $orders['DeliveryAddress']['name_on_bell'] . ', ' . $orders['DeliveryAddress']['address'] . ' ,' . $orders['DeliveryAddress']['city'];
                                                }
                                                ?></p>
                                        </div>
                                    </div>
                                    <div class="responsive-table">
                                        <table class="table table-striped order-history-table">
                                            <tr>
                                                <th style="width:30%;"><?php echo __('Items'); ?></th>
                                                <th style="width:15%;"><?php echo __('Size'); ?></th>
                                                <th style="width:20%;"><?php echo __('Preferences'); ?></th>
                                                <th style="width:15%;"><?php echo __('Add-ons'); ?></th>
                                                <th style="width:20%;"><?php echo __('Store'); ?></th>

                                                <?php if ($orders['Order']['order_status_id'] == 5 || $orders['Order']['order_status_id'] == 7) { ?>
                                                    <th></th>
                                                <?php } ?>
                                            </tr>
                                            <?php foreach ($orders['OrderItem'] as $order) { ?>
                                                <tr>
                                                    <td><?php
                                                        $Interval = "";
                                                        if (isset($order['interval_id'])) {
                                                            $intervalId = $order['interval_id'];
                                                            $Interval = $this->Common->getIntervalName($intervalId);
                                                        }

                                                        echo $order['quantity'] . 'X' . $order['Item']['name'];
                                                        echo ($Interval) ? "(" . $Interval . ")" : "";
                                                        ?><br>
                                                        <?php
                                                        if (!empty($order['OrderOffer'])) {
                                                            echo "<innerTag class='greyFont'>Offer Items :</innerTag>";
                                                            foreach ($order['OrderOffer'] as $offer) {
                                                                echo '<br/>' . $offer['quantity'] . 'X' . $offer['Item']['name'];
                                                            }
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php
                                                        if (!empty($order['Size'])) {
                                                            echo $order['Size']['size'];
                                                        } else {
                                                            echo ' - ';
                                                        }
                                                        ?></td>
                                                    <td><?php
                                                        if (!empty($order['OrderPreference'])) {
                                                            $preference = "";
                                                            $prefix = '';
                                                            foreach ($order['OrderPreference'] as $key => $opre) {
                                                                $preference .= $prefix . '' . $opre['SubPreference']['name'] . "";
                                                                $prefix = ', ';
                                                            }
                                                            echo $preference;
                                                        } else {
                                                            echo ' - ';
                                                        }
                                                        ?></td>
                                                    <td>
                                                        <?php
                                                        if (!empty($order['OrderTopping'])) {
                                                            $prefix = '';
                                                            foreach ($order['OrderTopping'] as $topping) {
                                                                echo $prefix . '' . $topping['Topping']['name'] . '';
                                                                $prefix = ', ';
                                                            }
                                                        } else {
                                                            echo ' - ';
                                                        }
                                                        ?> </td>
                                                    <td>
                                                        <?php
                                                        if (!empty($orders['Store'])) {
                                                            echo $orders['Store']['store_name'];
                                                        }
                                                        ?> </td>
                                                </tr><?php } ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="repeat-deatil">
                            <div class="responsive-table">
                                <table class="table table-striped order-history-table">
                                    <tr>
                                        <td><?php echo __("No orders have been saved yet"); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>             
                    <?php } ?>
                </div>
                <!-- /FROM VIEW -->
                <?php echo $this->element('pagination'); ?>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        $('.reorder').click(function () {
            var orderId = $(this).attr('name');
            window.location = "/Users/customerDashboard/<?php echo $encrypted_storeId; ?>/<?php echo $encrypted_merchantId; ?>/" + orderId;
        });

        $("#MerchantStoreId").change(function () {
            $("#AdminId").submit();
        });
    });
</script>