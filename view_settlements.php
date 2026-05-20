<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }
$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

function translateStatus($s){
    $m=['OPEN'=>'جديدة','UNDER_REVIEW'=>'قيد المراجعة','IN_MEDIATION'=>'في الوساطة',
        'ESCALATED'=>'مصعدة للإدارة العليا','CLOSED_SETTLED'=>'مغلقة (تسوية)','CLOSED_DECIDED'=>'مغلقة (قرار نهائي)'];
    return $m[$s]??$s;
}

$user_id=$_SESSION['user_id'];

if(isset($_POST['respond'])){
    $session_id   = intval($_POST['session_id']);
    $action       = $_POST['action'];
    $reject_reason= trim($_POST['comment']??'');

    $session=$conn->query("SELECT s.*,c.case_id FROM sessions s JOIN cases c ON s.case_id=c.case_id WHERE s.session_id=$session_id")->fetch_assoc();
    if(!$session) die("<script>alert('الجلسة غير موجودة');history.back();</script>");
    if(empty($session['settlement_text'])) die("<script>alert('لا توجد توصية بعد');history.back();</script>");
    if($conn->query("SELECT response_id FROM settlementresponses WHERE session_id=$session_id AND employee_id=$user_id")->num_rows>0)
        die("<script>alert('لقد قمت بالرد مسبقاً');history.back();</script>");
    if($action=='REJECT'&&empty($reject_reason))
        die("<script>alert('يجب كتابة سبب الرفض');history.back();</script>");

    // جدول الردود
    $stmt=$conn->prepare("INSERT INTO settlementresponses(session_id,employee_id,response,reject_reason) VALUES(?,?,?,?)");
    $rr=($action=='REJECT')?$reject_reason:null;
    $stmt->bind_param("iiss",$session_id,$user_id,$action,$rr);
    $stmt->execute();

    $case_id=$session['case_id'];
    $res=$conn->query("SELECT response FROM settlementresponses WHERE session_id=$session_id");
    $accept=0;$reject=0;
    while($r=$res->fetch_assoc()){ if($r['response']=='ACCEPT')$accept++; if($r['response']=='REJECT')$reject++; }

    $total = $conn->query("SELECT COUNT(DISTINCT employee_id) as cnt FROM caseparties WHERE case_id=$case_id AND employee_id IS NOT NULL")->fetch_assoc()['cnt'];
    $responded = $accept + $reject;

    if($reject > 0){
        // تصعيد فوري عند أول رفض
        $conn->query("UPDATE cases SET status='ESCALATED' WHERE case_id=$case_id");
        header("Location: ".$_SERVER['PHP_SELF']); exit();
    } elseif($accept >= $total){
        // حالة القبول
        $conn->query("UPDATE cases SET status='CLOSED_SETTLED' WHERE case_id=$case_id");
    }
    // انتظار ردود الأطراف
    header("Location: ".$_SERVER['PHP_SELF']); exit();
}

$cases=$conn->query("
    SELECT DISTINCT c.* FROM cases c
    JOIN caseparties cp ON c.case_id=cp.case_id
    WHERE cp.employee_id=$user_id
    ORDER BY c.case_id DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>الرد على التسوية</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
<style>
body{background:radial-gradient(circle at top right,#fff,#f6fbff,#f8f5ff,#f3fff8);font-family:'Cairo',sans-serif;color:#111827;}
h3{font-weight:800;color:#01251a;}
.table{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(0,0,0,.06);}
.table thead tr{background:linear-gradient(90deg,#e0f2fe,#ede9fe,#dcfce7);}
.table th{font-weight:800;border:none!important;padding:14px 10px;}
.table td{font-weight:600;border-color:rgba(0,0,0,.06)!important;vertical-align:middle;}
.table tbody tr:nth-child(even){background:#f8fafc;}
.table tbody tr:hover{background:#eef2ff;transition:.2s;}
.modal-content{background:rgba(255,255,255,.97);border-radius:18px;}
.modal-header{background:linear-gradient(90deg,#01251a,#014a36);border-radius:18px 18px 0 0;}
.modal-header h5,.modal-header .btn-close{color:#fff!important;}
.box{background:#f8fafc;padding:12px;border-radius:12px;margin-bottom:10px;border:1px solid rgba(0,0,0,.06);}
.badge{font-size:12px;padding:6px 10px;border-radius:8px;font-weight:700;}
.btn-back{background:#01251a;color:#fff;border:none;border-radius:10px;font-weight:700;padding:10px 24px;text-decoration:none;}
.btn-back:hover{background:#014a36;color:#fff;}
</style>
</head>
<body>
<div class="container mt-4">
<h3 class="text-center mb-4">الرد على التسوية</h3>
<div class="table-responsive">
<table class="table table-striped text-center">
<thead><tr>
<th>رقم</th><th>العنوان</th><th>الحالة</th><th>نوع المدعى عليه</th><th>التوصية</th><th>حالة الرد</th><th>تفاصيل</th>
</tr></thead>
<tbody>
<?php while($row=$cases->fetch_assoc()):
$case_id=$row['case_id'];
$isEsc=($row['status']=='ESCALATED');

$def=$conn->query("SELECT party_type FROM caseparties WHERE case_id=$case_id AND party_role='مدعى عليه' LIMIT 1")->fetch_assoc();
$def_type=$def['party_type']??'غير محدد';

$ses=$conn->query("SELECT * FROM sessions WHERE case_id=$case_id LIMIT 1")->fetch_assoc();
$session_id=$ses['session_id']??0;
$settlement_text=$ses['settlement_text']??'';

$my_responded=false; $my_res=null;
if($session_id){
    $mq=$conn->query("SELECT response FROM settlementresponses WHERE session_id=$session_id AND employee_id=$user_id");
    if($mq->num_rows>0){$my_responded=true;$my_res=$mq->fetch_assoc()['response'];}
}
?>
<tr>
<td><?=$case_id?></td>
<td><?=htmlspecialchars($row['title'])?></td>
<td>
<?php
$bc='bg-info';
if($isEsc)$bc='bg-danger';
elseif(in_array($row['status'],['CLOSED_SETTLED','CLOSED_DECIDED']))$bc='bg-success';
elseif($row['status']=='OPEN')$bc='bg-secondary';
echo "<span class='badge $bc'>".translateStatus($row['status'])."</span>";

?>
</td>
<td><span class="badge <?=['موظف'=>'bg-primary','جهة خارجية'=>'bg-danger'][$def_type]??'bg-secondary'?>"><?=$def_type?></span></td>
<td><?=!empty($settlement_text)?"<span class='badge bg-success'>تم إصدار التوصية</span>":"<span class='badge bg-secondary'>لا توجد توصية</span>"?></td>
<td><?php if($my_responded) echo $my_res=='ACCEPT'?"<span class='badge bg-success'>تم القبول</span>":"<span class='badge bg-danger'>تم الرفض</span>";
else echo "<span class='badge bg-warning text-dark'>لم يتم الرد</span>"; ?></td>
<td><button class="btn btn-sm fw-bold" style="background:#01251a;color:#fff;border-radius:10px;" data-bs-toggle="modal" data-bs-target="#m<?=$case_id?>">تفاصيل</button></td>
</tr>

<div class="modal fade" id="m<?=$case_id?>" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header"><h5>تفاصيل الشكوى #<?=$case_id?></h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body p-4">
<?php if($isEsc):?><div class="alert alert-danger fw-bold">🔺 تم تصعيد هذه الشكوى للإدارة العليا</div><?php endif;?>
<p><strong>العنوان:</strong> <?=htmlspecialchars($row['title'])?></p>
<p><strong>الوصف:</strong> <?=htmlspecialchars($row['description'])?></p>
<p><strong>الحالة:</strong> <span class="badge <?=$isEsc?'bg-danger':'bg-info'?>"><?=translateStatus($row['status'])?></span></p>
<p><strong>الأولوية:</strong>
<?php
$pc=$row['priority'];
$pb=($pc=='عالية')?'bg-danger':(($pc=='متوسطة')?'bg-warning text-dark':'bg-success');
echo "<span class='badge $pb'>$pc</span>";
?>
</p>
<?php
// القرار النهائي
$fd=$conn->query("SELECT decision_text FROM finaldecisions WHERE case_id=$case_id LIMIT 1")->fetch_assoc();
if(!empty($fd['decision_text'])):?>
<div class="alert alert-success"><strong>القرار النهائي:</strong><br><?=htmlspecialchars($fd['decision_text'])?></div>
<?php endif;?>
<hr>
<h6>👥 الأطراف وردودهم</h6>
<?php
// ردود الأطراف
$parties=$conn->query("
    SELECT cp.party_role,cp.party_type,e.full_name,c.external_party_name,
           sr.response as pres, sr.reject_reason
    FROM caseparties cp
    LEFT JOIN employees e ON cp.employee_id=e.employee_id
    LEFT JOIN cases c ON c.case_id=cp.case_id
    LEFT JOIN sessions ses ON ses.case_id=cp.case_id
    LEFT JOIN settlementresponses sr ON sr.session_id=ses.session_id AND sr.employee_id=cp.employee_id
    WHERE cp.case_id=$case_id
");
while($p=$parties->fetch_assoc()){
    $nm=!empty($p['full_name'])?$p['full_name']:($p['external_party_name']??'غير محدد');
    $rb='bg-warning text-dark';$rt='لم يرد بعد';
    if($p['pres']=='ACCEPT'){$rb='bg-success';$rt='قبل التوصية';}
    elseif($p['pres']=='REJECT'){$rb='bg-danger';$rt='رفض التوصية';}
    echo "<div class='box d-flex justify-content-between align-items-center'>
        <div><strong>".htmlspecialchars($nm)."</strong>
        <br><small class='text-muted'>{$p['party_role']} ({$p['party_type']})</small>";
    if($p['pres']=='REJECT'&&$p['reject_reason'])
        echo "<br><small class='text-danger'>السبب: ".htmlspecialchars($p['reject_reason'])."</small>";
    echo "</div><span class='badge $rb'>$rt</span></div>";
}
?>
<hr>
<p><strong>التوصية:</strong></p>
<div class="alert alert-success"><?=!empty($settlement_text)?htmlspecialchars($settlement_text):'لا توجد توصية'?></div>
<?php if(!$my_responded && !empty($settlement_text)):?>
<?php if($isEsc):?>
<div class="alert alert-warning fw-bold">🔺 الشكوى مصعدة - لكن لا يزال بإمكانك تسجيل ردك</div>
<?php endif;?>
<form method="POST">
<input type="hidden" name="session_id" value="<?=$session_id?>">
<input type="hidden" name="action" id="act<?=$case_id?>">
<textarea name="comment" class="form-control mb-3" placeholder="سبب الرفض (إلزامي عند الرفض)"></textarea>
<div class="d-flex gap-2">
<button type="submit" name="respond" class="btn btn-success fw-bold" onclick="document.getElementById('act<?=$case_id?>').value='ACCEPT'">قبول</button>
<button type="submit" name="respond" class="btn btn-danger fw-bold" onclick="document.getElementById('act<?=$case_id?>').value='REJECT'">رفض</button>
</div></form>
<?php elseif($my_responded):?>
<div class="alert alert-info">✅ تم تسجيل ردك بنجاح</div>
<?php if($isEsc):?>
<div class="alert alert-danger">🔺 تم تصعيد الشكوى للإدارة العليا</div>
<?php endif;?>
<?php else:?>
<div class="alert alert-secondary">لا توجد توصية بعد</div>
<?php endif;?>
</div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button></div>
</div></div></div>
<?php endwhile;?>
</tbody></table>
</div>
<div class="text-center mt-4 mb-4"><a href="employee_dashboard.php" class="btn-back">⬅ رجوع</a></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
