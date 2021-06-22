<?php

namespace mod_knowledgefox\task;

/**
 * An example of a scheduled task.
 */
class getgrades extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('getgrades', 'mod_knowledgefox');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $wsparams = new \stdClass();
        $serverData = get_config('knowledgefox', 'knowledgefoxserver');
        $knowledgefoxInstances = $DB->get_records('knowledgefox');

        foreach($knowledgefoxInstances as $knowledgefox) {

            // todo recursive kursbereich pruefen
            $moduleId = $DB->get_field("modules", "id", array("name" => "knowledgefox"));
            $coursemoduleId = $DB->get_field("course_modules", "id", array("instance" => $knowledgefox->id, "module" => $moduleId));

            $coursetemp = $DB->get_record('course', array('id' => $knowledgefox->course), 'id, category');
            $categoryids = array();
            $categoryid = $coursetemp->category;

            while ($categoryid != 0) { // Should always exist, but just in case ...
                array_push($categoryids, $categoryid);
                $category = $DB->get_record('course_categories', array('id' => $categoryid), 'id, parent');
                $categoryid = $category->parent;
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
            if(!$catFound){
                if (is_siteadmin()) $mess.= "<i> Keine Kursbereichsid definiert, erster Server ". $serverData[0][0] ." aus der Pluginkonfiguration wird verwendet.</i>";
                $wsparams->knowledgefoxserver=$serverData[0][0];
                $wsparams->knowledgeauthuser=$serverData[0][1];
                $wsparams->knowledgeauthpwd=$serverData[0][2];
            }

            if (empty($wsparams->knowledgefoxserver)) {
                $mess.="<br>Kein Server vorhanden";
            }

            $group = \mod_knowledgefox\helper::getGroupid($knowledgefox->lernpaket ,$wsparams);
            $gradings = \mod_knowledgefox\helper::getGradings($group,$wsparams);

            $students = \mod_knowledgefox\helper::getStudents($knowledgefox->course);

            foreach( $students as $student){
                foreach( $gradings as $grading){
                    if($student->username == $grading->username){
                        $DB->insert_record("course_modules_completion",array('coursemoduleid' => $coursemoduleId, 'userid' => $student->id, 'completionstate' => 1, 'viewed' => 0, 'timemodified' => time()));
                    }
                }
            }


        }
    }
}
