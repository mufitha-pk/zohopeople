<?php
include "db.php"; // mysqli connection

date_default_timezone_set("Asia/Kolkata");

/* =============================
   STEP 1: GET ACTIVE BIRTHDAY WORKFLOWS
============================= */

$wf_sql = "
SELECT * FROM workflows
WHERE status = 'Active'
AND action_based_events = 'Birthday'
";

$wf_result = mysqli_query($conn, $wf_sql);

if (mysqli_num_rows($wf_result) == 0) {
    exit("No active birthday workflows found");
}

/* =============================
   STEP 2: LOOP EACH WORKFLOW
============================= */

while ($workflow = mysqli_fetch_assoc($wf_result)) {

    $email_alert_id = $workflow['action_reference_id'];

    /* =============================
       STEP 3: GET EMAIL ALERT
    ============================= */

    $alert_sql = "SELECT * FROM email_alerts WHERE id = $email_alert_id";
    $alert_res = mysqli_query($conn, $alert_sql);

    if (mysqli_num_rows($alert_res) == 0) {
        continue;
    }

    $email_alert = mysqli_fetch_assoc($alert_res);

    $template_id = $email_alert['template_id'];

    /* =============================
       STEP 4: GET EMAIL TEMPLATE
    ============================= */

    $tpl_sql = "SELECT * FROM email_templates WHERE id = $template_id";
    $tpl_res = mysqli_query($conn, $tpl_sql);

    if (mysqli_num_rows($tpl_res) == 0) {
        continue;
    }

    $template = mysqli_fetch_assoc($tpl_res);

    /* =============================
       STEP 5: FIND TODAY'S BIRTHDAYS
    ============================= */

    $today = date('m-d');

    $emp_sql = "
    SELECT emp_id, first_name, email_id
    FROM employee_registration
    WHERE employee_status = 'active'
    AND DATE_FORMAT(birthdate,'%m-%d') = '$today'
    ";

    $emp_res = mysqli_query($conn, $emp_sql);

    if (mysqli_num_rows($emp_res) == 0) {
        continue;
    }

    /* =============================
       STEP 6: SEND EMAIL + INSERT FEED
    ============================= */

    while ($emp = mysqli_fetch_assoc($emp_res)) {

        // 🔹 Replace merge fields
        $message = str_replace(
            '$first_name',
            $emp['first_name'],
            $template['message']
        );

        // 🔹 Send Email
        mail(
            $emp['email_id'],
            $email_alert['subject'],
            $message,
            "From: " . $email_alert['from_email']
        );

        // 🔹 Insert Feed
        $feed_text = "🎉 Happy Birthday {$emp['first_name']}! – Kyoto Team";

        $feed_sql = "
        INSERT INTO feeds (emp_id, actor_id, module, action_text, created_at)
        VALUES (
            {$emp['emp_id']},
            1,
            'Birthday',
            '$feed_text',
            NOW()
        )";

        mysqli_query($conn, $feed_sql);
    }
}

echo "Birthday workflow executed successfully";
$conn->close();
