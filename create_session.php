<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "معالج قانوني"){
    header("Location: login.php"); exit();
}
$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");
$employee_id = $_SESSION['user_id'];

function translateStatus($s){
    $m=['OPEN'=>'جديدة','UNDER_REVIEW'=>'قيد المراجعة','IN_MEDIATION'=>'في الوساطة',
        'ESCALATED'=>'مصعدة للإدارة العليا','CLOSED_SETTLED'=>'مغلقة (تسوية)','CLOSED_DECIDED'=>'مغلقة (قرار نهائي)'];
    return $m[$s]??$s;
}

$sessions=$conn->query("
    SELECT s.session_id,s.session_date,s.session_time,s.location_details,
           s.settlement_status,s.settlement_text,
           c.case_id,c.title,c.status AS case_status
    FROM sessions s
    JOIN cases c ON s.case_id=c.case_id
    JOIN casehandlers ch ON ch.case_id=c.case_id
    WHERE ch.employee_id=$employee_id
    AND c.status NOT IN ('CLOSED_SETTLED','CLOSED_DECIDED','ESCALATED')
    AND (s.settlement_text IS NULL OR s.settlement_text = '')
    ORDER BY s.session_date DESC,s.session_time DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><title>جلساتي</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
<style>
body{background:radial-gradient(circle at top right,#fff,#f6fbff,#f8f5ff,#f3fff8);font-family:'Cairo',sans-serif;color:#111827;}
h3{font-weight:800;color:#01251a;}
.table{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(0,0,0,.06);}
.table thead tr{background:linear-gradient(90deg,#e0f2fe,#ede9fe,#dcfce7);}
.table th{font-weight:800;border:none!important;padding:14px 10px;color:#01251a;}
.table td{font-weight:600;border-color:rgba(0,0,0,.06)!important;vertical-align:middle;}
.table tbody tr:nth-child(even){background:#f8fafc;}
.table tbody tr:hover{background:#eef2ff;transition:.2s;}
.badge{font-size:12px;padding:6px 10px;border-radius:8px;font-weight:700;}
.btn-back{background:#01251a;color:#fff;border:none;border-radius:10px;font-weight:700;padding:10px 24px;text-decoration:none;}
.btn-back:hover{background:#014a36;color:#fff;}
.modal-content{background:rgba(255,255,255,.97);border-radius:18px;}
.modal-header{background:linear-gradient(90deg,#01251a,#014a36);border-radius:18px 18px 0 0;}
.modal-header h5{color:#fff!important;}
.modal-header .btn-close{filter:invert(1);}
.info-row{background:#f8fafc;padding:10px 14px;border-radius:10px;margin-bottom:8px;border:1px solid rgba(0,0,0,.06);}
.party-box{background:#f1f5f9;padding:10px 14px;border-radius:10px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;}
</style>
</head>
<body>
<div class="container mt-5 mb-5">
<h3 class="text-center mb-4">📅 جلساتي المجدولة</h3>

<?php if($sessions->num_rows==0): ?>
<div class="alert alert-info text-center fw-bold fs-5">لا توجد جلسات معينة لك حتى الآن</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped text-center">
<thead><tr>
<th>رقم الشكوى</th><th>العنوان</th><th>التاريخ</th><th>الوقت</th>
<th>حالة الشكوى</th><th>حالة التوصية</th><th>تفاصيل</th>
</tr></thead>
<tbody>
<?php while($row=$sessions->fetch_assoc()):
$sid=$row['session_id']; $cid=$row['case_id']; $cs=$row['case_status'];
$isEsc=($cs=='ESCALATED'); $isClosed=in_array($cs,['CLOSED_SETTLED','CLOSED_DECIDED']);
$cb='bg-info';
if($isEsc)$cb='bg-danger';
elseif($isClosed)$cb='bg-success';
elseif($cs=='OPEN')$cb='bg-secondary';

// حالة التوصية من ردود الأطراف الفعلية
$sb='bg-secondary'; $st='لا توجد توصية';
if(!empty($row['settlement_text'])){
    $total_p = $conn->query("SELECT COUNT(*) AS c FROM caseparties WHERE case_id=$cid AND employee_id IS NOT NULL")->fetch_assoc()['c'];
    $accept_p = $conn->query("SELECT COUNT(*) AS c FROM settlementresponses WHERE session_id=$sid AND response='ACCEPT'")->fetch_assoc()['c'];
    $reject_p = $conn->query("SELECT COUNT(*) AS c FROM settlementresponses WHERE session_id=$sid AND response='REJECT'")->fetch_assoc()['c'];
    if($reject_p > 0){ $sb='bg-danger'; $st='مرفوضة - تم التصعيد'; }
    elseif($accept_p >= $total_p && $total_p > 0){ $sb='bg-success'; $st='مقبولة'; }
    else { $sb='bg-warning text-dark'; $st='قيد الانتظار'; }
}
$parties=$conn->query("
    SELECT cp.party_role,cp.party_type,e.full_name,c2.external_party_name,
           sr.response,sr.reject_reason
    FROM caseparties cp
    LEFT JOIN employees e ON cp.employee_id=e.employee_id
    LEFT JOIN cases c2 ON c2.case_id=cp.case_id
    LEFT JOIN settlementresponses sr ON sr.session_id=$sid AND sr.employee_id=cp.employee_id
    WHERE cp.case_id=$cid
");
?>
<tr>
<td>#<?=$cid?></td>
<td><?=htmlspecialchars($row['title'])?></td>
<td><?=date('Y/m/d',strtotime($row['session_date']))?></td>
<td><?=$row['session_time']?></td>
<td><span class="badge <?=$cb?>"><?=translateStatus($cs)?></span></td>
<td><span class="badge <?=$sb?>"><?=$st?></span></td>
<td><button class="btn btn-sm fw-bold" style="background:#01251a;color:#fff;border-radius:10px;"
    data-bs-toggle="modal" data-bs-target="#ms<?=$sid?>">عرض</button></td>
</tr>

<div class="modal fade" id="ms<?=$sid?>" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5>جلسة #<?=$sid?> — شكوى #<?=$cid?></h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-4">
<?php if($isEsc):?><div class="alert alert-danger fw-bold">🔺 الشكوى مصعدة للإدارة العليا</div><?php endif;?>
<div class="info-row"><strong>📋 العنوان:</strong> <?=htmlspecialchars($row['title'])?></div>
<div class="info-row">
<strong>📅 التاريخ:</strong> <?=date('Y/m/d',strtotime($row['session_date']))?>
&nbsp;&nbsp;<strong>⏰ الوقت:</strong> <?=$row['session_time']?>
</div>
<div class="info-row"><strong>🔗 الرابط:</strong> <?=htmlspecialchars($row['location_details']??'غير محدد')?></div>
<div class="info-row">
<strong>حالة الشكوى:</strong> <span class="badge <?=$cb?>"><?=translateStatus($cs)?></span>
&nbsp;<strong>حالة التوصية:</strong> <span class="badge <?=$sb?>"><?=$st?></span>
</div>
<hr>
<h6 class="fw-bold mb-3">👥 ردود الأطراف:</h6>
<?php $hp=false; while($p=$parties->fetch_assoc()): $hp=true;
$nm=!empty($p['full_name'])?$p['full_name']:($p['external_party_name']??'غير محدد');
$rb='bg-warning text-dark';$rt='لم يرد بعد';
if($p['response']=='ACCEPT'){$rb='bg-success';$rt='قبل التوصية';}
elseif($p['response']=='REJECT'){$rb='bg-danger';$rt='رفض التوصية';}?>
<div class="party-box">
<div><strong><?=htmlspecialchars($nm)?></strong>
<br><small class="text-muted"><?=$p['party_role']?> (<?=$p['party_type']?>)</small>
<?php if($p['response']=='REJECT'&&$p['reject_reason']):?>
<br><small class="text-danger">سبب الرفض: <?=htmlspecialchars($p['reject_reason'])?></small>
<?php endif;?></div>
<span class="badge <?=$rb?>"><?=$rt?></span>
</div>
<?php endwhile; if(!$hp):?><div class="alert alert-secondary">لا توجد أطراف مسجلة</div><?php endif;?>
<?php if(!empty($row['settlement_text'])):?>
<hr><h6 class="fw-bold">📄 نص التوصية:</h6>
<div class="alert alert-success"><?=htmlspecialchars($row['settlement_text'])?></div>
<?php endif;?>
</div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button></div>
</div></div></div>
<?php endwhile;?>
</tbody></table></div>
<?php endif;?>
<div class="text-center mt-4"><a href="legal_dashboard.php" class="btn-back">⬅ العودة</a></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
