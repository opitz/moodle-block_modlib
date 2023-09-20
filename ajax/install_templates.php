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
 * Install the template items AJAX style.
 *
 * @package    block_modlib
 * @copyright 2023 onwards Matthias Opitz (opitz@gmx.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once('../../../course/lib.php');

require_login();
require_sesskey();

$sectionid = required_param('sectionid', PARAM_INT);
$type = required_param('type', PARAM_ALPHANUM);

if ($type == 'sections') {
    try {
        echo install_sections($sectionid, $_POST['payload']);
    } catch (coding_exception | dml_exception | restore_controller_exception $e) {
        throw new Exception($e->getMessage());
    }
} else {
    try {
        echo install_modules($sectionid, $_POST['payload']);
    } catch (base_plan_exception | base_setting_exception | coding_exception | dml_exception | restore_controller_exception |
    moodle_exception $e) {
        throw new Exception($e->getMessage());
    }
}

/**
 * Install the selected section(s).
 *
 * @param int $sectionid The ID of the section after the new section(s) will be installed.
 * @param array $sections The array of sections to install.
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws restore_controller_exception
 */
function install_sections (int $sectionid, array $sections):string {
    if (is_array($sections)) {
        foreach ($sections as $section) {
            install_section($sectionid, $section['id']);
        }
        if (count($sections) === 1) {
            return get_string('section_installed', 'block_modlib');
        } else {
            return get_string('all_sections_installed', 'block_modlib');
        }
    }
    return get_string('no_sections_installed', 'block_modlib');
}

/**
 * Install the selected module(s) into the section.
 *
 * @param int $sectionid The ID if the section to install to.
 * @param array $modules The array of modules to install.
 * @return string
 * @throws base_plan_exception
 * @throws base_setting_exception
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 * @throws restore_controller_exception
 */
function install_modules (int $sectionid, array $modules):string {
    if (is_array($modules)) {
        foreach ($modules as $module) {
            install_module($sectionid, $module['cmid'], $module['type']);
        }
        if (count($modules) === 1) {
            return get_string('module_installed', 'block_modlib');
        } else {
            return get_string('all_modules_installed', 'block_modlib');
        }
    }
    return get_string('no_modules_installed', 'block_modlib');
}

/**
 * Install the section.
 *
 * @param int $targetsid The ID of the target section.
 * @param int $templatesid The ID of the template section.
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws restore_controller_exception
 */
function install_section(int $targetsid, int $templatesid):string {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->libdir . '/filelib.php');

    // For this we need to keep empty directories on backup - and will revert it to it's original setting after.
    $keeptempdirectoriesonbackup = $CFG->keeptempdirectoriesonbackup;
    $CFG->keeptempdirectoriesonbackup = true;

    // Get the section after which the new section will be installed.
    $templatesection = $DB->get_record('course_sections', array('id' => $templatesid));
    $insertaftersection = $DB->get_record('course_sections', array('id' => $targetsid));
    $course = $DB->get_record('course', array('id' => $insertaftersection->course));
    $coursesections = $DB->get_records('course_sections', array('course' => $course->id));

    // Step 1: Backup the section modules.
    $bc = new backup_controller(backup::TYPE_1SECTION, $templatesid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id);

    $backupid       = $bc->get_backupid();

    $bc->execute_plan();
    $bc->destroy();

    // Step 2: Restore the backup immediately.
    // When restoring a section backup the section will be placed into the very position it was backup'd from
    // If there is already a section at this position we need to insert an empty section there
    // to allow it to be overwritten.
    if (count($coursesections) >= $templatesection->section + 1) {
        $insertsection = course_create_section($course->id, $templatesection->section);
    }

    $rc = new restore_controller($backupid, $course->id,
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id, backup::TARGET_EXISTING_ADDING);
    $rc->set_status(backup::STATUS_AWAITING);
    try {
        $rc->execute_plan();
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
    $rc->destroy();

    // Step 3: Move the new section to the desired position.
    if (count($coursesections) >= $templatesection->section + 1) {
        $section2move = (int) $insertsection->section;
        $position2move2 = (int) $insertaftersection->section + 1;
        if ($section2move != $position2move2) { // Only move it if it needs to be moved.
            move_section_to($course, $section2move, $position2move2);
        }
    } else {
        move_section_to($course, $templatesection->section, $insertaftersection->section + 1);
        // In case the target course has less sections than the position of the backup section, empty sections between
        // this and the position of the restored section have been automatically inserted and are now deleted again
        // As they will have all higher IDs than the ID of the newly inserted section lets delete these...
        $newsection = $DB->get_record('course_sections', array('course' => $course->id,
            'section' => $insertaftersection->section + 1));
        $targetsections = $DB->get_records('course_sections', array('course' => $course->id));
        foreach ($targetsections as $targetsection) {
            if ($targetsection->id > $newsection->id) {
                $DB->delete_records('course_sections', array('id' => $targetsection->id));
            }
        }
    }

    $CFG->keeptempdirectoriesonbackup = $keeptempdirectoriesonbackup;

    // Rebuild the cache for that course so the changes become effective.
    rebuild_course_cache($course->id, true);

    return get_string('installed', 'block_modlib', 'Section');
}

/**
 * Install the module.
 *
 * @param int $sectionid
 * @param int $cmid
 * @param string $type
 * @return string
 * @throws base_plan_exception
 * @throws base_setting_exception
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 * @throws restore_controller_exception
 */
function install_module(int $sectionid, int $cmid, string $type):string {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->libdir . '/filelib.php');

    // Get course from sectionid.
    $courseid = $DB->get_field('course_sections', 'course', array('id' => $sectionid));
    $course = $DB->get_record('course', array('id' => $courseid));
    $keeptempdirectoriesonbackup = $CFG->keeptempdirectoriesonbackup;
    $CFG->keeptempdirectoriesonbackup = true;

    // Backup the activity.
    $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cmid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);

    $backupid       = $bc->get_backupid();
    $backupbasepath = $bc->get_plan()->get_basepath();

    $bc->execute_plan();
    $bc->destroy();

    // Restore the backup immediately.
    $rc = new restore_controller($backupid, $course->id,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_CURRENT_ADDING);

    // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
    $plan = $rc->get_plan();
    $groupsetting = $plan->get_setting('groups');
    if (empty($groupsetting->get_value())) {
        $groupsetting->set_value(true);
    }

    $cmcontext = context_module::instance($cmid);
    if (!$rc->execute_precheck()) {
        $precheckresults = $rc->get_precheck_results();
        if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($backupbasepath);
            }
        }
    }

    try {
        $rc->execute_plan();
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }

    // Now a bit hacky part follows - we try to get the cmid of the newly
    // restored copy of the module.
    $newcmid = null;
    $tasks = $rc->get_plan()->get_tasks();
    foreach ($tasks as $task) {
        if (is_subclass_of($task, 'restore_activity_task')) {
            if ($task->get_old_contextid() == $cmcontext->id) {
                $newcmid = $task->get_moduleid();
                break;
            }
        }
    }

    $rc->destroy();

    if (empty($CFG->keeptempdirectoriesonbackup)) {
        fulldelete($backupbasepath);
    }

    if ($newcmid) {
        // Move the module to the destination section.
        $newcm = get_coursemodule_from_id($type, $newcmid);
        $section = $DB->get_record('course_sections', array('id' => $sectionid));
        moveto_module($newcm, $section);

        // Update calendar events with the duplicated module.
        // The following line is to be removed in MDL-58906.
        course_module_update_calendar_events($newcm->modname, null, $newcm);

        // Trigger course module created event. We can trigger the event only if we know the newcmid.
        $newcm = get_fast_modinfo($course)->get_cm($newcmid);
        $event = \core\event\course_module_created::create_from_cm($newcm);
        $event->trigger();
    }

    $CFG->keeptempdirectoriesonbackup = $keeptempdirectoriesonbackup;

    // Rebuild the cache for that course so the changes become effective.
    rebuild_course_cache($courseid, true);

    return get_string('installed', 'block_modlib', ucfirst($type));
}
