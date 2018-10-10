<?php
/*
 * © Copyright by Laboratorio de Redes 2012
 */

$user_type = elgg_extract('user_type', $vars, 'student');
$mark = elgg_extract('entity', $vars, false);
if (!$mark)
    return true;

$student_id = is_array($mark) ? $mark['student'] : $mark->student_guid;
$student = get_entity($student_id);
$student_name = $student->name;
$student_icon = elgg_view_entity_icon($student, 'tiny');
$professor_id = is_array($mark) ? $mark['owner'] : $mark->getOwnerGUID();
$professor = get_entity($professor_id);
$professor_name = $professor->name;
$professor_icon = elgg_view_entity_icon($professor, 'tiny');
$mark_value_view = elgg_view('socialwire_marks/mark_value', array('mark' => $mark, 'user_type' => $user_type));
?>
<tr>
    <td width="5%"><?php echo $student_icon; ?></td>
    <td width="35%"><?php echo $student_name; ?></td>
    <td width="5%"><?php echo $professor_icon; ?></td>
    <td width="35%"><span style="font-style:italic;"><?php echo $professor_name; ?></span></td>
    <td><?php echo $mark_value_view; ?></td>
</tr>            

