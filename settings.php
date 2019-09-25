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
    $default = "Modlib Templates";

    $settings->add(new admin_setting_configtext($name, $title, $description, $default));
//    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));
}
