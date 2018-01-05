<style>
    .ordr-bx-pos{ padding-right: 0px;}    
    .modal-dialog .orderTypePickUp, .modal-dialog .orderTypeDelivery{width:70%;}
    .modal-dialog label {
        color: #737373;
        font-size: 16px;
        font-weight: 400;
    }
    #orderTypeID > p{padding-top: 34px;}
</style>
<?php
//date time div start
$PreorderAllowed = $this->Common->checkPreorder();
/*
  PreorderAllowed - 0 (Preorder not allow), 1 (Allowed) - Flag Based
  nowAvail - 0 (current date Black out), 1 (Current day available)- Date based
  setPre - 0 (Now is avalable), 1 (Preorder is available) - Time based
  close day  - array based on holidays dates
 */
if (!empty($PreorderAllowed) && !empty($nowAvail)) {
    //echo "Both are avalibale Show calendar";
    ?>
    <li class='orderTypePickUp ordr-bx-pos'>

        <div style="float:left">
            <span class="title"><label>Pick Up Date <em>*</em></label></span>    
            <?php
            echo $this->Form->input('orderType.pick_up_date', array('type' => 'text', 'class' => 'inbox date-select', 'placeholder' => 'Date', 'label' => false, 'div' => false, 'required' => true, 'readOnly' => true));
            echo $this->Form->error('orderType.pick_up_date');
            ?>
        </div>

        <div id="resvTime">

        </div>

    </li>  
    <?php
} elseif (!empty($PreorderAllowed) && empty($nowAvail)) {
    //echo "Only Preorder allowed Show calendar";
    ?>
    <li class='orderTypePickUp ordr-bx-pos'>

        <div style="float:left">
            <span class="title"><label>Pick Up Date <em>*</em></label></span>    
            <?php
            echo $this->Form->input('orderType.pick_up_date', array('type' => 'text', 'class' => 'inbox date-select', 'placeholder' => 'Date', 'label' => false, 'div' => false, 'required' => true, 'readOnly' => true));
            echo $this->Form->error('orderType.pick_up_date');
            ?>
        </div>

        <div id="resvTime">

        </div>

    </li> 
    <?php
} elseif (empty($PreorderAllowed) && !empty($nowAvail) && empty($setPre)) {
    //echo "Only Now allowed not to Show calendar";
    echo '<p>';
    echo (!empty($nowData['pickup_date_time'])) ? 'Order Time : ' . $nowData['pickup_date_time'] : '';
    echo '</p>';
    ?>

    <?php
} else {
    //echo "None is available";
    ?>
    <section class="form-layout" style="width:100%;padding:10px;">
        <span class="closeStore">Store is closed</span>
    </section>
    <?php
}
//date time div end
?>
<script>

    function getTime(date, orderType, preOrder, returnspan, ortype) {
        if (date) {
            var type1 = 'Store';
            var type2 = 'pickup_time';
            var type3 = ortype;
            var storeId = '<?php echo $encrypted_storeId; ?>';
            var merchantId = '<?php echo $encrypted_merchantId; ?>';
            $.ajax({
                url: "<?php echo $this->Html->url(array('controller' => 'Users', 'action' => 'getStoreTime')); ?>",
                type: "Post",
                dataType: 'html',
                data: {storeId: storeId, merchantId: merchantId, date: date, type1: type1, type2: type2, type3: type3, orderType: orderType, preOrder: preOrder},
                success: function (result) {
                    $('#' + returnspan).html(result);
                }
            });
        }
    }


    $('#orderTypePickUpDate').on('change', function () {
        var date = $(this).val();
        var orderType = 2; // 3= Take-away/pick-up
        var preOrder = '1';
        var type1 = 'Store';
        var type2 = 'pickup_time';
        var type3 = 'StorePickupTime';
        var storeId = '<?php echo $encrypted_storeId; ?>';
        var merchantId = '<?php echo $encrypted_merchantId; ?>';
        $.ajax({
            url: "<?php echo $this->Html->url(array('controller' => 'Users', 'action' => 'getStoreTime')); ?>",
            type: "Post",
            dataType: 'html',
            data: {storeId: storeId, merchantId: merchantId, date: date, type1: type1, type2: type2, type3: type3, orderType: orderType, preOrder: preOrder},
            success: function (result) {
                $('#resvTime').html(result);
            }
        });
    });

</script>


<script>

//Pickup Date Scripts  -start   
    $('#orderTypePickUpDate').datepicker({
        dateFormat: 'mm-dd-yy',
        minDate: '<?php echo $finaldata['pickcurrentDateVar']; ?>',
        maxDate: '<?php echo $finaldata['pickupmaxdate']; ?>',
        beforeShowDay: function (date) {
            var day = date.getDay();
            var array = '<?php echo json_encode($closedDay); ?>';
            var finarr = $.parseJSON(array);
            var arr = [];
            for (elem in finarr) {
                arr.push(finarr[elem]);
            }
            return [arr.indexOf(day) == -1];
        }
    });
    $("#orderTypePickUpDate").datepicker("setDate", '<?php echo $finaldata['pickcurrentDateVar']; ?>');
    var date = '<?php echo $finaldata['pickcurrentDateVar']; ?>';
    getTime(date, 2, 1, 'resvTime', 'orderTypePickUpDate', true);


    $('#orderTypePickUpDate').on('change', function () {
        var date = $(this).val();
        var orderType = 2; // 3= Take-away/pick-up
        var preOrder = 1;
        getTime(date, orderType, preOrder, 'resvTime', 'orderTypePickUpDate');
    });
//Pickup Date Scripts  end

</script>
