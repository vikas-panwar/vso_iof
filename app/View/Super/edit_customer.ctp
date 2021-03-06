
    <div class="row">
            <div class="col-lg-6">
                <h3>Edit Customer</h3> <br>
                <?php echo $this->Session->flash();?>   
            </div> 
            <div class="col-lg-6">                        
                <div class="addbutton">                
                        <?php //echo $this->Html->link('Back','/admin/admins/dashboard/',array('title' => 'Back'));?>
                </div>
            </div>
    </div>   
    <div class="row">        

<?php
            echo $this->Form->create('Super', array('inputDefaults' => array('label' => false, 'div'=>false, 'required' => false, 'error' => false, 'legend' => false,'autocomplete' => 'off'),'id'=>'UsersRegistration','enctype'=>'multipart/form-data'));

                           echo $this->Form->input('User.role_id',array('type'=>'hidden','value'=>4));
			    echo $this->Form->input('User.store_id',array('type'=>'hidden'));
			    echo $this->Form->input('User.merchant_id',array('type'=>'hidden'));
			    echo $this->Form->input('User.id',array('type'=>'hidden'));
			?>
        <div class="col-lg-6">            
	    <div class="form-group">		 
                <label>Salutation<span class="required"> * </span></label>               
              
	    <?php
		 echo $this->Form->input('User.salutation',array('type'=>'select','options'=>array('Mr.'=>'Mr.','Ms.'=>'Ms.','Mrs.'=>'Mrs.'),'class'=>'txtbox usrname-input txtbx','label'=>false,'div'=>false));

	?>
            </div>
	    
	   <div class="form-group form_margin">		 
                <label>First Name<span class="required"> * </span></label>               
              
		<?php
			echo $this->Form->input('User.fname',array('type'=>'text','class'=>'form-control valid','placeholder'=>'Enter Your First Name','label'=>false,'div'=>false));
			echo $this->Form->error('User.fname');
				       ?>

            </div>
	   <div class="form-group form_margin">		 
                <label>Last Name<span class="required"> * </span></label>               
              
		<?php 
					  echo $this->Form->input('User.lname',array('type'=>'text','class'=>'form-control valid ','placeholder'=>'Enter Your Last Name','label'=>false,'div'=>false));         
					  echo $this->Form->error('User.lname');
				       ?>

            </div>
	   
	    <div class="form-group form_margin">		 
                <label>Email<span class="required"> * </span></label>               
              
		<?php 
				       echo $this->Form->input('User.email',array('type'=>'text','class'=>'form-control valid','placeholder'=>'Enter Your Email','label'=>false,'div'=>false,'required'=>true,'readonly' => true));            
				       echo $this->Form->error('User.email');
				    ?>

            </div>
	    
	     <div class="form-group form_margin">		 
                <label>Mobile Phone<span class="required"> * </span></label>               
              
		<?php
					  echo $this->Form->input('User.phone',array('data-mask'=>'mobileNo','type'=>'text','class'=>'form-control valid phone_number','placeholder'=>'Mobile Phone','label'=>false,'div'=>false,'required'=>true));            
					  echo $this->Form->error('User.phone');

				       ?>
 <span class="blue">(eg. 111-111-1111)</span> 
            </div>
	     <div class="form-group form_margin">		 
                <label>DOB<span class="required"> * </span></label>               
              
		<?php
		$this->request->data['User']['dateOfBirth']=$this->Dateform->us_format($this->request->data['User']['dateOfBirth']);
				        echo $this->Form->input('User.dateOfBirth',array('type'=>'text','class'=>'form-control','div'=>false,'readonly'=>true));    
                
				       
				       echo $this->Form->error('User.dateOfBirth');
				       ?>

            </div>
<!--	     <div class="form-group form_spacing">
                <label>Address</label> 
		<?php echo $this->Form->input('User.address',array('type'=>'textarea','class'=>'form-control valid','placeholder'=>'Address','label'=>'','div'=>false));  
                  echo $this->Form->error('User.address');?>
            </div>
             <br>-->
	    <div class="form-group form_margin">
                <label>Status<span class="required"> * </span></label>                
               &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;
                <?php    
                echo $this->Form->input('User.is_active', array(
  'type' => 'radio',
  'options' => array('1' => 'Active&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '0' => 'Inactive') ,
  'default'=>1
));
                echo $this->Form->error('User.is_active');
                   ?>
            </div>
            
	    

             <?php //if($seasonalpost){ $display="style='display:block;'";}else{$display="style='display:none;'";}?>

	       	       
 
	  
            <?php echo $this->Form->button('Save', array('type' => 'submit','class' => 'btn btn-default'));?>             
            <?php echo $this->Html->link('Cancel', "/super/customerList/", array("class" => "btn btn-default",'escape' => false)); ?>
        </div>
        <?php echo $this->Form->end(); ?>
    </div><!-- /.row -->
    
    
   <script>
    $(document).ready(function() {
        $(".phone_number").keypress(function (e) {
        //if the letter is not digit then display error and don't type anything
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {	       
                  return false;
        }
    });
    $("[data-mask='mobileNo']").mask("(999) 999-9999");
	    $("#UsersRegistration").validate({
            debug: false,
            errorClass: "error",
            errorElement: 'span',
            onkeyup: false,
            rules: {
                "data[User][fname]": {
                    required: true,
                    lettersonly: true, 
                },
                 "data[User][lname]": {
                    required: false,
                    lettersonly: true, 
                },
                 "data[User][email]": {
                    required: true,
                    email: true,
                },
                
                "data[User][phone]": { 
                required: true, 
                
                },"data[User][dateOfBirth]": { 
                required: false,
                
                },  
            },
            messages: {
                "data[User][fname]": {
                    required: "Please enter your first name",
                    lettersonly:"Only alphabates allowed",
                },
                 "data[User][lname]": {
                    required: "Please enter your last name",
                    lettersonly:"Only alphabates Allowed",
                },
                "data[User][email]": {
                    required: "Please enter your email",
                    email:"Please enter valid email",
                },
                
                 "data[User][phone]": {
                    required: "Contact number required",
                },
               
            },highlight: function (element, errorClass) {
                $(element).removeClass(errorClass);
            },
    });
	    
	     $('#UserFname').change(function(){
      var str = $(this).val();
      if ($.trim(str) === '') {
         $(this).val('');
         $(this).css('border', '1px solid red');
         $(this).focus();
      }else{
         $(this).css('border', '');
      }
      });     
	});
</script>
