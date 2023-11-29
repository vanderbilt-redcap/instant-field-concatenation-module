<?php
$prefix = htmlspecialchars($_GET['prefix']);
$page = 'process-all-records';
$pid = htmlspecialchars($_GET['pid']);
$save = isset($_GET['save']) ? $_GET['save'] : 'false'; 

$toSave = ($save === 'true');
$processOnlyChecked = ($toSave ? '' : 'checked');
$processAndSaveChecked = ($toSave ? 'checked' : '');

echo
"<form method='get'> 
    <input type='hidden' name='prefix' value= '$prefix'>
    <input type='hidden' name='page' value='$page'>
    <input type='hidden' name='pid' value='$pid'>
    <input type='hidden' name='submitted' value='true'>

    <input type='radio' id='process_only' name='save' value='false' $processOnlyChecked>
    <label for='process_only'>Process but not save</label><br>
    <input type='radio' id='process_and_save' name='save' value='true' $processAndSaveChecked>
    <label for='process_and_save'>Process and save</label><br>

    <input type='submit' value='Submit'>
</form>";

echo "<hr>";

if(SUPER_USER !== '1'){
    die("You're not allowed to use this function!");
}

if(!isset($_GET['submitted']))
{
    die('Choose an option.');
}


// $recordIdFieldName = json_decode(\REDCap::getDataDictionary($_GET['pid'], 'json'), true)[0]['field_name'];
$dict = json_decode(\REDCap::getDataDictionary($_GET['pid'], 'json'), true);
$recordIdFieldName = $dict[0]['field_name'];
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

        
        if ($destField) 
        {
            $expected = '';

            foreach ($srcFields as $src) 
            {
                if($field_data['spaces'] && !empty($expected)){
                    $expected .= " ";
                }
                // $expected .= $record[$src];

                $srcChecked = $record[$src];
                foreach ($dict as $field) 
                {
                    if ($field['field_name'] == $src) 
                    {
                        // Consider date may not be saved as the same format as date validation
                        // concatenation need to be made with the date validation format to be consistant with real time concatenation in openned form
                        $validationType = $field['text_validation_type_or_show_slider_number'];
                        if(strpos($validationType, 'date_') !== false)
                        {
                            // Define the date formats corresponding to REDCap's validation types
                            $dateFormats = ['date_dmy' => 'd-m-Y',
                                            'date_mdy' => 'm-d-Y',
                                            'date_ymd' => 'Y-m-d'];
                            
                            // find the validation/format of $srcChecked and save in a DateTime object
                            $dateObject = false;
                            $validationSaved = "";
                            foreach($dateFormats as $validation => $format)
                            {
                                $dateObject = DateTime::createFromFormat($format, $srcChecked);
                                if($dateObject && $dateObject->format($format) == $srcChecked)
                                {
                                    $validationSaved = $validation;
                                    break;
                                }
                            }
                            // replace with correct validation type
                            if($dateObject && $validationSaved != $validationType)
                            {
                                $srcChecked = $dateObject->format($dateFormats[$validationType]);
                            }
                        }
                        break;
                    }
                }               
                $expected .= $srcChecked;
            }

            $actual = $record[$destField];

            if($expected !== $actual){
                //deleted by ws 2023-11-29:keep the destination field consistant with the sources, the empty fields can be intentional
                // the following conditions has been changed accordingly to keep the logic for choice field
                // $saveValue = !empty($expected); 

                if($saveValue)
                {
                    $choices = array_filter($module->getChoiceLabels($destField));
                    if(!empty($choices) )
                    {
                        if(empty($expected))
                        {
                            $saveValue = false;
                        }
                        else if(!isset($choices[$expected]))
                        {                            
                            // This is a choice field, and the expected value is not a valid choice option.
                            $saveValue = false;
                        }
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
				## Need to escape before echoing
                echo htmlspecialchars("$recordId, $destField, $expected, $actual, $updated", ENT_QUOTES)."<br />";
            }
        }
    }

    if(!empty($recordToSave)){
        $recordsToSave[] = $recordToSave;
    }
}

echo "<pre>";
// if(isset($_GET['save']))
if ($save=='true')
{
    if(empty($recordsToSave)){
        die('nothing to save');
    }

    var_dump(REDCap::saveData($_GET['pid'], 'json', json_encode($recordsToSave)));
}

echo "</pre>";