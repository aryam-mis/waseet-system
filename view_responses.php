<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['department'] !=  "مدير قانوني"){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error){
    die("فشل الاتصال");
}

$rec_filter = $_GET['rec'] ?? 'all';
$esc_filter = $_GET['esc'] ?? 'all';

$query = "SELECT * FROM cases WHERE 1";

if($rec_filter == 'yes'){
    $query .= " AND recommendation IS NOT NULL AND recommendation != ''";
}elseif($rec_filter == 'no'){
    $query .= " AND (recommendation IS NULL OR recommendation = '')";
}

if($esc_filter == 'yes'){
    $query .= " AND status='ESCALATED'";
}elseif($esc_filter == 'no'){
    $query .= " AND status!='ESCALATED'";
}

$query .= " ORDER BY case_id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الشكاوى</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{
background: linear-gradient(135deg,#f5f7ff,#eef2ff,#f8faff);
color:#1f2937;
font-family:'Cairo',sans-serif;
}

.container{
margin-top:40px;
}

h3{
color:#1e3a8a;
font-weight:bold;
}

.table{
background: rgba(255,255,255,0.75);
backdrop-filter: blur(10px);
border-radius:12px;
overflow:hidden;
color:#1f2937;
box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

.table thead{
background:#1e3a8a;
color:white;
}

.table tbody tr:hover{
background:rgba(30,58,138,0.08);
}

.modal-content{
background: rgba(255,255,255,0.95);
color:#1f2937;
border-radius:14px;
}

.modal-header{
background:#1e3a8a;
color:white;
}

.badge{
border-radius:8px;
padding:6px 10px;
font-size:12px;
}

.bg-info{background:#60a5fa !important;}
.bg-success{background:#22c55e !important;}
.bg-danger{background:#ef4444 !important;}
.bg-warning{background:#fbbf24 !important; color:#1f2937 !important;}
.bg-secondary{background:#94a3b8 !important;}

.btn-info{
background:#3b82f6;
border:none;
}

.btn-info:hover{
background:#2563eb;
}

.btn-secondary{
background:#64748b;
border:none;
}

.btn-secondary:hover{
background:#475569;
}

.btn-outline-light{
background:#1e3a8a;
color:#fff;
border:none;
border-radius:10px;
}

.btn-outline-light:hover{
background:#1d4ed8;
}

.text-muted{
color:#6b7280 !important;
}

.table{
    background:#ffffff;
    color:#111827;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,0.06);
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

.table th{
    background: linear-gradient(90deg,#e0f2fe);
    color:#111827;
    font-weight:800;
    border:none !important;
}

.table td{
    font-weight:600;
    border-color:rgba(0,0,0,0.06) !important;
}
</style>
</head>

<body>

<div class="container mt-5">

<h3 class="text-center mb-4">إدارة جميع الشكاوى</h3>

<table class="table  table-striped text-center">

<thead>
<tr>
<th>رقم</th>
<th>العنوان</th>
<th>الحالة</th>
<th>التوصية</th>
<th>التصعيد</th>

<th>القرار النهائي</th>
<th >تفاصيل</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()){ 
$case_id = $row['case_id'];

$accept_list = "";
$reject_list = "";

$parts = $conn->query("
SELECT e.full_name, cp.party_role
FROM caseparties cp
JOIN employees e ON cp.employee_id = e.employee_id
WHERE cp.case_id = $case_id
");
?>

<tr>

<td><?= $case_id ?></td>
<td><?= $row['title'] ?></td>

<td>
<span class="badge bg-info"><?= $row['status'] ?></span>
</td>

<td>
<?= !empty($row['recommendation']) 
? "<span class='badge bg-success'>  تم الاصدار </span>" 
: "<span class='badge bg-secondary'>غير لم يتم الاصدار</span>" ?>
</td>

<td>
<?= $row['status']=='ESCALATED'
? "<span class='badge bg-danger'>مصعدة</span>"
: "<span class='badge bg-success'>لا</span>" ?>
</td>

<td>
<?= !empty($row['final_decision']) 
? "<span class='badge bg-success'>موجود</span>"
: "<span class='badge bg-warning'>غير موجود</span>" ?>
</td>

<td>
<button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#m<?= $case_id ?>" style="color:white">
عرض
</button>
</td>

</tr>

<div class="modal fade" id="m<?= $case_id ?>" tabindex="-1">
<div class="modal-dialog modal-sm modal-dialog-centered">
<div class="modal-content">

<div class="modal-header">
<h5 >تفاصيل الشكوى #<?= $case_id ?></h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<p><strong>العنوان:</strong> <?= $row['title'] ?></p>
<p><strong>الوصف:</strong> <?= $row['description'] ?></p>

<hr>

<p><strong>الأطراف:</strong></p>

<?php
$parties = $conn->query("
    SELECT 
    cp.party_role,
    cp.party_type,
    e.full_name,
    c.external_party_name,
    sr.response
    FROM caseparties cp
    LEFT JOIN employees e ON cp.employee_id = e.employee_id
    LEFT JOIN cases c ON c.case_id = cp.case_id
    LEFT JOIN sessions s ON s.case_id = cp.case_id
    LEFT JOIN settlementresponses sr ON sr.session_id = s.session_id 
        AND sr.employee_id = cp.employee_id
    WHERE cp.case_id = $case_id
");

while($p = $parties->fetch_assoc()){

    if($p['party_type'] == 'موظف'){
        $name = $p['full_name'];
    } elseif($p['party_type'] == 'قسم'){
        $name = $p['external_party_name'] ?? 'قسم';
    } else {
        $name = $p['external_party_name'] ?? 'جهة خارجية';
    }

    if(strtolower($p['response']) == 'accept'){
    $status = "<span class='badge bg-success'>قبول</span>";
} elseif(strtolower($p['response']) == 'reject'){
    $status = "<span class='badge bg-danger'>رفض</span>";
} else {
        $status = "<span class='badge bg-warning text-dark'>قيد الانتظار</span>";
    }
?>

<div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,0.05);padding:8px;margin-bottom:6px;border-radius:8px;">
    
    <div>
        <strong><?= $name ?></strong>
        <br>
        <small><?= $p['party_role'] ?> (<?= $p['party_type'] ?>)</small>  <?= $status ?>
    </div>

    <div>
       
    </div>

</div>

<?php } ?>

<hr>

<p><strong>القرار النهائي:</strong></p>
<p>
<?= !empty($row['final_decision']) 
? "<span class='text-success'>{$row['final_decision']}</span>"
: "<span class='text-warning'>لم يصدر قرار</span>" ?>
</p>

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

<div class="text-center mt-4">
<a href="legal_dashboard.php" class="btn btn-outline-light">
⬅ العودة
</a>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>