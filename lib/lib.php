<?php

function knowledgefox_is_teacher($context = null, $userid = null) {
	$context = knowledgefox_get_context_from_courseid($context);
//$context = context_course::instance($course);
	return has_capability('mod/knowledgefox:teacher', $context, $userid);
}

function knowledgefox_is_student($context = null) {
	$context = knowledgefox_get_context_from_courseid($context);

	// a teacher can not be a student in the same course
	return has_capability('mod/knowledgefox:student', $context) && !has_capability('mod/knowledgefox:teacher', $context);
}

function knowledgefox_get_context_from_courseid($courseid) {
	if ($courseid instanceof context) {
		// already context
		return $courseid;
	} else if (is_numeric($courseid)) { // don't use is_int, because eg. moodle $COURSE->id is a string!
		return context_course::instance($courseid);
	} else if ($courseid === null) {
		return context_course::instance(g::$COURSE->id);
	} else {
		throw new \moodle_exception('wrong courseid type '.gettype($courseid));
	}
}

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
//print_r($grades);
	return grade_update('mod/knowledgefox', $instance->course, 'mod', 'knowledgefox', $instance->id, 0, $grades, $params);
}

function knowledgefox_output_get_json_content($val){
	$pos = strpos($val, "application/json");
	if ($pos === false) {
	    return false;
	} else {
			$pos=$pos+17;
	    $val=substr($val,$pos);
	    $posend=strripos($val, "]");
	    if ($posend === false) {
			} else {
				$val=substr($val,0,$posend+1);
			}
	    
	}
	return $val;
}
function knowledgefox_output_get_json_statuscode($val){
	$pos = strpos($val, "HTTP/1.1 ");
	if ($pos === false) {
	    return false;
	} else {
			$pos=$pos+9;
	    $val=substr($val,$pos);
	    $posend=strpos($val, " ");
	    if ($posend === false) {
			} else {
				$val=substr($val,0,$posend);
			}
	    return "".$val;
	}
}
function knowledgefox_output_get_json_statustext($val){
	$pos = strpos($val, "HTTP/1.1 ");
	if ($pos === false) {
	    return false;
	} else {
			$pos=$pos+13;
	    $val=substr($val,$pos);
	    $posend=strripos($val, "date:");
	    if ($posend === false) {
			} else {
				$val=substr($val,0,$posend-1);
			}
	   return ", ".$val.".";
	}
	
}
function knowledgefox_output_get_json_errordescription($val){
	$pos = strpos($val, "\"errors\"");
	if ($pos === false) {
	    return false;
	} else {
			$pos=$pos+11;
	    $val=substr($val,$pos);
	    $posend=strripos($val, "]}");
	    if ($posend === false) {
			} else {
				$val=substr($val,0,$posend-1);
			}
			$val=str_replace("\"errorName\""," Fehler",$val);
			$val=str_replace("\"errorCode\""," Fehlercode",$val);
			$val=str_replace("\"message\""," Beschreibung",$val);
	   return ", ".$val.".";
	}
	
}
function knowledgefox_user_is_ingroup($kf_user,$kfgroupname){
		foreach($kf_user->groupTitles as $grouptitle){
			if($grouptitle==$kfgroupname) {return true; exit;}
		}
		return false;
}
function knowledgefox_get_students_by_course($courseid) {

	$context = context_course::instance($courseid);
	$students = get_users_by_capability($context, 'mod/knowledgefox:student', '', 'lastname,firstname');

	// TODO ggf user mit exacomp:teacher hier filtern?
	return $students;
}
function knowledgefox_is_in_kfuserslist($username,$kf_users){
	foreach ($kf_users as $kf_user){
		if ($kf_user->username==$username) {
			return $kf_user;
			exit;
		}
	}
	return false;
}
function doUserCheck($kf_users,$mdluser,$kfgroup,$wsparams){
	
	$kf_user=knowledgefox_is_in_kfuserslist($mdluser->username,$kf_users);
	global $mess;
	if ($kf_user){
		$mess.="<li>Der Benutzer ".$mdluser->username." existiert in Knowledgefox und hat die id".$kf_user->userId;
		if (knowledgefox_user_is_ingroup($kf_user,$kfgroup->title)){
			$mess.= "<br>Der Benutzer <b>".$mdluser->username."</b> ist auf Knowledgefox in der Gruppe ".$kfgroup->title." eingeschrieben.</li>";
			return true;
		}else{
			$mess.= "<br>Der Benutzer <b>".$mdluser->username."</b> ist nicht eingeschreiben in der Gruppe".$kfgroup->title.".</li>";
			return knowledgefox_ws_kfenroluser($kf_user,$kfgroup->groupId,$wsparams);
		}
	}else{
		if ($kf_user=knowledgefox_ws_kfadduser($mdluser,$wsparams)){
					return knowledgefox_ws_kfenroluser($kf_user,$kfgroup->groupId,$wsparams);
		}
		
	}
	
}
/*Webservice functions */
function knowledgefox_ws_get_kfusers($wsparams){
 	global $mess;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/users?projection=import");
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	$output = curl_exec($ch);
	//$info = curl_getinfo($ch);
	if ($wsparams->LOCALH) $output='#####HTTP/1.1 200 OK Date: Mon, 27 Nov 2017 10:31:37 GMT Server: Apache Strict-Transport-Security: max-age=31536000 Set-Cookie: JSESSIONID=3CB1655F86BA94B791D14A78D29E3150; Path=/KnowledgePulse/; Secure; HttpOnly X-Content-Type-Options: nosniff X-XSS-Protection: 1; mode=block Cache-Control: no-cache, no-store, max-age=0, must-revalidate Pragma: no-cache Expires: 0 Strict-Transport-Security: max-age=31536000 ; includeSubDomains Transfer-Encoding: chunked Content-Type: application/json [ { "userId" : 16, "username" : "gsadmin", "roleId" : 1, "groupTitles" : [ "KnowledgeApp", "public content" ] }, { "userId" : 17, "username" : "kfoxadmin", "roleId" : 8, "groupTitles" : [ "KnowledgeApp", "public content" ] }, { "userId" : 31258, "username" : "fpernegger@gtn-solutions.com", "roleId" : 7, "groupTitles" : [ "private@fpernegger@gtn-solutions.com", "public content" ] }, { "userId" : 31471, "username" : "schueler1", "roleId" : 1, "groupTitles" : [ "KnowledgeApp", "public content" ] }, { "userId" : 22, "username" : "fischer", "roleId" : 1, "groupTitles" : [ "Ernährungsfüchse","KnowledgeApp", "public content" ] }, { "userId" : 27, "username" : "test1", "roleId" : 1, "groupTitles" : [ "KnowledgeApp", "public content" ] }, { "userId" : 37, "username" : "dangerer", "roleId" : 5, "groupTitles" : [ "Ernährungsfüchse", "KnowledgeApp", "public content" ] }, { "userId" : 38, "username" : "gtn_support", "roleId" : 1, "groupTitles" : [ "Ernährungsfüchse", "KnowledgeApp", "public content" ] }, { "userId" : 39, "username" : "gtnuser2", "roleId" : 1, "groupTitles" : [ "KnowledgeApp", "public content" ] } ]';
	curl_close($ch);
	if (knowledgefox_output_get_json_statuscode($output)==200){
		$output=knowledgefox_output_get_json_content($output);
		$kf_users=json_decode($output);
		return $kf_users;
	}else{
		if (is_siteadmin()) $mess.="<br>Statuscode (users?projection=import): " . knowledgefox_output_get_json_statuscode($output);
		$mess.=knowledgefox_output_get_json_errordescription($output);
		return false;
	}
}
function knowledgefox_ws_get_user_grading($groupuid,$wsparams){
	global $mess;
	$ch = curl_init();
	//curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/groups?uid=".$groupuid);
  curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/stats/coursecompletions?includeTestCompletedNotPassed=true");
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
	//curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_USERPWD, "kfoxadmin:d3c11f15644aaef0ba844c5413aa328748b583f2");
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	$output = curl_exec($ch);
	curl_close($ch);
	
	//$info = curl_getinfo($ch);
	if ($wsparams->LOCALH) $output= 'HTTP/1.1 200 chunked Content-Type: application/json [{"groupId" : 1, "uid" : "1111111111111111123456789abcdef0", "title" : "ErnährungsfüchseTTTT"}]';
	
	if (knowledgefox_output_get_json_statuscode($output)==200){
		$kf_completedcourses=json_decode(knowledgefox_output_get_json_content($output));
		if (is_array($kf_completedcourses)) {
			return $kf_completedcourses;
		}
		else{	return -1;}
	}else{
		if (is_siteadmin()) $mess.="<br>Statuscode (stats/coursecompletions): ".knowledgefox_output_get_json_statuscode($output); 
		return false;
	}
}


function knowledgefox_ws_get_kfgroup($uid,$wsparams){
	global $mess;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/groups?uid=".$uid);

	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	//curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_USERPWD, "kfoxadmin:d3c11f15644aaef0ba844c5413aa328748b583f2");
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	$output = curl_exec($ch);
	curl_close($ch);
	//var_dump($output);die;
	//$info = curl_getinfo($ch);
	if ($wsparams->LOCALH) $output= 'HTTP/1.1 200 chunked Content-Type: application/json [{"groupId" : 1, "uid" : "1111111111111111123456789abcdef0", "title" : "ErnährungsfüchseTTTT"}]';
	//$output=knowledgefox_output_get_json_content($output);
	if (knowledgefox_output_get_json_statuscode($output)==200){
		
		$kf_groups=json_decode(knowledgefox_output_get_json_content($output));

		if ($kf_groups) {
			$kf_group=new stdClass();
			$kf_group->title=$kf_groups[0]->title;
			$kf_group->groupId=$kf_groups[0]->groupId;
				return $kf_group;
		}
		else{	return -1;}
	}else{
		if (is_siteadmin()) $mess.="<br>Statuscode (groups?uid=".$uid."): ".knowledgefox_output_get_json_statuscode($output); 
		$mess.=knowledgefox_output_get_json_errordescription($output);
		return false;
	}
}

function knowledgefox_ws_kfenroluser($kf_user,$kf_groupId,$wsparams){
  global $mess;
	$ch = curl_init();
	//curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/users/".$kf_user->userId);
	curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/users/".$kf_user->userId."/groups/".$kf_groupId);


	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
	curl_setopt($ch, CURLOPT_PUT, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	/*[{"groupId" : 1, "uid" : "123456789abcdef0123456789abcdef0", "title" :
"public content"}]*/
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	
	$output = curl_exec($ch);

	curl_close($ch);
	if ($wsparams->LOCALH) $output="HTTP/1.1 405 Method Not Allowed Date: Wed, 06 Dec 2017 10:15:37 GMT Server: Apache Strict-Transport-Security: max-age=31536000 Set-Cookie: JSESSIONID=60F8066F97A941B90F4431B6EA2AE8FB; Path=/KnowledgePulse/; Secure; HttpOnly Allow: DELETE,OPTIONS,PUT X-Content-Type-Options: nosniff X-XSS-Protection: 1; mode=block Cache-Control: no-cache, no-store, max-age=0, must-revalidate Pragma: no-cache Expires: 0 Strict-Transport-Security: max-age=31536000 ; includeSubDomains Content-Length: 0 Connection: close ";
	if (knowledgefox_output_get_json_statuscode($output)==100){
		$mess.="<br>Der Benutzer mit id ".$kf_user->userId." wurde in die Gruppe mit id ".$kf_groupId." eingeschrieben!";
		return true;
	}else{
		if (is_siteadmin()) $mess.="<br>Statuscode (users/".$kf_user->userId."/groups/".$kf_groupId."): ".knowledgefox_output_get_json_statuscode($output); 
		return false;
	}

}

function knowledgefox_ws_kfadduser($mdluser,$wsparams){
  global $mess;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/users");

	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
	curl_setopt($ch, CURLOPT_POST, 1);
	$usera=array();
	$usera["username"]=$mdluser->username;
	$usera["password"]="zufallspasswort";
	$usera["email"]=$mdluser->email;
	$usera["username"]=$mdluser->username;
	$usera["lastname"]=$mdluser->lastname;
	$usera["firstname"]=$mdluser->firstname;
	$usera["provider"]="MOODLE";
	$data_string = json_encode($usera);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	$output = curl_exec($ch);
	curl_close($ch);
	//var_dump($output);die;
	if (knowledgefox_output_get_json_statuscode($output)==201){
		$mess.= "<br>Der Benutzer ".$mdluser->username." wurde angelegt";
		$output=knowledgefox_output_get_json_content($output);
		$kf_user=json_decode($output);
		return $kf_user;
	}else{
		$mess.= "<li>Der Benutzer ".$mdluser->username." konnte nicht bei Knowledgefox angelegt werden.";
		if (is_siteadmin()) $mess.="<br>Statuscode (users):".knowledgefox_output_get_json_statuscode($output);
		$mess.=knowledgefox_output_get_json_errordescription($output)."</li>";
		return false;
	}
}

function knowledgefox_ws_createNewGroup($kursId, $wsparams, $moodleCourseId, $activityid){
    global $mess, $DB;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/groups");

    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
    curl_setopt($ch, CURLOPT_POST, 1);
    $usera=array();
    $usera["title"]= "Moodle-" . $moodleCourseId . "-" . $kursId;
    $data_string = json_encode($usera);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $output = curl_exec($ch);
    
    curl_close($ch);
    

    if (knowledgefox_output_get_json_statuscode($output)==201){

        $output=knowledgefox_output_get_json_content($output);
        $kf_group=json_decode($output);
        $mess.= "<br>Die Gruppe Moodle-" . $moodleCourseId . "-" . $kursId." wurde angelegt";
        $groupId = json_decode($output)->groupId;
        $groupUid = json_decode($output)->uid; // hash wert (lernpaket)


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/courses?uid=".$kursId."&projection=id");
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $output = curl_exec($ch);
       curl_close($ch);
        if (knowledgefox_output_get_json_statuscode($output)==200){
            $mess.= "<br>Die KF interne Kursid wurde gefunden";

            $output=knowledgefox_output_get_json_content($output);

            $DB->update_record("knowledgefox", array("id" => $activityid, "lernpaket" => $groupUid));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $wsparams->knowledgefoxserver."/KnowledgePulse/ws/rest/client/3.0/courses/".(json_decode($output)->id)."/groups/".$groupId);

            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $wsparams->knowledgeauthuser.":".$wsparams->knowledgeauthpwd);
            curl_setopt($ch, CURLOPT_PUT, 1);;
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            $output = curl_exec($ch);
            curl_close($ch);
            if (knowledgefox_output_get_json_statuscode($output)==100) {
                $mess .= "<br>Der Kurs wurde der Gruppe zugewiesen";
            }else {
                $mess .= "<br>Der Kurs konnte der Gruppe nicht zugewiesen werden";
                if (is_siteadmin()) $mess.="<br>Statuscode(courses/".(json_decode($output)->id)."/groups/".$groupId."): ".knowledgefox_output_get_json_statuscode($output);
                $mess.=knowledgefox_output_get_json_errordescription($output);
            }

        } else {
            $mess.= "<br>Die KF interne Kursid wurde nicht gefunden";
            if (is_siteadmin()) $mess.="<br>Statuscode (courses?uid=".$kursId."&projection=id): ".knowledgefox_output_get_json_statuscode($output);
            $mess.=knowledgefox_output_get_json_errordescription($output);
        }
        return true;
    }else{
        $mess.= "<br>Die Gruppe konnte nicht angelegt werden";
        if (is_siteadmin()) $mess.="<br>Statuscode: ".knowledgefox_output_get_json_statuscode($output);
        $mess.=knowledgefox_output_get_json_errordescription($output);
        return false;
    }

}