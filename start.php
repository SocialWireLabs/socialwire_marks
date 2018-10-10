<?php

/*
 * © Copyright by Laboratorio de Redes 2012
 */

define('NUMERIC10', 1);
define('NUMERIC100', 2);
define('BOOLEAN', 3);
define('STRINGUNI', 4);
define('STRINGHSC', 5);

define('PASS', 10);
define('FAIL', 0);

define('HONOURS', 10);
define('OUTSTANDING', 9);
define('VERYGOOD', 7);
define('GOOD', 6);
define('SUFFICIENT', 5);
define('INSUFFICIENT', 3);
define('VERYDEFICIENT', 0);

function socialwire_marks_mark_value_to_string($value, $mark_type) {
    $string_value = elgg_echo('no:mark');
    if ($value !== null) {
        switch ($mark_type) {
            case NUMERIC10:
                $string_value = labredes_do_in_locale(sprintf, "%.2f", $value);
                break;
            case NUMERIC100:
                $string_value = labredes_do_in_locale(sprintf, "%.2f", $value);
                break;
            case BOOLEAN:
                $string_value = $value == PASS ? elgg_echo('mark:pass') : elgg_echo('mark:fail');
                break;
            case STRINGUNI: case STRINGHSC:
                switch ($value) {
                    case HONOURS:
                        $string_value = elgg_echo('mark:honours');
                        break;
                    case OUTSTANDING:
                        $string_value = elgg_echo('mark:outstanding');
                        break;
                    case VERYGOOD:
                        $string_value = elgg_echo('mark:verygood');
                        break;
                    case GOOD:
                        $string_value = elgg_echo('mark:good');
                        break;
                    case SUFFICIENT:
                        $string_value = $mark_type == STRINGUNI ? elgg_echo('mark:pass') : elgg_echo('mark:sufficient');
                        break;
                    case INSUFFICIENT:
                        $string_value = $mark_type == STRINGUNI ? elgg_echo('mark:fail') : elgg_echo('mark:insufficient');
                        break;
                    case VERYDEFICIENT:
                        $string_value = elgg_echo('mark:verydeficient');
                        break;
                    default:
                        $string_value = $value;
                }
                break;
            default:
                $string_value = $value;
        }
    }
    return $string_value;
}

function socialwire_marks_convert_mark_value($value, $mark_type, $inverse = false) {
    $converted_value = -1;
    if ($value >= 0) {
        switch ($mark_type) {
            case NUMERIC10:
                $converted_value = $inverse ? round($value / 1000., 2) : (int) (round($value, 2) * 1000);
                break;
            case NUMERIC100:
                $converted_value = $inverse ? round($value / 100., 2) : (int) (round($value, 2) * 100);
                break;
            case BOOLEAN:
                if ($inverse)
                    $converted_value = $value >= 5000 ? PASS : FAIL;
                else
                    $converted_value = $value >= 5 ? PASS * 1000 : FAIL;
                break;
            case STRINGUNI: case STRINGHSC:
                if (!$inverse)
                    $converted_value = (int) ($value * 1000);
                else {
                    if ($mark_type == STRINGUNI) {
                        if ($value >= HONOURS * 1000)
                            $converted_value = HONOURS;
                        else if ($value >= OUTSTANDING * 1000)
                            $converted_value = OUTSTANDING;
                        else if ($value >= VERYGOOD * 1000)
                            $converted_value = VERYGOOD;
                        else if ($value >= SUFFICIENT * 1000)
                            $converted_value = SUFFICIENT;
                        else
                            $converted_value = INSUFFICIENT;
                    } else {
                        if ($value >= HONOURS * 1000)
                            $converted_value = HONOURS;
                        else if ($value >= OUTSTANDING * 1000)
                            $converted_value = OUTSTANDING;
                        else if ($value >= VERYGOOD * 1000)
                            $converted_value = VERYGOOD;
                        else if ($value >= GOOD * 1000)
                            $converted_value = GOOD;
                        else if ($value >= SUFFICIENT * 1000)
                            $converted_value = SUFFICIENT;
                        else if ($value >= INSUFFICIENT * 1000)
                            $converted_value = INSUFFICIENT;
                        else
                            $converted_value = VERYDEFICIENT;
                    }
                }
                break;
            default:
                $converted_value = $value;
        }
    }
    return $converted_value;
}

function socialwire_marks_get_mark_type($task) {
    $mark_type = null;
    if ($task)
        $mark_type = $task->mark_type;
    if ($mark_type === null)
        $mark_type = NUMERIC10;
    return $mark_type;
}

function socialwire_marks_create_mark($professor_id, $student_id, $task_id, $value, $page_number = null) {
    $professor = get_entity($professor_id);
    $student = get_entity($student_id);
    $task = get_entity($task_id);

    $existing_marks = elgg_get_entities_from_metadata(array(
        'type' => 'object',
        'subtype' => 'socialwire_mark',
        'owner_guid' => $professor_id,
        'metadata_name_value_pairs' => array(
            array('name' => 'student_guid', 'value' => $student_id),
            array('name' => 'task_guid', 'value' => $task_id),
	    array('name' => 'task_page_number', 'value' => $page_number)),
        'metadata_case_sensitive' => false,
        'limit' => 0,
        'count' => true));

     if (!$existing_marks && elgg_instanceof($professor, 'user') && (elgg_instanceof($student, 'user') || elgg_instanceof($student, 'group')) && (elgg_instanceof($task, 'object', 'task') || elgg_instanceof($task, 'object', 'test') || elgg_instanceof($task, 'object', 'questionnaire') || elgg_instanceof($task, 'object', 'e_portfolio_group_setup'))) {

        if (elgg_instanceof($student, 'group')) {
            $group_members = elgg_get_entities_from_relationship(array(
                                    'relationship' => 'member',
                                    'inverse_relationship' => true,
                                    'type' => 'user',
                                    'relationship_guid'=>$student_id, 
                                    'limit'=>0));
            $group_members_ids = array();
            if ($group_members) {
                foreach ($group_members as $group_member) {
                    $group_member_id = $group_member->getGUID();
                    if (!socialwire_marks_is_professor($group_member_id, $student_id))
                        $group_members_ids[] = $group_member_id;
                }
            }
            if (!count($group_members_ids))
                return false;
        }

        $mark_type = socialwire_marks_get_mark_type($task);
        $internal_value = socialwire_marks_convert_mark_value($value, $mark_type);
        if ($internal_value < 0)
            return false;

        $mark = new ElggObject();
        $mark->subtype = 'socialwire_mark';
        $mark->owner_guid = $professor_id;
        $mark->container_guid = $task->group_guid;
        $mark->access_id = ACCESS_PRIVATE;
        $mark->title = elgg_echo('mark');
        $mark->description = elgg_echo('mark');
        $mark->student_guid = $student_id;
        $mark->task_guid = $task_id;
	if (elgg_instanceof($task, 'object', 'e_portfolio_group_setup'))
	   $mark->task_page_number = $page_number;
        $mark->value = $internal_value;
        if ($group_members_ids)
            $mark->group_members = $group_members_ids;
        return $mark->save();
    }
    
    return false;
}

function socialwire_marks_update_mark($mark_id, $value, $mark_type = null, $page_number = null) {
    $mark = get_entity($mark_id);
    if ($mark) {
        $student_id = $mark->student_guid;
        $subject_id = $mark->container_guid;
        $task_id = $mark->task_guid;
        $user_guid = elgg_get_logged_in_user_guid();
        if (elgg_is_admin_logged_in() || socialwire_marks_is_professor($user_guid, $subject_id)) {
            if (!$mark_type)
                $mark_type = socialwire_marks_get_mark_type(get_entity($task_id));
	    if (strcmp($value,"not_qualified")==0) {
	        $access = elgg_set_ignore_access(true);
	        $result = socialwire_marks_delete_marks(array($mark));
		elgg_set_ignore_access($access);
	    } else {
                $internal_value = socialwire_marks_convert_mark_value($value, $mark_type);
                if ($internal_value >= 0) {
                    if ($mark->value == $internal_value)
                        return true;
                    $access = elgg_set_ignore_access(true);
                    if ($mark->getOwnerGUID() == $user_guid) {
                        $mark->value = $internal_value;
                        $result = true;
                    } else {
                        $result = socialwire_marks_delete_marks(array($mark));
                        if ($result)
                            $result = socialwire_marks_create_mark($user_guid, $student_id, $task_id, $value,$page_number);
                    }
                    elgg_set_ignore_access($access);
                    return $result;
                 }
	     }
         }
    }    
    return false;
}

function socialwire_marks_delete_marks($marks = null) {
    if ($marks === null) // Delete all marks
        $marks = elgg_get_entities(array('type' => 'object', 'subtype' => 'socialwire_mark', 'limit' => 0));

    if ($marks) {
        foreach ($marks as $mark) {
            $result = $mark->delete();
            if (!$result)
                return $result;
        }
    }
    return true;
}

function socialwire_marks_sort_marks($marks = false, $sort_by = 'student') {
    $sorted_marks = false;
    if ($marks) {
        foreach ($marks as $key => $mark) {
            if (is_array($mark)) {
                if ($sort_by == 'value')
                    $sorted_values[$key] = $mark['value'] !== null ? $mark['value'] : -1;
                else {
                    $sort_value = is_numeric($mark[$sort_by]) ? get_entity($mark[$sort_by])->name : $mark[$sort_by];
                    $sorted_values[$key] = str_replace(array('á', 'é', 'í', 'ó', 'ú'), array('a', 'e', 'i', 'o', 'u'), mb_strtolower($sort_value));
                }
            } else {
                if ($sort_by == 'value')
                    $sorted_values[$key] = $mark->value;
                else {
                    $sort_metadata = $sort_by . '_guid';
                    $sorted_values[$key] = str_replace(array('á', 'é', 'í', 'ó', 'ú'), array('a', 'e', 'i', 'o', 'u'), mb_strtolower(get_entity($mark->$sort_metadata)->name));
                }
            }
        }
        if ($sort_by == 'value')
            arsort($sorted_values);
        else
            asort($sorted_values);
        foreach ($sorted_values as $key => $value)
            $sorted_marks[$key] = $marks[$key];
    }
    return $sorted_marks;
}

function socialwire_marks_get_marks($professor_id = null, $student_id = null, $task_id = null, $subject_id = null, $page_number = null) {
    $task = get_entity($task_id);

    if (get_entity($student_id) instanceof ElggUser) {
        $group_marks = elgg_get_entities_from_metadata(array(
            'type' => 'object',
            'subtype' => 'socialwire_mark',
            'container_guid' => $subject_id,
            'owner_guid' => $professor_id,
            'metadata_name_value_pairs' => array(
                array('name' => 'group_members', 'value' => $student_id),
                array('name' => 'task_guid', 'value' => $task_id),
		array('name' => 'task_page_number', 'value' => $page_number)),
            'metadata_case_sensitive' => false,
            'limit' => 0));
    }

    $marks = elgg_get_entities_from_metadata(array(
        'type' => 'object',
        'subtype' => 'socialwire_mark',
        'container_guid' => $subject_id,
        'owner_guid' => $professor_id,
        'metadata_name_value_pairs' => array(
            array('name' => 'student_guid', 'value' => $student_id),
            array('name' => 'task_guid', 'value' => $task_id),
	    array('name' => 'task_page_number', 'value' => $page_number)),
        'metadata_case_sensitive' => false,
        'limit' => 0));

    if ($marks && $group_marks)
        $marks = array_merge($marks, $group_marks);
    else if ($group_marks)
        $marks = $group_marks;
    return $marks;
}

function socialwire_marks_get_task_marks($task = null, $sort_by = 'student', $user = null, $page_number = null) {
    $marks = array();
    if ($task) {
        $task_id = $task->getGUID();
        $subject_id = $task->group_guid;
        $task_who_answers = $task->who_answers;
        if (!$task_who_answers)
            $task_who_answers = 'member';
        if ($task_who_answers == 'member') {
            $group_members = $user ? array($user) : elgg_get_entities_from_relationship(array(
                                                                'relationship' => 'member',
                                                                'inverse_relationship' => true,
                                                                'type' => 'user',
                                                                'relationship_guid'=>$subject_id, 
                                                                'limit'=>0));
            if ($group_members) {
                foreach ($group_members as $group_member) {
                    $group_member_id = $group_member->getGUID();
                    if (!socialwire_marks_is_professor($group_member_id, $subject_id)) {
                        $previous_marks = socialwire_marks_get_marks(null, $group_member_id, $task_id, $subject_id, $page_number);
                        if ($previous_marks) {
                            foreach ($previous_marks as $previous_mark) {
                                if ($previous_mark->student_guid == $group_member_id) {
                                    $marks[$group_member_id] = $previous_mark;
                                    break;
                                }
                            }
                        } else
                            $marks[$group_member_id] = array('owner' => $user_id, 'student' => $group_member_id, 'task' => $task_id);
                    }
                }
            }
        } else if ($task_who_answers == 'subgroup') {
            $group_subgroups = elgg_get_entities(array(
                'type' => 'group',
                'subtype' => 'lbr_subgroup',
                'container_guid' => $subject_id,
                'limit' => 0));
            if ($group_subgroups) {
                foreach ($group_subgroups as $group_subgroup) {
                    $group_subgroup_id = $group_subgroup->getGUID();
                    if (!$user || in_array($user, elgg_get_entities_from_relationship(array(
                                                        'relationship' => 'member',
                                                        'inverse_relationship' => true,
                                                        'type' => 'user',
                                                        'relationship_guid'=>$group_subgroup_id, 
                                                        'limit'=>0)))) {
                        $previous_marks = socialwire_marks_get_marks(null, $group_subgroup_id, $task_id, $subject_id, $page_number);
                        $marks[$group_subgroup_id] = $previous_marks ? $previous_marks[0] : array('owner' => $user_id, 'student' => $group_subgroup_id, 'task' => $task_id);
                    }
                }
            }
        }
        $marks = socialwire_marks_sort_marks($marks, $sort_by);
    }
    return $marks;
}

function socialwire_marks_is_professor($user_id, $subject_id) {
    if (!$user_id || !$subject_id)
        return false;
    if (!check_entity_relationship($user_id, 'group_admin', $subject_id)) {
        $subject = get_entity($subject_id);
        if (!$subject)
            return false;
        $subject_owner_id = $subject->getOwnerGUID();
        if ($user_id != $subject_owner_id)
            return false;
    }
    return true;
}

function socialwire_marks_print_marks($marks = null) {
    $content = '<table border="1" cellpadding="5" cellspacing="5" width="100%">';
    $content .= '<tr><th><b>mark</b></th><th><b>student</b></th><th><b>professor</b></th><th><b>task</b></th><th><b>subject</b></th><th><b>value</b></th><th><b>weight</b></th><th><b>type</b></th><th><b>group</b></th></tr>';
    $bgcolor = '#33CCCC';
    $offset = 0;
    if ($marks === null)
        $marks = socialwire_marks_get_marks();
    if ($marks) {
        foreach ($marks as $mark) {
            $group_members = $mark->group_members;
            $group_members_str = is_array($group_members) ? implode(',', $group_members) : $group_members;
            $student = get_entity($mark->student_guid);
            $professor = get_entity($mark->owner_guid);
            $task = get_entity($mark->task_guid);
	    if (elgg_instanceof($task, 'object', 'e_portfolio_group_setup'))
	       $page_number = $mark->task_page_number;
            $subject = get_entity($mark->container_guid);
	    if (elgg_instanceof($task, 'object', 'e_portfolio_group_setup')) {
	       $task_title = elgg_echo('e_portfolio:e_portfolio');
	       if (!$task->var_pages)
	          $task_title .= " (#" . $mark->task_page_number . ")";
               $content .= "<tr bgcolor=\"{$bgcolor}\"><td>{$mark->guid}</td><td>{$student->username}</td><td>{$professor->username}</td><td>{$task_title}</td><td>{$subject->name}</td><td>{$mark->value}</td><td>{$task->mark_weight}</td><td>{$task->mark_type}</td><td>{$group_members_str}</td></tr>";
	    } else {
	       $content .= "<tr bgcolor=\"{$bgcolor}\"><td>{$mark->guid}</td><td>{$student->username}</td><td>{$professor->username}</td><td>{$task->title}</td><td>{$subject->name}</td><td>{$mark->value}</td><td>{$task->mark_weight}</td><td>{$task->mark_type}</td><td>{$group_members_str}</td></tr>"; 
	    }
            $bgcolor = $bgcolor == '#33CCCC' ? '#99FFCC' : '#33CCCC';
        }
    }
    $content .= '</table>';
    return $content;
}

function socialwire_marks_delete_user_marks($event, $object_type, $object) {
    $user_id = $object->getGUID();
    $delete_student_marks = array();
    $access = elgg_set_ignore_access(true);
    $student_marks = socialwire_marks_get_marks(null, $user_id, null);
    if ($student_marks) {
        foreach ($student_marks as $student_mark) {
            if ($user_id == $student_mark->student_guid)
                $delete_student_marks[] = $student_mark;
            else {
                elgg_delete_metadata(array('guid' => $student_mark->getGUID(), 'metadata_name' => 'group_members', 'metadata_value' => $user_id, 'limit' => 0));
                if ($student_mark->group_members == false)
                    $delete_student_marks[] = $student_mark;
            }
        }
    }
    if ($delete_student_marks)
        socialwire_marks_delete_marks($delete_student_marks);

    $professor_marks = socialwire_marks_get_marks($user_id, null, null);
    if ($professor_marks)
        socialwire_marks_delete_marks($professor_marks);
    elgg_set_ignore_access($access);
}

function socialwire_marks_delete_task_marks($event, $object_type, $object) {
    $task_id = $object->getGUID();
    $access = elgg_set_ignore_access(true);
    $marks = socialwire_marks_get_marks(null, null, $task_id);
    if ($marks)
        socialwire_marks_delete_marks($marks);
    elgg_set_ignore_access($access);
}

function socialwire_marks_delete_group_marks($event, $object_type, $object) {
    $group_id = $object->getGUID();
    $access = elgg_set_ignore_access(true);
    $marks = socialwire_marks_get_marks(null, $group_id, null);
    if ($marks)
        socialwire_marks_delete_marks($marks);
    $marks = socialwire_marks_get_marks(null, null, null, $group_id);
    if ($marks)
        socialwire_marks_delete_marks($marks);
    elgg_set_ignore_access($access);
}

function socialwire_marks_owner_block_menu($hook, $type, $return, $params) {
    if (elgg_instanceof($params['entity'], 'group') && $params['entity']->socialwire_marks_enable != 'no') {
        $url = "socialwire_marks/show/subject/{$params['entity']->getGUID()}";
        $item = new ElggMenuItem('socialwire_marks', elgg_echo('item:object:socialwire_mark'), $url);
        $return[] = $item;
    } else if (elgg_instanceof($params['entity'], 'user')) {
        $url = "socialwire_marks/show/user/" . $params['entity']->guid;
        $item = new ElggMenuItem('socialwire_marks', elgg_echo('item:object:socialwire_mark'), $url);
        $return[] = $item;
    }
    return $return;
}

function socialwire_marks_page_handler($page) {
    $pages = dirname(__FILE__) . '/pages/socialwire_marks';
    switch ($page[0]) {
        case 'show':
            gatekeeper();
            set_input($page[1], $page[2]);
            @include "$pages/show.php";
            break;
        case 'test':
            admin_gatekeeper();
            @include "$pages/test.php";
            break;
        case 'reset':
            admin_gatekeeper();
            @include "$pages/reset.php";
            break;
        default:
            include "$pages/show.php";
    }
}

function socialwire_marks_init() {

    elgg_register_entity_type('object', 'socialwire_mark');

    elgg_register_page_handler('socialwire_marks', 'socialwire_marks_page_handler');

   // Show marks in groups
   add_group_tool_option('socialwire_marks', elgg_echo('socialwire_marks:enable_group_marks'));

   elgg_register_action('socialwire_marks/save_marks', elgg_get_plugins_path() . 'socialwire_marks/actions/save_marks.php', 'logged_in');

    elgg_register_event_handler('delete', 'user', 'socialwire_marks_delete_user_marks');
    elgg_register_event_handler('delete', 'group', 'socialwire_marks_delete_group_marks');
    elgg_register_event_handler('delete', 'object', 'socialwire_marks_delete_task_marks');

    elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'socialwire_marks_owner_block_menu');
    elgg_extend_view('object/task', 'socialwire_marks/mark_menu');
    elgg_extend_view('object/test', 'socialwire_marks/mark_menu');
    elgg_extend_view('object/questionnaire', 'socialwire_marks/mark_menu');
}

elgg_register_event_handler('init', 'system', 'socialwire_marks_init');
?>