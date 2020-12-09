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
 * Behat data generator for mod_quiz.
 *
 * @package   block_modlib
 * @category  test
 * @copyright 2020 Matthias Opitz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Behat data generator for plugin configuration.
 *
 * @copyright 2020 Matthias Opitz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_modlib_generator extends component_generator_base {
    public function create_plugin_config($plugin_config) {
        global $DB;
        $DB->insert_record('config_plugins', $plugin_config);
    }
}