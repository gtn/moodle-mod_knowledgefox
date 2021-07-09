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



        $knowledgefoxInstances = $DB->get_records('knowledgefox');
				
        foreach($knowledgefoxInstances as $knowledgefox) {

            $moduleId = $DB->get_field("modules", "id", array("name" => "knowledgefox"));
            $coursemoduleId = $DB->get_field("course_modules", "id", array("instance" => $knowledgefox->id, "module" => $moduleId));

            $wsparams = new \stdClass();
            $wsparams = \mod_knowledgefox\helper::getServer($knowledgefox ,$wsparams);
            
            $group = \mod_knowledgefox\helper::getGroupid(trim($knowledgefox->lernpaket) ,$wsparams);
            $gradings = \mod_knowledgefox\helper::getGradings($group[0]->groupId,$wsparams);

            $students = \mod_knowledgefox\helper::getStudents($knowledgefox->course);

            foreach( $students as $student){
                foreach( $gradings as $grading){
                    if($student->username == $grading->username){
                        $existing = $DB->get_record('course_modules_completion', array('coursemoduleid' => $coursemoduleId , 'userid' => $student->id));
                        if(empty($existing)){
                            $updateGrade = new \StdClass;
                            $updateGrade->rawgrade = 1;
                            $updateGrade->feedback = "";
                            $updateGrade->userid = $student->id;
                            \mod_knowledgefox\helper::updateGrades($knowledgefox, $updateGrade);
                            $DB->insert_record("course_modules_completion",array('coursemoduleid' => $coursemoduleId, 'userid' => $student->id, 'completionstate' => 1, 'viewed' => 0, 'timemodified' => time()));
                        }
                    }
                }
            }


        }
    }
}
