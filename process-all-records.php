<?php
if(SUPER_USER !== '1'){
    die("You're not allowed to view this page!");
}

$recordIdFieldName = json_decode(\REDCap::getDataDictionary($_GET['pid'], 'json'), true)[0]['field_name'];

$fieldNames = [
    $recordIdFieldName
];

$subSettings = $module->getSubSettings('concatenated-fields');
foreach($subSettings as $field_data) {
    $fieldNames[] = $field_data['destination'];
    foreach ($field_data['source'] as $src) {
        $fieldNames[] = $src;
    }    
}

$data = json_decode(REDCap::getData([
    'return_format' => 'json',
    'fields' => $fieldNames,
]), true);

echo "Record, Field, Expected, Actual, Update Performed<br>";

$recordsToSave = [];
foreach($data as $record){
    $recordId = $record[$recordIdFieldName];
    $recordToSave = [];
    foreach($subSettings as $field_data) {
        $destField = $field_data['destination'];
        $srcFields = $field_data['source'];
        if (!is_array($srcFields)) {
            $srcFields = array($srcFields);
        }
        $spaces = $field_data['spaces'];
        $space = "";
        if ($spaces) {
            $space = " ";
        }

        
        if ($destField) {
            $expected = '';

            foreach ($srcFields as $src) {
                if($field_data['spaces'] && !empty($expected)){
                    $expected .= " ";
                }

                $expected .= $record[$src];
            }

            $actual = $record[$destField];

            if($expected !== $actual){
                $saveValue = !empty($expected);

                if($saveValue){
                    $choices = array_filter($module->getChoiceLabels($destField));
                    if(!empty($choices) && !isset($choices[$expected])){
                        // This is a choice field, and the expected value is not a valid choice option.
                        $saveValue = false;
                    }
                }

                if($saveValue){
                    $updated = 'Yes';
                    $recordToSave[$recordIdFieldName] = $recordId;
                    $recordToSave[$destField] = $expected;
                }
                else{
                    $updated = 'No';
                }

                echo "$recordId, $destField, $expected, $actual, $updated<br>";
            }
        }
    }

    if(!empty($recordToSave)){
        $recordsToSave[] = $recordToSave;
    }
}

echo "<pre>";
if(isset($_GET['save'])){
    if(empty($recordsToSave)){
        die('nothing to save');
    }

    var_dump(REDCap::saveData($_GET['pid'], 'json', json_encode($recordsToSave)));
}
echo "</pre>";