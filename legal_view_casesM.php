<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "مدير قانوني"){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error){
    die("فشل الاتصال");
}

function translateStatus($status){
    $map = [
        'OPEN' => 'جديدة',
        'UNDER_REVIEW' => 'قيد المراجعة',
        'IN_MEDIATION' => 'في الوساطة',
        'ESCALATED' => 'مصعدة',
        'CLOSED_SETTLED' => 'مغلقة (تسوية)',
        'CLOSED_DECIDED' => 'مغلقة (قرار نهائي)'
    ];
    return $map[$status] ?? $status;
}

if(isset($_POST['update_priority'])){
    $case_id = intval($_POST['case_id']);
    $priority = $_POST['priority'];
    $conn->query("UPDATE cases SET priority='$priority' WHERE case_id=$case_id");
    echo "<script>
        alert('تم تغيير الأولوية بنجاح');
        window.location.href = '".$_SERVER['PHP_SELF']."';
    </script>";
    exit();
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';

// جلب الشكاوى
$query = "SELECT c.*, e.full_name AS complainant,
          (SELECT COUNT(*) FROM sessions s WHERE s.case_id = c.case_id) AS has_session
          FROM cases c
          JOIN employees e ON c.created_by_employee_id = e.employee_id
          WHERE 1";

// شكاوى بدون معالج
$query_unassigned = "SELECT c.*, e.full_name AS complainant
    FROM cases c
    JOIN employees e ON c.created_by_employee_id = e.employee_id
    LEFT JOIN casehandlers ch ON c.case_id = ch.case_id
    WHERE ch.handler_id IS NULL
    AND c.status NOT IN ('CLOSED_SETTLED','CLOSED_DECIDED')
    ORDER BY c.created_at DESC";
$result_unassigned = $conn->query($query_unassigned);

if($search != ''){
    $query .= " AND c.case_id = ".intval($search);
}

if($status != '' && $status != 'all'){
    if($status == 'IN_PROGRESS'){
        $query .= " AND c.status IN ('UNDER_REVIEW','IN_MEDIATION')";
    }
    elseif($status == 'CLOSED'){
        $query .= " AND c.status IN ('CLOSED_SETTLED','CLOSED_DECIDED')";
    }
    else{
        $query .= " AND c.status = '$status'";
    }
}

if($priority != '' && $priority != 'all'){
    $query .= " AND c.priority = '$priority'";
}

$query .= " ORDER BY c.created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>مراجعة الشكاوى - المدير القانوني</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
body{
    background: linear-gradient(135deg,#f6f9ff,#eef3ff,#f3f0ff);
    color:#2d2d2d;
    font-family:'Cairo',sans-serif;
}

h3{
    color:#01251a;
    font-weight:800;
}

.form-control, select{
    background:rgba(255,255,255,0.85);
    color:#333;
    border:1px solid rgba(0,0,0,0.1);
    border-radius:12px;
    padding:10px 14px;
    transition:0.3s;
}

.form-control:focus, select:focus{
    border-color:#a5b4fc;
    box-shadow:0 0 0 3px rgba(165,180,252,0.3);
}

.table{
    background:#ffffff;
    color:#111827;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,0.06);
}

.table thead tr{
    background: linear-gradient(90deg,#a7e6d3);
}

.table th{
    font-weight:800;
    border:none !important;
    padding:14px 10px;
    color:#111827;
}

.table tbody tr{
    background:#ffffff;
    color:#111827;
}

.table tbody tr:nth-child(even){
    background:#f8fafc;
}

.table tbody tr:hover{
    background:#eef2ff;
    transition:0.2s;
}

.table td{
    font-weight:600;
    border-color:rgba(0,0,0,0.06) !important;
    vertical-align:middle;
    padding:10px;
}

.btn-outline-light{
    color:#fff;
    border:none;
    background:#01251a;
    border-radius:12px;
    transition:0.3s;
    font-weight:700;
}

.btn-outline-light:hover{
    background:#014a36;
    color:#fff;
}

.modal-content{
    background:rgba(255,255,255,0.97);
    color:#333;
    border-radius:18px;
    box-shadow:0 15px 40px rgba(0,0,0,0.12);
}

.modal-header{
    background: linear-gradient(90deg,#01251a,#014a36);
    border-radius:18px 18px 0 0;
}

.modal-header h5, .modal-header button{
    color:#fff !important;
}

.badge{
    border-radius:10px;
    padding:6px 12px;
    font-size:12px;
    font-weight:700;
}

.bg-info{background:#a7e6d3 !important;color:#0f172a !important;}
.bg-success{background:#22c55e !important;color:#fff !important;}
.bg-danger{background:#ef4444 !important;color:#fff !important;}
.bg-warning{background:#fbbf24 !important; color:#1f2937 !important;}
.bg-secondary{background:#94a3b8 !important;color:#fff !important;}

.escalated-row{background:#fff5f5 !important;}
.session-yes{color:#16a34a;font-weight:800;}
.session-no{color:#dc2626;font-weight:800;}

.response-box{
    background:#f8fafc;
    padding:10px 14px;
    border-radius:10px;
    margin-bottom:8px;
    border:1px solid rgba(0,0,0,0.06);
}
</style>
</head>

<body>

<div class="container-fluid mt-4 px-4">

<h3 class="mb-4 text-center">مراجعة الشكاوى</h3>

<form method="GET" class="row mb-4 g-2">

<div class="col-md-3">
<input type="number" name="search" class="form-control" placeholder="بحث برقم الشكوى" value="<?php echo $search; ?>">
</div>

<div class="col-md-3">
<select name="status" class="form-control">
<option value="all">كل الحالات</option>
<option value="OPEN" <?php if($status=="OPEN") echo "selected"; ?>>جديدة</option>
<option value="IN_PROGRESS" <?php if($status=="IN_PROGRESS") echo "selected"; ?>>قيد المعالجة</option>
<option value="ESCALATED" <?php if($status=="ESCALATED") echo "selected"; ?>>مصعدة</option>
<option value="CLOSED" <?php if($status=="CLOSED") echo "selected"; ?>>مغلقة</option>
</select>
</div>

<div class="col-md-3">
<select name="priority" class="form-control">
<option value="all">كل الأولويات</option>
<option value="عالية" <?php if($priority=="عالية") echo "selected"; ?>>عالية</option>
<option value="متوسطة" <?php if($priority=="متوسطة") echo "selected"; ?>>متوسطة</option>
<option value="منخفضة" <?php if($priority=="منخفضة") echo "selected"; ?>>منخفضة</option>
</select>
</div>

<div class="col-md-3">
<button class="btn btn-outline-light w-100">بحث</button>
</div>

</form>

<div class="table-responsive">
<table class="table table-striped text-center">
<thead>
<tr>
<th>رقم الشكوى</th>
<th>العنوان</th>
<th>الحالة</th>
<th>الأولوية</th>
<th>التاريخ</th>
<th>مقدم الشكوى</th>
<th>المعالج</th>
<th>مدعى عليه</th>
<th>الجلسة</th>
<th>تفاصيل</th>
<th>الإجراءات</th>
</tr>
</thead>

<tbody>

<?php
if($result->num_rows > 0){
while($row = $result->fetch_assoc()){

$case_id = $row['case_id'];
$isEscalated = ($row['status'] == 'ESCALATED');

$handler_q = $conn->query("
SELECT e.full_name 
FROM casehandlers ch
JOIN employees e ON ch.employee_id = e.employee_id
WHERE ch.case_id = $case_id
LIMIT 1
");
$handler_row = $handler_q->fetch_assoc();
$handler_name = $handler_row['full_name'] ?? 'لم يتم التعيين';

$parties = $conn->query("
SELECT 
    cp.employee_id,
    cp.party_role,
    e.full_name,
    c.external_party_name
FROM caseparties cp
LEFT JOIN employees e ON cp.employee_id = e.employee_id
LEFT JOIN cases c ON c.case_id = cp.case_id
WHERE cp.case_id = $case_id
");

$defendant_text = "";
while($p = $parties->fetch_assoc()){
    if($p['party_role'] == 'مدعى عليه'){
        if(!empty($p['employee_id']) && !empty($p['full_name'])){
            $defendant_text .= $p['full_name']."<br>";
        } else {
            $defendant_text .= ($p['external_party_name'] ?? '')."<br>";
        }
    }
}

$hasSession = ($row['has_session'] > 0);
?>

<tr class="<?= $isEscalated ? 'escalated-row' : '' ?>">
<td><?= $row['case_id']; ?></td>
<td><?= htmlspecialchars($row['title']); ?></td>
<td>
<?php
$statusLabel = translateStatus($row['status']);
$bc = 'bg-info';
if($isEscalated) $bc='bg-danger';
elseif($row['status']=='CLOSED_SETTLED'||$row['status']=='CLOSED_DECIDED') $bc='bg-success';
elseif($row['status']=='OPEN') $bc='bg-secondary';
echo "<span class='badge $bc'>$statusLabel</span>";

?>
</td>

<td>
<form method="POST" class="d-flex gap-1 justify-content-center">
    <input type="hidden" name="case_id" value="<?= $row['case_id']; ?>">
    <select name="priority" class="form-control form-control-sm" style="width:auto;">
        <option value="عالية" <?= $row['priority']=="عالية"?'selected':''; ?>>عالية</option>
        <option value="متوسطة" <?= $row['priority']=="متوسطة"?'selected':''; ?>>متوسطة</option>
        <option value="منخفضة" <?= $row['priority']=="منخفضة"?'selected':''; ?>>منخفضة</option>
    </select>
    <button type="submit" name="update_priority" class="btn btn-warning btn-sm">حفظ</button>
</form>
</td>

<td><?= $row['created_at']; ?></td>
<td><?= htmlspecialchars($row['complainant']); ?></td>
<td><?= htmlspecialchars($handler_name); ?></td>
<td><?= $defendant_text; ?></td>

<td>
<?php
$isClosed = in_array($row['status'], ['CLOSED_SETTLED','CLOSED_DECIDED']);
if($isClosed){ ?>
<span style="color:#64748b;font-weight:700;">منتهية</span>
<?php } elseif($hasSession){ ?>
<span class="session-yes">مجدولة</span>
<?php } else { ?>
<span class="session-no">غير مجدولة</span>
<?php } ?>
</td>

<td>
<button class="btn btn-outline-light btn-sm" data-bs-toggle="modal"
data-bs-target="#modal<?= $row['case_id']; ?>">
عرض
</button>
</td>

<td>
<?php
$chkH = $conn->query("SELECT handler_id FROM casehandlers WHERE case_id={$row['case_id']} LIMIT 1");
$isEsc2    = ($row['status'] == 'ESCALATED');
$isClosed2 = in_array($row['status'], ['CLOSED_SETTLED','CLOSED_DECIDED']);
?>
<?php
$chkSession = $conn->query("SELECT session_id FROM sessions WHERE case_id={$row['case_id']} LIMIT 1");
$hasSessionRow = ($chkSession->num_rows > 0);
if($isClosed2){ ?>
<span class="badge bg-secondary">منتهية</span>
<?php } else { ?>
<div class="d-flex flex-column gap-1 align-items-center">
<?php if($chkH->num_rows == 0 && !$isEsc2){ ?>
<a href="assign_handler.php?case_id=<?= $row['case_id']; ?>" class="btn btn-success btn-sm">تعيين معالج</a>
<?php } else { ?>
<span class="badge bg-info text-dark">تم التعيين</span>
<?php } ?>
<?php if($chkH->num_rows > 0 && !$hasSessionRow){ ?>
<a href="create_sessionM.php?case_id=<?= $row['case_id']; ?>" class="btn btn-primary btn-sm">جدولة جلسة</a>
<?php } elseif($hasSessionRow){ ?>
<span class="badge bg-success">جلسة مجدولة ✓</span>
<?php } ?>
</div>
<?php } ?>
</td>

</tr>

<div class="modal fade" id="modal<?= $row['case_id']; ?>" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">تفاصيل الشكوى #<?= $row['case_id']; ?></h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body p-4">

<?php if($isEscalated){ ?>
<div class="alert alert-danger fw-bold">
🔺 هذه الشكوى مصعدة
</div>
<?php } ?>

<p><strong>العنوان:</strong> <?= htmlspecialchars($row['title']); ?></p>
<p><strong>الوصف:</strong> <?= htmlspecialchars($row['description']); ?></p>
<p><strong>الحالة:</strong> <span class="badge <?= $isEscalated?'bg-danger':'bg-info' ?>"><?= translateStatus($row['status']); ?></span></p>
<p><strong>الأولوية:</strong> <?= htmlspecialchars($row['priority']); ?></p>
<p><strong>التاريخ:</strong> <?= $row['created_at']; ?></p>
<p><strong>مقدم الشكوى:</strong> <?= htmlspecialchars($row['complainant']); ?></p>
<p><strong>المعالج:</strong> <?= htmlspecialchars($handler_name); ?></p>

<?php
$fd = $conn->query("SELECT decision_text FROM finaldecisions WHERE case_id={$row['case_id']} LIMIT 1")->fetch_assoc();
if(!empty($fd['decision_text'])){ ?>
<div class="alert alert-success mt-2">
<strong>القرار النهائي:</strong><br>
<?= htmlspecialchars($fd['decision_text']) ?>
</div>
<?php } ?>

<hr>

<p><strong>أطراف النزاع:</strong></p>

<?php
$parties2 = $conn->query("
SELECT 
    cp.party_role,
    cp.party_type,
    e.full_name,
    c.external_party_name,
    cp.employee_id,
    (
        SELECT sr.response 
        FROM settlementresponses sr
        JOIN sessions ses ON sr.session_id = ses.session_id
        WHERE ses.case_id = cp.case_id AND sr.employee_id = cp.employee_id
        LIMIT 1
    ) as party_resp
FROM caseparties cp
LEFT JOIN employees e ON cp.employee_id = e.employee_id
LEFT JOIN cases c ON c.case_id = cp.case_id
WHERE cp.case_id = {$row['case_id']}
");

if($parties2->num_rows > 0){
    while($p = $parties2->fetch_assoc()){
        echo "<div class='response-box d-flex justify-content-between align-items-center'>";
        echo "<div>";
        if(!empty($p['employee_id']) && !empty($p['full_name'])){
            echo "<strong>".$p['full_name']."</strong>";
        } else {
            echo "<strong>".($p['external_party_name'] ?? 'غير محدد')."</strong>";
        }
        echo "<br><small class='text-muted'>".$p['party_role']." (".$p['party_type'].")</small>";
        echo "</div>";
        echo "<div>";
        if(!empty($p['party_resp'])){
            $rb = (strtolower($p['party_resp'])=='accept') ? 'bg-success' : 'bg-danger';
            $rt = (strtolower($p['party_resp'])=='accept') ? 'قبل التوصية' : 'رفض التوصية';
            echo "<span class='badge $rb'>$rt</span>";
        } else {
            echo "<span class='badge bg-warning text-dark'>لم يرد بعد</span>";
        }
        echo "</div>";
        echo "</div>";
    }
} else {
    echo "<p>لا توجد أطراف</p>";
}
?>

</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
</div>

</div>
</div>
</div>

<?php } } else {
echo "<tr><td colspan='11' class='py-4 text-muted'>لا توجد بيانات</td></tr>";
}
$conn->close();
?>

</tbody>
</table>
</div>

<div class="text-center mt-4 mb-4">
<a href="legal_dashboardM.php" class="btn btn-outline-light">⬅ رجوع</a>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
