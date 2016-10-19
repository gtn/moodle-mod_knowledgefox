<?php

function knowledgefox_grade_delete($instance) {
	global $CFG;
	require_once $CFG->libdir.'/gradelib.php';

	return grade_update('mod/knowledgefox', $instance->course, 'mod', 'knowledgefox', $instance->id, 0, null, array('deleted' => 1));
}

function knowledgefox_grade_update($instance, $grades=null) {
	global $CFG;
	require_once $CFG->libdir.'/gradelib.php';

	$params = array('itemname' => $instance->name);
	// idnumber = $instance->cmidnumber;
	$params['gradetype'] = GRADE_TYPE_VALUE;
	$params['grademax'] = 100;
	$params['grademin'] = 0;

	if ($grades === 'reset') {
		$params['reset'] = true;
		$grades = null;
	}

	return grade_update('mod/knowledgefox', $instance->course, 'mod', 'knowledgefox', $instance->id, 0, $grades, $params);
}
