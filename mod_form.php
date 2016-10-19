<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_knowledgefox_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        $strrequired = get_string('required');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'lernpaket', 'Lernpaket');
           $mform->setType('lernpaket', PARAM_TEXT);
        $mform->addRule('lernpaket', $strrequired, 'required', null, 'client');

        // $this->standard_intro_elements(get_string('customintro', 'survey'));

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }


}

