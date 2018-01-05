<!-- static-banner -->
<?php echo $this->element('hquser/static_banner'); ?>
<!-- /banner -->
<!--- SIGN UP FORM-->
<div class="signup-form">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="common-title clearfix">
                    <span class="yello-dash"></span>
                    <h2>Sign Up</h2>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-sm-12">
                <div class="account-info pull-right">
                    <p>Already have an account?<span class="ask-login">Login</span></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $this->Session->flash(); ?>
                <div class="form-bg">
                    <?php
                    echo $this->Form->create('User', array('inputDefaults' => array('autocomplete' => 'off'), 'id' => 'MerchantRegistration', 'class' => 'sign-up'));
                    ?>
                    <div class="main-form clearfix">
                        <div class="form-group">
                            <div class="left-tile">
                                <label>First Name <em>*</em></label>
                            </div>
                            <div class="rgt-box">
                                <?php
                                echo $this->Form->input('User.fname', array('type' => 'text', 'class' => 'form-control custom-text', 'placeholder' => 'Enter Your First Name', 'maxlength' => '20', 'label' => false, 'div' => false, 'required' => true));
                                echo $this->Form->error('User.fname');
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="left-tile">
                                <label>Last Name<em>*</em></label>
                            </div>
                            <div class="rgt-box">
                                <?php
                                echo $this->Form->input('User.lname', array('type' => 'text', 'class' => 'form-control custom-text', 'placeholder' => 'Enter Your Last Name', 'maxlength' => '20', 'label' => false, 'div' => false));
                                echo $this->Form->error('User.lname');
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="left-tile">
                                <label>Email <em>*</em></label>
                            </div>
                            <div class="rgt-box">
                                <?php
                                echo $this->Form->input('User.email', array('id' => 'oldemail', 'type' => 'text', 'class' => 'form-control custom-text', 'placeholder' => 'Enter Your Email', 'maxlength' => '50', 'label' => false, 'div' => false, 'required' => true, 'autocomplete' => 'off'));
                                echo $this->Form->error('User.email');
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="left-tile">
                                <label>Password <em>*</em></label>
                            </div>
                            <div class="rgt-box">
                                <?php
                                echo $this->Form->input('User.password', array('type' => 'password', 'class' => 'form-control custom-text', 'placeholder' => 'Create Your Password', 'maxlength' => '20', 'label' => false, 'div' => false, 'required' => true, 'id' => 'signup_password', 'autocomplete' => 'off'));
                                echo $this->Form->error('User.password');
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="left-tile">
                                <label>Confirm Password <em>*</em></label>
                            </div>
                            <div class="rgt-box">
                                <?php
                                echo $this->Form->input('User.password_match', array('type' => 'password', 'class' => 'form-control custom-text', 'placeholder' => 'Confirm Your Password', 'maxlength' => '20', 'label' => false, 'div' => false));
                                echo $this->Form->error('User.password_match');
                                ?>
                            </div>
                        </div>
                        <div class="form-group twin-block">
                            <div class="left-tile">
                                <label>Mobile Phone <em>*</em></label>
                            </div>
                            <div class="rgt-box">
                                <?php echo $this->Form->input('User.country_code_id', array('type' => 'select', 'options' => $countryCode, 'class' => 'spin-count custom-text country-code', 'label' => false, 'div' => false)); ?>
                                <div class="phone-input"> <?php
                                    echo $this->Form->input('User.phone', array('data-mask' => 'mobileNo', 'type' => 'text', 'class' => 'form-control custom-text phone', 'placeholder' => 'Mobile Phone ( 111-111-1111)', 'label' => false, 'div' => false, 'required' => true));
                                    echo $this->Form->error('User.phone');
                                    ?> </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="left-tile">
                                <label>Date of Birth <em>*</em></label>
                            </div>
                            <div class="rgt-box city-sel">
                                <?php echo $this->Form->input('User.dateOfBirth', array('type' => 'text', 'class' => 'form-control custom-text', 'placeholder' => 'Enter Your Date of Birth Ex:(mm-dd-yyyy)', 'maxlength' => '12', 'label' => false, 'div' => false, 'required' => true)); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="left-tile">
                                <label>City <em>*</em></label>
                            </div>
                            <div class="rgt-box city-sel">
                                <?php echo $this->Form->input('User.city_id', array('type' => 'text', 'class' => 'form-control custom-text', 'label' => false, 'div' => false, 'placeholder' => 'Enter City')); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="left-tile">
                                <label>State <em>*</em></label>
                            </div>
                            <div class="rgt-box state-sel">
                                <?php echo $this->Form->input('User.state_id', array('type' => 'text', 'class' => 'form-control custom-text', 'label' => false, 'div' => false, 'placeholder' => "Select State")); ?> 
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="left-tile">
                                <label>Zip <em>*</em></label>
                            </div>
                            <div class="rgt-box zip-sel">
                                <?php echo $this->Form->input('User.zip_id', array('type' => 'text', 'class' => 'form-control custom-text', 'label' => false, 'div' => false, 'placeholder' => 'Enter Zip', 'maxlength' => '5')); ?>
                            </div>
                        </div>
                        <div class="tnc">
                            <span>
                                <span class="custom-checkbox">
                                    <input type="checkbox" id="privacy_policy" name="data[User][is_privacypolicy]" checked="checked" />
                                    <label for="privacy_policy"><p>I agree to our <span class="bold-text termAndPolicy" data-name="Term"><?php echo $this->Html->link('Terms and Conditions', array('controller' => 'hqusers', 'action' => 'termsPolicy'), array('escape' => false, 'alt' => 'Logo', 'class' => 'termAndPolicy')); ?></span> &amp;
                                            <span class="bold-text termAndPolicy" data-name="Policy">	<?php echo $this->Html->link('Privacy Policy', array('controller' => 'hqusers', 'action' => 'privacyPolicy'), array('escape' => false, 'alt' => 'Logo', 'class' => 'termAndPolicy'));
                                ?></span></p></label>
                                </span>
                                <br/>
                                <label id="data[User][is_privacypolicy]-error" class="error" for="data[User][is_privacypolicy]"></label>
                            </span>

                        </div>
                        <div class="submit-btn">
                            <?php echo $this->Form->button('SUBMIT', array('type' => 'submit', 'class' => 'btn common-config black-bg')); ?>
                        </div>
                    </div>

                    <?php echo $this->Form->end(); ?>
                    <div class="ext-border">
                        <?php echo $this->Html->image('hq/thick-border.png', array('alt' => 'user')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- -->
<script>
    $(document).ready(function () {
        $('html, body').animate({
            scrollTop: $(".signup-form").offset().top
        }, 2000);
        $(".phone").keypress(function (e) {
            //if the letter is not digit then display error and don't type anything
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                return false;
            }
        });

        jQuery.validator.addMethod("passw", function (pass, element) {
            pass = pass.replace(/\s+/g, "");
            return this.optional(element) || pass.length > 7 &&
                    pass.match(/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[A-Za-z\d$@$!%*#?& ]{8,}$/);
        }, "Atleast one digit, one upper and one lower case letter");

        jQuery.validator.addMethod("lettersonly", function (value, element)
        {
            return this.optional(element) || /^[a-z," "]+$/i.test(value);
        }, "Letters and spaces only please");
        jQuery.validator.addMethod("DOB",
                function (value, element) {
                    var d = new Date();
                    var curr_date = d.getDate();
                    var curr_month = d.getMonth() + 1;
                    var curr_year = d.getFullYear();
                    d.setFullYear(curr_year, (curr_month - 1), curr_date);
                    var today = curr_month + '-' + curr_date + '-' + curr_year;
                    var firstValue = value.split('-');
                    var firstDate = new Date();
                    firstDate.setFullYear(firstValue[2], (firstValue[0] - 1), firstValue[1]);
                    if (firstDate > d) {
                        return false;
                    } else {
                        return true;
                    }
                },
                "Please enter a date in the format!"
                );
        jQuery.validator.addMethod("DOBFORMAT",
                function (value, element) {
                    return value.match(/^(0[1-9]|1[0-2])\-(0[1-9]|1\d|2\d|3[01])\-(19|20)\d{2}$/);
                },
                "Please enter a date in the format!"
                );

        $('.date_select').datepicker({
            dateFormat: 'mm-dd-yy',
            changeMonth: true,
            changeYear: true,
            yearRange: '1950:2017'
        });
        $("#MerchantRegistration").validate({
            rules: {
                "data[User][fname]": {
                    required: true,
                    lettersonly: true
                },
                "data[User][lname]": {
                    required: true,
                    lettersonly: true
                },
                "data[User][email]": {
                    required: true,
                    email: true,
                    remote: "/hqusers/checkHqEndUserEmail"
                },
                "data[User][password]": {
                    required: true,
                    minlength: 8,
                    maxlength: 20,
                    passw: true
                },
                "data[User][password_match]": {
                    required: true,
                    equalTo: "#signup_password"
                },
                "data[User][phone]": {
                    required: true
                }, "data[User][dateOfBirth]": {
                    required: true,
                    DOBFORMAT: true,
                    DOB: true,
                }, "data[User][is_privacypolicy]": {
                    required: true
                }, "data[User][state_id]": {
                    required: true,
                    lettersonly: true
                }, "data[User][city_id]": {
                    required: true,
                    lettersonly: true
                }, "data[User][zip_id]": {
                    required: true,
                    number: true,
                    minlength: 5,
                    maxlength: 5
                }
            },
            messages: {
                "data[User][fname]": {
                    required: "Please enter your first name.",
                    lettersonly: "Only alphabates are allowed."
                },
                "data[User][lname]": {
                    required: "Please enter your last name.",
                    lettersonly: "Only alphabates are allowed."
                },
                "data[User][email]": {
                    required: "Please enter your email.",
                    email: "Please enter valid email.",
                    remote: "Email already exists."
                },
                "data[User][password]": {
                    required: "Please enter your password.",
                    minlength: "Password must be at least 8 characters.",
                    maxlength: "Please enter no more than 20 characters.",
                    passw: "Atleast one digit, one upper and one lower case letter."
                },
                "data[User][password_match]": {
                    required: "Please enter your password again.",
                    equalTo: "Password not matched."
                },
                "data[User][phone]": {
                    required: "Contact number required."
                },
                "data[User][dateOfBirth]": {
                    required: "Please enter your DOB.",
                    DOBFORMAT: "Please enter a date in the format.",
                    DOB: "Please enter a valid DOB.",
                },
                "data[User][is_privacypolicy]": {
                    required: "Please agree to our Terms and Conditions & Privacy Policy."
                }, "data[User][state_id]": {
                    required: "Please select State.",
                    lettersonly: "Only alphabates are allowed."
                }, "data[User][city_id]": {
                    required: "Please enter City.",
                    lettersonly: "Only alphabates are allowed."
                }, "data[User][zip_id]": {
                    required: "Please enter Zipcode.",
                    number: "Only numbers are allowed."
                },
            }
        });
        $("[data-mask='mobileNo']").mask("(999) 999-9999");
        $('#UserDateOfBirth').mask('99-99-9999');
    });
    $(function () {
        $("#UserFname").focus();
        $(".account-info").click(function () {
            $(".login-pop").slideToggle("shop-popup");
            $('html,body').animate({
                scrollTop: $('#log-in-pop').offset().top - 1000
            }, 'fast');
        });
    });
    $(document).ready(function () {
        $("#UserStateId").autocomplete({
            source: "<?php echo $this->Html->url(array('controller' => 'hqusers', 'action' => 'getState')); ?>",
            minLength: 1,
            select: function (event, ui) {
                console.log(ui.item.value);
            }
        });

    });
</script>