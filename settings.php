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
 *  Module Library
 *
 *  @package    block_modlib
 *  @copyright  2019 (C) QMUL
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

//require_once __DIR__.'/lib/settingslib.php';

if ($ADMIN->fulltree) {
    $name = 'block_modlib/templatecategory';
    $title = get_string('templatecategory', 'block_modlib');
    $description = get_string('templatecategory_desc', 'block_modlib');
    $default = "Templates";

    // get all course categories as options and show a drop down menu
    $options = array();
    $categories = $DB->get_records('course_categories', null, 'name', 'name');
    foreach($categories as $category) {
        $options[$category->name] = $category->name;
    }
    $settings->add(new admin_setting_configselect($name, $title, $description, 1, $options));
    //    $settings->add(new admin_setting_configtext($name, $title, $description, $default));
    if(isset($category)){
        $name = 'block_modlib/defaulttemplate';
        $title = get_string('defaulttemplate', 'block_modlib');
        $description = get_string('defaulttemplate_desc', 'block_modlib');
        $default = get_string('notemplate', 'block_modlib');

        // get all course categories as options and show a drop down menu
        $options = array();
        $sql = "select c.* from {course} c join {course_categories} cc on cc.id = c.category where cc.name = '$category->name'";
        $tcourses = $DB->get_records_sql($sql);
        $options[$default] = '';
        foreach($tcourses as $tcourse) {
            $options[$tcourse->fullname] = $tcourse->fullname;
        }
        $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options));
    }
}
