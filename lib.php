<?php

require __DIR__.'/inc.php';

function knowledgefox_add_instance($kf) {
	global $DB;

	$kf->intro = '';
	$kf->introformat = '';
	$kf->timemodified = time();

	$kf->id = $DB->insert_record("knowledgefox", $kf);

	knowledgefox_grade_update($kf);

	return $kf->id;
}

function knowledgefox_update_instance($kf) {
	global $DB;

	$kf->timemodified = time();
	$kf->id = $kf->instance;

	knowledgefox_grade_update($kf);

	return $DB->update_record("knowledgefox", $kf);
}

function knowledgefox_delete_instance($id) {
	global $DB;

	if (!$kf = $DB->get_record("knowledgefox", array("id" => $id))) {
		return false;
	}

	$result = true;

	if (!$DB->delete_records("knowledgefox", array("id" => $kf->id))) {
		$result = false;
	}

	knowledgefox_grade_delete($kf);

	return $result;
}

function knowledgefox_supports($feature) {
	switch ($feature) {
		case FEATURE_GROUPS:
			return false;
		case FEATURE_GROUPINGS:
			return false;
		case FEATURE_MOD_INTRO:
			return false;
		case FEATURE_COMPLETION_TRACKS_VIEWS:
			return false;
		case FEATURE_COMPLETION_HAS_RULES:
			return false;
		case FEATURE_GRADE_HAS_GRADE:
			return true;
		case FEATURE_GRADE_OUTCOMES:
			return false;
		case FEATURE_BACKUP_MOODLE2:
			return false;
		case FEATURE_SHOW_DESCRIPTION:
			return false;
		case FEATURE_CONTROLS_GRADE_VISIBILITY:
			return false;
		case FEATURE_USES_QUESTIONS:
			return false;

		default:
			return null;
	}
}
