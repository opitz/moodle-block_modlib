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

/**
 * The edit form
 *
 * @package   block_modlib
 * @copyright 2023 onwards Matthias Opitz (opitz@gmx.de)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_modlib_edit_form extends block_edit_form {

    /**
     * Specific definition.
     *
     * @param MoodleQuickForm $mform Moodle Quick Form
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function specific_definition($mform) {
        global $DB;

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Get all courses from the 'Template' category.
        $catid = get_config('block_modlib', 'templatecategory');
        $sql = "select c.* from {course} c join {course_categories} cc on cc.id = c.category where cc.id = '$catid'";
        $tcourses = $DB->get_records_sql($sql);

        $options = array();
        $options[0] = get_string('no_selection', 'block_modlib');
        foreach ($tcourses as $tcourse) {
            $options[$tcourse->id] = $tcourse->fullname;
        }
        $mform->addElement('select', 'config_template_course',
            get_string('template_course', 'block_modlib'), $options);
        $mform->setDefault('config_template_course',
            get_config('block_modlib', 'defaulttemplate'));
        $mform->setType('config_template_course', PARAM_RAW);
    }
}
