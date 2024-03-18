<?php
function export_exam_results_callback()
{
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to export exam results.', 403);
    }

    // Check if exam ID is provided in the request
    if (!isset($_POST['exam_id'])) {
        wp_send_json_error('Exam ID is missing.', 400);
    }

    // Get exam ID from the request
    $exam_id = intval($_POST['exam_id']);

    // Fetch exam details from the database based on the exam ID
    global $wpdb;
    $exam_table = $wpdb->prefix . 'exam_tbl';
    $exam_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM $exam_table WHERE ex_id = %d", $exam_id));

    // Check if exam details are found
    if (!$exam_details) {
        wp_send_json_error('Exam details not found.', 404);
    }

    // Fetch exam results from another table in the database
    $exam_results_table = $wpdb->prefix . 'exam_answers';
    $exam_results = $wpdb->get_results($wpdb->prepare("SELECT distinct axmne_id FROM $exam_results_table WHERE exam_id = %d", $exam_id), ARRAY_A);

    // Check if exam results are found
    if (!$exam_results) {
        wp_send_json_error('No results found for this exam.', 404);
    }

    // Prepare exam data
    $export_data = array();
    $export_data[] = array('Name', 'Score', 'Percentage');
    foreach ($exam_results as $result) {
        $selScore = $wpdb->get_results($wpdb->prepare("
            SELECT COUNT(*) as count, display_name
            FROM {$wpdb->prefix}exam_question_tbl eqt
            INNER JOIN {$wpdb->prefix}exam_answers ea 
            INNER JOIN {$wpdb->users} us ON
            us.ID = ea.axmne_id AND
            eqt.eqt_id = ea.quest_id AND eqt.exam_answer = ea.exans_answer
            WHERE ea.axmne_id = %d
            AND ea.exam_id = %d
            AND ea.exans_status = 'new'
        ", $result['axmne_id'], $exam_id));

        foreach ($selScore as $scoreRow) {
            $over = $scoreRow->count . '/' . $exam_details->ex_questlimit_display;
            $score = $scoreRow->count;
            $ans = $score / $exam_details->ex_questlimit_display * 100;
            $percentage = number_format($ans, 2) . '%';

            $export_data[] = array($scoreRow->display_name, $over, $percentage);
        }
    }

    // Return exam data
    wp_send_json_success($export_data);
}

// AJAX Handler to Export Exam Results
add_action('wp_ajax_export_exam_results', 'export_exam_results_callback');
?>
