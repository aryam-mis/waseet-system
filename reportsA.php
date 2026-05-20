<?php
session_start();

/*
=================================
السماح للإدارة العليا + مسؤول النظام
=================================
*/

if(
    !isset($_SESSION['user_id']) ||

    (
        $_SESSION['department'] != "الإدارة العليا" &&
        $_SESSION['department'] != "مسؤول النظام"
    )

){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");

if($conn->connect_error){
    die("فشل الاتصال");
}

/*
========================
📊 الإحصائيات
========================
*/

$totalUsers = $conn->query("SELECT COUNT(*) as c FROM employees")->fetch_assoc()['c'];

$totalCases = $conn->query("SELECT COUNT(*) as c FROM cases")->fetch_assoc()['c'];

$escalated = $conn->query("SELECT COUNT(*) as c FROM cases WHERE status='ESCALATED'")->fetch_assoc()['c'];

$closed = $conn->query("SELECT COUNT(*) as c FROM cases WHERE status LIKE 'CLOSED%'")->fetch_assoc()['c'];

$underReview = $conn->query("SELECT COUNT(*) as c FROM cases WHERE status='UNDER_REVIEW'")->fetch_assoc()['c'];

/*
========================
📌 فلتر الحالات
========================
*/

$status = $_GET['status'] ?? 'all';

/*
========================
📌 الاستعلام الرئيسي
========================
*/

$query = "
SELECT c.*,

(
    SELECT s.settlement_text
    FROM sessions s
    WHERE s.case_id = c.case_id
    AND s.settlement_text IS NOT NULL
    ORDER BY s.session_id DESC
    LIMIT 1
) AS recommendation,

(
    SELECT s.session_date
    FROM sessions s
    WHERE s.case_id = c.case_id
    ORDER BY s.session_id DESC
    LIMIT 1
) AS last_session_date

FROM cases c
WHERE 1
";

if($status != 'all'){
    $query .= " AND c.status='$status'";
}

$query .= " ORDER BY c.case_id DESC";

$cases = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>التقارير والإحصائيات</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#ffffff;
    color:#173d38;
    font-family:'Tahoma',sans-serif;
}

.main-section{
    padding:40px;
}

.page-title{
    text-align:center;
    margin-bottom:40px;
}

.page-title h1{
    font-size:42px;
    font-weight:900;
    color:#173d38;
}

.page-title p{
    color:#60706d;
    font-size:18px;
}

/* =========================
   الإحصائيات
========================= */

.stats-container{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:25px;
    margin-bottom:40px;
}

.card-box{
    background:#ffffff;
    border-radius:24px;
    padding:28px;
    text-align:center;
    border:1px solid rgba(0,0,0,0.05);
    box-shadow:0 8px 20px rgba(0,0,0,0.05);
    transition:0.35s;
}

.card-box:hover{
    transform:translateY(-8px);
    box-shadow:0 18px 35px rgba(0,0,0,0.08);
}

.card-box i{
    font-size:40px;
    margin-bottom:15px;
    color:#173d38;
}

.card-box h6{
    font-size:18px;
    font-weight:800;
    margin-bottom:10px;
}

.stat-number{
    font-size:34px;
    font-weight:900;
    color:#173d38;
}

/* =========================
   الفلتر
========================= */

.filter-box{
    margin-bottom:35px;
}

/* =========================
   الجدول
========================= */

.table-container{
    background:#ffffff;
    border-radius:24px;
    padding:20px;
    box-shadow:0 8px 20px rgba(0,0,0,0.05);
}

.table{
    margin:0;
}

.table thead{
    background:#ece8e1;
}

.table th{
    color:#173d38;
    font-weight:900;
    text-align:center;
}

.table td{
    text-align:center;
    vertical-align:middle;
}

/* =========================
   زر الرجوع
========================= */

.back-btn{
    background:#173d38;
    color:#fff;
    border:none;
    padding:12px 28px;
    border-radius:12px;
    text-decoration:none;
    font-weight:700;
    display:inline-block;
    margin-top:35px;
}

.back-btn:hover{
    background:#0f2d29;
    color:#fff;
}

</style>

</head>

<body>

<section class="main-section">

<div class="page-title">

<h1>التقارير والإحصائيات</h1>

<p>
متابعة حالة النزاعات والشكاوى داخل النظام
</p>

</div>

<!-- الإحصائيات -->

<div class="stats-container">

<div class="card-box">
<i class="bi bi-people"></i>
<h6>المستخدمين</h6>
<div class="stat-number"><?= $totalUsers ?></div>
</div>

<div class="card-box">
<i class="bi bi-folder2-open"></i>
<h6>إجمالي الشكاوى</h6>
<div class="stat-number"><?= $totalCases ?></div>
</div>

<div class="card-box">
<i class="bi bi-exclamation-triangle"></i>
<h6>الشكاوى المصعدة</h6>
<div class="stat-number"><?= $escalated ?></div>
</div>

<div class="card-box">
<i class="bi bi-check-circle"></i>
<h6>الشكاوى المغلقة</h6>
<div class="stat-number"><?= $closed ?></div>
</div>

<div class="card-box">
<i class="bi bi-search"></i>
<h6>قيد المراجعة</h6>
<div class="stat-number"><?= $underReview ?></div>
</div>

</div>

<!-- الفلتر -->

<div class="filter-box text-center">

<form method="GET">

<select name="status" class="form-select w-50 mx-auto" onchange="this.form.submit()">

<option value="all">كل الحالات</option>

<option value="OPEN" <?= $status=='OPEN'?'selected':'' ?>>
جديدة
</option>

<option value="UNDER_REVIEW" <?= $status=='UNDER_REVIEW'?'selected':'' ?>>
قيد المراجعة
</option>

<option value="ESCALATED" <?= $status=='ESCALATED'?'selected':'' ?>>
مصعدة
</option>

</select>

</form>

</div>

<!-- الجدول -->

<div class="table-container">

<table class="table table-hover">

<thead>

<tr>

<th>رقم الشكوى</th>

<th>العنوان</th>

<th>الحالة</th>

<th>التوصية</th>

</tr>

</thead>

<tbody>

<?php while($row = $cases->fetch_assoc()){ ?>

<tr>

<td><?= $row['case_id'] ?></td>

<td><?= $row['title'] ?></td>

<td><?= $row['status'] ?></td>

<td>

<?= !empty($row['recommendation'])
? 'تم إصدار توصية'
: 'لا توجد توصية' ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<div class="text-center">

<a href="admin_dashboard.php" class="back-btn">

⬅ العودة للوحة التحكم

</a>

</div>

</section>

</body>
</html>