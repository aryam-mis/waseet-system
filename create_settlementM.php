<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error){
    die("فشل الاتصال");
}

function translateStatus($status){
    $map = [
        'OPEN' => 'جديدة',
        'UNDER_REVIEW' => 'قيد المراجعة',
        'IN_MEDIATION' => 'في الوساطة',
        'ESCALATED' => 'مصعدة للإدارة العليا',
        'CLOSED_SETTLED' => 'مغلقة (تسوية)',
        'CLOSED_DECIDED' => 'مغلقة (قرار نهائي)'
    ];
    return $map[$status] ?? $status;
}

$query = "SELECT c.*,
         (SELECT COUNT(*) FROM sessions s WHERE s.case_id = c.case_id) AS has_session
          FROM cases c
          WHERE NOT EXISTS (
              SELECT 1 FROM sessions s2 
              WHERE s2.case_id = c.case_id 
              AND s2.settlement_text IS NOT NULL 
              AND s2.settlement_text != ''
          )
          ORDER BY c.case_id DESC";

$cases = $conn->query($query);

$back = "employee_dashboard.php";
if(isset($_SESSION['department'])){
    if($_SESSION['department'] == "مدير قانوني") $back = "legal_dashboardM.php";
    if($_SESSION['department'] == "الإدارة العليا") $back = "admin_dashboard.php";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إصدار التوصية</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
body{
    background: linear-gradient(135deg,#f5f7ff,#eef2ff,#f8faff);
    color:#1f2937;
    font-family:'Cairo',sans-serif;
}
h3{color:#01251a;font-weight:800;}
.table{
    background:#ffffff;color:#111827;
    border-radius:16px;overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,0.06);
}
.table thead tr{background: linear-gradient(90deg,#a7e6d3);}
.table th{font-weight:800;border:none !important;padding:14px 10px;color:#111827;}
.table tbody tr:nth-child(even){background:#f8fafc;}
.table tbody tr:hover{background:#eef2ff;transition:0.2s;}
.table td{font-weight:600;border-color:rgba(0,0,0,0.06)!important;vertical-align:middle;}
.badge{border-radius:10px;padding:6px 12px;font-size:12px;font-weight:700;}
.box{background:#f8fafc;padding:12px;border-radius:10px;margin-bottom:8px;border:1px solid rgba(0,0,0,0.06);}
.modal-content{border-radius:18px;background:#fff;}
.modal-header{background:linear-gradient(90deg,#01251a,#014a36);border-radius:18px 18px 0 0;}
.modal-header h5,.modal-header button{color:#fff!important;}
.btn-back{background:#01251a;color:#fff;border:none;border-radius:10px;font-weight:700;padding:10px 24px;text-decoration:none;}
.btn-back:hover{background:#014a36;color:#fff;}
</style>
</head>
<body>
<div class="container mt-4">

<h3 class="text-center mb-2">إصدار التوصية</h3>
<p class="text-center text-muted mb-4">يعرض الشكاوى التي لم تصدر لها توصية بعد</p>

<div class="table-responsive">
<table class="table table-striped text-center">
<thead>
<tr>
<th>رقم</th>
<th>العنوان</th>
<th>الحالة</th>
<th>الجلسة</th>
<th>إصدار توصية</th>
<th>تفاصيل</th>
</tr>
</thead>
<tbody>

<?php
if($cases->num_rows == 0){
    echo "<tr><td colspan='6' class='py-4 text-muted'>لا توجد شكاوى بدون توصية</td></tr>";
}
while($row = $cases->fetch_assoc()){

$case_id = $row['case_id'];

$session_q = $conn->query("SELECT * FROM sessions WHERE case_id=$case_id ORDER BY session_date DESC LIMIT 1");
$session = $session_q->fetch_assoc();
$session_id = $session['session_id'] ?? null;

$parties = $conn->query("
SELECT cp.party_role, cp.party_type, e.full_name, c.external_party_name
FROM caseparties cp
LEFT JOIN employees e ON cp.employee_id = e.employee_id
LEFT JOIN cases c ON c.case_id = cp.case_id
WHERE cp.case_id = $case_id
");
?>

<tr>
<td><?= $case_id ?></td>
<td><?= htmlspecialchars($row['title']) ?></td>
<td>
<?php
$bc='bg-info';
if($row['status']=='ESCALATED') $bc='bg-danger';
elseif($row['status']=='CLOSED_SETTLED'||$row['status']=='CLOSED_DECIDED') $bc='bg-success';
elseif($row['status']=='OPEN') $bc='bg-secondary';
?>
<span class="badge <?= $bc ?>"><?= translateStatus($row['status']) ?></span>
</td>
<td>
<?php if($session_id){ ?>
<span class="text-success fw-bold">✅ موجودة</span>
<?php } else { ?>
<span class="text-danger fw-bold">❌ لا توجد</span>
<?php } ?>
</td>
<td>
<?php if($session_id){ ?>
<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#rec<?= $case_id ?>">
إصدار توصية
</button>
<?php } else { ?>
<span class="text-muted small">يجب جدولة جلسة أولاً</span>
<?php } ?>
</td>
<td>
<button class="btn btn-sm fw-bold" style="background:#01251a;color:#fff;border-radius:10px;"
data-bs-toggle="modal" data-bs-target="#m<?= $case_id ?>">
تفاصيل
</button>
</td>
</tr>

<!-- Modal تفاصيل -->
<div class="modal fade" id="m<?= $case_id ?>" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">تفاصيل الشكوى #<?= $case_id ?></h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-4">
<h6>📌 الشكوى</h6>
<div class="box">
<strong>العنوان:</strong> <?= htmlspecialchars($row['title']) ?><br>
<strong>الوصف:</strong> <?= htmlspecialchars($row['description']) ?>
</div>
<h6>👥 الأطراف</h6>
<div class="box">
<?php while($p = $parties->fetch_assoc()){
    $name = !empty($p['full_name']) ? $p['full_name'] : ($p['external_party_name'] ?? 'غير محدد');
?>
<div class="d-flex justify-content-between mb-1">
<strong><?= htmlspecialchars($name) ?></strong>
<div>
<span class="badge bg-primary"><?= $p['party_role'] ?></span>
<span class="badge bg-secondary ms-1"><?= $p['party_type'] ?></span>
</div>
</div>
<?php } ?>
</div>
<h6>📅 الجلسة</h6>
<div class="box">
<?php if($session){ ?>
رقم الجلسة: <strong><?= $session['session_id'] ?></strong><br>
التاريخ: <strong><?= $session['session_date'] ?></strong><br>
الوقت: <strong><?= date("h:i A", strtotime($session['session_time'])) ?></strong>
<?php } else { ?>
<span class="text-danger">لا توجد جلسة</span>
<?php } ?>
</div>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
</div>
</div>
</div>
</div>

<!-- Modal التوصية -->
<?php if($session_id){ ?>
<div class="modal fade" id="rec<?= $case_id ?>" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<form method="POST" action="save_recommendation.php">
<div class="modal-header">
<h5 class="modal-title">إصدار توصية للشكوى #<?= $case_id ?></h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-4">
<input type="hidden" name="session_id" value="<?= $session_id ?>">
<label style="font-weight:700;color:#01251a;">نص التوصية</label>
<textarea name="response" class="form-control mt-2" rows="5" required placeholder="اكتب التوصية هنا..."></textarea>
</div>
<div class="modal-footer">
<button type="submit" class="btn btn-success fw-bold">حفظ التوصية</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
</div>
</form>
</div>
</div>
</div>
<?php } ?>

<?php } ?>

</tbody>
</table>
</div>

<div class="text-center mt-4 mb-4">
<a href="<?= $back ?>" class="btn-back">⬅ رجوع</a>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
