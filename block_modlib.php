<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for editing HTML block instances.
 *
 * @package   block_modlib
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_modlib extends block_base {

//----------------------------------------------------------------------------------------------------------------------
    function init() {
        global $PAGE;
        $PAGE->requires->js_call_amd('block_modlib/test', 'init', array());
        $this->title = get_string('pluginname', 'block_modlib');
    }

//----------------------------------------------------------------------------------------------------------------------
    function get_content() {
        global $CFG;

        $this->content = new stdClass;
//        $this->content->text = 'GNUPF!';
//        $this->content->text = html_writer::tag('div','Gnupfig', array('class' => 'gnupf'));
        $this->content->text = $this->get_library_modules();
        $this->content->footer = '<hr>(c) by QMUL 2019';

        return $this->content;
    }

//----------------------------------------------------------------------------------------------------------------------
    function get_library_modules() {
        global $DB;



        // The ID of the 'Module Library' course
        $lib_course_id = 2;

        // The ID of the section 1 of that course since this is the one containing the currently valid library
        $libsec_id = 8;

        // get the modules
        $raw_mods = $DB->get_records('course_modules', array('course' => $lib_course_id, 'section' => $libsec_id));
        if(sizeof($raw_mods) == 0) {
            return "No library found!";
        }

        // Show what we found
        $o = '';
//        $o .= html_writer::start_tag('div', '', array());
//        $o .= html_writer::end_tag('div');

        $o .= html_writer::start_tag('table', array());
        foreach($raw_mods as $raw_mod) {
            // get the module type
            $module_type = $DB->get_record('modules', array('id' => $raw_mod->module));
            // get the module record
            $module = $DB->get_record($module_type->name, array('id' => $raw_mod->instance));

            $o .= html_writer::start_tag('tr', array('class' => 'module '.$module_type->name));
//            $o .= html_writer::tag('td','<b>'.ucfirst($module_type->name).'</b>: ', array());
            $o .= html_writer::tag('td', $module->name, array());
            $o .= html_writer::end_tag('tr');
        }
        $o .= html_writer::end_tag('table');

        return $o;
    }
}
