<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "الإدارة العليا"){
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
        'ESCALATED' => 'مصعدة',
        'CLOSED_SETTLED' => 'مغلقة (تسوية)',
        'CLOSED_DECIDED' => 'مغلقة (قرار)'
    ];
    return $map[$status] ?? $status;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الشكاوى</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
background: radial-gradient(circle at top right,#ffffff,#f6fbff,#f8f5ff,#f3fff8);
font-family:'Cairo',sans-serif;
font-weight:600;
}

.table{
background:#fff;
border-radius:16px;
overflow:hidden;
box-shadow:0 12px 30px rgba(0,0,0,0.06);
}

.table thead{
background: linear-gradient(90deg,#e0f2fe,#ede9fe,#dcfce7);
font-weight:800;
}

.badge{
font-size:12px;
padding:6px 10px;
border-radius:8px;
font-weight:700;
}

.btn-back{
background:#0f172a;
color:#fff;
border-radius:10px;
padding:8px 16px;
text-decoration:none;
}
</style>
</head>

<body>

<div class="container mt-5">

<h3 class="text-center mb-4">إدارة جميع الشكاوى</h3>

<div class="text-center mb-3">
<a href="admin_dashboard.php" class="btn-back">⬅ العودة</a>
</div>

<!-- فلاتر البحث -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <input type="number" id="f_id" class="form-control" placeholder="رقم الشكوى">
  </div>
  <div class="col-md-3">
    <select id="f_type" class="form-control">
      <option value="">-- نوع الشكوى --</option>
      <option>مالي</option>
      <option>عمالي</option>
      <option>تعاقدي</option>
      <option>امتثال</option>
    </select>
  </div>
  <div class="col-md-3">
    <select id="f_priority" class="form-control">
      <option value="">-- الأولوية --</option>
      <option>عالية</option>
      <option>متوسطة</option>
      <option>منخفضة</option>
    </select>
  </div>
  <div class="col-md-3">
    <button class="btn w-100 fw-bold" style="background:#01251a;color:#fff;border-radius:10px;" onclick="filterTable()">بحث</button>
  </div>
</div>

<table class="table table-striped text-center" id="casesTable">

<thead>
<tr>
<th>رقم</th>
<th>العنوان</th>
<th>الحالة</th>
<th>الأولوية</th>
<th>التصعيد</th>
<th>التوصية</th>
<th>القرار النهائي</th>
<th>تفاصيل</th>
</tr>
</thead>

<tbody>

<?php
$cases = $conn->query("SELECT * FROM cases ORDER BY case_id DESC");

while($row = $cases->fetch_assoc()){

$case_id = $row['case_id'];

$rec = $conn->query("
SELECT settlement_text 
FROM sessions 
WHERE case_id=$case_id 
AND settlement_text IS NOT NULL 
ORDER BY session_date DESC 
LIMIT 1
")->fetch_assoc();

$final = $conn->query("
SELECT decision_text 
FROM finaldecisions 
WHERE case_id=$case_id 
LIMIT 1
")->fetch_assoc();

$parties = $conn->query("
SELECT cp.*, e.full_name
FROM caseparties cp
LEFT JOIN employees e ON cp.employee_id = e.employee_id
WHERE cp.case_id=$case_id
");

$sessions = $conn->query("
SELECT * FROM sessions 
WHERE case_id=$case_id 
ORDER BY session_date DESC
");
?>

<tr class="case-row" data-id="<?= $case_id ?>" data-type="<?= htmlspecialchars($row['title']) ?>" data-priority="<?= htmlspecialchars($row['priority']) ?>">
<td><?= $case_id ?></td>
<td><?= $row['title'] ?></td>

<td>
<span class="badge bg-info"><?= translateStatus($row['status']) ?></span>
</td>

<td>
<span class="badge bg-secondary"><?= $row['priority'] ?></span>
</td>

<td>
<?= $row['status']=='ESCALATED'
? "<span class='badge bg-danger'>مصعدة</span>"
: "<span class='badge bg-success'>لا</span>" ?>
</td>

<td>
<?= !empty($rec['settlement_text'])
? "<span class='badge bg-success'>تم إصدار التوصية</span>"
: "<span class='badge bg-warning text-dark'>لم يتم اصدار التوصية</span>" ?>
</td>

<td>
<?= !empty($final['decision_text'])
? "<span class='badge bg-success'>تم اصدار القرار</span>"
: "<span class='badge bg-warning text-dark'>لم يتم اصدار القرار النهائي</span>" ?>
</td>

<td>
<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#m<?= $case_id ?>">
تفاصيل
</button>
</td>
</tr>

<div class="modal fade" id="m<?= $case_id ?>" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header">
<h5>تفاصيل الشكوى #<?= $case_id ?></h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<p><strong>العنوان:</strong> <?= $row['title'] ?></p>
<p><strong>الوصف:</strong> <?= $row['description'] ?></p>

<hr>

<h6>الحالة</h6>
<p><?= translateStatus($row['status']) ?></p>

<hr>

<h6>التوصية</h6>
<p><?= $rec['settlement_text'] ?? 'لم يتم اصدار التوصية توصية' ?></p>

<hr>

<h6>القرار النهائي</h6>
<p><?= $final['decision_text'] ?? 'لم يتم اصدار القرار النهائي قرار' ?></p>

<hr>

<h6>الجلسات</h6>
<?php while($s = $sessions->fetch_assoc()){ ?>
<p>
<?= $s['session_date'] ?> - <?= $s['summary_notes'] ?? 'بدون ملاحظات' ?>
</p>
<?php } ?>

<hr>

<h6>الأطراف</h6>
<?php while($p = $parties->fetch_assoc()){ 

if($p['party_type']=='موظف'){
    $name = $p['full_name'];
}else{
    $name = $row['external_party_name'] ?? 'طرف خارجي';
}
?>

<p>
<?= $name ?> - <?= $p['party_role'] ?> (<?= $p['party_type'] ?>)
<?= !empty($p['response']) ? " - ".$p['response'] : "" ?>
</p>

<?php } ?>

</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
</div>

</div>
</div>
</div>

<?php } ?>

</tbody>
</table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function filterTable(){
    var id = document.getElementById('f_id').value.trim();
    var type = document.getElementById('f_type').value;
    var priority = document.getElementById('f_priority').value;
    var rows = document.querySelectorAll('#casesTable tbody tr.case-row');
    rows.forEach(function(row){
        var rowId = row.getAttribute('data-id') || '';
        var rowType = row.getAttribute('data-type') || '';
        var rowPriority = row.getAttribute('data-priority') || '';
        var show = true;
        if(id && rowId !== id) show = false;
        if(type && rowType !== type) show = false;
        if(priority && rowPriority !== priority) show = false;
        row.style.display = show ? '' : 'none';
    });
}
</script>
</body>
</html>