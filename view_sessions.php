<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php"); exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

$user_id = $_SESSION['user_id'];
$dept = $_SESSION['department'];

// تصفية الجلسات
if($dept == 'معالج قانوني'){
    $stmt = $conn->prepare("
        SELECT s.session_id, s.case_id, s.session_date, s.session_time,
               s.location_details, s.settlement_text, c.title
        FROM sessions s
        JOIN cases c ON s.case_id=c.case_id
        JOIN casehandlers ch ON ch.case_id=c.case_id
        WHERE ch.employee_id=?
        AND c.status NOT IN ('CLOSED_SETTLED','CLOSED_DECIDED','ESCALATED')
        AND (s.settlement_text IS NULL OR s.settlement_text = '')
        GROUP BY s.session_id
        ORDER BY s.session_date DESC
    ");
} else {
    // جلسات الموظف
    $stmt = $conn->prepare("
        SELECT s.session_id, s.case_id, s.session_date, s.session_time,
               s.location_details, s.settlement_text, c.title
        FROM sessions s
        JOIN cases c ON s.case_id=c.case_id
        JOIN caseparties cp ON cp.case_id=c.case_id
        WHERE cp.employee_id=?
        GROUP BY s.session_id
        ORDER BY s.session_date DESC
    ");
}
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result=$stmt->get_result();

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><title>الجلسات</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
<style>
body{background:radial-gradient(circle at top right,#fff,#f6fbff,#f8f5ff,#f2fff7);font-family:'Cairo',sans-serif;color:#111827;}
h3{font-weight:800;color:#01251a;}
.table{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(0,0,0,.06);}
.table thead tr{background:linear-gradient(90deg,#a7e6d3);}
.table th{font-weight:800;border:none!important;padding:14px 10px;color:#111827;}
.table td{font-weight:600;border-color:rgba(0,0,0,.06)!important;vertical-align:middle;padding:12px 10px;}
.table tbody tr:nth-child(even){background:#f8fafc;}
.table tbody tr:hover{background:#eef2ff;transition:.2s;}
.modal-content{background:rgba(255,255,255,.97);border-radius:18px;}
.modal-header{background:linear-gradient(90deg,#01251a,#014a36);border-radius:18px 18px 0 0;}
.modal-header h5,.modal-header .btn-close{color:#fff!important;}
.info-box{background:#f8fafc;padding:12px 16px;border-radius:12px;margin-bottom:10px;border:1px solid rgba(0,0,0,.06);}
.badge{font-size:12px;padding:6px 10px;border-radius:8px;font-weight:700;}
.btn-back{background:#01251a;color:#fff;border:none;border-radius:10px;font-weight:700;padding:10px 24px;text-decoration:none;}
.btn-back:hover{background:#014a36;color:#fff;}
.session-link{color:#01251a;font-weight:700;word-break:break-all;}
</style>
</head>
<body>
<div class="container mt-4">
<h3 class="text-center mb-4">الجلسات المجدولة</h3>

<?php if($result->num_rows==0): ?>
<div class="alert alert-info text-center">لا توجد جلسات مجدولة</div>
<?php else: ?>
<div class="table-responsive">
<table class="table text-center">
<thead><tr>
<th>#</th><th>رقم الشكوى</th><th>عنوان الشكوى</th><th>رقم الجلسة</th>
<th>التاريخ</th><th>الوقت</th><th>رابط الجلسة</th><th>التوصية</th><th>تفاصيل</th>
</tr></thead>
<tbody>
<?php $i=1; while($row=$result->fetch_assoc()):
$case_id=$row['case_id'];
$session_id=$row['session_id'];
$link=$row['location_details']?:"www/$case_id";
$date_only=date('Y-m-d',strtotime($row['session_date']));
$time_fmt=date('h:i A',strtotime($row['session_time']));
$session_passed = ($date_only <= $today);
$has_rec = !empty($row['settlement_text']);
?>
<tr>
<td><?=$i++?></td>
<td><?=$case_id?></td>
<td><?=htmlspecialchars($row['title'])?></td>
<td><span class="badge bg-secondary"><?=$session_id?></span></td>
<td><?=$date_only?></td>
<td><?=$time_fmt?></td>
<td><a href="https://<?=$link?>" target="_blank" class="session-link"><?=$link?></a></td>
<td>
<?php if($has_rec): ?>
<span class="badge bg-success">تم إصدار التوصية</span>
<?php elseif($dept=='معالج قانوني' && $session_passed): ?>
<a href="create_settlement.php" class="badge bg-warning text-dark" style="text-decoration:none;">إصدار توصية</a>
<?php elseif($dept=='معالج قانوني' && !$session_passed): ?>
<span class="badge bg-secondary">قبل انعقاد الجلسة</span>
<?php else: ?>
<span class="badge bg-secondary">لا توجد توصية</span>
<?php endif; ?>
</td>
<td><button class="btn btn-sm fw-bold" style="background:#c4b5fd;border:none;border-radius:10px;" data-bs-toggle="modal" data-bs-target="#m<?=$session_id?>">تفاصيل</button></td>
</tr>

<div class="modal fade" id="m<?=$session_id?>" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5>تفاصيل الجلسة #<?=$session_id?></h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-4">
<div class="row g-3">
<div class="col-md-4"><div class="info-box"><small class="text-muted d-block">📅 التاريخ</small><strong><?=$date_only?></strong></div></div>
<div class="col-md-4"><div class="info-box"><small class="text-muted d-block">⏰ الوقت</small><strong><?=$time_fmt?></strong></div></div>
<div class="col-md-4"><div class="info-box"><small class="text-muted d-block">🔗 الرابط</small><a href="https://<?=$link?>" target="_blank" class="session-link"><?=$link?></a></div></div>
</div>
<hr>
<?php
$handler=$conn->query("SELECT e.full_name FROM casehandlers ch JOIN employees e ON ch.employee_id=e.employee_id WHERE ch.case_id=$case_id LIMIT 1")->fetch_assoc();
?>
<h6 class="mb-3">⚖️ المعالج</h6>
<div class="info-box"><?=$handler['full_name']??'غير محدد'?></div>
<hr>
<h6 class="mb-3">👥 الأطراف</h6>
<?php
$parties=$conn->query("
    SELECT cp.party_role,cp.party_type,e.full_name,c.external_party_name
    FROM caseparties cp
    LEFT JOIN employees e ON cp.employee_id=e.employee_id
    LEFT JOIN cases c ON c.case_id=cp.case_id
    WHERE cp.case_id=$case_id
");
while($p=$parties->fetch_assoc()){
    $nm=!empty($p['full_name'])?$p['full_name']:($p['external_party_name']??'جهة خارجية');
    echo "<div class='info-box d-flex justify-content-between align-items-center'>
        <strong>".htmlspecialchars($nm)."</strong>
        <div><span class='badge bg-primary'>{$p['party_role']}</span>
        <span class='badge bg-secondary ms-1'>{$p['party_type']}</span></div></div>";
}
?>
<?php if($has_rec): ?>
<hr>
<h6>📋 التوصية</h6>
<div class="alert alert-success"><?=htmlspecialchars($row['settlement_text'])?></div>
<?php endif; ?>
</div>
<div class="modal-footer">
<?php if($dept=='معالج قانوني' && $session_passed && !$has_rec): ?>
<a href="create_settlement.php" class="btn btn-warning fw-bold">إصدار توصية</a>
<?php endif; ?>
<a href="https://<?=$link?>" target="_blank" class="btn btn-success fw-bold">دخول الجلسة</a>
<button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
</div>
</div></div></div>
<?php endwhile; ?>
</tbody></table>
</div>
<?php endif; ?>

<div class="text-center mt-4 mb-4">
<?php if($dept=='معالج قانوني'): ?>
<a href="legal_dashboard.php" class="btn-back">⬅ رجوع</a>
<?php else: ?>
<a href="employee_dashboard.php" class="btn-back">⬅ رجوع</a>
<?php endif; ?>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
