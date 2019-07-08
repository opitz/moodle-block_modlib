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
        $this->title = get_string('pluginname', 'block_modlib');
    }

//----------------------------------------------------------------------------------------------------------------------
    function get_content() {
        global $PAGE;

        $PAGE->requires->js_call_amd('block_modlib/install_module', 'init', array());

        $this->content = new stdClass;
//        $this->content->text = 'GNUPF!';
//        $this->content->text = html_writer::tag('div','Gnupfig', array('class' => 'gnupf'));
        $this->content->text = $this->get_library_modules();
//        $this->content->footer = '<hr>(c) by QMUL 2019';
        $this->content->footer = '';

        return $this->content;
    }

//----------------------------------------------------------------------------------------------------------------------
    function get_library_modules() {
        global $DB;



        // The ID of the 'Templete Course' course
        $lib_course_id = 15;

        // The ID of the section 1 of that course since this is the one containing the currently valid library
        $rec = $DB->get_record('course_sections', array('course' => $lib_course_id, 'section' => "1"));
        $libsec_id = $rec->id;

        // get the modules
        $raw_mods = $DB->get_records('course_modules', array('course' => $lib_course_id, 'section' => $libsec_id));
        if(sizeof($raw_mods) == 0) {
            return "No library found!";
        }

        // Show what we found
        return $this->render_modules($raw_mods);
        /*
        $o = '';
//        $o .= html_writer::start_tag('div', '', array());
//        $o .= html_writer::end_tag('div');

        $o .= html_writer::start_tag('table', array());
        $supported_types = array('book');
        foreach($raw_mods as $raw_mod) {
            // get the module type
            $module_type = $DB->get_record('modules', array('id' => $raw_mod->module));
            // get the module record
            $module = $DB->get_record($module_type->name, array('id' => $raw_mod->instance));

            $o .= html_writer::start_tag('tr', array('id' => $module->id, 'class' => 'module_row'));
            $o .= html_writer::tag('td', '<input type="checkbox" class="module" value="'.$module->id.'" module_type ="'.$module_type->name.'" name="'.$module->name.'">');
//            $o .= html_writer::tag('td', '<input type="checkbox" class="module" value="'.$module->id.'" "module_type" => '.$module_type->name.' name="'.$module->name.'">');
            $o .= html_writer::tag('td','<b>'.ucfirst($module_type->name).'</b>: ', array());
            $o .= html_writer::tag('td', $module->name, array());
            $o .= html_writer::end_tag('tr');
        }
        $o .= html_writer::end_tag('table');

        $o .= $this->build_topics_menu();

        return $o;
        */
    }

    function render_modules1($raw_mods) {
        global $DB;
        $supported_types = array('book'); // array of yet supported module types

        $o = '';
//        $o .= html_writer::start_tag('div', '', array());
//        $o .= html_writer::end_tag('div');

        foreach($raw_mods as $raw_mod) {
            $o .= html_writer::start_tag('div', array('class' => 'form-check'));
            // get the module type
            $module_type = $DB->get_record('modules', array('id' => $raw_mod->module));
            // get the module record
            $module = $DB->get_record($module_type->name, array('id' => $raw_mod->instance));
            $module_name = '<b>'.ucfirst($module_type->name).'</b>: ' . $module->name;
            $not_supported = "This module type is not yet supported";
//            $o .= html_writer::checkbox('modlib_item',$module->id,false, $module_name, array('class' => 'module'));
//            $o .= "";
            if(in_array($module_type->name, $supported_types)) {
                $o .= html_writer::empty_tag('input', array('class' => 'form-check-input module', 'type' => 'checkbox', 'value' => $module->id, 'id' => 'module'.$module->id ));
            } else {
                $o .= html_writer::empty_tag('input', array('class' => 'form-check-input module', 'type' => 'checkbox', 'value' => $module->id, 'id' => 'module'.$module->id, 'disabled' => "", 'title' => $not_supported));
            }
            $o .= html_writer::tag('label', $module_name, array('class' => 'form-check-label', 'for' => 'module'.$module->id));


            $o .= html_writer::end_tag('div');
        }

        $o .= $this->build_topics_menu();
        return $o;
    }
    function render_modules($raw_mods) {
        global $DB;
        $o = '';
//        $o .= html_writer::start_tag('div', '', array());
//        $o .= html_writer::end_tag('div');

        $o .= html_writer::start_tag('table', array());
        $supported_types = array('book', 'data');
        foreach($raw_mods as $raw_mod) {
            // get the module type
            $module_type = $DB->get_record('modules', array('id' => $raw_mod->module));
            // get the module record
            $module = $DB->get_record($module_type->name, array('id' => $raw_mod->instance));

            $not_supported = "This module type is not yet supported";
            $o .= html_writer::start_tag('tr', array('id' => $module->id, 'class' => 'module_row'));
            if(in_array($module_type->name, $supported_types)) {
                $o .= html_writer::tag('td', '<input type="checkbox" class="module" value="'.$module->id.'" module_type ="'.$module_type->name.'" name="'.$module->name.'"> ');
            } else {
                $o .= html_writer::tag('td', '<input type="checkbox" class="module" value="'.$module->id.'" module_type ="'.$module_type->name.'" name="'.$module->name.'" title="'.$not_supported.'" disabled> ');
            }
//            $o .= html_writer::tag('td', '<input type="checkbox" class="module" value="'.$module->id.'" "module_type" => '.$module_type->name.' name="'.$module->name.'">');
            $o .= html_writer::tag('td','<b>'.ucfirst($module_type->name).'</b>: '.$module->name, array());
//            $o .= html_writer::tag('td', $module->name, array());
            $o .= html_writer::end_tag('tr');
        }
        $o .= html_writer::end_tag('table');

        $o .= $this->build_topics_menu();

        return $o;
    }

    function build_topics_menu() {
        global $COURSE, $PAGE;
        $o = '';
        // build a drop down menu to select a target topic
        $course = $this->page->course;
        $courseformat = course_get_format($course);
        $coursesections = $courseformat->get_sections();

        $o .= html_writer::empty_tag('hr');
        $o .= "<form method='post'>";

        // build the commands array
        $commands = array();

        $o .= html_writer::start_tag('button', array('type' => 'button', 'id'=>'command', 'class' => 'btn dropdown-toggle btn-primary', 'data-toggle' => 'dropdown'));
//        $o .= get_string('select_section', 'block_modlib');
        $o .= 'Select a Topic to install to';
        $o .= html_writer::end_tag('button');

        $o .= html_writer::start_tag('div', array('class' => 'dropdown-menu modlib-sections'));
        foreach($coursesections as $section) {
            $o.= html_writer::tag('a', $section->name, array(
                'class' => 'dropdown-item',
                'value' => $section->id
            ));
        }
        $o .= html_writer::end_tag('div');

        $o .= "</form>";
        return $o;
    }
}

