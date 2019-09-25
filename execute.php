<?php
require_once('../../config.php');
include_once ('../../course/lib.php');

$sectionid = $_POST['sectionid'];
$cmid = $_POST['cmid'];
$moduleid = $_POST['moduleid'];
$type = $_POST['type'];

echo install_module($sectionid, $cmid, $type);

//----------------------------------------------------------------------------------------------------------------------
function install_module($sectionid, $cmid, $type) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->libdir . '/filelib.php');

    // get course from sectionid
    $courseid = $DB->get_field('course_sections', 'course', array('id' => $sectionid));
    $course = $DB->get_record('course', array('id' => $courseid));

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

    $rc->execute_plan();

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
        // move the module to the destination section
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

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective

    return get_string('installed', 'block_modlib', ucfirst($type));
}


