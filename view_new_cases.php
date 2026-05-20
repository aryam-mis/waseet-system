<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "القسم القانوني"){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error){
    die("فشل الاتصال بقاعدة البيانات");
}

$sql = "SELECT * FROM cases WHERE status='OPEN' OR status='قيد المراجعة'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الشكاوي الجديدة - القسم القانوني</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: linear-gradient(135deg,#0a1f44,#102a5c,#1a3b7a); color:white; font-family:'Cairo',sans-serif; }
.table { color:white; }
.table th, .table td { vertical-align: middle; }
.navbar { background: rgba(0,0,0,0.4); backdrop-filter: blur(10px);}
.btn-logout { position:absolute; top:15px; left:20px; }
</style>
</head>
<body>

<nav class="navbar navbar-dark p-3">
    <div class="container-fluid">
        <h4><i class="bi bi-folder"></i> الشكاوي الجديدة</h4>
        <a href="legal_dashboard.php" class="btn btn-light btn-logout"><i class="bi bi-arrow-left"></i> العودة</a>
    </div>
</nav>

<div class="container mt-5">
<table class="table table-dark table-striped table-hover text-center">
<thead>
<tr>
<th>رقم الشكوى</th>
<th>العنوان</th>
<th>الوصف</th>
<th>الحالة</th>
<th>تاريخ الإنشاء</th>
<th>إجراءات</th>
</tr>
</thead>
<tbody>
<?php
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        echo "<tr>";
        echo "<td>".$row['case_id']."</td>";
        echo "<td>".$row['title']."</td>";
        echo "<td>".$row['description']."</td>";
        echo "<td>".$row['status']."</td>";
        echo "<td>".$row['created_at']."</td>";
        echo "<td>
            <a href='escalate_case.php?case_id=".$row['case_id']."' class='btn btn-warning btn-sm'>
                تصعيد للإدارة العليا
            </a>
        </td>";
        echo "</tr>";
    }
}else{
    echo "<tr><td colspan='6'>لا توجد شكاوي جديدة</td></tr>";
}
$conn->close();
?>
</tbody>
</table>
</div>

</body>
</html>