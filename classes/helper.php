<?php

namespace mod_knowledgefox;

require_once("lib/lib.php");

class helper {

    public static function getGroupid($groupUid, $wsparams){
        return knowledgefox_ws_getGroupid($groupUid ,$wsparams);
    }

    public static function getGradings($group, $wsparams){
        return knowledgefox_ws_get_user_grading($group,$wsparams);
    }

    public static function getStudents($course){
        return knowledgefox_get_students_by_course($course);
    }
}