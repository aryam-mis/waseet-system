<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "مدير قانوني"){
    header("Location: login.php"); exit();
}
$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

$errors = []; $success = "";

if(isset($_POST['save'])){
    $case_id      = intval($_POST['case_id']);
    $session_date = $_POST['session_date'];
    $session_time = $_POST['session_time'];

    // التحقق من الوقت
    if(strtotime("$session_date $session_time") <= time())
        $errors[] = "لا يمكن جدولة جلسة في وقت سابق";

    // التحقق من ساعات العمل
    $h = (int)date("H", strtotime($session_time));
    if($h < 8 || $h >= 16)
        $errors[] = "الوقت يجب أن يكون بين 8 صباحاً و 4 مساءً";

    // التحقق من المعالج
    $handler_row = $conn->query("SELECT employee_id FROM casehandlers WHERE case_id=$case_id LIMIT 1")->fetch_assoc();
    if(!$handler_row)
        $errors[] = "لا يوجد معالج معين لهذه الشكوى، قم بتعيين معالج أولاً";

    // التحقق من الجلسات السابقة
    if($conn->query("SELECT session_id FROM sessions WHERE case_id=$case_id LIMIT 1")->num_rows > 0)
        $errors[] = "هذه الشكوى لديها جلسة مجدولة مسبقاً";

    if(empty($errors)){
        $hid = $handler_row['employee_id'];

        // منع تعارض الجلسات

        // التحقق من تعارض المعالج
        $conflict_handler = $conn->query("
            SELECT s.session_id FROM sessions s
            JOIN casehandlers ch ON s.case_id = ch.case_id
            WHERE ch.employee_id = $hid
            AND DATE(s.session_date) = '$session_date'
            AND ABS(
                TIME_TO_SEC(s.session_time) - TIME_TO_SEC('$session_time')
            ) < 3600
            LIMIT 1
        ");
        if($conflict_handler->num_rows > 0)
            $errors[] = "المعالج لديه جلسة في نفس اليوم والفرق أقل من ساعة — مدة الجلسة ساعة كاملة";

        // التحقق من تعارض الأطراف
        $parties_q = $conn->query("SELECT employee_id FROM caseparties WHERE case_id=$case_id AND employee_id IS NOT NULL");
        while($pt = $parties_q->fetch_assoc()){
            $pid = $pt['employee_id'];
            $conflict_party = $conn->query("
                SELECT s.session_id FROM sessions s
                JOIN caseparties cp ON s.case_id = cp.case_id
                WHERE cp.employee_id = $pid
                AND DATE(s.session_date) = '$session_date'
                AND ABS(
                    TIME_TO_SEC(s.session_time) - TIME_TO_SEC('$session_time')
                ) < 3600
                AND s.case_id != $case_id
                LIMIT 1
            ");
            if($conflict_party->num_rows > 0){
                $pname = $conn->query("SELECT full_name FROM employees WHERE employee_id=$pid")->fetch_assoc()['full_name'] ?? 'أحد الأطراف';
                $errors[] = "تعارض: $pname لديه جلسة في نفس اليوم والفرق أقل من ساعة";
            }
        }
    }

    if(empty($errors)){
        $link = "www/$case_id";
        $conn->query("
            INSERT INTO sessions (case_id, session_date, session_time, location_details, settlement_status)
            VALUES($case_id, '$session_date 00:00:00', '$session_time', '$link', 'PENDING')
        ");
        $sid = $conn->insert_id;
        // حفظ الأطراف
        $pts = $conn->query("SELECT employee_id FROM caseparties WHERE case_id=$case_id AND employee_id IS NOT NULL");
        while($pt = $pts->fetch_assoc())
            $conn->query("INSERT IGNORE INTO session_parties(session_id,employee_id) VALUES($sid,{$pt['employee_id']})");
        // حفظ المعالج
        $conn->query("INSERT IGNORE INTO session_parties(session_id,employee_id) VALUES($sid,$hid)");
        // تحديث الحالة
        $conn->query("UPDATE cases SET status='IN_MEDIATION' WHERE case_id=$case_id");
        $success = "✅ تم جدولة الجلسة بنجاح — رابط الجلسة: $link";
    }
}

$case_id = intval($_POST['case_id'] ?? $_GET['case_id'] ?? 0);
$case = null; $handler_name = ""; $parties_list = [];

if($case_id > 0){
    $case = $conn->query("SELECT * FROM cases WHERE case_id=$case_id")->fetch_assoc();
    $h = $conn->query("SELECT e.full_name FROM casehandlers ch JOIN employees e ON ch.employee_id=e.employee_id WHERE ch.case_id=$case_id LIMIT 1")->fetch_assoc();
    $handler_name = $h['full_name'] ?? '';
    $pts = $conn->query("
        SELECT cp.party_role, cp.party_type, e.full_name, c.external_party_name
        FROM caseparties cp
        LEFT JOIN employees e ON cp.employee_id=e.employee_id
        LEFT JOIN cases c ON c.case_id=cp.case_id
        WHERE cp.case_id=$case_id
    ");
    while($p = $pts->fetch_assoc()) $parties_list[] = $p;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><title>جدولة جلسة</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
<style>
body{background:#f4f7fb;font-family:'Cairo',sans-serif;}
.box{background:#fff;padding:30px;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:640px;margin:40px auto;}
h4{color:#01251a;font-weight:800;}
label{font-weight:700;color:#01251a;margin-bottom:6px;display:block;}
.form-control{border-radius:12px;border:1px solid #e0e7ff;padding:10px 14px;width:100%;font-family:'Cairo',sans-serif;cursor:pointer;}
.form-control:focus{border-color:#01251a;box-shadow:0 0 0 3px rgba(1,37,26,.1);outline:none;}
.btn-main{background:#01251a;color:#fff;border:none;border-radius:12px;padding:12px;font-weight:700;width:100%;font-size:16px;}
.btn-main:hover{background:#014a36;}
.btn-back{background:#64748b;color:#fff;border:none;border-radius:12px;padding:10px 20px;font-weight:700;text-decoration:none;display:inline-block;}
.info-card{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:16px 20px;margin-bottom:20px;}
.info-card p{margin:0;font-weight:600;color:#166534;line-height:2;}
.party-row{background:#f8fafc;border:1px solid rgba(0,0,0,.06);border-radius:10px;padding:10px 14px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;}
.link-box{background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:12px 16px;color:#166534;font-weight:700;}
.badge{font-size:12px;padding:5px 10px;border-radius:8px;font-weight:700;}
</style>
</head>
<body>
<div class="box">
<h4 class="text-center mb-4">📅 جدولة جلسة وساطة</h4>

<?php foreach($errors as $e) echo "<div class='alert alert-danger fw-bold'>⚠️ $e</div>"; ?>
<?php if($success) echo "<div class='alert alert-success fw-bold'>$success</div>"; ?>

<?php if($case): ?>

<div class="info-card">
    <p><strong>رقم الشكوى:</strong> #<?= $case_id ?></p>
    <p><strong>العنوان:</strong> <?= htmlspecialchars($case['title']) ?></p>
    <p><strong>المعالج المعين:</strong>
        <?= $handler_name
            ? "<span class='badge bg-success'>$handler_name</span>"
            : "<span class='badge bg-danger'>لم يتم التعيين بعد</span>" ?>
    </p>
</div>

<?php if(!empty($parties_list)): ?>
<p class="fw-bold mb-2">👥 أطراف الشكوى:</p>
<?php foreach($parties_list as $p):
    $nm = !empty($p['full_name']) ? $p['full_name'] : ($p['external_party_name'] ?? 'غير محدد');
    $rb = ($p['party_role']=='مدعي') ? 'bg-primary' : 'bg-danger';
?>
<div class="party-row">
    <strong><?= htmlspecialchars($nm) ?></strong>
    <div>
        <span class="badge <?= $rb ?>"><?= $p['party_role'] ?></span>
        <span class="badge bg-secondary ms-1"><?= $p['party_type'] ?></span>
    </div>
</div>
<?php endforeach; ?>
<hr>
<?php endif; ?>

<?php if(empty($success)): ?>
<form method="POST">
<input type="hidden" name="case_id" value="<?= $case_id ?>">
<div class="mb-4">
    <label>📅 تاريخ الجلسة</label>
    <input type="date" name="session_date" class="form-control"
           min="<?= date('Y-m-d') ?>" required onclick="this.showPicker()">
</div>
<div class="mb-4">
    <label>⏰ وقت الجلسة</label>
    <input type="time" name="session_time" class="form-control"
           min="08:00" max="23:00" required onclick="this.showPicker()">
</div>
<div class="mb-4">
    <label>🔗 رابط الجلسة</label>
    <div class="link-box">يتم توليده تلقائياً: <strong>www/<?= $case_id ?></strong></div>
</div>
<button type="submit" name="save" class="btn-main">جدولة الجلسة</button>
</form>
<?php endif; ?>

<?php else: ?>
<form method="GET">
<div class="mb-4">
    <label>اختر الشكوى</label>
    <select name="case_id" class="form-control" required onchange="this.form.submit()">
    <option value="">-- اختر شكوى --</option>
    <?php
    $list = $conn->query("
        SELECT c.case_id, c.title FROM cases c
        JOIN casehandlers ch ON c.case_id=ch.case_id
        WHERE c.case_id NOT IN (SELECT case_id FROM sessions)
        AND c.status NOT IN ('CLOSED_SETTLED','CLOSED_DECIDED')
        ORDER BY c.case_id DESC
    ");
    while($c = $list->fetch_assoc())
        echo "<option value='{$c['case_id']}'>#{$c['case_id']} - ".htmlspecialchars($c['title'])."</option>";
    ?>
    </select>
</div>
</form>
<?php endif; ?>

</div>
<div class="text-center mt-3">
    <a href="legal_dashboardM.php" class="btn-back">⬅ العودة للرئيسية</a>
</div>
</body>
</html>
