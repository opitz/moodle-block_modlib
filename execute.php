<?php
require_once('../../config.php');
include_once ('../../course/lib.php');

$sectionid = $_POST['sectionid'];
$cmid = $_POST['cmid'];
$moduleid = $_POST['moduleid'];
$type = $_POST['type'];

echo '';
echo install_module($sectionid, $cmid, $type);

/*
switch ($type) {
    case 'assign':
        echo install_assign($sectionid, $moduleid);
        break;
    case 'book':
        echo install_book($sectionid, $cmid);
        break;
    case 'chat':
        echo install_chat($sectionid, $moduleid);
        break;
    case 'data':
        echo install_data($sectionid, $moduleid);
        break;
    case 'feedback':
        echo install_feedback($sectionid, $moduleid);
        break;
    case 'glossary':
        echo install_glossary($sectionid, $moduleid);
        break;
    case 'lesson':
        echo install_lesson($sectionid, $moduleid);
        break;
    case 'page':
        echo install_page($sectionid, $moduleid);
        break;
    case 'quiz':
        echo install_quiz($sectionid, $moduleid);
        break;
    case 'survey':
        echo install_survey($sectionid, $moduleid);
        break;
    case 'wiki':
        echo install_wiki($sectionid, $moduleid);
        break;
    case 'workshop':
        echo install_workshop($sectionid, $moduleid);
        break;
    default:
        echo "";
}
*/

//----------------------------------------------------------------------------------------------------------------------
function install_module($sectionid, $cmid, $type) {
    global $DB, $USER, $COURSE;
//    list($course, $cm) = get_course_and_cm_from_cmid($cmid);

    // get course from sectionid
    $courseid = $DB->get_field('course_sections', 'course', array('id' => $sectionid));
    $course = $DB->get_record('course', array('id' => $courseid));

    $templateid = $DB->get_field('course_modules', 'course', array('id' => $cmid));
    $template = $DB->get_record('course', array('id' => $templateid));

    // Get cm from get_fast_modinfo.
    $modinfo = get_fast_modinfo($template, $USER->id);
    $cm = $modinfo->get_cm($cmid);

//    $cm->course = $courseid;
//    $cm->section = $sectionid;

//    install_template_module($course, $cm);
    install_template_module($course, $sectionid, $template, $cmid, $type);
    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective

    return "Book installed.";
}

//----------------------------------------------------------------------------------------------------------------------
function install_template_module($course, $sectionid, $template, $cmid, $type) {
    global $CFG, $DB, $USER;
    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->libdir . '/filelib.php');

    // Backup the activity.

    $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cmid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
    
    $backupid       = $bc->get_backupid();
    $backupbasepath = $bc->get_plan()->get_basepath();

    $bc->execute_plan();

    $bc->destroy();

    // Restore the backup immediately.

    $rc = new restore_controller($backupid, $course->id,
        backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

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
//        }

        // Update calendar events with the duplicated module.
        // The following line is to be removed in MDL-58906.
        course_module_update_calendar_events($newcm->modname, null, $newcm);

        // Trigger course module created event. We can trigger the event only if we know the newcmid.
        $newcm = get_fast_modinfo($course)->get_cm($newcmid);
        $event = \core\event\course_module_created::create_from_cm($newcm);
        $event->trigger();
    }

    return isset($newcm) ? $newcm : null;
}



//----------------------------------------------------------------------------------------------------------------------
function save_module0000($courseid = false, $sectionid = false, $moduleid = false, $moduletype = false, $new_instanceid, $target) {
    global $DB;
    if(!$courseid || !$sectionid || !$moduleid || !$moduletype) {
        return false;
    }

    // get the module type code
    $type_code = $DB->get_record('modules', array('name' => $moduletype));
    // now relate the data to the target section
    $cm = $DB->get_record('course_modules', array('module' => $type_code->id, 'instance' => $moduleid));
    unset($cm->id);
    $cm->course = $courseid;
    $cm->instance = $new_instanceid;
    $cm->section = $sectionid;

    $cm_id = $DB->insert_record('course_modules', $cm);

    // finally update the module sequence of the target section
    if($target->sequence === '' || $target->sequence == null) {
        $target->sequence = $cm_id;
    } else {
        $target->sequence .= ','.$cm_id;

    }
    $DB->update_record('course_sections', $target);

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective
    return true;
}

//----------------------------------------------------------------------------------------------------------------------
function install_assign0000($sectionid, $moduleid) {
    global $DB;
    list($course, $cm) = get_course_and_cm_from_cmid($moduleid);

    return "";
}
function install_assign0($sectionid, $moduleid) {
    global $DB;
    // get the base assign module
    $assign = $DB->get_record('assign', array('id' => $moduleid));
    $grade_item = $DB->get_record('grade_items', array('iteminstance' => $assign->id));
    unset($assign->id);

    // get the target section
    $target = $DB->get_record('course_sections', array('id' => $sectionid));
    $courseid = $target->course;

    // Save a new copy of the module
    $assign->course = $courseid;
    $new_assign_id = $DB->insert_record('assign', $assign);

    // now save a copy of the grade item too
    unset($grade_item->id);
    $grade_item->iteminstance = $new_assign_id;
    $new_grade_item_id = $DB->insert_record('grade_items', $grade_item);

    // finally save the new module and relate it to the target section
    save_module($courseid, $sectionid, $moduleid, 'assign', $new_assign_id, $target);

    // get any files related to the assignment module
    $files = get_module_files($assign->id);
    if($files) {
        set_module_files($files, $new_assign_id);
    }

    return "Assignment $assign->name installed";
}

//----------------------------------------------------------------------------------------------------------------------
function install_book000($sectionid, $cmid) {
    global $DB, $USER, $COURSE;
//    list($course, $cm) = get_course_and_cm_from_cmid($cmid);

    // get course from sectionid
    $courseid = $DB->get_field('course_sections', 'course', array('id' => $sectionid));
    $course = $DB->get_record('course', array('id' => $courseid));

    $templateid = $DB->get_field('course_modules', 'course', array('id' => $cmid));
    $template = $DB->get_record('course', array('id' => $templateid));

    // Get cm from get_fast_modinfo.
    $modinfo = get_fast_modinfo($template, $USER->id);
    $cm = $modinfo->get_cm($cmid);

//    $cm->course = $courseid;
//    $cm->section = $sectionid;

//    install_template_module($course, $cm);
    install_template_module($courseid, $template, $cmid);

    return "Book installed.";
}

//----------------------------------------------------------------------------------------------------------------------
function install_book0($sectionid, $moduleid) {
    global $DB;

    // get the base book module
    $book = $DB->get_record('book', array('id' => $moduleid));
    $chapters = $DB->get_records('book_chapters', array('bookid' => $book->id));
    unset($book->id);

    // get the target section
    $target = $DB->get_record('course_sections', array('id' => $sectionid));
    $courseid = $target->course;

    // Save a new copy of the book
    $book->course = $courseid;
    $new_bookid = $DB->insert_record('book', $book);
    // now save copies of the chapters too
    foreach($chapters as $chapter) {
        unset($chapter->id);
        $chapter->bookid = $new_bookid;
        $new_chapterid = $DB->insert_record('book_chapters', $chapter);
    }

    // finally save the new module and relate it to the target section
    save_module($courseid, $sectionid, $moduleid, 'book', $new_bookid, $target);
/*
    // get the module type code for a book
    $type_code = $DB->get_record('modules', array('name' => 'book'));
    // now relate the book to the target section
    $cm = $DB->get_record('course_modules', array('module' => $type_code->id, 'instance' => $moduleid));
    unset($cm->id);
    $cm->course = $courseid;
    $cm->instance = $new_bookid;
    $cm->section = $sectionid;

    $cm_id = $DB->insert_record('course_modules', $cm);

    // finally update the module sequence of the target section
    if($target->sequence === '' || $target->sequence == null) {
        $target->sequence = $cm_id;
    } else {
        $target->sequence .= ','.$cm_id;

    }
    $DB->update_record('course_sections', $target);

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective
*/
    return "Book $book->name installed";
}

//----------------------------------------------------------------------------------------------------------------------
function install_data0000($sectionid, $moduleid) {
    global $DB;

    // get the target section
    $target = $DB->get_record('course_sections', array('id' => $sectionid));
    $courseid = $target->course;

    // get the base data record
    $data = $DB->get_record('data', array('id' => $moduleid));
    $data_fields = $DB->get_records('data_fields', array('dataid' => $data->id));
    $data_records = $DB->get_records('data_records', array('dataid' => $data->id));

    // save the new data record
    unset($data->id);
    $data->course = $courseid;
    $new_dataid = $DB->insert_record('data', $data);

    // now save the data fields for the new data
    $new_data_field_ids = array();
    foreach($data_fields as $data_field) {
        $old_field_id = $data_field->id;
        unset($data_field->id);
        $data_field->dataid = $new_dataid;
        $data_field->id = $DB->insert_record('data_fields', $data_field);
        $new_data_field_ids[$old_field_id] = $data_field->id;
    }

    // now save the records
    $new_data_records = array();
    foreach($data_records as $data_record) {
        $data_record_id = $data_record->id; // we need the original record ID
        // 1st get the data content of the original record
        $data_content = $DB->get_records('data_content', array('recordid' => $data_record->id));

        // save the new record
        unset($data_record->id);
        $data_record->dataid = $new_dataid;
        $new_data_record_id = $DB->insert_record('data_records', $data_record);

        // save the record data for the new record
        foreach($data_content as $content) {
            unset($content->id);
            $content->recordid = $new_data_record_id;
            $content->fieldid = $new_data_field_ids[$content->fieldid];
            $data_field->id = $DB->insert_record('data_content', $content);
        }
    }

    // finally save the new module and relate it to the target section
    save_module($courseid, $sectionid, $moduleid, 'data', $new_dataid, $target);
/*
    // get the module type code
    $type_code = $DB->get_record('modules', array('name' => 'data'));
    // now relate the data to the target section
    $cm = $DB->get_record('course_modules', array('module' => $type_code->id, 'instance' => $moduleid));
    unset($cm->id);
    $cm->course = $courseid;
    $cm->instance = $new_dataid;
    $cm->section = $sectionid;

    $cm_id = $DB->insert_record('course_modules', $cm);

    // finally update the module sequence of the target section
    if($target->sequence === '' || $target->sequence == null) {
        $target->sequence = $cm_id;
    } else {
        $target->sequence .= ','.$cm_id;

    }
    $DB->update_record('course_sections', $target);

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective
*/
    return "Data $data->name installed";
}



