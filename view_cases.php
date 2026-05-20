<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "موظف"){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");

if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
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

function priorityBadge($priority){
    if($priority == 'عالية') return "<span class='badge bg-danger'>عالية</span>";
    if($priority == 'متوسطة') return "<span class='badge bg-warning text-dark'>متوسطة</span>";
    if($priority == 'منخفضة') return "<span class='badge bg-success'>منخفضة</span>";
    return "<span class='badge bg-secondary'>غير محدد</span>";
}

$user_id = $_SESSION['user_id'];
$priority = $_GET['priority'] ?? '';

$sql = "
SELECT DISTINCT c.*
FROM cases c
JOIN caseparties cp 
ON c.case_id = cp.case_id
WHERE cp.employee_id = ?
";

if($priority != ''){
    $sql .= " AND c.priority = ? ";
}

$sql .= " ORDER BY c.created_at DESC ";

$stmt = $conn->prepare($sql);

if($priority != ''){
    $stmt->bind_param("is", $user_id, $priority);
}else{
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>شكاويي</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

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
    color:#111827;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,0.06);
}

.table th{
    background: linear-gradient(90deg,#a7e6d3);
    color:#111827;
    font-weight:800;
    border:none !important;
    padding:14px 10px;
}

.table tbody tr{
    background:#ffffff;
    color:#111827;
}

.table tbody tr:nth-child(even){
    background:#f8fafc;
}

.table tbody tr:hover{
    background:#eef2ff;
    transition:0.2s;
}

.table td{
    font-weight:600;
    border-color:rgba(0,0,0,0.06) !important;
    vertical-align:middle;
}

.modal-content{
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(16px);
    border-radius: 18px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 25px 60px rgba(0,0,0,0.12);
    color:#111827;
    font-weight:600;
}

.modal-header{
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background:linear-gradient(90deg,#01251a,#014a36);
    border-radius:18px 18px 0 0;
}

.modal-header h5, .modal-header button{color:#fff !important;}

.box{
    background: rgba(255,255,255,0.85);
    padding:10px;
    border-radius:12px;
    margin-bottom:8px;
    border:1px solid rgba(0,0,0,0.06);
    box-shadow: 0 6px 15px rgba(0,0,0,0.05);
}

.badge{
    font-size: 12px;
    padding:6px 10px;
    border-radius: 8px;
    font-weight:700;
}

.btn-info{
    background:#01251a;
    border:none;
    color:#fff;
    font-weight:700;
    border-radius:10px;
}

.btn-success{
    background:linear-gradient(135deg,#86efac,#34d399);
    border:none;
    color:#0f172a;
    font-weight:700;
    border-radius:10px;
}

.btn-secondary{
    background:linear-gradient(135deg,#01251a);
    border:none;
    color:#fff;
    font-weight:700;
    border-radius:10px;
}

.btn-outline-light{
    background:#01251a;
    color:#fff;
    border:none;
    font-weight:700;
    border-radius:10px;
}

.btn-outline-light:hover{
    background:#014a36;
    color:#fff;
}

.escalated-badge{
    display:inline-block;
    background:#dc2626;
    color:#fff;
    padding:3px 8px;
    border-radius:6px;
    font-size:11px;
    font-weight:700;
    margin-top:3px;
}

</style>

</head>

<body>

<div class="container mt-4">

<h2 class="text-center mb-4">الشكاوى الخاصة بي</h2>

<form method="GET" class="mb-4 d-flex justify-content-center">

<select 
name="priority" 
class="form-control"
style="max-width:200px;"
onchange="this.form.submit()"
>

<option value="">كل الأولويات</option>

<option value="عالية" <?= ($priority=='عالية'?'selected':'') ?>>
عالية
</option>

<option value="متوسطة" <?= ($priority=='متوسطة'?'selected':'') ?>>
متوسطة
</option>

<option value="منخفضة" <?= ($priority=='منخفضة'?'selected':'') ?>>
منخفضة
</option>

</select>

</form>

<div class="table-responsive">
<table class="table table-striped text-center">

<thead>

<tr>
<th>رقم</th>
<th>العنوان</th>
<th>الحالة</th>
<th>الأولوية</th>
<th>المدعي</th>
<th>المدعى عليه</th>
<th>نوع المدعى عليه</th>
<th>المعالج</th>
<th>التاريخ</th>
<th>تفاصيل</th>
</tr>

</thead>

<tbody>

<?php while($row = $result->fetch_assoc()):

$case_id = $row['case_id'];
$isEscalated = ($row['status'] == 'ESCALATED');

?>

<tr>

<td><?= $case_id ?></td>

<td><?= htmlspecialchars($row['title']) ?></td>

<td>
<?php
$bc = 'bg-info';
if($isEscalated) $bc = 'bg-danger';
elseif($row['status']=='CLOSED_SETTLED'||$row['status']=='CLOSED_DECIDED') $bc = 'bg-success';
elseif($row['status']=='OPEN') $bc = 'bg-secondary';
echo "<span class='badge $bc'>".translateStatus($row['status'])."</span>";

?>
</td>

<td>
<?= priorityBadge($row['priority']) ?>
</td>

<td>

<?php

$plaintiff = $conn->query("
SELECT 
e.full_name
FROM caseparties cp
JOIN employees e 
ON cp.employee_id = e.employee_id
WHERE cp.case_id = $case_id
AND cp.party_role = 'مدعي'
");

while($p = $plaintiff->fetch_assoc()){
    echo "<div>".$p['full_name']."</div>";
}

?>

</td>

<td>

<?php

$defendant = $conn->query("
SELECT 
cp.employee_id,
cp.party_type,
c.external_party_name,
e.full_name
FROM caseparties cp
LEFT JOIN employees e 
ON cp.employee_id = e.employee_id
LEFT JOIN cases c
ON cp.case_id = c.case_id
WHERE cp.case_id = $case_id
AND cp.party_role = 'مدعى عليه'
");

while($d = $defendant->fetch_assoc()){
    if(!empty($d['employee_id'])){
        echo "<div>".$d['full_name']."</div>";
    }else{
        echo "<div>".htmlspecialchars($d['external_party_name'])."</div>";
    }
}

?>

</td>

<td>

<?php

$typeQ = $conn->query("
SELECT party_type
FROM caseparties
WHERE case_id = $case_id
AND party_role = 'مدعى عليه'
LIMIT 1
");

if($typeQ && $typeQ->num_rows > 0){
    $t = $typeQ->fetch_assoc();
    echo "<span class='badge bg-secondary'>".$t['party_type']."</span>";
}else{
    echo "<span class='badge bg-secondary'>غير محدد</span>";
}

?>

</td>

<td>

<?php

$handlerQ = $conn->query("
SELECT e.full_name
FROM casehandlers ch
JOIN employees e
ON ch.employee_id = e.employee_id
WHERE ch.case_id = $case_id
LIMIT 1
");

if($handlerQ && $handlerQ->num_rows > 0){
    $h = $handlerQ->fetch_assoc();
    echo "<span class='badge bg-success'>".$h['full_name']."</span>";
}else{
    echo "<span class='badge bg-secondary'>لم يتم تعيينه</span>";
}

?>

</td>

<td><?= $row['created_at'] ?></td>

<td>

<button 
class="btn btn-outline-light btn-sm"
data-bs-toggle="modal"
data-bs-target="#m<?= $case_id ?>"
>
عرض
</button>

</td>

</tr>

<div class="modal fade" id="m<?= $case_id ?>" tabindex="-1">

<div class="modal-dialog modal-lg modal-dialog-centered">

<div class="modal-content">

<div class="modal-header">

<h5>تفاصيل الشكوى #<?= $case_id ?></h5>

<button 
class="btn-close btn-close-white"
data-bs-dismiss="modal"
></button>

</div>

<div class="modal-body p-4">

<?php if($isEscalated){ ?>
<div class="alert alert-danger fw-bold">
🔺 تم تصعيد هذه الشكوى للإدارة العليا
</div>
<?php } ?>

<p>
<strong>العنوان:</strong>
<?= htmlspecialchars($row['title']) ?>
</p>

<p>
<strong>الوصف:</strong>
<?= htmlspecialchars($row['description']) ?>
</p>

<p>
<strong>الأولوية:</strong>
<?= priorityBadge($row['priority']) ?>
</p>

<p>
<strong>الحالة:</strong>
<span class="badge <?= $isEscalated ? 'bg-danger' : 'bg-info' ?>">
<?= translateStatus($row['status']) ?>
</span>
</p>

<?php
// القرار النهائي
$fd = $conn->query("
    SELECT decision_text FROM finaldecisions
    WHERE case_id=$case_id
    LIMIT 1
")->fetch_assoc();

if(!empty($fd['decision_text'])){ ?>
<div class="alert alert-success">
<strong>القرار النهائي:</strong><br>
<?= htmlspecialchars($fd['decision_text']) ?>
</div>
<?php } ?>

</div>

<div class="modal-footer">

<button 
class="btn btn-secondary"
data-bs-dismiss="modal"
>
إغلاق
</button>

</div>

</div>

</div>

</div>

<?php endwhile; ?>

</tbody>

</table>
</div>

<div class="text-center mt-4">

<a href="employee_dashboard.php" class="btn btn-outline-light">
رجوع
</a>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
