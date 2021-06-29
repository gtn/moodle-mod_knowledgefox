<?php
	
require_once("inc.php");


global $USER;global $mess;global $PAGE;global $COURSE; global $OUTPUT; global $DB;

$wsparams=new stdClass();
if ($_SERVER['HTTP_HOST']=="localhost"){
	$wsparams->LOCALH=false;
}else{
	$wsparams->LOCALH=false;
}



$mess="";
$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$l = optional_param('l', 0, PARAM_INT);     // knowledgefox ID

if ($id) {
	$PAGE->set_url('/mod/knowledgefox/index.php', array('id' => $id));
}else{
	 print_error('invalidcoursemodule');
}
if ($l) {
    if (!$knowledgefox = $DB->get_record('knowledgefox', array('id'=>$l))) {
        //resource_redirect_if_migrated($l, 0);
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('knowledgefox', $knowledgefox->id, $knowledgefox->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('knowledgefox', $id)) {
    	
        //resource_redirect_if_migrated(0, $id);
        print_error('invalidcoursemodule');
    }
    $knowledgefox = $DB->get_record('knowledgefox', array('id'=>$cm->instance), '*', MUST_EXIST); // cm kommt von mdl_course_modules tabelle
}
if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
		print_error('coursemisconf');
	}




$serverData = get_config('knowledgefox', 'knowledgefoxserver');

// todo recursive kursbereich pruefen
$coursetemp = $DB->get_record('course', array('id' => $knowledgefox->course), 'id, category');
$categoryids = array();
$categoryid = $coursetemp->category;
//var_dump($categoryid);
while ($categoryid != 0) { // Should always exist, but just in case ...
    array_push($categoryids, $categoryid);
    $category = $DB->get_record('course_categories', array('id' => $categoryid), 'id, parent');
    $categoryid = $category->parent;
//    var_dump($categoryid);
}


$serverData = explode("\r\n", $serverData);
for($i=0;$i<count($serverData);$i++){
    $serverData[$i] = explode(";", $serverData[$i]);
}

// todo recursive kursbereich pruefen
$catFound = false;
foreach($serverData as $data){
    foreach($categoryids as $categoryid){
        if($data[3] == $categoryid){
            $wsparams->knowledgefoxserver=$data[0];
            $wsparams->knowledgeauthuser=$data[1];
            $wsparams->knowledgeauthpwd=$data[2];
            $catFound = true;
            break;
        }
    }
    if($catFound){
        break;
    }
}
//if (is_siteadmin()) $mess.="<br>Verbindung mit Server ".$wsparams->knowledgefoxserver." und dem Benutzer ".$wsparams->knowledgeauthuser." wird verwendet!";


if(!$catFound){
    if (is_siteadmin()) $mess.= "<i> Keine Kursbereichsid definiert, erster Server ". $serverData[0][0] ." aus der Pluginkonfiguration wird verwendet.</i>";
    $wsparams->knowledgefoxserver=$serverData[0][0];
    $wsparams->knowledgeauthuser=$serverData[0][1];
    $wsparams->knowledgeauthpwd=$serverData[0][2];
}

if (empty($wsparams->knowledgefoxserver)) {
    $mess.="<br>Kein Server vorhanden";
}

require_login($course, true, $cm);

$PAGE->set_title("das ist mein titel");
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// TEST INPUT


// create a new group for the course in kfox
if($knowledgefox->lernpaket == NULL){
    knowledgefox_ws_createNewGroup($knowledgefox->kursid, $wsparams, $knowledgefox->course, $cm->instance);
}
// get updated lernpaket data
$knowledgefox = $DB->get_record('knowledgefox', array('id'=>$cm->instance), '*', MUST_EXIST);
//knowledgefox_grade_update($knowledgefox, (object)['rawgrade' => 10,	'userid' => 3,]);

//if teacher go through students and enrole them on gtn.knowledgefox.at
//else if student show link to gtn.knowledgefox.at

$mess.="<h1>".$knowledgefox->name."</h1>";
$kfgroup=knowledgefox_ws_get_kfgroup($knowledgefox->lernpaket,$wsparams);

if (knowledgefox_is_teacher($course->id,$USER->id)){
	$mess.="<p><i>Knowledgefox Gruppe ".$kfgroup->title."</i></p>";
	//echo '<h2>Users zu exportieren</h2>';*/
	//echo '<pre>';
	//var_dump($enrolledUsers);
	$mess.= '<p>Dieser Lerninhalt befindet sich auf einem verbundenen Knowledgefox Server.<p>';
	$mess.="<p>Es wird nun geprüft, ob die Teilnehmer*innen dieses Kurses im verknüpften Knowledgefox Kurs eingeschrieben sind. <br> Bei Bedarf wird eine Einschreibung vorgenommen.<br> Es folgen Statusinformationen zu den einzelnen Teilnehmer*innen:</p>";
	//get all students from this course
	$students=knowledgefox_get_students_by_course($course->id);
	//get all users from knowledgefox (all groups)
	$kf_users=knowledgefox_ws_get_kfusers($wsparams);
	
	foreach ($students as $student){
		//check if user exists in knowledgefox. If not, create user in Knowledgefox with webservice and enrole user in knowledgefox in the current group
		doUserCheck($kf_users,$student,$kfgroup,$wsparams,1);
	}
	//echo '</pre>';

	$mess.="<br /><p>Teilnehmer*innen sehen an dieser Stelle einen Link zum Knowledgefoxserver.</p>";
	//grading
		/*$grading=knowledgefox_grade_update($knowledgefox, (object)[
		'rawgrade' => 9,
		'userid' => 8,
		]);*/
		//print_r($grading);
}
if (knowledgefox_is_student($course->id,$USER->id)){
	//user existiert auf knowledgefox?

	$kf_users=knowledgefox_ws_get_kfusers($wsparams);
	//print_r($kf_users);
	

	if (doUserCheck($kf_users,$USER,$kfgroup,$wsparams,2)){
		$mess.= '<p>Dieser Lerninhalt befindet sich auf einem verbundenen Knowledgefox Server.<p>';
		$mess.= '<p>Bitte klicken sie unten auf "weiter" und sie werden zu Knowledgefox weitergeleitet.<br>'; 
		$mess.= 'Auf der aufgerufenen Knowledgefox Anmeldeseite klicken sie bitte auf "Anmelden mit Moodle".</p>';
		$mess.= '<br><p style="font-size:x-large"> <a target="_blank" href="'.$wsparams->knowledgefoxserver.'">Weiter</a> zu Knowledgefox.</p>';
	}
	/*$kf_completedcourses=knowledgefox_ws_get_user_grading($knowledgefox->lernpaket,$wsparams);
	if (is_array($kf_completedcourses)) {
		foreach($kf_completedcourses as $kf_completedcourse){
				$completiondate = date('d.m.Y', $kf_completedcourse->completionDate);
				echo "Benutzer ".$kf_completedcourse->username." hat den Kurs '".$kf_completedcourse->courseTitle."' am ".$completiondate." abgeschlossen. <br>";
				echo "<hr>";
		}
	}
	*/
	/*
	$kf_users=knowledgefox_ws_get_kfusers($wsparams);
	$kf_user=knowledgefox_is_in_kfuserslist($USER->username,$kf_users);
	$kfgroup=knowledgefox_ws_get_kfgroup($knowledgefox->lernpaket,$wsparams);
	if ($kf_user){
		echo "Dieser Benutzer existiert in Knowledgefox und hat die id".$kf_user->userId;
		$link2kfox='<a href="https://gtn.knowledgefox.net:443/KnowledgePulse/client/course'.$kfgroup->groupId.'">zu Knowledgefox</a>';
		if (knowledgefox_user_is_ingroup($kf_user,$kfgroup->title)){
			echo "Benutzer ist auf Knowledgefox in der Gruppe ".$kfgroup->title." eingeschrieben";
			echo $link2kfox;
		}else{
			echo "Der Benutzer ist nicht eingeschreiben in gruppe".$kfgroup->title;
			if (knowledgefox_ws_kfenroluser($kf_user,$knowledgefox->lernpaket,$wsparams)){
				echo "einschreibung wurde gemacht";
				echo $link2kfox;
			}else{
				echo "keine Einschreibung m�glich";
			}
		}
	}else{
		if ($kf_user=knowledgefox_ws_kfadduser($USER,$wsparams)){
			echo "Der Benutzer wurde angelegt";
			if (knowledgefox_ws_kfenroluser($kf_user,$kfgroup->title)){
				echo "einschreibung wurde gemacht";
				echo $link2kfox;
			}else{
				echo "keine Einschreibung m�glich";
			}
		}else{
			echo "Der Benutzer konnte nicht bei Knowledgefox angelegt werden.";
		}
		
	}*/
	
}
echo $mess;
//$usercontext = context_user::instance($user->id);

/*$enrolledUsers = $DB->get_records_sql("
	SELECT user.id, user.firstname, user.lastname, user.email, course.fullname, knowledgefox.lernpaket
	FROM {user} user
	JOIN {user_enrolments} enrolment ON enrolment.userid=user.id
	JOIN {enrol} enrol ON enrol.id=enrolment.enrolid
	JOIN {course} course ON course.id=enrol.courseid
	JOIN {knowledgefox} knowledgefox ON knowledgefox.course=course.id
	GROUP BY user.id
");*/

echo $OUTPUT->footer();




