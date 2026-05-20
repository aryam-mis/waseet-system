<?php
session_start();

$conn = new mysqli("localhost","root","","wasit_system");

if($conn->connect_error){
    die("فشل الاتصال");
}

$errors = [];
$success = "";

function checkConflict($conn, $case_id, $date, $time){

    $newTime = strtotime(
        date("Y-m-d", strtotime($date))." ".$time
    );

    $oneHour = 3600;

    $handlerQuery = $conn->query("
        SELECT employee_id
        FROM casehandlers
        WHERE case_id = $case_id
        LIMIT 1
    ");

    $handlerData = $handlerQuery->fetch_assoc();

    if(!$handlerData){
        return "لا يوجد معالج لهذه الشكوى";
    }

    $handler_id = $handlerData['employee_id'];

    $q1 = $conn->query("
        SELECT s.session_date, s.session_time
        FROM sessions s
        JOIN casehandlers ch ON s.case_id = ch.case_id
        WHERE ch.employee_id = $handler_id
        AND s.case_id != $case_id
    ");

    while($r = $q1->fetch_assoc()){

        $existingTime = strtotime(
            date("Y-m-d", strtotime($r['session_date']))
            ." ".
            $r['session_time']
        );

        if(abs($newTime - $existingTime) < $oneHour){
            return "تضارب مع جلسة أخرى لنفس المعالج";
        }
    }

    $parties = [];

    $partyQuery = $conn->query("
        SELECT employee_id
        FROM caseparties
        WHERE case_id = $case_id
        AND employee_id IS NOT NULL
    ");

    while($p = $partyQuery->fetch_assoc()){
        $parties[] = $p['employee_id'];
    }

    if(!empty($parties)){

        $partyList = implode(",", $parties);

        $q2 = $conn->query("
            SELECT s.session_date, s.session_time
            FROM sessions s
            JOIN caseparties cp ON s.case_id = cp.case_id
            WHERE cp.employee_id IN ($partyList)
            AND s.case_id != $case_id
        ");

        while($r = $q2->fetch_assoc()){

            $existingTime = strtotime(
                date("Y-m-d", strtotime($r['session_date']))
                ." ".
                $r['session_time']
            );

            if(abs($newTime - $existingTime) < $oneHour){
                return "تضارب مع أحد أطراف الجلسة";
            }
        }
    }

    $q3 = $conn->query("
        SELECT session_date, session_time
        FROM sessions
        WHERE case_id = $case_id
    ");

    while($r = $q3->fetch_assoc()){

        $existingTime = strtotime(
            date("Y-m-d", strtotime($r['session_date']))
            ." ".
            $r['session_time']
        );

        if(abs($newTime - $existingTime) < $oneHour){
            return "هذه الشكوى لديها جلسة قريبة بنفس الوقت";
        }
    }

    return false;
}

if(isset($_POST['save'])){

    $case_id = intval($_POST['case_id']);
    $session_date = $_POST['session_date'];
    $session_time = $_POST['session_time'];
    $location = $_POST['location'];

    $datetime = strtotime("$session_date $session_time");

    if($datetime <= time()){
        $errors[] = "لا يمكن إنشاء جلسة في الماضي";
    }

    $hour = date("H", strtotime($session_time));

    if($hour < 8 || $hour > 23){
        $errors[] = "الوقت يجب أن يكون بين 8 صباحًا و 12 منتصف الليل";
    }

    $checkHandlerExists = $conn->query("
        SELECT 1
        FROM casehandlers
        WHERE case_id = $case_id
        LIMIT 1
    ");

    if($checkHandlerExists->num_rows == 0){
        $errors[] = "لا يوجد معالج لهذه الشكوى";
    }

    $checkCaseSession = $conn->query("
        SELECT 1
        FROM sessions
        WHERE case_id = $case_id
    ");

    if($checkCaseSession->num_rows > 0){
        $errors[] = "هذه الشكوى لديها جلسة مسبقًا";
    }

    $conflict = checkConflict(
        $conn,
        $case_id,
        $session_date,
        $session_time
    );

    if($conflict){
        $errors[] = $conflict;
    }

    if(empty($errors)){

        $stmt = $conn->prepare("
            INSERT INTO sessions
            (
                case_id,
                session_date,
                session_time,
                location_details,
                settlement_status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                'PENDING'
            )
        ");

        $stmt->bind_param(
            "isss",
            $case_id,
            $session_date,
            $session_time,
            $location
        );

        $stmt->execute();

        $success = "تم إنشاء الجلسة بنجاح";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إنشاء جلسة</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f7fb;
    font-family:'Cairo',sans-serif;
}

.container{
    max-width:600px;
    margin-top:60px;
}

.box{
    background:#fff;
    padding:25px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

button{
    background-color: #01251a;
    border-radius:10px;
}
</style>
</head>

<body>

<div class="container">

<div class="box">

<h4 class="text-center mb-4">إنشاء جلسة جديدة</h4>

<?php
if(!empty($errors)){
    foreach($errors as $e){
        echo "<div class='alert alert-danger'>$e</div>";
    }
}

if($success){
    echo "<div class='alert alert-success'>$success</div>";
}
?>

<form method="POST">

<div class="mb-3">
<label>اختر الشكوى</label>
<select name="case_id" class="form-control" required>

<option value="">-- اختر --</option>

<?php
$cases = $conn->query("
SELECT case_id, title
FROM cases
");

while($c = $cases->fetch_assoc()){
    echo "<option value='{$c['case_id']}'>#{$c['case_id']} - {$c['title']}</option>";
}
?>

</select>
</div>

<div class="mb-3">
<label>تاريخ الجلسة</label>
<input type="date" name="session_date" class="form-control"
min="<?= date('Y-m-d') ?>" required>
</div>

<div class="mb-3">
<label>وقت الجلسة</label>
<input type="time" name="session_time" class="form-control"
min="08:00" max="23:59" required>
</div>

<div class="mb-3">
<label>رابط / موقع الجلسة</label>
<input type="text" name="location" class="form-control" required>
</div>

<button type="submit" name="save" class="btn btn-dark w-100" style="background:#01251a">
إنشاء الجلسة
</button>

</form>

</div>

<div class="text-center mt-3">
<a href="legal_dashboardM.php" class="btn btn-secondary" style="background:#01251a">⬅ العودة</a>
</div>

</div>

</body>
</html>