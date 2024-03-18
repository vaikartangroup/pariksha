<?php
// Function to display user information using shortcode
function display_user_info_shortcode($atts)
{

    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . '../js/user.js', array('jquery'), '1.0', true);
    wp_localize_script(
        'custom-script',
        'plugin_vars',
        array(
            'ajax_url' => plugin_dir_url(__FILE__) . 'result_submission.php',
        )
    );

    $user_info = get_user_info();
    if ($user_info) {
        // User is logged in, display user information
        ob_start();

        $current_user_id = $user_info['user_id'];
        $atts = shortcode_atts(
            array(
                'exam_id' => '',
            ),
            $atts
        );

        $examination_id = $atts['exam_id'];
        if ($examination_id != '') {
            global $wpdb;
            // Getting the data from the attempt table to get the attempt information
            $table_exam_attempt = $wpdb->prefix . 'exam_attempt';
            // Construct SQL query
            $queryexamattempt = $wpdb->prepare("
                SELECT *
                FROM $table_exam_attempt
                WHERE exmne_id = %d
            ", $current_user_id);

            // Execute the query
            $results_exams_attempt = $wpdb->get_results($queryexamattempt);

            if (!$results_exams_attempt) {
                // Get the table name with prefix
                $table_exam = $wpdb->prefix . 'exam_tbl';
                // Construct SQL query
                $queryexam = $wpdb->prepare("
                            SELECT *
                            FROM $table_exam
                            WHERE ex_id = %d
                            ", $examination_id);

                // Execute the query
                $results_exams = $wpdb->get_results($queryexam);

                // Check if results are found
                if ($results_exams) {
                    // Loop through results
                    $num_rows_exam = count($results_exams);

                    if ($num_rows_exam == 1) {
                        foreach ($results_exams as $exam) {
                            // Access individual fields
                            $product_id = $exam->cou_id;

                            // Fetching the status
                            $table_orders = $wpdb->prefix . 'wc_orders';
                            $table_order_lookup = $wpdb->prefix . 'wc_order_product_lookup';
                            $query_status = $wpdb->prepare("
                            SELECT $table_orders.status, $table_orders.customer_id, $table_order_lookup.product_id
                            FROM $table_order_lookup
                            INNER JOIN $table_orders
                            ON $table_order_lookup.customer_id = $table_orders.customer_id
                            AND $table_order_lookup.order_id = $table_orders.id
                            WHERE $table_orders.status = 'wc-completed'
                            AND $table_orders.customer_id = %d
                            AND $table_order_lookup.product_id = %d
                        ", $current_user_id, $product_id);

                            // Execute the query
                            $results_status = $wpdb->get_results($query_status);

                            if ($results_status) {
                                // Loop through results
                                $num_rows_status = count($results_status);

                                if ($num_rows_status == 1) {
                                    foreach ($results_exams as $exam) {

                                        ?>
                                        <center>
                                            <div class="warning-box" id="warningBox">
                                                <br><br><br>

                                                <div class="icon-warning">
                                                    <h2>Are You Sure?</h2>
                                                </div>
                                                <div class="icon-warning">
                                                    <h5>You want to take this exam now, your time will start automatically!</h5>
                                                </div>
                                                <div style="gap:2%;">
                                                    <button type="button" class="swal2-confirm swal2-styled" id="start_exam"
                                                        style="display: inline-block; background-color: rgb(48, 133, 214); border-left-color: rgb(48, 133, 214); border-right-color: rgb(48, 133, 214); padding: 10px 20px; color: white; font-size: 16px; border: none; cursor: pointer; margin-right: 10px;"
                                                        aria-label="" onclick="showOtherContents()">Yes, start now!</button>

                                                    <button type="button" class="swal2-cancel swal2-styled"
                                                        style="display: inline-block; background-color: rgb(221, 51, 51); padding: 10px 20px; color: white; font-size: 16px; border: none; cursor: pointer;"
                                                        aria-label="" onclick="window.location.href = '<?php echo esc_url(home_url()); ?>';">Cancel</button>
                                                </div>
                                                <br><br><br>
                                            </div>
                                            <div id="mainContents" style="display:none">
                                                <!-- Question Fields -->
                                                <div class="app-main__inner">
                                                    <div class="col-md-12">
                                                        <div class="app-page-title">
                                                            <div class="page-title-wrapper">
                                                                <div class="page-title-heading">
                                                                    <div>
                                                                        <h3>
                                                                            <?php echo $exam->ex_title; ?>
                                                                        </h3>
                                                                        <div class="page-title-subheading">
                                                                            <h5>
                                                                                <?php echo $exam->ex_description; ?>
                                                                            </h5>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="page-title-actions mr-5" style="font-size: 20px;">
                                                                    <form name="cd">
                                                                        <input type="hidden" name="" id="timeExamLimit"
                                                                            value="<?php echo $exam->ex_time_limit; ?>">
                                                                        <label>Remaining Time : </label>
                                                                        <input style="border:none;background-color: transparent;color:blue;" name="disp"
                                                                            type="text" class="clock" id="txt" value="00:00" size="4" readonly="true" />minutes
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-12 p-0 mb-4 question-list">
                                                        <center>
                                                            <form method="post" id="submitAnswerFrm">
                                                                <input type="hidden" name="exam_id" id="exam_id" value="<?php echo $examination_id; ?>">
                                                                <input type="hidden" name="examAction" id="examAction">
                                                                <input type="hidden" name="examineeid" id="examineeid" value="<?php echo $current_user_id; ?>">
                                                                <table class="align-middle mb-0 table table-borderless table-striped table-hover"
                                                                    id="tableList">
                                                                    <?php
                                                                    $exam_questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}exam_question_tbl WHERE exam_id = %d ORDER BY RAND() LIMIT %d", $examination_id, $exam->ex_questlimit_display));
                                                                    if (!empty ($exam_questions)) {
                                                                        $i = 1;
                                                                        foreach ($exam_questions as $question) { ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <p><b>
                                                                                            <?php echo $i++; ?> .)

                                                                                            <?php echo $question->exam_question; ?>
                                                                                            <?php if (!empty ($question->question_image)) {
                                                                                                echo '<br><img src="' . $question->question_image . '" style="width: 50%;">';
                                                                                            }
                                                                                            ?>
                                                                                        </b></p>
                                                                                    <div class="row">
                                                                                        <div class="col-lg-6 form-group pl-4 ">
                                                                                            <input name="answer[<?php echo $question->eqt_id; ?>][correct]"
                                                                                                value="<?php echo $question->exam_ch1; ?>" class="form-check-input"
                                                                                                type="radio" value="" id="invalidCheck" required>

                                                                                            <label class="form-check-label" for="invalidCheck">
                                                                                                <?php echo $question->exam_ch1; ?>
                                                                                            </label>
                                                                                        </div>

                                                                                        <div class="col-lg-6 form-group pl-4">
                                                                                            <input name="answer[<?php echo $question->eqt_id; ?>][correct]"
                                                                                                value="<?php echo $question->exam_ch2; ?>" class="form-check-input"
                                                                                                type="radio" value="" id="invalidCheck" required>

                                                                                            <label class="form-check-label" for="invalidCheck">
                                                                                                <?php echo $question->exam_ch2; ?>
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="row">
                                                                                        <div class="col-lg-6 form-group pl-4">
                                                                                            <input name="answer[<?php echo $question->eqt_id; ?>][correct]"
                                                                                                value="<?php echo $question->exam_ch3; ?>" class="form-check-input"
                                                                                                type="radio" value="" id="invalidCheck" required>

                                                                                            <label class="form-check-label" for="invalidCheck">
                                                                                                <?php echo $question->exam_ch3; ?>
                                                                                            </label>
                                                                                        </div>

                                                                                        <div class="col-lg-6 form-group pl-4">
                                                                                            <input name="answer[<?php echo $question->eqt_id; ?>][correct]"
                                                                                                value="<?php echo $question->exam_ch4; ?>" class="form-check-input"
                                                                                                type="radio" value="" id="invalidCheck" required>

                                                                                            <label class="form-check-label" for="invalidCheck">
                                                                                                <?php echo $question->exam_ch4; ?>
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        <?php }
                                                                        ?>
                                                                        <tr>
                                                                            <td style="padding: 20px;" class="row">
                                                                                <div class="col-lg-2">
                                                                                    <input name="submit" type="submit" value="Submit" id="submitAnswerFrmBtn"
                                                                                        style="background-color: #4CAF50; border: none; color: white; padding: 15px 32px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; transition-duration: 0.4s; cursor: pointer;">
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                        <?php
                                                                    } else { ?>
                                                                        <b>No questions available at this moment</b>
                                                                    <?php } ?>
                                                                </table>

                                                            </form>
                                                        </center>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                function showOtherContents() {
                                                    var warningBox = document.getElementById('warningBox');
                                                    var mainContents = document.getElementById('mainContents');

                                                    if (warningBox && mainContents) {
                                                        warningBox.style.display = 'none';
                                                        mainContents.style.display = 'block';
                                                    }
                                                }

                                            </script>
                                        </center>
                                        <?php
                                        return ob_get_clean();
                                    }
                                } else {
                                    return '<center><h3>Error: 0x00Va002. <br>Please contact administrator.</h3></center>';
                                }
                            } else {
                                return '<img src="../wp-content/uploads/enroll.png" style="width: 100%; display: block;">';
                            }
                        }
                    } else {
                        return '<center><h3>Error: 0x00Va001. <br>Please contact administrator.</h3></center>';
                    }
                } else {
                    return '<center><h3>Error: 0x00Va000.<br> Please contact administrator.</h3></center>';
                }
            } else {
                // Get the table name with prefix
                $table_exam = $wpdb->prefix . 'exam_tbl';
                // Construct SQL query
                $queryexam = $wpdb->prepare("
                                            SELECT *
                                            FROM $table_exam
                                            WHERE ex_id = %d
                                            ", $examination_id);

                // Execute the query
                $results_exams = $wpdb->get_results($queryexam);
                if ($results_exams) {
                    // Loop through results
                    $num_rows_exam = count($results_exams);

                    if ($num_rows_exam == 1) {
                        foreach ($results_exams as $exam) {
                            wp_enqueue_style('pariksha-plugin-main-style', plugins_url('../css/main_pariksha.css', __FILE__));

                            ?>
                            <div class="app-main__inner">
                                <div id="refreshData">

                                    <div class="col-md-12">
                                        <center>
                                        <div class="col-md-12" style="margin-left: 2%;">
                                        <h2><?php echo $exam->ex_title; ?></h2>
                                        <p><?php echo $exam->ex_description; ?></p>

                                            <h4 class="text-primary">RESULTS</h4>
                                        </div>
                                        </center>
                                        <div class="row" style="margin: 2%">
                                            <div class="col-md-6">
                                                <div class="main-card mb-3 card">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Your Answer's</h5>
                                                        <table class="align-middle mb-0 table table-borderless table-striped table-hover"
                                                            id="tableList">
                                                            <?php
                                                            $selQuest = $wpdb->get_results($wpdb->prepare("
                                SELECT * 
                                FROM {$wpdb->prefix}exam_question_tbl eqt 
                                INNER JOIN {$wpdb->prefix}exam_answers ea 
                                ON eqt.eqt_id = ea.quest_id 
                                WHERE eqt.exam_id = %d 
                                AND ea.axmne_id = %d 
                                AND ea.exans_status = 'new'
                            ", $examination_id, $current_user_id));

                                                            $i = 1;
                                                            foreach ($selQuest as $selQuestRow) {
                                                                ?>
                                                                <tr>
                                                                    <td>
                                                                        <b>
                                                                            <p>
                                                                                <?php echo $i++; ?> .)
                                                                                <?php echo $selQuestRow->exam_question; ?>
                                                                            </p>
                                                                        </b>
                                                                        <label class="pl-4 text-success">
                                                                            Answer :
                                                                            <?php
                                                                            if ($selQuestRow->exam_answer != $selQuestRow->exans_answer) {
                                                                                ?>
                                                                                <span style="color:red">
                                                                                    <?php echo $selQuestRow->exans_answer; ?>
                                                                                </span>
                                                                                <?php
                                                                            } else {
                                                                                ?>
                                                                                <span class="text-success">
                                                                                    <?php echo $selQuestRow->exans_answer; ?>
                                                                                </span>
                                                                                <?php
                                                                            }
                                                                            ?>
                                                                        </label>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                            }
                                                            ?>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="row">
                                                    <div class="col-md-5">
                                                        <div class="card mb-3 widget-content bg-night-fade">
                                                            <div class="widget-content-wrapper text-white">
                                                                <div class="widget-content-left">
                                                                    <div class="widget-heading">
                                                                        <h5>Score</h5>
                                                                    </div>
                                                                    <div class="widget-subheading" style="color: transparent;">/</div>
                                                                </div>
                                                                <div class="widget-content-right">
                                                                    <div class="widget-numbers text-white">
                                                                        <?php
                                                                        $selScore = $wpdb->get_results($wpdb->prepare("
                                        SELECT * 
                                        FROM {$wpdb->prefix}exam_question_tbl eqt 
                                        INNER JOIN {$wpdb->prefix}exam_answers ea 
                                        ON eqt.eqt_id = ea.quest_id 
                                        AND eqt.exam_answer = ea.exans_answer  
                                        WHERE ea.axmne_id = %d 
                                        AND ea.exam_id = %d 
                                        AND ea.exans_status = 'new'
                                    ", $current_user_id, $examination_id));
                                                                        ?>
                                                                        <span>
                                                                            <?php echo count($selScore); ?>
                                                                            <?php
                                                                            $over = $exam->ex_questlimit_display;
                                                                            ?>
                                                                        </span> /
                                                                        <?php echo $over; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="card mb-3 widget-content bg-happy-green">
                                                            <div class="widget-content-wrapper text-white">
                                                                <div class="widget-content-left">
                                                                    <div class="widget-heading">
                                                                        <h5>Percentage</h5>
                                                                    </div>
                                                                    <div class="widget-subheading" style="color: transparent;">/</div>
                                                                </div>
                                                                <div class="widget-content-right">
                                                                    <div class="widget-numbers text-white">
                                                                        <?php
                                                                        $selScore = $wpdb->get_results($wpdb->prepare("
                                        SELECT * 
                                        FROM {$wpdb->prefix}exam_question_tbl eqt 
                                        INNER JOIN {$wpdb->prefix}exam_answers ea 
                                        ON eqt.eqt_id = ea.quest_id 
                                        AND eqt.exam_answer = ea.exans_answer  
                                        WHERE ea.axmne_id = %d 
                                        AND ea.exam_id = %d 
                                        AND ea.exans_status = 'new'
                                    ", $current_user_id, $examination_id));

                                                                        $score = count($selScore);
                                                                        $ans = $score / $over * 100;
                                                                        echo number_format($ans, 2);
                                                                        echo "%";
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        return '<center><h3>Error: 0x00Va001. <br>Please contact administrator.</h3></center>';
                    }
                } else {
                    return '<center><h3>Error: 0x00Va000.<br> Please contact administrator.</h3></center>';
                }


                // return '<center><h3>Error: 0x00Va003.<br> You have already given this exam. You are not allowed to give it again</h3></center>';
            }
        } else {
            return '<img src="../wp-content/uploads/exam.png" style="width: 100%; display: block;">';
        }
    } else {
        // User is not logged in
        return '<img src="../wp-content/uploads/login.png" style="width: 100%; display: block;">';
    }


}
add_shortcode('display_user_info', 'display_user_info_shortcode');
?>