<?php
session_start();

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$message = "";

// شرط: الفورم ما يتنفذ إلا لو ضغطوا زر تسجيل الدخول
if(isset($_POST['login'])){
    $login_input = trim($_POST['login_input']);
    $password    = trim($_POST['password']);

    // شرط: لو أي حقل فاضي توقفي ولا تكملين
    if($login_input=="" || $password==""){
        $message = "يرجى تعبئة جميع الحقول";
    // شرط: كلمة المرور لازم 8 أحرف على الأقل
    } elseif(strlen($password) < 8){
        $message = "كلمة المرور يجب ألا تقل عن 8 رموز";
    } else {
        // استعلام: يدور على المستخدم في جدول 
        $stmt = $conn->prepare("SELECT * FROM employees WHERE email=? OR username=?");
        $stmt->bind_param("ss",$login_input,$login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        // شرط: لازم يلقى سجل واحد بالضبط
        if($result->num_rows == 1){
            $user = $result->fetch_assoc();

            // شرط: يتحقق من كلمة المرور مع الهاش المخزن في قاعدة البيانات
            if(password_verify($password, $user['password_hash'])){

                // التحقق من الحساب
                $status = $user['account_status'] ?? 'APPROVED';

                // شرط: لو الحساب ما زال منتظر موافقة المسؤول
                if($status == 'PENDING'){
                    $message = "⏳ طلبك قيد المراجعة، يرجى انتظار موافقة مسؤول النظام.";
                // شرط: لو الحساب مرفوض
                } elseif($status == 'REJECTED'){
                    $message = "❌ تم رفض طلبك، يرجى التواصل مع مسؤول النظام.";
                } else {
                    // تسجيل الدخول: نحفظ بيانات المستخدم في الـ SESSION
                    $_SESSION['user_id']    = $user['employee_id'];
                    $_SESSION['full_name']  = $user['full_name'];
                    $_SESSION['department'] = $user['department'];

                    // شرط: نوجه المستخدم للداشبورد المناسب حسب دوره
                    if($user['department'] == "موظف")
                        header("Location: employee_dashboard.php");
                    elseif($user['department'] == "معالج قانوني")
                        header("Location: legal_dashboard.php");
                    elseif($user['department'] == "مدير قانوني")
                        header("Location: legal_dashboardM.php");
                    elseif($user['department'] == "الإدارة العليا")
                        header("Location: admin_dashboard.php");
                    elseif($user['department'] == "مسؤول النظام")
                        header("Location: system_dashboard.php");
                    else
                        $message = "الدور غير معرف";
                    exit();
                }
            } else {
                $message = "كلمة المرور غير صحيحة";
            }
        } else {
            $message = "البريد الإلكتروني أو اسم المستخدم غير موجود";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{margin:0;height:100vh;font-family:'Cairo',sans-serif;background:#f5f7fb;display:flex;align-items:center;justify-content:center;overflow:hidden;}
body::before,body::after{content:"";position:absolute;width:700px;height:700px;border-radius:50%;filter:blur(140px);opacity:0.45;animation:float 12s ease-in-out infinite alternate;}
body::before{background:#d8fff1;top:-180px;left:-180px;}
body::after{background:#dff6ff;bottom:-180px;right:-180px;animation-delay:2s;}
@keyframes float{from{transform:translate(0,0) scale(1);}to{transform:translate(60px,-40px) scale(1.05);}}
.container{position:relative;z-index:2;width:100%;max-width:420px;}
.card{background:rgba(255,255,255,0.9);backdrop-filter:blur(10px);border-radius:24px;padding:40px;border:1px solid rgba(0,0,0,0.05);box-shadow:0 20px 50px rgba(0,0,0,0.08);transition:0.35s;}
.card:hover{transform:translateY(-8px);box-shadow:0 25px 60px rgba(0,0,0,0.12);}
h2{text-align:center;color:#01251a;margin-bottom:28px;font-weight:800;}
label{color:#01251a;font-weight:600;margin-bottom:6px;}
.form-control{width:100%;padding:13px 15px;border-radius:14px;border:1px solid #dbe4e8;background:#f9fbfc;transition:0.25s;box-shadow:0 4px 10px rgba(0,0,0,0.03);}
.form-control:focus{outline:none;border-color:#01251a;background:#fff;box-shadow:0 0 0 4px rgba(1,37,26,0.12);}
.btn-custom{width:100%;padding:13px;border:none;border-radius:14px;background:#01251a;color:#fff;font-weight:700;transition:0.3s;box-shadow:0 10px 25px rgba(1,37,26,0.2);}
.btn-custom:hover{background:#01412d;transform:translateY(-2px);color:#fff;}
.msg{padding:12px;border-radius:12px;text-align:center;margin-bottom:15px;font-weight:600;}
.msg-error{background:#fef2f2;color:#dc2626;}
.msg-warning{background:#fffbeb;color:#92400e;}
.login-note{text-align:center;color:#6b7280;margin-top:15px;font-size:14px;}
.login-note a{color:#01251a;text-decoration:none;font-weight:700;}
</style>
</head>
<body>
<div class="container">
<div class="card">
<h2>تسجيل الدخول</h2>

<?php if($message): ?>
<div class="msg <?= str_contains($message,'⏳')||str_contains($message,'❌') ? 'msg-warning' : 'msg-error' ?>">
<?= $message ?>
</div>
<?php endif; ?>

<form method="POST">
<div class="mb-3">
<label>البريد الإلكتروني أو اسم المستخدم</label>
<input type="text" class="form-control" name="login_input" placeholder="ادخل اسم المستخدم أو البريد" required>
</div>

<div class="mb-3">
<label>كلمة المرور</label>
<input type="password" class="form-control" name="password" placeholder="كلمة المرور لا تقل عن 8 رموز" minlength="8" required>
</div>

<div class="d-grid mb-3">
<button type="submit" name="login" class="btn btn-custom">تسجيل الدخول</button>
</div>
</form>

<div class="login-note">
ليس لديك حساب؟ <a href="register.php">طلب إنشاء حساب</a>
</div>

</div>
</div>
</body>
</html>
