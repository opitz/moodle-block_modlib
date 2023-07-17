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
 * @copyright 2023 onwards Matthias Opitz (opitz@gmx.de)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_modlib extends block_base {

    /**
     * Config?
     *
     * @return bool
     */
    public function has_config() : bool {
        return true;
    }

    /**
     * Initialise
     *
     * @return void
     * @throws coding_exception
     */
    public function init() : void {
        $this->title = get_string('pluginname', 'block_modlib');
    }

    /**
     * Get the content.
     *
     * @return object
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_content() : object {

        // Only show this when the user is editing.
        if (!$this->page->user_is_editing()) {
            $this->content = new stdClass;
            $this->content->text = '';
        } else {
            $this->page->requires->js_call_amd('block_modlib/install_templates', 'init', array());

            $this->content = new stdClass;
            $this->content->text = $this->get_library_modules();
        }
        $this->content->footer = '';
        return $this->content;
    }

    /**
     * Get the modules from the template course.
     *
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_library_modules() : string {
        global $DB;

        // The ID of the 'Template Course' course.
        $libcourseid = $this->config->template_course ?? get_config('block_modlib', 'defaulttemplate');

        $result = array();
        // The ID of the sections of that course.
        $sections = $DB->get_records('course_sections', array('course' => $libcourseid));
        foreach ($sections as $section) {
            if ($section->sequence != '' && $section->visible != 0) {
                // Get the modules.
                $section->modules = $DB->get_records('course_modules', array(
                    'course' => $libcourseid,
                    'section' => $section->id
                ));
                $result[] = $section;
            }
        }

        if (count($sections) == 0) {
            return get_string('no_library', 'block_modlib');
        }

        // Show what we found.
        return $this->render_modules($result);
    }

    /**
     * Render the modules.
     *
     * @param array $sections
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function render_modules(array $sections) :string {
        global $CFG, $DB;
        $o = '';
        // Create a modal dialog that will be shown when installing modules.
        $o .= html_writer::start_tag('div', array(
            'id' => 'modlib-spinner-modal',
            'class' => 'modlib-modal',
            'style' => 'display: none;'
        ));
        $o .= html_writer::start_tag('div', array('class' => 'spinner-container'));
        $idleurl = $CFG->wwwroot.'/blocks/modlib/img/tapping.gif';
        $o .= '<img src="'.$idleurl.'" class="spinner"  height="125">';
        $o .= html_writer::tag('div', get_string('please_wait', 'block_modlib'), array(
            'id' => 'modlib-modal-msg',
            'style' => 'margin-top: 10px;'
        ));
        $o .= html_writer::end_div();
        $o .= html_writer::end_div();

        // An introduction.
        $o .= html_writer::div(get_string('intro_text', 'block_modlib'));
        $o .= html_writer::empty_tag('hr');

        // A table with available modules.
        $o .= html_writer::start_tag('table', array());

        foreach ($sections as $section) {
            // Ignore section 0.
            if ($section->section == 0) {
                continue;
            }

            $o .= html_writer::start_tag('tr', array('class' => 'template_section', 'sectionid' => $section->id));
            // Get the section name.
            if ($section->name == '') {
                $sectionname = get_string('generic_sectionname', 'block_modlib') . ' '. $section->section;
            } else {
                $sectionname = $section->name;
            }

            $o .= html_writer::tag('td', '<input type="checkbox" class="template_section" value="'.
                $section->section . '" sid ="' . $section->id.'" name="' . $sectionname.'"> ');
            $o .= html_writer::tag('th', $sectionname, array('colspan' => '3'));
            $o .= html_writer::end_tag('tr');
            foreach ($section->modules as $rawmod) {
                // Get the module type.
                $moduletype = $DB->get_record('modules', array('id' => $rawmod->module));
                // Get the module record.
                $module = $DB->get_record($moduletype->name, array('id' => $rawmod->instance));

                $o .= html_writer::start_tag('tr', array('id' => $module->id, 'class' => 'module_row'));
                $o .= html_writer::tag('td', '&nbsp;');
                $o .= html_writer::tag('td', '<input type="checkbox" class="template_module" sid="' .
                    $section->id.'" value="' . $module->id . '" cmid ="'.$rawmod->id . '" module_type ="' .
                    $moduletype->name.'" name="' . $module->name . '"> ');
                $o .= html_writer::tag('td', '<b>' . ucfirst($moduletype->name) . '</b>: '.
                    $module->name, array());
                $o .= html_writer::end_tag('tr');
            }
        }
        $o .= html_writer::end_tag('table');

        $o .= $this->build_topics_menu();

        return $o;
    }

    /**
     * Build the topics menu.
     *
     * @return string
     * @throws coding_exception
     */
    public function build_topics_menu() : string {
        $o = '';

        // Build a drop down menu to select a target topic.
        $course = $this->page->course;
        $courseformat = course_get_format($course);
        $coursesections = $courseformat->get_sections();

        $o .= html_writer::empty_tag('hr');
        $o .= "<form method='post'>";

        $title = get_string('select_section_mouseover', 'block_modlib');
        $o .= html_writer::start_tag('button', array(
            'type' => 'button',
            'id' => 'target_topic_btn',
            'class' => 'btn dropdown-toggle btn-primary disabled',
            'data-toggle' => 'dropdown',
            'title' => $title
        ));
        $o .= get_string('select_section', 'block_modlib');
        $o .= html_writer::end_tag('button');

        $o .= html_writer::start_tag('div', array('class' => 'dropdown-menu modlib-sections'));
        foreach ($coursesections as $section) {
            if ($section->name == '') {
                $sectionname = get_string('generic_sectionname', 'block_modlib') . ' ' . $section->section;
            } else {
                $sectionname = $section->name .' ('.get_string('generic_sectionname', 'block_modlib') .
                    ' ' . $section->section .')';
            }
            $o .= html_writer::tag('a', $sectionname, array(
                'class' => 'dropdown-item',
                'value' => $section->id
            ));
        }
        $o .= html_writer::end_tag('div');

        $o .= "</form>";
        return $o;
    }

}

