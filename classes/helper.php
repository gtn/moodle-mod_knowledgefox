<?php

namespace mod_knowledgefox;
require_once dirname(__DIR__).'/lib/lib.php';
class helper {

    public static function getGroupid($groupUid, $wsparams){
        return knowledgefox_ws_get_kfgroup($groupUid ,$wsparams);
    }

    public static function getGradings($group, $wsparams){
        return knowledgefox_ws_get_user_grading($group,$wsparams);
    }
    public static function getProgress($kursid, $userid, $wsparams){
        return knowledgefox_ws_get_user_progress($kursid, $userid, $wsparams);
    }

    public static function getKfuser($student, $wsparams){
        $kf_users = knowledgefox_ws_get_kfusers($wsparams);
        $kf_user = knowledgefox_is_in_kfuserslist($student->username,$kf_users);
        return $kf_user;
    }
    public static function getCourseid($kursId, $wsparams){
        return knowledgefox_ws_get_courseId($kursId, $wsparams);
    }


    public static function getStudents($course){
        return knowledgefox_get_students_by_course($course);
    }

    public static function updateGrades($knowledgefox, $grade){
        knowledgefox_grade_update($knowledgefox, $grade);
    }

    public static function updateProgress($knowledgefox, $grade){
        knowledgefox_progress_update($knowledgefox, $grade);
    }

    public static function getServer($knowledgefox, $wsparams){
        return knowledgefox_get_kfox_server($knowledgefox, $wsparams);
    }
}