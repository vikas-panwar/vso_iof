<style>
    .chole-main .pay-check{
        display:block;
    }
</style>
<?php
$osDetail = $this->Session->read('ordersummary');
if (!empty($osDetail['pickup_date'])) {
    ?>
    <input type="hidden" id="osPickupDate" value="<?php echo @$osDetail['pickup_date']; ?>"/>
    <input type="hidden" id="osPickupHour" value="<?php echo @$osDetail['pickup_hour']; ?>"/>
    <input type="hidden" id="osPickupMinute" value="<?php echo @$osDetail['pickup_minute']; ?>"/>
    <input type="hidden" id="osOrderType" value="<?php echo @$osDetail['order_type']; ?>"/>
    <input type="hidden" id="osPreOrderType" value="<?php echo (!empty($osDetail['preorder_type'])) ? $osDetail['preorder_type'] : ''; ?>"/>
<?php } ?>
<?php //$PreorderAllowed = $this->Common->checkPreorder(); ?>
<div class="panel panel-default">
    <div class="panel-heading active">
        <h4 class="panel-title">
            <a data-toggle="collapse" data-parent="#accordion1" href="#collapseTwo">
                SELECT ORDER TYPE
                <span class="arrow-down">
                    <i class="indicator fa fa-angle-up fa-2x" aria-hidden="true"></i>
                </span>
            </a>
        </h4>
    </div>
    <?php
    $guestUserDetail = $this->Session->check('GuestUser');
    $userId = AuthComponent::User('id');
    if (!empty($userId) || !empty($guestUserDetail)) {
        ?>
        <?php
        echo $this->Form->create('Order', array('id' => 'OTDetail'));
        ?>
        <div id="collapseTwo" class="panel-collapse collapse in">
            <div class="panel-body nested-ac">
                <div class=" pay-wrap select-order clearfix">
                    <div class="pay-check">
                        <?php if ($store_data_app['Store']['is_delivery'] == 1) { ?>
                            <span>
                                <input type="checkbox" id="test1" name="data[Order][type]" value="3" class="order_type">
                                <label for="test1">Delivery</label>
                            </span>
                        <?php } if ($store_data_app['Store']['is_take_away'] == 1) { ?>
                            <span>
                                <input type="checkbox" id="test2" name="data[Order][type]" value="2" class="order_type">
                                <label for="test2">Pick-up</label>
                            </span>
                            <?php
                        }
                        ?>
                    </div>

                    <div id="getOrderType">

                    </div>
                    <?php
                    if (!empty($userId) || !empty($guestUserDetail)) {
                        echo $this->element('orderoverview/pickup_address');
                    }
                    ?> 
                    <div class="hidden" id="deliveryAddress">
                        <?php
                        if (!empty($userId)) {
                            echo $this->element('orderoverview/delivery_address');
                        } elseif (!empty($guestUserDetail)) {
                            echo $this->element('orderoverview/delivery_address_guest');
                        }
                        ?>
                    </div>
                    <div>
                        <?php
                        echo $this->Form->button('Save', array('type' => 'button', 'class' => 'saveOrderType cont-btn btn btn-info theme-bg-1 hidden'));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo $this->Form->end(); ?>
    <?php } ?>
</div>
<script>
    function refreshCart() {
        $.ajax({
            type: 'post',
            url: '/Products/refreshCart',
            async: false,
            success: function (result) {
                if (result) {
                    $('#ordercart').html(result);
                }
            }
        });
    }
    function checkDeliverType() {
        $.ajax({
            url: "<?php echo $this->Html->url(array('controller' => 'orderOverviews', 'action' => 'checkDeliverType')); ?>",
            type: "Post",
            beforeSend: function () {
                $.blockUI({css: {
                        border: 'none',
                        padding: '15px',
                        backgroundColor: '#000',
                        '-webkit-border-radius': '10px',
                        '-moz-border-radius': '10px',
                        opacity: .5,
                        color: '#fff'
                    }});
            },
            success: function (result) {
                if (result) {
                    $('#checkDeliverType').html(result);
                }
            },
            complete: function () {
                $.unblockUI();
            }
        });
    }
    $(document).ready(function () {

        $("#OTDetail").validate({
            debug: false,
            errorClass: "error",
            errorElement: 'span',
            onkeyup: false,
            rules: {
                'data[Order][type]': {
                    required: true,
                },
                'data[pickup][type]': {
                    required: true,
                },
                'data[Store][pickup_date]': {
                    required: true
                },
                "data[Store][pickup_minute]": {
                    required: true,
                },
                "data[Store][pickup_hour]": {
                    required: true,
                },
                "data[DeliveryAddress][id]": {
                    required: true,
                },
                "data[DeliveryAddress][address]": {
                    required: true,
                    minlength: 2,
                    maxlength: 50
                },
                "data[DeliveryAddress][city]": {
                    required: true,
                    lettersonly: true
                },
                "data[DeliveryAddress][state]": {
                    required: true,
                    lettersonly: true,
                },
                "data[DeliveryAddress][zipcode]": {
                    required: true,
                    number: true,
                    minlength: 5,
                    maxlength: 5
                }
            },
            messages: {
            }, highlight: function (element, errorClass) {
                $(element).removeClass(errorClass);
            }
        });
        $(document).on('click', '.saveOrderType', function (e) {
            e.stopImmediatePropagation();
            if ($("#OTDetail").valid()) {
                if ($("#now").is(":checked")) {
                    $("#StorePickuphour").val($("#StorePickuphour option:first").val());
                    $("#StorePickupmin").val($("#StorePickupmin option:first").val());
                }
                if ($('.pay-check').find('#test1').prop("checked")) {
<?php if (!empty($userId)) { ?>
                        var addressId = $('input[name="data[DeliveryAddress][id]"]').val();
                        if (addressId == '' || addressId == undefined) {
                            alert('Please add address.');
                            return false;
                        }
<?php } ?>
<?php if (!empty($userId)) { ?>
                        var checkAddress = $('input[name="data[DeliveryAddress][id]"]').is(":checked");
                        console.log(checkAddress);
                        if (!checkAddress) {
                            alert('Please select delivery address.');
                            return false;
                        }
<?php } ?>
                }
                var formData = $("#OTDetail").serialize();
                $.ajax({
                    type: 'post',
                    url: "<?php echo $this->Html->url(array('controller' => 'orderOverviews', 'action' => 'ajaxOrderDetailSave')); ?>",
                    data: {'formData': formData},
                    beforeSend: function () {
                        $.blockUI({css: {
                                border: 'none',
                                padding: '15px',
                                backgroundColor: '#000',
                                '-webkit-border-radius': '10px',
                                '-moz-border-radius': '10px',
                                opacity: .5,
                                color: '#fff'
                            }});
                    },
                    complete: function () {
                        $.unblockUI();
                    },
                    success: function (successResult) {
                        try {
                            data = JSON.parse(successResult);
                            $("#errorPop").modal('show');
                            $("#errorPopMsg").html(data.msg);
                        } catch (e) {
                            $('#collapseTwo').html(successResult);
                            $('#collapseThree').collapse('show');
                            $(".theme-color-1").prop('disabled', false);
                            refreshCart();
                            checkDeliverType();
                        }
                    }
                });

            }
            e.preventDefault();
        });

        $(document).on('change', '.order_type', function (e) {
            e.stopImmediatePropagation();
            var orderType = $(this).val();
            if (orderType && $(".order_type").is(':checked')) {
                $(".saveOrderType").removeClass("hidden");
                if (orderType == 3) {
                    $("#deliveryAddress").removeClass('hidden');
                    $(".store-contact-info-ele").addClass('hidden');
                    //check address in zone or not
                    var deliveryAddressId = $("input[type='radio'][class='deladdress']:checked").val();
                    if (deliveryAddressId) {

                        $.ajax({
                            type: 'post',
                            url: "<?php echo $this->Html->url(array('controller' => 'orderOverviews', 'action' => 'checkAddressInZone')); ?>",
                            async: false,
                            data: {'deliveryAddressId': deliveryAddressId},
                            success: function (response) {
                                if (response) {
                                    result = jQuery.parseJSON(response);
                                    if (result.status == 'Error') {
                                        $("#errorPop").modal('show');
                                        $("#errorPopMsg").html(result.msg);
                                        checkDeliverType();
                                        return false;
                                    }
                                }
                                checkDeliverType();
                            }
                        });

                    }
                } else {
                    $("#deliveryAddress").addClass('hidden');
                    $(".store-contact-info-ele").removeClass('hidden');
                }
                $.ajax({
                    url: "<?php echo $this->Html->url(array('controller' => 'orderOverviews', 'action' => 'getDateTime')); ?>",
                    type: "Post",
                    data: {orderType: orderType},
                    beforeSend: function () {
                        $.blockUI({css: {
                                border: 'none',
                                padding: '15px',
                                backgroundColor: '#000',
                                '-webkit-border-radius': '10px',
                                '-moz-border-radius': '10px',
                                opacity: .5,
                                color: '#fff'
                            }});
                    },
                    success: function (result) {
                        if (result) {
                            $('#getOrderType').html(result);
                        }
                    },
                    complete: function () {
                        checkDeliverType();
                        $.unblockUI();
                    }
                });
            } else {
                $('#getOrderType').html('');
                $(".store-contact-info-ele").addClass('hidden');
            }

            if (this.checked) {
                $('.order_type').not((this)).prop('checked', false);
            } else {
                $("#deliveryAddress").addClass('hidden');
            }
        });

        // To Show
        $(document).on('click', '#pre-order', function (e) {
            $('.pay-date').removeClass("hidden");
        });

        // To hide
        $(document).on('click', '#now', function (e) {
            $('.pay-date').addClass("hidden");
        });


        if ($('#now').is(':checked')) {
            $('.pay-date').addClass("hidden");
        }

        if (!$(".order_type").is(':checked')) {
            $(".saveOrderType").addClass("hidden");
        }
        var test = $("#osOrderType").val();
        if (test == 3) {
            $("#test1").trigger('click');
            $(".store-contact-info-ele").addClass('hidden');
        } else if (test == 2) {
            $("#test2").trigger('click');
            $(".store-contact-info-ele").removeClass('hidden');
        }
    });
</script>
