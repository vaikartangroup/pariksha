<?php
if (isset($_POST['update_btn_exam']) && isset($_POST['action']) && $_POST['action'] == 'update_exam_details') {
      // Retrieve exam ID from form submission
      $exam_id = intval($_POST['exam_id']);
      if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'edit_exam_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
      // Update values in the database
      $courseId = intval($_POST['courseId']);
      $examTitle = sanitize_text_field($_POST['examTitle']);
      $examDesc = sanitize_text_field($_POST['examDesc']);
      $examLimit = intval($_POST['examLimit']);
      $examQuestDipLimit = intval($_POST['examQuestDipLimit']);
  
      // Update query
      $result = $wpdb->update(
          $exam_table,
          array(
              'cou_id' => $courseId,
              'ex_title' => $examTitle,
              'ex_description' => $examDesc,
              'ex_time_limit' => $examLimit,
              'ex_questlimit_display' => $examQuestDipLimit
          ),
          array('ex_id' => $exam_id),
          array(
              '%d', // cou_id
              '%s', // ex_title
              '%s', // ex_description
              '%d', // ex_time_limit
              '%d' // ex_questlimit_display
          ),
          array('%d') // ex_id format
      );
  
      // Check if update was successful
      if ($result !== false) {
          echo '<script>window.location.reload();</script>'; // Reload the page
      } else {
          $error_message = $wpdb->last_error;
          echo '<script>alert("Failed to update exam details. Error: ' . $error_message . '"); window.location.reload();</script>'; // Display error message in alert and reload the page
      }
}
?>