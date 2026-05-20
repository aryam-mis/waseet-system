<?php
session_start();
// شرط: الصفحة للمسؤول فقط، أي حد ثاني يروح لصفحة الدخول
if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "مسؤول النظام"){
    header("Location: login.php"); exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

// شرط: لو المسؤول ضغط قبول أو رفض من المودال
if(isset($_POST['action'])){
    $emp_id = intval($_POST['emp_id']);
    $action = $_POST['action'];

    // شرط: لو الإجراء قبول — نغير الحالة لـ APPROVED ونعيد تحميل الصفحة
    if($action == 'APPROVED'){
        // استعلام: نحدث account_status للموظف المحدد
        $conn->query("UPDATE employees SET account_status='APPROVED' WHERE employee_id=$emp_id");
        echo "<script>alert('تم قبول الطلب بنجاح ✅');window.location='".$_SERVER['PHP_SELF']."';</script>";
        exit();
    } elseif($action == 'REJECTED'){
        // منع إعادة التسجيل
        // استعلام: نجيب بيانات الموظف عشان نتأكد إنه موجود قبل الرفض
        $emp = $conn->query("SELECT email,username FROM employees WHERE employee_id=$emp_id")->fetch_assoc();
        if($emp){
            $email = $conn->real_escape_string($emp['email']);
            $username = $conn->real_escape_string($emp['username']);
            // استعلام: نغير الحالة لـ REJECTED — يمنع المستخدم من تسجيل الدخول ويعطيه رسالة رفض
            $conn->query("UPDATE employees SET account_status='REJECTED' WHERE employee_id=$emp_id");
        }
        echo "<script>alert('تم رفض الطلب ❌');window.location='".$_SERVER['PHP_SELF']."';</script>";
        exit();
    }
}

$deptLabels = [
    'موظف'          => ['label'=>'طلب إنشاء حساب موظف',                    'color'=>'bg-primary'],
    'معالج قانوني'  => ['label'=>'طلب إنشاء حساب معالج قانوني',            'color'=>'bg-info text-dark'],
    'مدير قانوني'   => ['label'=>'طلب إنشاء حساب مسؤول الإدارة القانونية', 'color'=>'bg-warning text-dark'],
    'الإدارة العليا'=> ['label'=>'طلب إنشاء حساب إدارة عليا',              'color'=>'bg-danger'],
];

// استعلام: نجيب الطلبات حسب الفلتر — افتراضياً يعرض PENDING (قيد الانتظار)
$filter = $_GET['filter'] ?? 'PENDING';
$requests = $conn->query("SELECT * FROM employees WHERE account_status='$filter' ORDER BY employee_id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><title>طلبات التسجيل</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
body{background:#f8f6f1;font-family:'Cairo',sans-serif;color:#173d38;}
.navbar-custom{width:100%;height:80px;background:#fff;box-shadow:0 3px 15px rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:space-between;padding:0 40px;position:sticky;top:0;z-index:100;}
.navbar-custom h2{font-size:22px;font-weight:900;color:#173d38;margin:0;}
.logout-nav{background:#173d38;color:#fff;text-decoration:none;padding:9px 20px;border-radius:12px;font-weight:700;}
.logout-nav:hover{background:#0f2d29;color:#fff;}
h3{color:#01251a;font-weight:800;}
.table{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 25px rgba(0,0,0,0.06);}
.table thead tr{background:linear-gradient(90deg,#a7e6d3);}
.table th{font-weight:800;border:none!important;padding:14px 10px;color:#111827;}
.table td{font-weight:600;border-color:rgba(0,0,0,.06)!important;vertical-align:middle;padding:12px 10px;}
.table tbody tr:nth-child(even){background:#f8fafc;}
.table tbody tr:hover{background:#eef2ff;transition:.2s;}
.badge{font-size:12px;padding:6px 12px;border-radius:8px;font-weight:700;}
.modal-content{border-radius:18px;background:#fff;}
.modal-header{background:linear-gradient(90deg,#01251a,#014a36);border-radius:18px 18px 0 0;}
.modal-header h5,.modal-header .btn-close{color:#fff!important;}
.info-row{background:#f8fafc;padding:10px 14px;border-radius:10px;margin-bottom:8px;border:1px solid rgba(0,0,0,.06);}
.filter-btn{border-radius:10px;font-weight:700;padding:8px 20px;border:none;}
.back-btn{background:#173d38;color:#fff;border:none;border-radius:10px;font-weight:700;padding:9px 20px;text-decoration:none;}
</style>
</head>
<body>
<header class="navbar-custom">
<div style="display:flex;align-items:center;gap:12px;">
<img src="logo.png" style="width:45px;">
<h2>وسيط — طلبات التسجيل</h2>
</div>
<div style="display:flex;gap:12px;align-items:center;">
<a href="system_dashboard.php" class="back-btn">⬅ الرئيسية</a>
<a href="logout.php" class="logout-nav">تسجيل الخروج</a>
</div>
</header>

<div class="container-fluid mt-4 px-4">
<h3 class="text-center mb-4">طلبات إنشاء الحسابات</h3>

<div class="d-flex gap-2 mb-4 justify-content-center flex-wrap">
<a href="?filter=PENDING" class="filter-btn btn <?= $filter=='PENDING'?'btn-warning':'btn-outline-warning' ?>">
⏳ قيد الانتظار
<?php $cnt=$conn->query("SELECT COUNT(*) c FROM employees WHERE account_status='PENDING'")->fetch_assoc(); ?>
<span class="badge bg-dark ms-1"><?= $cnt['c'] ?></span>
</a>
<a href="?filter=APPROVED" class="filter-btn btn <?= $filter=='APPROVED'?'btn-success':'btn-outline-success' ?>">✅ مقبولة</a>
<a href="?filter=REJECTED" class="filter-btn btn <?= $filter=='REJECTED'?'btn-danger':'btn-outline-danger' ?>">❌ مرفوضة</a>
</div>

<div class="table-responsive">
<table class="table text-center">
<thead><tr>
<th>رقم</th><th>الاسم</th><th>اسم المستخدم</th><th>البريد</th><th>نوع الطلب</th><th>الحالة</th><th>تفاصيل / إجراء</th>
</tr></thead>
<tbody>
<?php if($requests->num_rows==0): ?>
<tr><td colspan="7" class="py-4 text-muted">لا توجد طلبات</td></tr>
<?php endif; ?>
<?php while($row=$requests->fetch_assoc()):
$dept=$row['department'];
$deptInfo=$deptLabels[$dept]??['label'=>$dept,'color'=>'bg-secondary'];
?>
<tr>
<td><?=$row['employee_id']?></td>
<td><?=htmlspecialchars($row['full_name'])?></td>
<td><?=htmlspecialchars($row['username'])?></td>
<td><?=htmlspecialchars($row['email'])?></td>
<td><span class="badge <?=$deptInfo['color']?>"><?=$deptInfo['label']?></span></td>
<td>
<?php
if($row['account_status']=='PENDING') echo "<span class='badge bg-warning text-dark'>قيد الانتظار</span>";
elseif($row['account_status']=='APPROVED') echo "<span class='badge bg-success'>مقبول</span>";
else echo "<span class='badge bg-danger'>مرفوض</span>";
?>
</td>
<td>
<button class="btn btn-sm fw-bold" style="background:#01251a;color:#fff;border-radius:8px;"
data-bs-toggle="modal" data-bs-target="#m<?=$row['employee_id']?>">عرض</button>
</td>
</tr>

<div class="modal fade" id="m<?=$row['employee_id']?>" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title"><?=$deptInfo['label']?></h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-4">
<div class="info-row"><strong>الاسم:</strong> <?=htmlspecialchars($row['full_name'])?></div>
<div class="info-row"><strong>اسم المستخدم:</strong> <?=htmlspecialchars($row['username'])?></div>
<div class="info-row"><strong>البريد:</strong> <?=htmlspecialchars($row['email'])?></div>
<div class="info-row"><strong>الدور:</strong> <span class="badge <?=$deptInfo['color']?>"><?=$deptInfo['label']?></span></div>
<div class="info-row"><strong>الحالة:</strong>
<?php
if($row['account_status']=='PENDING') echo "<span class='badge bg-warning text-dark'>قيد الانتظار</span>";
elseif($row['account_status']=='APPROVED') echo "<span class='badge bg-success'>مقبول</span>";
else echo "<span class='badge bg-danger'>مرفوض</span>";
?>
</div>
<?php if($row['account_status']=='PENDING'): ?>
<hr>
<div class="d-flex gap-2 justify-content-center mt-3">
<form method="POST">
<input type="hidden" name="emp_id" value="<?=$row['employee_id']?>">
<input type="hidden" name="action" value="APPROVED">
<button type="submit" class="btn btn-success fw-bold px-4">✅ قبول</button>
</form>
<form method="POST">
<input type="hidden" name="emp_id" value="<?=$row['employee_id']?>">
<input type="hidden" name="action" value="REJECTED">
<button type="submit" class="btn btn-danger fw-bold px-4" onclick="return confirm('هل تريد رفض هذا الطلب؟')">❌ رفض</button>
</form>
</div>
<?php endif; ?>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
</div>
</div>
</div>
</div>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
