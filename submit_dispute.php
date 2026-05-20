<?php
session_start();

// شرط: الصفحة تشتغل بس لو المستخدم سجل دخول
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Aden');

$conn = new mysqli("localhost","root","","wasit_system");

if($conn->connect_error){
    die("فشل الاتصال بقاعدة البيانات");
}

$user_id = $_SESSION['user_id'];

// شرط: الكود ما يشتغل إلا لو ضغطوا زر الإرسال
if(isset($_POST['submit'])){

    $case_type_raw = trim($_POST['case_type']);
    $other_type = trim($_POST['other_type'] ?? '');

    // شرط: لو اختاروا "أخرى" لازم يكتبون عنوان يدوي، وإلا يوقف
    // تحديد نوع الشكوى
    if($case_type_raw == 'أخرى'){
        if(empty($other_type)){
            die("يرجى كتابة عنوان المشكلة");
        }
        $case_type = $other_type;
    } else {
        $case_type = $case_type_raw;
    }

    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    $target_type = trim($_POST['target_type']);

    $date = date("Y-m-d H:i:s");

    $other_party_id = null;
    $external_name = null;
    $party_type = "";

    // شرط: لو المدعى عليه موظف نجيب الـ ID من القائمة المنسدلة
    if($target_type == 'employee'){
        $other_party_id = !empty($_POST['employee_party']) 
        ? intval($_POST['employee_party']) 
        : null;
        $party_type = 'موظف';
    }
    // شرط: لو المدعى عليه جهة خارجية نجيب الاسم من حقل النص
    elseif($target_type == 'external'){
        $external_name = trim($_POST['external_name']);
        $party_type = 'جهة خارجية';
    }

    // شرط: الحقول الأساسية لازم تكون ممتلئة
    if(empty($case_type) || empty($description) || empty($priority)){
        die("جميع الحقول مطلوبة");
    }

    // استعلام: نضيف الشكوى الجديدة في جدول cases بحالة OPEN
    $stmt = $conn->prepare("
        INSERT INTO cases
        (
            title,
            description,
            priority,
            status,
            created_at,
            created_by_employee_id,
            external_party_name,
            party_type
        )
        VALUES
        (?, ?, ?, 'OPEN', ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssiss",
        $case_type,
        $description,
        $priority,
        $date,
        $user_id,
        $external_name,
        $party_type
    );

    if(!$stmt->execute()){
        die("خطأ في إضافة القضية");
    }

    // نجيب رقم الشكوى اللي اتضاف عشان نضيف الأطراف
    $case_id = $stmt->insert_id;

    // استعلام: نضيف المستخدم الحالي كـ "مدعي" في جدول caseparties
    $stmt1 = $conn->prepare("
        INSERT INTO caseparties
        (
            case_id,
            employee_id,
            party_role,
            party_type
        )
        VALUES
        (?, ?, 'مدعي', 'موظف')
    ");

    $stmt1->bind_param("ii", $case_id, $user_id);

    if(!$stmt1->execute()){
        die("خطأ في إضافة المدعي");
    }

    // شرط: لو الطرف الثاني موظف نضيفه كـ "مدعى عليه" بـ employee_id
    if($target_type == 'employee' && !empty($other_party_id)){

        // استعلام: نضيف الموظف المختار كطرف ثاني في caseparties
        $stmt2 = $conn->prepare("
            INSERT INTO caseparties
            (
                case_id,
                employee_id,
                party_role,
                party_type
            )
            VALUES
            (?, ?, 'مدعى عليه', 'موظف')
        ");

        $stmt2->bind_param("ii", $case_id, $other_party_id);

        if(!$stmt2->execute()){
            die("خطأ في إضافة المدعى عليه");
        }
    }

    // شرط: لو الطرف خارجي نضيفه بدون employee_id (NULL)
    if($target_type == 'external'){

        // استعلام: نضيف الجهة الخارجية كطرف في caseparties بـ employee_id = NULL
        $stmt3 = $conn->prepare("
            INSERT INTO caseparties
            (
                case_id,
                employee_id,
                party_role,
                party_type
            )
            VALUES
            (?, NULL, 'مدعى عليه', ?)
        ");

        $stmt3->bind_param("is", $case_id, $party_type);

        if(!$stmt3->execute()){
            die("خطأ في إضافة الطرف الخارجي");
        }
    }

    echo "
    <script>
    alert('تم تسجيل الشكوى رقم: $case_id');
    window.location='employee_dashboard.php';
    </script>
    ";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تقديم شكوى</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
body{
background: #f4f7fb;
color:#2d2d2d;
font-family:'Cairo',sans-serif;
}

.container{
max-width:650px;
padding:35px;
border-radius:24px;
box-shadow:0 20px 50px #01251a33;
backdrop-filter:blur(14px);
border:1px solid rgba(0,0,0,0.05);
animation:fadeIn 0.5s ease;
background:#fff;
}

h2{
font-weight:700;
color:#01251a;
}

.form-control,select,textarea{
background:#fff;
border:1px solid #e7dfd6;
border-radius:14px;
padding:12px;
color:#2d2d2d;
transition:0.3s;
cursor:pointer;
}

.form-control:focus,
select:focus,
textarea:focus{
border-color:#bfa58a;
box-shadow:0 0 0 0.2rem rgba(191,165,138,0.2);
}

label{
font-weight:600;
margin-bottom:6px;
color:#01251a;
}

.btn-custom{
background:#01251a;
color:#fff;
border:none;
border-radius:14px;
padding:11px;
font-weight:600;
transition:0.3s;
}

.btn-custom:hover{
background:#01251a;
color:#fff;
transform:translateY(-2px);
}

.time-box{
background:#f8f4ef;
padding:12px;
border-radius:14px;
border:1px solid #eadfce;
font-weight:600;
color:#5c4633;
}

/* تنسيق القائمة المنسدلة لنوع النزاع */
select#case_type option.type-label{
color:#111;
font-weight:700;
}

select#case_type option.type-sub{
color:#9ca3af;
font-weight:400;
font-size:0.9em;
}

@keyframes fadeIn{
from{opacity:0;transform:translateY(15px);}
to{opacity:1;transform:translateY(0);}
}
</style>
</head>

<body>

<div class="container mt-5">

<h2 class="text-center mb-4">تقديم شكوى</h2>

<form method="POST" id="disputeForm">

<div class="mb-3">
<label>نوع النزاع / المشكلة</label>

<select name="case_type" id="case_type" class="form-control" required onchange="handleCaseType()">
<option value="">-- اختر --</option>
<option value="مالي" data-sub="رواتب، بدلات، مكافآت">مالي &nbsp;|&nbsp; رواتب، بدلات، مكافآت</option>
<option value="إداري" data-sub="تقييم أداء، ترقيات، إجازات">إداري &nbsp;|&nbsp; تقييم أداء، ترقيات، إجازات</option>
<option value="نزاع سلوكي" data-sub="تعامل غير مهني، سوء تواصل">نزاع سلوكي &nbsp;|&nbsp; تعامل غير مهني، سوء تواصل</option>
<option value="نزاع تشغيلي" data-sub="توزيع مهام، تداخل صلاحيات">نزاع تشغيلي &nbsp;|&nbsp; توزيع مهام، تداخل صلاحيات</option>
<option value="أخرى">أخرى &nbsp;|&nbsp; يرجى كتابة العنوان يدوياً</option>
</select>

<!-- حقل النص عند اختيار "أخرى" -->
<div id="otherTypeField" style="display:none; margin-top:10px;">
<input 
  type="text" 
  name="other_type" 
  id="other_type"
  class="form-control" 
  placeholder="اكتب عنوان المشكلة (بحد أقصى 10 أحرف)"
  maxlength="10"
>
<small class="text-muted">سيُستخدم هذا النص كعنوان للشكوى</small>
</div>
</div>

<div class="mb-3">
<label>الوصف</label>
<textarea name="description" class="form-control" rows="4" required></textarea>
</div>

<div class="mb-3">
<label>الأولوية</label>

<select name="priority" id="prioritySelect" class="form-control" required onchange="updatePriorityColor(this)">
<option value="" disabled selected style="color:#9ca3af;">يرجى تحديد درجة الأولوية ▾</option>
<option value="عالية" style="background:#fee2e2;color:#991b1b;font-weight:700;">عالية</option>
<option value="متوسطة" style="background:#fef9c3;color:#854d0e;font-weight:700;">متوسطة</option>
<option value="منخفضة" style="background:#dcfce7;color:#166534;font-weight:700;">منخفضة</option>
</select>
<script>
function updatePriorityColor(sel){
    var colors = {
        'عالية':   {bg:'#fee2e2',color:'#991b1b'},
        'متوسطة':  {bg:'#fef9c3',color:'#854d0e'},
        'منخفضة':  {bg:'#dcfce7',color:'#166534'}
    };
    var v = sel.value;
    if(colors[v]){
        sel.style.background = colors[v].bg;
        sel.style.color = colors[v].color;
        sel.style.fontWeight = '700';
    }
}
// تهيئة الصفحة
window.addEventListener('load', function(){
    var s = document.getElementById('prioritySelect');
    if(s) updatePriorityColor(s);
});
</script>
</div>

<div class="mb-3">
<label>نوع المدعى عليه</label>

<select name="target_type" id="targetType" class="form-control" onchange="handleTarget()" required>
<option value="">-- اختر --</option>
<option value="employee">موظف</option>
<option value="external">جهة خارجية</option>
</select>
</div>

<div class="mb-3" id="employeeList" style="display:none;">
<label>اختر الموظف</label>

<select name="employee_party" class="form-control">

<?php
$users = $conn->query("
SELECT employee_id, full_name
FROM employees
WHERE employee_id != $user_id
AND department='موظف'
AND account_status='APPROVED'
");

while($row = $users->fetch_assoc()){
echo "<option value='{$row['employee_id']}'>{$row['full_name']}</option>";
}
?>

</select>
</div>

<div class="mb-3" id="externalField" style="display:none;">
<label>اسم الجهة الخارجية</label>
<input type="text" name="external_name" class="form-control">
</div>

<div class="mb-3">
<label>التاريخ والوقت الحالي</label>

<input 
type="text" 
id="liveDateTime"
class="form-control time-box"
readonly
>
</div>

<div class="d-flex gap-2">

<button type="submit" name="submit" class="btn btn-custom w-50">
إرسال
</button>

<a href="employee_dashboard.php" class="btn btn-custom w-50">
رجوع
</a>

</div>

</form>
</div>

<script>
function handleCaseType(){
    var val = document.getElementById("case_type").value;
    var otherField = document.getElementById("otherTypeField");
    var otherInput = document.getElementById("other_type");

    if(val === "أخرى"){
        otherField.style.display = "block";
        otherInput.setAttribute("required","required");
    } else {
        otherField.style.display = "none";
        otherInput.removeAttribute("required");
        otherInput.value = "";
    }
}

function handleTarget(){
    let type = document.getElementById("targetType").value;

    document.getElementById("employeeList").style.display = "none";
    document.getElementById("externalField").style.display = "none";

    if(type === "employee"){
        document.getElementById("employeeList").style.display = "block";
    }
    else if(type === "external"){
        document.getElementById("externalField").style.display = "block";
    }
}

function updateDateTime(){
    let now = new Date();
    let year = now.getFullYear();
    let month = String(now.getMonth() + 1).padStart(2,'0');
    let day = String(now.getDate()).padStart(2,'0');
    let hours = String(now.getHours()).padStart(2,'0');
    let minutes = String(now.getMinutes()).padStart(2,'0');
    let seconds = String(now.getSeconds()).padStart(2,'0');
    let full = year + "-" + month + "-" + day + " " + hours + ":" + minutes + ":" + seconds;
    document.getElementById("liveDateTime").value = full;
}

setInterval(updateDateTime,1000);
updateDateTime();
</script>

</body>
</html>
