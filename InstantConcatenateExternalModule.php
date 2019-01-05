<?php namespace Vanderbilt\InstantConcatenateExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class InstantConcatenateExternalModule extends AbstractExternalModule
{
	function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id) {
		$this->concatenate($project_id, $record, $instrument, $event_id, $group_id);
	}

	function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id) {
		$this->concatenate($project_id, $record, $instrument, $event_id, $group_id);
	}

	function concatenate($project_id, $record, $instrument, $event_id, $group_id, $survey_hash = NULL, $response_id = NULL) {
		if ($project_id) {
			# get the specifications
			foreach($this->getSubSettings('concatenated-fields') as $field_data) {
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
					echo "<script type='text/javascript'>
						$(document).ready(function() {
							function concat() {
								var value = '';
								var src = " . json_encode($srcFields) . ";
								var space = '" . $space . "';
								for (var i=0; i < src.length; i++) {
									if (i > 0) {
										value = value + space;
									}
									value = value + $('[name=\"'+src[i]+'\"]').val();
								}
								var destination = $('[name=\"" . $destField . "\"]');
								destination.val(value);
								// Trigger a change event for other modules, branching logic, etc.
								destination.change();
							}";
					foreach ($srcFields as $src) {
						echo "$('[name=\"" . $src . "\"]').blur(function() { concat(); }); ";
					}
					echo " });\n";
					echo "</script>";
				}
			}
		}
	}
}
