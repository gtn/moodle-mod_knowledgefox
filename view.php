<?php

require_once("inc.php");

$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$l = optional_param('l', 0, PARAM_INT);     // knowledgefox ID

if ($id) {
	$PAGE->set_url('/mod/knowledgefox/index.php', array('id' => $id));
	if (!$cm = get_coursemodule_from_id('knowledgefox', $id)) {
		print_error('invalidcoursemodule');
	}

	if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
		print_error('coursemisconf');
	}

	if (!$knowledgefox = $DB->get_record("knowledgefox", array("id" => $cm->instance))) {
		print_error('invalidcoursemodule');
	}

} else {
	/*
    $PAGE->set_url('/mod/knowledgefox/index.php', array('l'=>$l));
    if (! $knowledgefox = $DB->get_record("knowledgefox", array("id"=>$l))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id"=>$knowledgefox->course)) ){
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("knowledgefox", $knowledgefox->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
	*/
	print_error('invalidcoursemodule');
}

require_login($course, true, $cm);

// redirect("$CFG->wwwroot/course/view.php?id=$course->id");

$kf = $DB->get_record('knowledgefox', ['course'=>$COURSE->id]);
knowledgefox_grade_update($kf, (object)[
	'rawgrade' => 10,
	'userid' => 3,
]);

$enrolledUsers = $DB->get_records_sql("
	SELECT user.id, user.firstname, user.lastname, user.email, course.fullname, knowledgefox.lernpaket
	FROM {user} user
	JOIN {user_enrolments} enrolment ON enrolment.userid=user.id
	JOIN {enrol} enrol ON enrol.id=enrolment.enrolid
	JOIN {course} course ON course.id=enrol.courseid
	JOIN {knowledgefox} knowledgefox ON knowledgefox.course=course.id
	GROUP BY user.id
");

echo '<h2>Users zu exportieren</h2>';
echo '<pre>';
var_dump($enrolledUsers);
echo '</pre>';

