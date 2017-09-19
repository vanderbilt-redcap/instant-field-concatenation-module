<?php namespace Vanderbilt\InstantConcatenateExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class InstantConcatenateExternalModule extends AbstractExternalModule
{
	function hook_data_entry_form($project_id, $record, $instrument, $event_id, $group_id) {
		$this->concatenate($project_id, $record, $instrument, $event_id, $group_id);
	}

	function hook_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id) {
		$this->concatenate($project_id, $record, $instrument, $event_id, $group_id);
	}

	function concatenate($project_id, $record, $instrument, $event_id, $group_id, $survey_hash = NULL, $response_id = NULL) {
		if ($project_id) {
			# get the specifications
            $module_data = ExternalModules::getProjectSettingsAsArray(array("vanderbilt_instantConcatenate"), $project_id);
			$destField = $module_data['destination']['value'];
			$srcFields = $module_data['source']['value'];
			if (!is_array($srcFields)) {
				$srcFields = array($srcFields);
			}
			$spaces = $module_data['spaces']['value'];
            $space = "";
            if ($spaces) {
                $space = " ";
            }

			if ($destField) {
				echo "<script>
                    $(document).ready(function() {
                        console.log('Instant Concatenate Loaded');
					    function concat() {
						    var value = '';
						    var src = ".json_encode($srcFields).";
						    var space = '".$space."';
						    for (var i=0; i < src.length; i++) {
							    if (i > 0) {
								    value = value + space;
							    }
							    value = value + $('[name=\"'+src[i]+'\"]').val();
						    }
                            console.log('Concatenating to '+value);
						    $('[name=\"".$destField."\"]').val(value);
					    }";
						foreach ($srcFields as $src) {
							echo "$('[name=\"".$src."\"]').change(function() { concat(); }); ";
						}
				echo " });\n";
				echo "</script>";
			}
		}
	}
}
