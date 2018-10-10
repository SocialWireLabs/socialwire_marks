<?php

/*
 * © Copyright by Laboratorio de Redes 2012
 */


$user_id = get_input('user', null);
$logged_in_user_id = elgg_get_logged_in_user_guid();
if ($user_id) {
    $view_type = 'user';
    elgg_set_page_owner_guid($user_id);
    $title = $logged_in_user_id == $user_id ? elgg_echo('your:mark') : elgg_echo('mark:user', array(get_entity($user_id)->name));
} else
    $user_id = $logged_in_user_id;

if ($user_id) {
    elgg_push_breadcrumb(elgg_echo('item:object:socialwire_mark'), "socialwire_marks/show/user/$logged_in_user_id");
    
    if ($view_type != 'user') {  
        $task_id = get_input('task', null);
        $task = get_entity($task_id);
        if ($task) {
	    if ($task->getSubtype() == 'e_portfolio_group_setup')
	       $task_title = elgg_echo("e_portfolio");
	    else
               $task_title = $task->title;
            $subject_id = $task->group_guid; 
            $view_type = 'task';
        } else {
            $subject_id = get_input('subject', null);
            $view_type = 'subject';            
        }

        $subject = get_entity($subject_id);
        if ($subject) {
            $subject_name = $subject->name;
            elgg_set_page_owner_guid($subject_id);
        }

        if ($view_type == 'subject') {
            elgg_push_breadcrumb($subject_name);
            $title = elgg_echo('mark:user', array($subject_name));
        } else {
            elgg_push_breadcrumb($subject_name, "socialwire_marks/show/subject/$subject_id");
	    if ($task->getSubtype() == 'e_portfolio_group_setup')
               elgg_push_breadcrumb($task_title, "");
	    else
	       elgg_push_breadcrumb($task_title, $task->getURL());
            $title = elgg_echo('mark:user', array($task_title));
        }
    }
    
    $user_type = socialwire_marks_is_professor($user_id, $subject_id) ? 'professor' : 'student';
    $sort_by = get_input('sort_by', 'student');

    $access = elgg_set_ignore_access(true);
    if ($view_type == 'user' && (elgg_is_admin_logged_in() || $user_id == elgg_get_logged_in_user_guid()))
        $content = elgg_view('socialwire_marks/user_marks', array('user_id' => $user_id));
    else if (elgg_is_admin_logged_in() || check_entity_relationship($user_id, 'member', $subject_id)) {
        if ($view_type == 'subject')
           $content = elgg_view('socialwire_marks/subject_marks', array('user_type' => $user_type, 'subject_id' => $subject_id, 'sort_by' => $sort_by));
        else {
            $mark_type = socialwire_marks_get_mark_type($task);
	    //test y cuestionarios nunca se pueden calificar desde el marks
            if ($user_type == 'professor') {
	       if (($task->getSubtype() == 'task')&&(!$task->task_rubric)&&($task->type_delivery == "online")&&(!task_check_status($task)))
	          $new_user_type = $user_type;
	       else
	          $new_user_type = 'professor_as_student';
            }
	    $marks = socialwire_marks_get_task_marks($task, $sort_by);
            $content = elgg_view('socialwire_marks/marks', array('marks' => $marks, 'view_type' => $view_type, 'user_type' => $new_user_type, 'mark_type' => $mark_type));
        }
    }
    elgg_set_ignore_access($access);
}

$body = elgg_view_layout('content', array(
    'content' => $content,
    'title' => $title,
    'filter' => ''));

echo elgg_view_page($title, $body);
?>