<?php
session_start();

// شرط: لازم يكون مسجل دخول
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error){
    die("فشل الاتصال");
}

$status = $_GET['status'] ?? 'all';

$query = "SELECT * FROM cases WHERE 1";

// شرط: لو اختاروا حالة معينة نضيف فلتر على الاستعلام
if($status != 'all'){
    $query .= " AND status='$status'";
}

$query .= " ORDER BY case_id DESC";

$result = $conn->query($query);

// استعلام: عدد كل الشكاوى في النظام بغض النظر عن حالتها
$total = $conn->query("SELECT COUNT(*) c FROM cases")->fetch_assoc()['c'];

// استعلام: عدد الشكاوى النشطة فقط — اللي لسا ما اتغلقت أو اتصعدت
$active = $conn->query("
SELECT COUNT(*) c FROM cases 
WHERE status IN ('OPEN','UNDER_REVIEW','IN_MEDIATION')
")->fetch_assoc()['c'];

// استعلام: عدد الشكاوى اللي عندها توصية مكتوبة في جدول sessions
$with_recommendation = $conn->query("
SELECT COUNT(DISTINCT c.case_id) c
FROM cases c
JOIN sessions s ON s.case_id = c.case_id
WHERE s.settlement_text IS NOT NULL AND s.settlement_text != ''
")->fetch_assoc()['c'];

// استعلام: عدد الشكاوى اللي صدر فيها قرار نهائي من finaldecisions
$with_decision = $conn->query("
SELECT COUNT(DISTINCT case_id) c 
FROM finaldecisions
")->fetch_assoc()['c'];

// استعلام: عدد الشكاوى اللي رفض فيها أحد الأطراف التوصية
$escalated_by_reject = $conn->query("
SELECT COUNT(DISTINCT case_id) c 
FROM caseparties 
WHERE response='reject'
")->fetch_assoc()['c'];

$back = "admin_dashboard.php";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة التقارير</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
background: linear-gradient(135deg,#f9fafb,#f3f4f6,#eef2ff);
font-family:'Cairo',sans-serif;
}

h3{
font-weight:800;
}

.card-box{
background: rgba(255,255,255,0.75);
backdrop-filter: blur(14px);
border-radius:18px;
padding:20px;
text-align:center;
margin-bottom:20px;
box-shadow:0 10px 25px rgba(0,0,0,0.06);
}

.card-box h6{
opacity:0.75;
font-weight:700;
}

.card-box h2{
font-size:32px;
font-weight:900;
}

.blue{border-left:6px solid #60a5fa;}
.orange{border-left:6px solid #fbbf24;}
.green{border-left:6px solid #34d399;}
.purple{border-left:6px solid #a78bfa;}
.red{border-left:6px solid #fb7185;}

.btn-back{
background:#0f172a;
color:#fff;
font-weight:bold;
border-radius:30px;
padding:10px 25px;
text-decoration:none;
display:inline-block;
}
</style>
</head>

<body>

<div class="container mt-5">

<h3 class="text-center mb-5">📊 لوحة التقارير والإحصائيات</h3>

<div class="row">

<div class="col-md-3">
<div class="card-box blue">
<h6>إجمالي الشكاوى</h6>
<h2><?= $total ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card-box orange">
<h6>الشكاوى النشطة</h6>
<h2><?= $active ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card-box purple">
<h6>بها توصية</h6>
<h2><?= $with_recommendation ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card-box green">
<h6>قرارات نهائية</h6>
<h2><?= $with_decision ?></h2>
</div>
</div>

</div>

<div class="row justify-content-center">

<div class="col-md-4">
<div class="card-box red">
<h6>مصعدة بسبب رفض طرف</h6>
<h2><?= $escalated_by_reject ?></h2>
</div>
</div>

</div>

<div class="text-center mt-4">
<a href="<?= $back ?>" class="btn-back">⬅ العودة للوحة التحكم</a>
</div>

</div>

</body>
</html>