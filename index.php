<?php

require_once("inc.php");

$id = required_param('id',PARAM_INT);   // course

$PAGE->set_url('/mod/knowledgefox/index.php', array('id'=>$id));

//nothing to do here, go to view

redirect("$CFG->wwwroot/course/view.php?id=$id");


