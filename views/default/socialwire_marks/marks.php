<?php
/*
 * © Copyright by Laboratorio de Redes 2012
 */

$url = $_SERVER['REDIRECT_URL'];
$student_str = "<span style=\"font-weight:bold;\"><a href=\"{$url}?sort_by=student\">" . elgg_echo('student') . '</a></span>';
$professor_str = "<span style=\"font-weight:bold;\"><a href=\"{$url}?sort_by=owner\">" . elgg_echo('professor') . '</a></span>';
$mark_type = elgg_extract('mark_type', $vars, NUMERIC10);
$echo_mark_types = array(NUMERIC10 => elgg_echo('mark:numeric10'),
    NUMERIC100 => elgg_echo('mark:numeric100'),
    BOOLEAN => elgg_echo('mark:boolean'),
    STRINGUNI => elgg_echo('mark:stringuni'),
    STRINGHSC => elgg_echo('mark:stringhsc'));
$mark_str = "<span style=\"font-weight:bold;\"><a href=\"{$url}?sort_by=value\">" . elgg_echo('mark') . " ($echo_mark_types[$mark_type])</a></span>";

$user_guid = elgg_get_logged_in_user_guid();

$page_number = elgg_extract('page_number', $vars, '');
$view_type = elgg_extract('view_type', $vars, 'task');
$user_type = elgg_extract('user_type', $vars, 'student');
$marks = elgg_extract('marks', $vars, false);
if ($marks) {
    $count = count($marks);
    $offset = get_input('offset', 0);
    $limit = get_input('limit', 20);
    $marks = array_slice($marks, $offset, $limit);
    foreach ($marks as $mark) {
        $task_id = $mark->task_guid;
        $task = get_entity($task_id);
	$student_id = $mark->student_guid;
	$student = get_entity($student_guid);
	$is_owner = false;
	if (elgg_instanceof($student, 'group')){
	   $group_members = get_group_members($student_id, 0);
	   if ($group_members) {
              foreach ($group_members as $one_group_member) {
	         $one_group_member_guid = $one_group_member->getGUID();
                 if ($one_group_member_guid == $user_guid) {
		   $is_owner = true;
		   break;
		 }
              }            
           }
	} else {
	   if ($student_id == $user_guid)
	      $is_owner = true;
	}

	$visibility = true;

	if (($user_type != 'professor')&&($user_type != 'professor_as_student')) {
	  
	   if (elgg_instanceof($task, 'object', 'e_portfolio_group_setup')) {
	      if ((!$task->public_global_marks)&&(!$is_owner)) {
	         $visibility = false;
	      }
	   } else {
	      if (elgg_instanceof($task, 'object', 'test')) {
	         if ((!$task->public_global_marks) && (!$is_owner))
	            $visibility = false;
	      } else {
	         if ((!$task->grading_visibility) || ((!$task->public_global_marks)&&(!$is_owner))) {
	            $visibility = false;
	         }
	      }
	   } 
	}
	if ($visibility){
		if ($mark instanceof ElggEntity)
            $marks_view .= elgg_view('object/socialwire_mark', array('entity' => $mark, 'view_type' => $view_type, 'user_type' => $user_type));
    }
    }
    $marks_view = '<table width="100%">' . $marks_view . '</table>';
    $marks_pagination = elgg_view('navigation/pagination', array('base_url' => $_SERVER['REQUEST_URI'], 'offset' => $offset, 'count' => $count, 'limit' => $limit));
}

if ($user_type == 'professor') {
    $marks_view .= elgg_view('input/submit', array('value' => elgg_echo('save')));
    $marks_view .= '&nbsp;';
    $marks_view .= elgg_view('input/reset', array('value' => elgg_echo('reset:marks')));
     $marks_view .= elgg_view('input/hidden', array('name' => 'page_number', 'value' => $page_number));
    $marks_view = elgg_view('input/form', array('body' => $marks_view, 'action' => 'action/socialwire_marks/save_marks'));
}
?>
<div class="contentWrapper">
    <table width="100%">
        <tr>
            <th width="40%"><?php echo $student_str; ?></th>
            <th width="40%"><?php echo $professor_str; ?></th>
            <th><?php echo $mark_str; ?></th>
        </tr>
    </table>
    <?php echo $marks_view; ?>
    <?php echo $marks_pagination; ?>
</div>
