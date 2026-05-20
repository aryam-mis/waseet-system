<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "مدير قانوني"){
    header("Location: login.php"); exit();
}
$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

function translateStatus($s){
    $m=['OPEN'=>'جديدة','UNDER_REVIEW'=>'قيد المراجعة','IN_MEDIATION'=>'في الوساطة',
        'ESCALATED'=>'مصعدة','CLOSED_SETTLED'=>'مغلقة (تسوية)','CLOSED_DECIDED'=>'مغلقة (قرار نهائي)'];
    return $m[$s]??$s;
}

// تصفية الشكاوى
$result = $conn->query("
    SELECT c.*, e.full_name AS complainant
    FROM cases c
    JOIN employees e ON c.created_by_employee_id = e.employee_id
    LEFT JOIN casehandlers ch ON c.case_id = ch.case_id
    WHERE ch.handler_id IS NULL
    AND c.status NOT IN ('CLOSED_SETTLED','CLOSED_DECIDED')
    ORDER BY c.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><title>تعيين مسؤول قانوني</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
<style>
body{background:linear-gradient(135deg,#f6f9ff,#eef3ff,#f3f0ff);font-family:'Cairo',sans-serif;color:#2d2d2d;}
h3{color:#01251a;font-weight:800;}
.table{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(0,0,0,.06);}
.table thead tr{background:linear-gradient(90deg,#a7e6d3);}
.table th{font-weight:800;border:none!important;padding:14px 10px;color:#111827;}
.table td{font-weight:600;border-color:rgba(0,0,0,.06)!important;vertical-align:middle;padding:10px;}
.table tbody tr:nth-child(even){background:#f8fafc;}
.table tbody tr:hover{background:#eef2ff;transition:.2s;}
.badge{border-radius:10px;padding:6px 12px;font-size:12px;font-weight:700;}
.btn-back{background:#01251a;color:#fff;border:none;border-radius:10px;font-weight:700;padding:10px 24px;text-decoration:none;}
.btn-back:hover{background:#014a36;color:#fff;}
</style>
</head>
<body>
<div class="container-fluid mt-4 px-4">
<h3 class="mb-4 text-center">👤 تعيين مسؤول قانوني</h3>

<?php if($result->num_rows == 0): ?>
<div class="alert alert-success text-center fw-bold fs-5">✅ جميع الشكاوى لديها معالج معين</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped text-center">
<thead><tr>
<th>رقم</th><th>العنوان</th><th>الحالة</th><th>الأولوية</th><th>التاريخ</th><th>مقدم الشكوى</th><th>تعيين</th>
</tr></thead>
<tbody>
<?php while($row = $result->fetch_assoc()):
$cid = $row['case_id'];
$bc = 'bg-secondary';
if($row['status']=='ESCALATED') $bc='bg-danger';
elseif($row['status']=='OPEN') $bc='bg-secondary';
else $bc='bg-info';
$pb = ($row['priority']=='عالية')?'bg-danger':(($row['priority']=='متوسطة')?'bg-warning text-dark':'bg-success');
?>
<tr>
<td><?= $cid ?></td>
<td><?= htmlspecialchars($row['title']) ?></td>
<td><span class="badge <?= $bc ?>"><?= translateStatus($row['status']) ?></span></td>
<td><span class="badge <?= $pb ?>"><?= $row['priority'] ?></span></td>
<td><?= date('Y/m/d', strtotime($row['created_at'])) ?></td>
<td><?= htmlspecialchars($row['complainant']) ?></td>
<td><a href="assign_handler.php?case_id=<?= $cid ?>" class="btn btn-success btn-sm fw-bold">تعيين معالج</a></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<div class="text-center mt-4 mb-4">
    <a href="legal_dashboardM.php" class="btn-back">⬅ رجوع</a>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
