<?php
if($sizeList){
    echo $this->Form->input('Size.id',array('type'=>'select','class'=>'form-control multiOnly valid','label'=>'','div'=>false,'autocomplete' => 'off','options'=>$sizeList,'multiple'=>true));    
}else{
   echo "No size Available";   
}
echo $this->Form->input('Size.issizeonly',array('type'=>'hidden','value'=>$sizeInfo['Category']['is_sizeonly']));
echo $this->Form->input('Category.is_mandatory',array('type'=>'hidden','value'=>$sizeInfo['Category']['is_mandatory']));
?>
<script>
$(document).ready(function() {
    $('.multiOnly').multiselect();
    
});
</script>