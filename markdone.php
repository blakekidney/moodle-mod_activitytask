<?php
/**
 * Marks an activity task as being completed.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_activitytask
 * @copyright  2015 Blake Kidney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//load Moodle
require_once('../../config.php');

//let's make sure that we have a logged in user before proceeding
//this is not the conventional method for doing this, but it is much faster
if(!$USER->id) redirect($CFG->wwwroot.'/index.php');

//get the course module id from the url
$id = optional_param('id', 0, PARAM_INT); 
if(!$id) print_error('missingparameter');

//obtain the activity task record from the database
//this is more for verification purposes than anything else
$activitytask = $DB->get_record('activitytask', array('id' => $id), 'id, course', MUST_EXIST);

//let's determine if this request is coming from ajax or not
$ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

//obtain the course module in order to verify access
/*
//$cm = get_coursemodule_from_instance('activitytask', $id, $activitytask->course, false, MUST_EXIST);
$cm = get_fast_modinfo($activitytask->course)->instances['activitytask'][$id]->get_course_module_record(); 

//verify the user is logged in and has appropriate capabilities
//see: https://docs.moodle.org/dev/Page_API
require_login($activitytask->course, true, $cm);
//*/

//make sure there is not already a status record for this particular task and user
$status = $DB->get_record('activitytask_status', array('userid' => $USER->id, 'activitytask' => $id), '*');

//create a new record if we don't have an entry
if(!$status) {
	$today = new DateTime();
	$status = new stdClass();
	$status->userid = $USER->id;
	$status->activitytask = $id;
	$status->datedone = $today->format("Y-m-d H:i:s");	
	$status->id = $DB->insert_record('activitytask_status', $status);
}

//log the event
//require_once($CFG->dirroot.'/mod/activitytask/classes/event/activitytask_completed.php');
\mod_activitytask\event\activitytask_completed::create_from_task($status, $activitytask, $USER->id)->trigger();

//if the request came from ajax, then simply exit that we are finished
if($ajax) exit('Done');

//if not from ajax, then let's redirect back to the course
redirect($CFG->wwwroot.'/course/view.php?id='.$activitytask->course);

