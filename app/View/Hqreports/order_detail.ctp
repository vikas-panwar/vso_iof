<?php
/*$arr[] = '';
if ($orderDetail['Order']['seqment_id'] == 2) {
    $arr = array_diff($statusList, array('Ready For Delivery', 'On the way', 'Delivered', 'Confirmed'));
}
if ($orderDetail['Order']['seqment_id'] == 3) {
    $arr = array_diff($statusList, array('Ready for Pick up', 'On the way', 'Confirmed', 'Picked Up'));
}
if ($orderDetail['Order']['seqment_id'] == 1) {
    $arr = $statusList;
}*/
?>



<?php
$arr[] = '';
if ($orderDetail['Order']['seqment_id'] == 2) {
    $arr = array_diff($statusList, array('Ready For Delivery', 'Delivered'));
}
if ($orderDetail['Order']['seqment_id'] == 3) {
    $arr = array_diff($statusList, array('Ready for Pick up', 'Picked Up'));
}
if ($orderDetail['Order']['seqment_id'] == 1) {
    $arr = $statusList;
}

$total_amount = number_format(($orderDetail['Order']['amount']), 2);
$devlInfo = $orderDetail['DeliveryAddress'];
$orderInfo = $orderDetail['Order'];
$userInfo = $orderDetail['User'];

if ($orderInfo['user_id'] == 0) {
    $enduser_name = $devlInfo['name_on_bell'];
    $enduser_phone = $devlInfo['phone'];
} else {
    $enduser_name = $userInfo['fname'] . ' ' . $userInfo['lname'];
    $enduser_phone = $userInfo['phone'];
}
$address = $devlInfo['address'] . ' ' . $devlInfo['city'];
$pickup_time = '';
$pickup_time = $this->Hq->storeTimeFormate($this->Hq->storeTimezone(null, $orderInfo['pickup_time'], null, $orderInfo['store_id']), true, $orderInfo['store_id']);
if ($orderInfo['seqment_id'] == 2) {
    $address = "Pickup";
    $address = "</br>Address : " . $address;
    $pickup_time = "| Pickup Time:&nbsp;&nbsp;" . $pickup_time;
    $orderType = "Pickup";
} else {
    $pickup_time = "| Delivery Time:&nbsp;&nbsp;" . $pickup_time;
    $address = "</br>Address : " . $address;
    $orderType = "Delivery";
}
if ($orderDetail['OrderPayment']['payment_gateway'] == 'COD') {
    if ($orderDetail['Order']['seqment_id'] == 3) {
        $paymentStatus = "UNPAID";
    } else {
        $paymentStatus = "UNPAID";
    }
} else {
    $paymentStatus = "PAID";
}
$paymentStatus = $paymentStatus . '-' . $orderDetail['OrderPayment']['payment_gateway'];
$created_time = $this->Hq->storeTimeFormate($this->Hq->storeTimezone(null, $orderInfo['created'], null, $orderInfo['store_id']), true, $orderInfo['store_id']);
//$created_time = $this->Common->storeTimeFormate($this->Hq->storeTimezone(null, $orderInfo['created'], null, $orderInfo['store_id']),true); 
$orderAttr = array('enduser_name' => $enduser_name, 'enduser_phone' => $enduser_phone, 'address' => $address, 'orderType' => $orderType, 'paymentStatus' => $paymentStatus, 'created_time' => $created_time, 'pickup_time' => $pickup_time);
?>

<style>
    .new-chkbx-wrap { float:left;padding:5px;width:30%;margin-bottom:10px;}
    .new-chkbx-wrap > input {
        float: left;
        margin-right: 5px;
        position: relative;
        top: -3px;
    }
</style>
<div class="row">
    <div class="col-lg-11">
        <h3>Order Details</h3>
        <br>
        <?php echo $this->Session->flash(); ?> 


        <?php echo $this->Form->input('Orders.id', array('type' => 'hidden', 'value' => $orderDetail['Order']['id'])); ?>
        
        
        
        <?php
        echo $this->element('hqreportorderdetail/order_detail_head');

        echo $this->element('hqreportorderdetail/order_detail', array('total_amount' => $total_amount, 'arr' => $arr, 'orderDetail' => $orderDetail, 'orderAttr' => $orderAttr));
        if (!in_array($orderDetail['Order']['order_status_id'], array('5', '7', '9'))) {
            echo $this->element('hqreportorderdetail/order_detail_bottom', array('total_amount' => $total_amount, 'arr' => $arr, 'orderDetail' => $orderDetail));
        }
        ?>
        
        
        <!--

        <table class="table tablesorter">
            <thead>
                <tr>
                    <th class="th_checkbox" colspan="5" style="text-align:left;">
                        Order Id  : <?php echo $orderDetail['Order']['order_number']; ?> | Cost :  $<?php
                        if ($orderDetail['Order']['coupon_discount'] > 0) {
                            $total_amount = $orderDetail['Order']['amount'];
                            echo number_format($total_amount, 2);
                        } else {

                            echo $total_amount = number_format($orderDetail['Order']['amount'], 2);
                        }
                        ?>
                        <?php
                        if ($orderDetail['Order']['tax_price']) {
                            echo "| Tax :$" . $orderDetail['Order']['tax_price'];
                        }
                        ?>
                        | Status : <?php
                        $data = $this->requestAction('/hqreports/ajaxRequest/' . $orderDetail['Order']['order_status_id'] . '');

                        echo $data;
                        ?>
                        <br>
                        Address : <?php
                        echo $orderDetail['DeliveryAddress']['address'] . " " . $orderDetail['DeliveryAddress']['city'];
                        if ($orderDetail['Segment']['id'] == 2) {
                            echo $orderDetail['Segment']['name'];
                        }
                        ?>
                        <br>
                        Order Type : <?php echo $orderDetail['Segment']['name']; ?>  |  Created : &nbsp;&nbsp;<?php echo $this->Hq->storeTimeFormate($this->Hq->storeTimezone(null, $orderDetail['Order']['created'], null, $orderDetail['Order']['store_id']), true, $orderDetail['Order']['store_id']); ?> |  Delivery Time : &nbsp;&nbsp;<?php echo $this->Hq->storeTimeFormate($orderDetail['Order']['pickup_time'], true, $orderDetail['Order']['store_id']); ?>

                    </th>
                </tr>
            </thead>
        </table>

        <table class="table table-bordered table-hover table-striped tablesorter">


            <thead>
                <tr>	    
                    <th  class="th_checkbox">Item</th>
                    <th  class="th_checkbox">Size</th>
                    <th  class="th_checkbox">Preference</th>
                    <th  class="th_checkbox">Add-ons</th>
                    <th  class="th_checkbox">Price ($)</th>
                    <th  class="th_checkbox">Tax ($)</th>

                </tr>
            </thead>

            <tbody class="dyntable">
                <?php
                $i = 0;
                $totalItemPrice = 0.00;
                foreach ($orderDetail['OrderItem'] as $key => $item) {
                    $class = ($i % 2 == 0) ? ' class="active"' : '';
                    ?>
                    <tr >	    
                        <td>

                            <?php echo $item['quantity']; ?> x <?php echo $item['Item']['category']['name'] . '-' . $item['Item']['name']; ?>
                            <?php
                            if (isset($item['OrderOffer'])) {
                                echo "<br>";
                                foreach ($item['OrderOffer'] as $j => $offer) {
                                    $offeroitem = "&nbsp;&nbsp;";
                                    if (isset($offer['quantity'])) {
                                        $offeroitem.=$offer['quantity'];
                                    }
                                    if (isset($offer['Size']['size'])) {
                                        $offeroitem.="x " . $offer['Size']['size'];
                                    }
                                    if ($offer['Item']['name']) {
                                        $offeroitem.=" " . $offer['Item']['name'] . "<br>";
                                    }

                                    echo $offeroitem;
                                }
                            }
                            ?>
                        </td>

                        <td>
                            <?php echo ($item['Size']) ? $item['Size']['size'] : "-"; ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($item['OrderPreference'])) {
                                $preference = "";
                                $prefix = '';
                                foreach ($item['OrderPreference'] as $key => $opre) {
                                    $preference .= $prefix . '' . $opre['SubPreference']['name'] . "";
                                    $prefix = ', ';
                                }
                                echo $preference;
                            } else {
                                echo ' - ';
                            }
                            //echo ($item['Type'])?$item['Type']['name']:"-";
                            ?>	
                        </td>


                        <td style="width: 300px; word-wrap: break-word; word-break: break-all;">
                            <?php
                            $Toppings = '';
                            if ($item['OrderTopping']) {
                                $Toppings = array();
                                foreach ($item['OrderTopping'] as $vkey => $toppingdetails) {
                                    if (isset($toppingdetails['Topping']['name'])) {
                                        $Toppings[] = $toppingdetails['Topping']['name'];
                                    }
                                }
                            }
                            if ($Toppings) {
                                $alltoppings = implode(',', $Toppings);
                                echo wordwrap($alltoppings, 5, "<br>\n");
                            } else {
                                echo "-";
                            }
                            ?>	
                        </td>

                        <td>
                            <?php
                            echo ($item['total_item_price']) ? $item['total_item_price'] : "-";
                            if ($item['total_item_price']) {
                                $totalItemPrice = $totalItemPrice + $item['total_item_price'];
                            }
                            ?>
                        </td>
                        <td>
                            <?php echo ($item['tax_price']) ? (number_format($item['quantity'] * $item['tax_price'], 2)) : "-"; ?>
                        </td>
                    </tr>
                    <?php $i++;
                }
                ?>

                <tr class="table-net-tr">

                    <td class="table-net-td" colspan="4"> 
                        <?php
                        $orderComment = '';
                        if (!empty($orderDetail['Order']['order_comments'])) {
                            $orderComment = $orderDetail['Order']['order_comments'];
                        }
                        echo "<b>Special comment:</b> " . $orderComment;
                        ?>
                    </td>
<!--			<td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>-->
                    <!--<td class="net-record-font-size"><?php echo number_format($totalItemPrice, 2); ?></td>
                    <td class="net-record-font-size"><?php echo number_format($orderDetail['Order']['tax_price'], 2); ?></td>
                </tr>
                <tr class="table-net-tr">
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="net-record-font-size">Sub-Total:</td>
                    <td class="net-record-font-size"><?php echo number_format($totalItemPrice + $orderDetail['Order']['tax_price'], 2); ?></td>
                </tr> 
                <tr class="table-net-tr">
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="net-record-font-size">Coupon Discount:</td>
                    <td class="net-record-font-size" style="color:#ff1a1a;"><?php echo ($orderDetail['Order']['coupon_discount']) ? "-" . number_format($orderDetail['Order']['coupon_discount'], 2) : '-'; ?></td>
                </tr>

                <tr class="table-net-tr">
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="net-record-font-size">Service Fee:</td>
                    <td class="net-record-font-size"><?php echo ($orderDetail['Order']['service_amount']) ? number_format($orderDetail['Order']['service_amount'], 2) : '-'; ?></td>
                </tr>
<?php if ($orderDetail['Segment']['id'] != 2) { ?>
                    <tr class="table-net-tr">
                        <td class="table-net-td">&nbsp;</td>
                        <td class="table-net-td">&nbsp;</td>
                        <td class="table-net-td">&nbsp;</td>
                        <td class="table-net-td">&nbsp;</td>
                        <td class="net-record-font-size">Delivery Fee:</td>
                        <td class="net-record-font-size"><?php echo ($orderDetail['Order']['delivery_amount']) ? number_format($orderDetail['Order']['delivery_amount'], 2) : '-'; ?></td>
                    </tr>
<?php } ?>
                <tr class="table-net-tr">
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="table-net-td">&nbsp;</td>
                    <td class="net-record-font-size">Total:</td>
                    <td class="net-record-font-size"><?php echo number_format($total_amount, 2); ?></td>
                </tr>

            </tbody>
        </table>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <div class="row">

        </div>
        <table>
            <tr>

                <td colspan="6">                       




                    <?php //echo $this->Form->button('Update Status', array('type' => 'submit','class' => 'btn btn-default'));            
                    ?>                     
                    <?php //echo $this->Html->link('Cancel', "/orders/index/", array("class" => "btn btn-default",'escape' => false));  ?>
<?php //echo $this->Html->link('Print', array('controller'=>'orders','action'=>'PrintReceipt',$this->Encryption->encode($orderDetail['Order']['id'])), array("class" => "btn btn-default",'escape' => false));   ?>


                </td>

            </tr>
        </table>   
        -->
    </div>
</div>
<?php echo $this->Html->css('pagination'); ?>