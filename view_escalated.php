<?php
session_start();

// شرط: الصفحة للإدارة العليا فقط
if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "الإدارة العليا"){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");

if($conn->connect_error){
    die("فشل الاتصال");
}

// شرط: الكود يشتغل بس لو ضغطوا زر "حفظ القرار"
if(isset($_POST['save_decision'])){

    $case_id = intval($_POST['case_id']);
    $decision = trim($_POST['decision']);

    // شرط: لو حقل القرار فاضي نوقف
    if(empty($decision)){
        echo "<script>alert('يجب كتابة القرار'); location.href=location.href;</script>";
        exit();
    }

    // استعلام: نتحقق لو في قرار مسبق لهذي الشكوى في finaldecisions
    $check = $conn->query("
    SELECT decision_id
    FROM finaldecisions
    WHERE case_id=$case_id
    ")->fetch_assoc();

    // شرط: لو في قرار مسبق نحدّثه، لو لأ نضيف جديد
    if($check){
        // استعلام: نحدث القرار الموجود
        $conn->query("
        UPDATE finaldecisions
        SET decision_text='$decision',
        decided_by_employee_id=".$_SESSION['user_id']."
        WHERE case_id=$case_id
        ");

    }else{
        // استعلام: نضيف قرار نهائي جديد في finaldecisions
        $stmt = $conn->prepare("
        INSERT INTO finaldecisions
        (case_id, decided_by_employee_id, decision_text)
        VALUES (?, ?, ?)
        ");

        $stmt->bind_param("iis",$case_id,$_SESSION['user_id'],$decision);
        $stmt->execute();
    }

    // استعلام: نغير حالة الشكوى لـ CLOSED_DECIDED بعد إصدار القرار
    $conn->query("
    UPDATE cases
    SET status='CLOSED_DECIDED'
    WHERE case_id=$case_id
    ");

    echo "<script>alert('تم إصدار القرار النهائي'); location.href=location.href;</script>";
    exit();
}

// استعلام: نجيب الشكاوى مع التوصية والقرار النهائي كـ subqueries
// الفلتر افتراضياً يعرض المصعدة
$filter = $_GET['filter'] ?? 'escalated';

$query = "
SELECT c.*,

(
SELECT settlement_text
FROM sessions s
WHERE s.case_id = c.case_id
AND s.settlement_text IS NOT NULL
ORDER BY s.session_id DESC
LIMIT 1
) AS recommendation,

(
SELECT decision_text
FROM finaldecisions fd
WHERE fd.case_id = c.case_id
LIMIT 1
) AS final_decision

FROM cases c
WHERE 1
";

// شرط: نضيف فلتر الحالة حسب اختيار المستخدم
if($filter == 'escalated'){
    $query .= " AND c.status='ESCALATED'";
}
elseif($filter == 'decided'){
    $query .= " AND c.status='CLOSED_DECIDED'";
}

$query .= " ORDER BY c.case_id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>

<meta charset="UTF-8">
<title>الإدارة العليا</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background: radial-gradient(circle at top right,#ffffff,#f6fbff,#f8f5ff,#f3fff8);
    color:#111827;
    font-family:'Cairo',sans-serif;
    font-weight:600;
}

h3{
    font-weight:800;
    color:#01251a;
}

.table{
    background:#ffffff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,0.06);
}

.table th{
    background: linear-gradient(90deg,#a7e6d3);
    color:#111827;
    font-weight:800;
}

.table td{
    vertical-align:middle;
}

.box{
    background:#fff;
    padding:10px;
    border-radius:12px;
    margin-bottom:8px;
    border:1px solid rgba(0,0,0,0.06);
}

.modal-content{
    border-radius:20px;
}

.btn-info{
    background:#01251a;
    border:none;
    color: white;
}

.btn-success{
    border:none;
}

.badge{
    padding:8px 12px;
    font-size:12px;
}

</style>

</head>

<body>

<div class="container mt-5">

<h3 class="text-center mb-4">الإدارة العليا</h3>

<form method="GET" class="mb-4 text-center">

<select name="filter" class="form-control w-50 mx-auto" onchange="this.form.submit()">

<option value="escalated" <?= $filter=='escalated'?'selected':'' ?>>
الشكاوى المصعدة
</option>

<option value="decided" <?= $filter=='decided'?'selected':'' ?>>
تم إصدار قرار نهائي
</option>

</select>

</form>

<table class="table table-striped text-center">

<thead>

<tr>
<th>رقم</th>
<th>العنوان</th>
<th>حالة التصعيد</th>
<th>القبول</th>
<th>الرفض</th>
<th>التوصية</th>
<th>القرار النهائي</th>
<th>تفاصيل</th>
<th>إجراء</th>
</tr>

</thead>

<tbody>

<?php while($row = $result->fetch_assoc()){ ?>

<?php

$case_id = $row['case_id'];

$accept_html = "";
$reject_html = "";
$party_html = "";

$latest_session = $conn->query("
SELECT session_id
FROM sessions
WHERE case_id = $case_id
ORDER BY session_id DESC
LIMIT 1
")->fetch_assoc();

$session_id = $latest_session['session_id'] ?? 0;

$parties = $conn->query("
SELECT
cp.party_role,
cp.party_type,
e.full_name,
c.external_party_name,
sr.response

FROM caseparties cp

LEFT JOIN employees e
ON cp.employee_id = e.employee_id

LEFT JOIN cases c
ON c.case_id = cp.case_id

LEFT JOIN settlementresponses sr
ON sr.employee_id = cp.employee_id
AND sr.session_id = $session_id

WHERE cp.case_id = $case_id
");

while($p = $parties->fetch_assoc()){

    if($p['party_type'] == 'موظف'){
        $name = $p['full_name'];
    }else{
        $name = $p['external_party_name'];
    }

    if($p['response'] == 'ACCEPT'){

        $accept_html .= "
        <div class='box'>
        <strong>$name</strong><br>
        {$p['party_role']}
        </div>";

        $status = "قبول";
    }
    elseif($p['response'] == 'REJECT'){

        $reject_html .= "
        <div class='box'>
        <strong>$name</strong><br>
        {$p['party_role']}
        </div>";

        $status = "رفض";
    }
    else{

        $status = "قيد الانتظار";
    }

    $party_html .= "
    <div class='box'>
    <strong>$name</strong><br>
    {$p['party_role']} - {$p['party_type']}<br>
    <span class='badge bg-info'>$status</span>
    </div>";
}
?>

<tr>

<td><?= $case_id ?></td>

<td><?= $row['title'] ?></td>

<td>
<?php if($row['status']=='ESCALATED'){ ?>
<span class='badge bg-danger'>مصعدة</span>
<?php } else { ?>
<span class='badge bg-success'>غير مصعدة</span>
<?php } ?>
</td>

<td>
<?= $accept_html ?: "<span class='text-muted'>لا يوجد</span>" ?>
</td>

<td>
<?= $reject_html ?: "<span class='text-muted'>لا يوجد</span>" ?>
</td>

<td>
<?= !empty($row['recommendation'])
? "<span class='badge bg-success'>تم اصدار التوصية</span>"
: "<span class='badge bg-warning'>لم يتم اصدار التوصية</span>"
?>
</td>

<td>
<?= !empty($row['final_decision'])
? "<span class='badge bg-success'>تم إصدار القرار</span>"
: "<span class='badge bg-secondary'>لم يتم اصدار قرار</span>"
?>
</td>
<td>
<button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#d<?= $case_id ?>">
عرض
</button>
</td>

<td>
<?php if($row['status']=='ESCALATED'){ ?>
<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#dec<?= $case_id ?>">
اصدار القرار النهائي 
</button>
<?php } ?>
</td>

</tr>

<!-- Modal التفاصيل -->
<div class="modal fade" id="d<?= $case_id ?>" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">تفاصيل الشكوى رقم <?= $case_id ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div class="row">

<div class="col-md-6">

<div class="box">
<h5>بيانات الشكوى</h5>
<hr>

<p><strong>العنوان:</strong> <?= $row['title'] ?></p>

<?php
$handler_det = $conn->query("
    SELECT e.full_name 
    FROM casehandlers ch
    JOIN employees e ON ch.employee_id=e.employee_id
    WHERE ch.case_id={$row['case_id']} LIMIT 1
")->fetch_assoc();
?>
<p><strong>المعالج:</strong> <?= !empty($handler_det['full_name']) ? htmlspecialchars($handler_det['full_name']) : '<span class="text-muted">غير معين</span>' ?></p>

<p><strong>الوصف:</strong><br>
<?= nl2br($row['description']) ?>
</p>

<p>
<strong>الحالة:</strong>
<span class="badge bg-info">
<?= $row['status'] ?>
</span>
</p>

<?php if(!empty($row['recommendation'])){ ?>
<p>
<strong>التوصية:</strong><br>
<?= nl2br($row['recommendation']) ?>
</p>
<?php } ?>

<?php if(!empty($row['final_decision'])){ ?>
<p>
<strong>القرار النهائي:</strong><br>
<?= nl2br($row['final_decision']) ?>
</p>
<?php } ?>

</div>

</div>

<div class="col-md-6">

<div class="box">
<h5>الأطراف</h5>
<hr>

<?php

$party_details = $conn->query("
SELECT 
cp.party_role,
cp.party_type,
e.full_name,
e.email,

c.external_party_name,
sr.response,
sr.reject_reason

FROM caseparties cp

LEFT JOIN employees e
ON cp.employee_id = e.employee_id

LEFT JOIN cases c
ON c.case_id = cp.case_id

LEFT JOIN settlementresponses sr
ON sr.employee_id = cp.employee_id
AND sr.session_id = $session_id

WHERE cp.case_id = $case_id
");

while($pp = $party_details->fetch_assoc()){

    if($pp['party_type'] == 'موظف'){
        $party_name = $pp['full_name'];
    }else{
        $party_name = $pp['external_party_name'];
    }

    if($pp['response'] == 'ACCEPT'){
        $resp_badge = "<span class='badge bg-success'>موافقة</span>";
    }
    elseif($pp['response'] == 'REJECT'){
        $resp_badge = "<span class='badge bg-danger'>رفض</span>";
    }
    else{
        $resp_badge = "<span class='badge bg-warning text-dark'>بانتظار الرد</span>";
    }

?>

<div class="box mb-2">

<p><strong>الاسم:</strong> <?= $party_name ?></p>

<p>
<strong>الدور:</strong>
<span class="badge bg-primary">
<?= $pp['party_role'] ?>
</span>
</p>

<p>
<strong>النوع:</strong>
<span class="badge bg-secondary">
<?= $pp['party_type'] ?>
</span>
</p>

<p><?= $resp_badge ?></p>

<?php if(!empty($pp['reject_reason'])){ ?>
<p>
<strong>تعليق الرد:</strong><br>
<?= nl2br($pp['reject_reason']) ?>
</p>
<?php } ?>

<?php if(!empty($pp['email'])){ ?>
<p><strong>البريد:</strong> <?= $pp['email'] ?></p>
<?php } ?>

<?php if(!empty($pp['phone'])){ ?>
<p><strong>الهاتف:</strong> <?= $pp['phone'] ?></p>
<?php } ?>

</div>

<?php } ?>

</div>

</div>

</div>

<hr>

<div class="box">

<h5>تفاصيل الجلسة</h5>
<hr>

<?php

$session_details = $conn->query("
SELECT *
FROM sessions
WHERE case_id = $case_id
ORDER BY session_id DESC
LIMIT 1
")->fetch_assoc();

if($session_details){

?>

<div class="row">

<div class="col-md-4">
<p><strong>رقم الجلسة:</strong> <?= $session_details['session_id'] ?></p>
</div>

<div class="col-md-4">
<p><strong>تاريخ الجلسة:</strong> <?= $session_details['session_date'] ?></p>
</div>

<div class="col-md-4">
<p><strong>وقت الجلسة:</strong> <?= $session_details['session_time'] ?></p>
</div>

<div class="col-md-6">
<?= $session_details['location_details'] ?></p>
</div>

<div class="col-md-6">
<p><strong>🔗 رابط الجلسة:</strong> <a href="https://<?= $session_details['location_details'] ?>" target="_blank"><?= $session_details['location_details'] ?></a></p>
</div>

<?php if(!empty($session_details['settlement_text'])){ ?>

<div class="col-12">
<p>
<strong>نص التسوية / التوصية:</strong><br>
<?= nl2br($session_details['settlement_text']) ?>
</p>
</div>

<?php } ?>

<?php if(!empty($session_details['settlement_status'])){ ?>

<div class="col-12">
<p>
<strong>حالة التسوية:</strong>
<span class="badge bg-dark">
<?= $session_details['settlement_status'] ?>
</span>
</p>
</div>

<?php } ?>

</div>

<?php } else { ?>

<div class="alert alert-warning">
لا توجد جلسات مسجلة لهذه الشكوى
</div>

<?php } ?>

</div>

</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
إغلاق
</button>
</div>

</div>
</div>
</div>

<!-- Modal القرار النهائي -->
<div class="modal fade" id="dec<?= $case_id ?>" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<form method="POST">

<div class="modal-header">
<h5 class="modal-title">إصدار القرار النهائي</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="case_id" value="<?= $case_id ?>">

<label class="mb-2">القرار النهائي</label>

<textarea 
name="decision"
class="form-control"
rows="6"
required
><?= $row['final_decision'] ?></textarea>

</div>

<div class="modal-footer">

<button type="submit" name="save_decision" class="btn btn-success">
حفظ القرار
</button>

<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
إغلاق
</button>

</div>

</form>

</div>
</div>
</div>

<div class="modal fade" id="dec<?= $case_id ?>" tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<form method="POST">

<div class="modal-header">

<h5 class="modal-title">
إصدار القرار النهائي
</h5>

<button type="button" class="btn-close" data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">

<input type="hidden" name="case_id" value="<?= $case_id ?>">

<textarea
name="decision"
class="form-control"
rows="5"
placeholder="اكتب القرار النهائي هنا"
required></textarea>

</div>

<div class="modal-footer">

<button type="submit" name="save_decision" class="btn btn-success">
حفظ القرار
</button>

<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
إغلاق
</button>

</div>

</form>

</div>

</div>

</div>

<?php } ?>

</tbody>

</table>

<div class="text-center mt-4">

<a href="admin_dashboard.php" class="btn btn-dark">
رجوع
</a>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>