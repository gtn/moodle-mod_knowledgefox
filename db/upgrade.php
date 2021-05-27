<?php  //$Id: upgrade.php,v 1.2 2007/08/08 22:36:54 stronk7 Exp $

// This file keeps track of upgrades to
// the newmodule module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_knowledgefox_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();
    $result = true;

    if ($oldversion < 2021051403) {
        $table = new xmldb_table('knowledgefox');
        $field = new xmldb_field('kursid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Exacomp savepoint reached.
        upgrade_mod_savepoint(true, 2021051403, 'knowledgefox');
    }



    return $result;
}
