<style>
    input[type="radio"]{
    line-height: initial;
    margin: 8px 2px 10px 17px;
}
</style>

<?php ?>
<div class="row">
            <div class="col-lg-6">
                <h3>Upload Excel</h3> 
                <?php echo $this->Session->flash();?>   
            </div>
    <div class="col-lg-6">
                <?php echo $this->Html->link('Back', "/hqtypes/index/", array("class" => "btn btn-default",'escape' => false)); ?>   
            </div>
    
    </div>
<br>
<div class="row"> 
    <div class="col-lg-6">
    <?php
    $options = array(
    'Upload' => 'Upload',
    'Download Sample' => 'Download Sample'
);

$attributes = array(
    'legend' => false,
    'class'=>"radiobtn"
);

echo $this->Form->radio('Size', $options, $attributes);?> 
        </div>
</div>
<br>
      <div class="row">      
            <?php echo $this->Form->create('Type', array('url'=>array('controller'=>'hqtypes','action'=> 'uploadfile'),"id" => "addfrm",'enctype'=>'multipart/form-data'));  ?>
    <div class="col-lg-6">
<!--        <div class="form-group form_margin" style="display: none">-->
            <div class="form-group form_margin addonstore" style="display: none">		
            <div class="form-group">
                <label>Select Store<span class="required"> * </span></label>
                <?php
            $merchantList = $mList = $this->Common->getHQStores($this->Session->read('merchantId'));
                    if (!empty($merchantList)) {
                        $allOption = array('All' => 'All');
                        $merchantList = array_replace($allOption, $merchantList);
                    }
            echo $this->Form->input('Type.store_id', array('options' => @$merchantList, 'class' => 'form-control', 'label' => false, 'div' => false, 'empty' => 'Select Store'));
            ?>
            </div>
        </div>
        
        
<!--	    <div class="form-group form_margin" style="display: none">	-->
                <div class="form-group form_margin addonfile" style="display: none">	
                <label>Excel File<span class="required"> * </span></label>               
              
		<?php echo $this->Form->input('Type.file', array('type'=>'file','div'=>false,'label'=>false));
                    echo $this->Form->error('Type.file'); ?>
            </div>
	    
<!--	<div class="form-group form_margin" style="display: none">	-->
            <div class="form-group form_margin addonuploadcancle" style="display: none">
            <?php echo $this->Form->button('Upload', array('type' => 'submit','class' => 'btn btn-default'));?>             
            <?php echo $this->Html->link('Cancel', "/hqtypes/index/", array("class" => "btn btn-default",'escape' => false)); ?>
        </div>
        
        <?php echo $this->Form->end(); ?>
    
<!--    <div class="col-lg-6" style="display: none">      -->
         <div class="sampleDownload" style="display: none">      
                <div class="addbutton"> 
                     <?php //echo $this->Form->button('Sample Download', array('type' => 'button','onclick'=>"window.location.href='/hqsizes/downloadaddonsize'",'class' => 'btn btn-default downloadaddonsize')); ?>
                     <?php echo $this->Form->button('Sample Download', array('type' => 'button','class' => 'btn btn-default downloadtype')); ?>
<!--                    <a href="/file_upload_format/Size_Sample_Excel.xls">Sample Download</a>-->
                </div>
            </div>
</div>
    </div><!-- /.row -->

   <script type="text/javascript">
    $(document).ready(function() {
        jQuery("#addfrm").validate({
            rules: {
                 'data[Type][file]': {
                    required: true
                }
            },
            messages: {
                'data[Type][file]': {
                    required: 'Please Upload File.'
                }
            }
        });
    });
    
    $(function(){
      // bind change event to select
      $('#TypeStoreId').on('change', function () {
          var storeid = $(this).val(); // get selected value
         $('.downloadtype').on('click', function () { // require a URL
                  url='/hqtypes/downloadtype';
                 window.location = url+'/'+storeid; // redirect
              });
          
      });
      
      $('#SizeUpload').on('click', function () { 
          $(".addonstore").show();
          $("#TypeStoreId").val("");
          $(".sampleDownload").hide();
          $(".addonfile").hide();
          $(".addonuploadcancle").hide();
          $('.addonstore').on('change', function () { 
                        $(".addonfile").show();
                        $(".addonuploadcancle").show();
                        $(".sampleDownload").hide();
                        if ($("#TypeStoreId").val() === "") {
                            $(".addonfile").hide();
                            $(".addonuploadcancle").hide();
                            $(".sampleDownload").hide();
                        }   
                         
            });
              });
              
              $('#SizeDownloadSample').on('click', function () { 
                        $(".addonstore").show();
                        $("#TypeStoreId").val("");
                        $(".addonfile").hide();
                        $(".addonuploadcancle").hide();
                    $('.addonstore').on('change', function () { 
                                  $(".sampleDownload").show();
                                  $(".addonfile").hide();
                                  $(".addonuploadcancle").hide();
                                  if ($("#TypeStoreId").val() === "") {
                                        $(".addonfile").hide();
                                        $(".addonuploadcancle").hide();
                                        $(".sampleDownload").hide();
                                    }

                      });
                  });
      
      
    });
</script>   
          
    
    



