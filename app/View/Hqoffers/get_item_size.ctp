<?php
if($sizeList){   
    echo $this->Form->input('Size.id',array('type'=>'select','class'=>'form-control valid','label'=>'','div'=>false,'autocomplete' => 'off','options'=>$sizeList));    
}else{
   echo "No size Available";   
}
?>