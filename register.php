<?php
session_start();

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

$message = "";
$success = false;

// شرط: الكود ما يشتغل إلا لو ضغطوا زر إرسال الطلب
if(isset($_POST['register'])){
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $username   = trim($_POST['username']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];
    $department = $_POST['department'];
    $errors = [];

    // شرط: نتحقق من كل الحقول قبل ما نروح قاعدة البيانات
    if(strlen($full_name) < 3)   $errors[] = "الاسم يجب أن يكون 3 أحرف على الأقل";
    // شرط: يتحقق إن صيغة البريد صحيحة مثل example@gmail.com
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صحيح";
    if(strlen($username) < 4)    $errors[] = "اسم المستخدم يجب أن يكون 4 أحرف على الأقل";
    if(strlen($password) < 8)    $errors[] = "كلمة المرور يجب أن تكون 8 أحرف على الأقل";
    // شرط: يتأكد إن كلمة المرور وتأكيدها متطابقتين
    if($password !== $confirm)   $errors[] = "كلمتا المرور غير متطابقتين";
    if(empty($department))       $errors[] = "يجب اختيار الدور";

    // شرط: نكمل فقط لو ما في أخطاء
    if(empty($errors)){
        // استعلام: يتحقق إن البريد أو اسم المستخدم مو مسجلين مسبقاً في employees
        $chk = $conn->prepare("SELECT employee_id, account_status FROM employees WHERE email=? OR username=?");
        $chk->bind_param("ss",$email,$username);
        $chk->execute();
        $chk_result = $chk->get_result();
        if($chk_result->num_rows > 0){
            $chk_row = $chk_result->fetch_assoc();
            // شرط: لو البيانات موجودة وحالتها مرفوضة نعطي رسالة خاصة
            if($chk_row['account_status'] == 'REJECTED'){
                $errors[] = "تم رفض طلبك مسبقاً، يرجى التواصل مع مسؤول النظام";
            } else {
                $errors[] = "البريد الإلكتروني أو اسم المستخدم مستخدم مسبقاً";
            }
        }
    }

    if(empty($errors)){
        // نشفر كلمة المرور قبل الحفظ، ما نخزنها نص صريح أبداً
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // استعلام: نضيف المستخدم الجديد بحالة PENDING — ينتظر موافقة المسؤول
        $stmt = $conn->prepare("
            INSERT INTO employees (full_name, department, email, username, password_hash, account_status)
            VALUES (?,?,?,?,?,'PENDING')
        ");
        $stmt->bind_param("sssss",$full_name,$department,$email,$username,$hash);
        if($stmt->execute()){
            $success = true;
            $message = "تم إرسال طلب التسجيل بنجاح، يرجى انتظار موافقة مسؤول النظام.";
        } else {
            $message = "حدث خطأ، يرجى المحاولة مرة أخرى";
        }
    } else {
        $message = implode(" | ", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>طلب إنشاء حساب</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
body{margin:0;min-height:100vh;font-family:'Cairo',sans-serif;background:#f5f7fb;display:flex;align-items:center;justify-content:center;overflow-x:hidden;position:relative;}
body::before,body::after{content:"";position:absolute;width:700px;height:700px;border-radius:50%;filter:blur(140px);opacity:0.45;animation:float 12s ease-in-out infinite alternate;}
body::before{background:#d8fff1;top:-180px;left:-180px;}
body::after{background:#dff6ff;bottom:-180px;right:-180px;animation-delay:2s;}
@keyframes float{from{transform:translate(0,0) scale(1);}to{transform:translate(60px,-40px) scale(1.05);}}
.container{position:relative;z-index:2;max-width:520px;width:100%;padding:30px 0;}
.card{background:rgba(255,255,255,0.92);backdrop-filter:blur(12px);border-radius:24px;padding:38px;border:1px solid rgba(0,0,0,0.05);box-shadow:0 20px 50px rgba(0,0,0,0.08);}
h2{color:#01251a;font-weight:800;}
label{color:#01251a;font-weight:600;margin-bottom:6px;}
.form-control,.form-select{border-radius:14px;border:1px solid #dbe4e8;padding:13px 15px;background:#f9fbfc;color:#111827;transition:0.25s;}
.form-control:focus,.form-select:focus{border-color:#01251a;background:#fff;box-shadow:0 0 0 4px rgba(1,37,26,0.12);}
.btn-custom{background:#01251a;color:#fff;border:none;border-radius:14px;padding:13px;font-size:16px;font-weight:700;transition:0.3s;width:100%;}
.btn-custom:hover{background:#01412d;color:#fff;transform:translateY(-2px);}
.alert-error{border-radius:12px;background:#fef2f2;color:#dc2626;border:none;font-weight:600;padding:12px;text-align:center;margin-bottom:15px;}
.alert-success{border-radius:12px;background:#f0fdf4;color:#166534;border:none;font-weight:600;padding:16px;text-align:center;margin-bottom:15px;}
.login-text{text-align:center;margin-top:15px;color:#6b7280;font-size:14px;}
.login-text a{color:#01251a;font-weight:700;text-decoration:none;}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2 class="text-center mb-4">طلب إنشاء حساب</h2>

<?php if($message): ?>
<div class="<?= $success ? 'alert-success' : 'alert-error' ?>">
<?= $success ? '✅ ' : '⚠️ ' ?><?= $message ?>
</div>
<?php endif; ?>

<?php if(!$success): ?>
<form method="POST">

<div class="mb-3">
<label>الاسم الكامل</label>
<input type="text" class="form-control" name="full_name" required placeholder="ادخل الاسم الكامل">
</div>

<div class="mb-3">
<label>البريد الإلكتروني</label>
<input type="email" class="form-control" name="email" required placeholder="example@gmail.com">
</div>

<div class="mb-3">
<label>اسم المستخدم</label>
<input type="text" class="form-control" name="username" required placeholder="ادخل اسم المستخدم">
</div>

<div class="mb-3">
<label>كلمة المرور</label>
<input type="password" class="form-control" name="password" required minlength="8" placeholder="8 أحرف على الأقل">
</div>

<div class="mb-3">
<label>تأكيد كلمة المرور</label>
<input type="password" class="form-control" name="confirm_password" required minlength="8" placeholder="أعد كتابة كلمة المرور">
</div>

<div class="mb-4">
<label>الدور الوظيفي</label>
<select class="form-select" name="department" required>
<option value="">-- اختر --</option>
<option value="موظف">موظف</option>
<option value="معالج قانوني">معالج قانوني</option>
<option value="مدير قانوني">مدير قانوني</option>
<option value="الإدارة العليا">الإدارة العليا</option>
</select>
</div>

<button type="submit" name="register" class="btn-custom">إرسال الطلب</button>

</form>
<?php else: ?>
<div class="text-center mt-3">
<a href="login.php" class="btn-custom" style="display:inline-block;text-decoration:none;padding:12px 30px;">تسجيل الدخول</a>
</div>
<?php endif; ?>

<div class="login-text mt-3">
لديك حساب؟ <a href="login.php">تسجيل الدخول</a>
</div>

</div>
</div>
</body>
</html>
