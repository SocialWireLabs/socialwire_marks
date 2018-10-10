<?php
/*
 * © Copyright by Laboratorio de Redes 2012
 */

$user_id = elgg_extract('user_id', $vars, false);
if ($user_id) {
    $user_type = elgg_extract('user_type', $vars, 'student');
    $subjects = elgg_get_entities_from_relationship(array(
        'type' => 'group',
        'relationship' => 'member',
        'relationship_guid' => $user_id,'metadata_name_value_pairs' => array(array('name' => 'socialwire_marks_enable', 'value' => 'yes'))));
        if ($subjects) {
        foreach ($subjects as $key => $subject) {
            if (($subject->e_portfolio_enable == 'no' && $subject->task_enable == 'no' && $subject->test_enable == 'no' && $subject->questionnaire_enable == 'no') || socialwire_marks_is_professor($user_id, $subject->getGUID()))
                unset($subjects[$key]);
        }
        if ($subjects) {
            $count = count($subjects);
            $offset = get_input('offset', 0);
            set_input('offset', 0);
            $limit = get_input('limit', 10);
            $subjects = array_slice($subjects, $offset, $limit);
            foreach ($subjects as $subject) {
                $content .= '<div class="contentWrapper" style="margin-bottom: 10px;">';
                $content .= '<h2><a href="' . $subject->getURL() . '">' . $subject->name . '</a></h2>';
                $content .= elgg_view('socialwire_marks/subject_marks', array('student_id' => $user_id, 'user_type' => $user_type, 'subject_id' => $subject->getGUID()));
                $content .= '</div>';
            }
            $subjects_pagination = elgg_view('navigation/pagination', array('base_url' => $_SERVER['REQUEST_URI'], 'offset' => $offset, 'count' => $count, 'limit' => $limit));
        }
    }
}
?>
<div class="contentWrapper">
    <?php echo $content; ?>
    <?php echo $subjects_pagination; ?>
</div>
