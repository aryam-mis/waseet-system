<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "مسؤول النظام"){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error){
    die("فشل الاتصال");
}

$errors = [];
$success = "";

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM employees WHERE employee_id=$id");
    header("Location: manage_users.php");
    exit();
}

if(isset($_POST['save_user'])){

    $id = $_POST['employee_id'] ?? '';
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $department = $_POST['department'];
    $password = $_POST['password'] ?? '';

    if(strlen($full_name) < 3){
        $errors[] = "❌ الاسم يجب أن يكون 3 أحرف على الأقل";
    }

    elseif(!str_ends_with($email, ".com")){
        $errors[] = "❌ البريد يجب أن ينتهي بـ .com";
    }

    if(strlen($username) < 4){
        $errors[] = "❌ اسم المستخدم يجب أن يكون 4 أحرف على الأقل";
    }

    if(empty($department)){
        $errors[] = "❌ يجب اختيار الدور";
    }

    if($id == "" && strlen($password) < 8){
        $errors[] = "❌ كلمة المرور يجب أن تكون 8 أحرف على الأقل";
    }

    /* التحقق */
    if(empty($errors)){
        $check = $conn->query("SELECT * FROM employees 
        WHERE (email='$email' OR username='$username') 
        AND employee_id != '$id'");

        if($check->num_rows > 0){
            $errors[] = "❌ البريد أو اسم المستخدم مستخدم مسبقاً";
        } else {

            if($id == ""){

                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $conn->query("
                    INSERT INTO employees(full_name,email,username,password_hash,department)
                    VALUES('$full_name','$email','$username','$password_hash','$department')
                ");

                $success = "✅ تم إضافة المستخدم بنجاح";

            } else {

                $conn->query("
                    UPDATE employees SET
                    full_name='$full_name',
                    email='$email',
                    username='$username',
                    department='$department'
                    WHERE employee_id=$id
                ");

                $success = "✅ تم تعديل بيانات المستخدم بنجاح";
            }
        }
    }
}

$editUser = null;

if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $editUser = $conn->query("SELECT * FROM employees WHERE employee_id=$id")->fetch_assoc();
}

$users = $conn->query("SELECT * FROM employees ORDER BY employee_id DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة المستخدمين</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>body{
background: linear-gradient(135deg,#f8fbfa,#eef7f5,#f5fffc);
color:#2d2d2d;
font-family:'Cairo',sans-serif;
}

h2{
color:#01251a;
font-weight:700;
letter-spacing:0.5px;
}

.form-control,
select{
background:rgba(255,255,255,0.78);
color:#333;
border:1px solid rgba(1,37,26,0.12);
border-radius:12px;
backdrop-filter:blur(8px);
box-shadow:0 4px 12px rgba(0,0,0,0.04);
transition:0.3s;
}

.form-control:focus,
select:focus{
border-color:#7fc8b2;
box-shadow:0 0 0 3px rgba(127,200,178,0.25);
background:#fff;
}

.table{
background:rgba(255,255,255,0.85);
color:#333;
border-radius:18px;
overflow:hidden;
backdrop-filter:blur(10px);
box-shadow:0 10px 25px rgba(0,0,0,0.06);
}

.table thead{
background:#01251a;
color:#ffffff;
}

.table-striped>tbody>tr:nth-of-type(odd){
background:#f7fbfa;
}

.table-striped>tbody>tr:hover{
background:rgba(183,231,218,0.35);
transition:0.2s;
}

.btn-outline-light{
color:#01251a;
border:1px solid rgba(1,37,26,0.15);
background:rgba(255,255,255,0.9);
border-radius:12px;
transition:0.3s;
font-weight:600;
}

.btn-outline-light:hover{
background:#01251a;
color:#fff;
border-color:#01251a;
}

.modal-content{
background:rgba(255,255,255,0.92);
color:#333;
border-radius:18px;
border:1px solid rgba(1,37,26,0.08);
backdrop-filter:blur(12px);
box-shadow:0 15px 40px rgba(0,0,0,0.1);
}

.btn-close{
filter:none;
}

.badge{
border-radius:10px;
padding:6px 12px;
font-size:12px;
}

.bg-info{
background:#b7e7da !important;
color:#01251a !important;
}

.bg-success{
background:#c7f5d9 !important;
color:#1b4332 !important;
}

.bg-danger{
background:#ffd6d6 !important;
color:#7a1f1f !important;
}

.bg-warning{
background:#ffe8a3 !important;
color:#6b4f00 !important;
}

.bg-secondary{
background:#e9ecef !important;
color:#495057 !important;
}

.table{
background:#ffffff;
color:#111827;
border-radius:16px;
overflow:hidden;
box-shadow:0 12px 30px rgba(0,0,0,0.05);
}

.table tbody tr{
background:#ffffff;
color:#111827;
}

.table tbody tr:nth-child(even){
background:#f8fcfb;
}

.table tbody tr:hover{
background:#edf8f4;
transition:0.2s;
}

.table th{
background:linear-gradient(90deg,#d6f5ec,#b7e7da);
color:#01251a;
font-weight:800;
border:none !important;
}

.table td{
font-weight:600;
border-color:rgba(0,0,0,0.05) !important;
}
</style>
</head>

<body>

<div class="container mt-5">

<h3 class="text-center mb-4">إدارة المستخدمين</h3>

<?php if($success != ""): ?>
<div class="alert alert-success text-center"><?= $success ?></div>
<?php endif; ?>

<?php if(!empty($errors)): ?>
<div class="alert alert-danger">
<ul class="mb-0">
<?php foreach($errors as $err): ?>
<li><?= $err ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<div class="card mb-4">

<form method="POST">

<input type="hidden" name="employee_id" value="<?= $editUser['employee_id'] ?? '' ?>">

<div class="row">

<div class="col-md-3">
<input type="text" name="full_name" class="form-control" placeholder="الاسم الكامل"
value="<?= $editUser['full_name'] ?? '' ?>" required>
</div>

<div class="col-md-3">
<input type="email" name="email" class="form-control" placeholder="البريد"
value="<?= $editUser['email'] ?? '' ?>" required>
</div>

<div class="col-md-2">
<input type="text" name="username" class="form-control" placeholder="اسم المستخدم"
value="<?= $editUser['username'] ?? '' ?>" required>
</div>

<div class="col-md-2">
<select name="department" class="form-select" required>
<option value="">الدور</option>
<option value="موظف">موظف</option>
<option value="القسم القانوني">القسم القانوني</option>
<option value="الإدارة العليا">الإدارة العليا</option>
<option value="مسؤول النظام">مسؤول النظام</option>
</select>
</div>

<div class="col-md-2">
<?php if(!$editUser){ ?>
<input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
<?php } else { ?>
<input type="password" class="form-control" value="******" disabled>
<?php } ?>
</div>

</div>

<div class="text-center mt-3">
<button type="submit" name="save_user" class="btn btn-light px-4">
حفظ
</button>
</div>

</form>

</div>

<table class="table table-striped text-center">

<thead>
<tr>
<th>الاسم</th>
<th>البريد</th>
<th>اسم المستخدم</th>
<th>الدور</th>
<th>إجراءات</th>
</tr>
</thead>

<tbody>

<?php while($u = $users->fetch_assoc()){ ?>
<tr>
<td><?= $u['full_name'] ?></td>
<td><?= $u['email'] ?></td>
<td><?= $u['username'] ?></td>
<td><?= $u['department'] ?></td>
<td>

<a href="?edit=<?= $u['employee_id'] ?>" class="btn btn-warning btn-sm">تعديل</a>

<a href="?delete=<?= $u['employee_id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل تريد حذف المستخدم؟')">
حذف
</a>

</td>
</tr>
<?php } ?>

</tbody>
</table>

<div class="text-center mt-4">
<a href="system_dashboard.php" class="btn btn-outline-light">
⬅ العودة للوحة التحكم
</a>
</div>

</div>

</body>
</html>