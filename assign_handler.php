<?php
session_start();
// شرط: لازم يكون مسجل دخول
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }
$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

// شرط: نحدد صفحة الرجوع بناءً على دور المستخدم (مدير قانوني أو معالج)
$back=(isset($_SESSION['department'])&&$_SESSION['department']=='مدير قانوني')
    ?'legal_view_casesM.php':'legal_view_cases.php';

// شرط: الكود يشتغل بس لو ضغطوا زر تعيين
if(isset($_POST['assign'])){
    $case_id   =intval($_POST['case_id']);
    $handler_id=intval($_POST['handler_id']);
    // استعلام: نتحقق لو فيه معالج مسبق لهذي الشكوى
    $exists=$conn->query("SELECT handler_id FROM casehandlers WHERE case_id=$case_id LIMIT 1")->num_rows;
    // شرط: لو في معالج مسبق نحدّثه، لو لأ نضيف سجل جديد
    if($exists>0){
        // استعلام: نغير المعالج الحالي بالمعالج الجديد
        $conn->query("UPDATE casehandlers SET employee_id=$handler_id, assigned_at=NOW() WHERE case_id=$case_id");
    } else {
        // استعلام: نضيف معالج جديد للشكوى
        $conn->query("INSERT INTO casehandlers(case_id,employee_id,handler_role,assigned_at) VALUES($case_id,$handler_id,'LEGAL_DEPT',NOW())");
    }
    // استعلام: نغير حالة الشكوى من OPEN لـ UNDER_REVIEW بس لو كانت OPEN
    $conn->query("UPDATE cases SET status='UNDER_REVIEW' WHERE case_id=$case_id AND status='OPEN'");
    echo "<script>alert('تم تعيين المعالج بنجاح');window.location='$back';</script>";
    exit();
}

// استعلام: نجيب بيانات الشكوى من cases
$case_id=intval($_GET['case_id']??0);
$case=$conn->query("SELECT * FROM cases WHERE case_id=$case_id")->fetch_assoc();
if(!$case){ echo "<script>alert('شكوى غير موجودة');window.location='$back';</script>"; exit(); }
// استعلام: نجيب قائمة المعالجين القانونيين من employees
$handlers=$conn->query("SELECT employee_id,full_name FROM employees WHERE department='معالج قانوني'");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>تعيين معالج</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
<style>
body{background:#f4f7fb;font-family:'Cairo',sans-serif;}
.box{background:#fff;padding:30px;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:500px;margin:60px auto;}
label{font-weight:700;color:#01251a;margin-bottom:6px;display:block;}
.form-control{border-radius:12px;border:1px solid #e0e7ff;padding:10px 14px;width:100%;font-family:'Cairo',sans-serif;}
.btn-main{background:#01251a;color:#fff;border:none;border-radius:12px;padding:12px;font-weight:700;width:100%;}
.btn-back{background:#64748b;color:#fff;border:none;border-radius:12px;padding:10px 20px;font-weight:700;text-decoration:none;display:inline-block;}
</style>
</head>
<body>
<div class="box">
<h4 class="text-center mb-4" style="color:#01251a;">تعيين معالج للشكوى #<?=$case_id?></h4>
<p><strong>العنوان:</strong> <?=htmlspecialchars($case['title'])?></p>
<form method="POST">
<input type="hidden" name="case_id" value="<?=$case_id?>">
<div class="mb-4">
<label>اختر المعالج القانوني</label>
<select name="handler_id" class="form-control" required>
<option value="">-- اختر --</option>
<?php while($h=$handlers->fetch_assoc()) echo "<option value='{$h['employee_id']}'>{$h['full_name']}</option>"; ?>
</select>
</div>
<button type="submit" name="assign" class="btn-main mb-3">تعيين</button>
</form>
<div class="text-center"><a href="<?=$back?>" class="btn-back">⬅ رجوع</a></div>
</div>
</body></html>
