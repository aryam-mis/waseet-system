<?php
session_start();
// شرط: لازم يكون مسجل دخول
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }
$conn = new mysqli("localhost","root","","wasit_system");
if($conn->connect_error) die("فشل الاتصال");

// شرط: لازم يوصل session_id ونص التوصية مع الطلب
if(isset($_POST['session_id']) && isset($_POST['response'])){
    $session_id = intval($_POST['session_id']);
    $text = trim($_POST['response']);

    // شرط: لو حقل التوصية فاضي نوقف
    if(empty($text)){
        echo "<script>alert('يجب كتابة التوصية');history.back();</script>";
        exit();
    }

    // استعلام: نتأكد إن الجلسة موجودة فعلاً في قاعدة البيانات
    // التحقق من الجلسة
    $ses = $conn->query("SELECT case_id, settlement_text FROM sessions WHERE session_id=$session_id")->fetch_assoc();
    if(!$ses){
        echo "<script>alert('الجلسة غير موجودة');history.back();</script>";
        exit();
    }

    // شرط: لو فيه توصية مسبقة لهذي الجلسة نوقف ولا نسمح بتوصية ثانية
    // التحقق من التوصية
    if(!empty($ses['settlement_text'])){
        echo "<script>alert('تم إصدار التوصية مسبقاً');history.back();</script>";
        exit();
    }

    $safe = mysqli_real_escape_string($conn, $text);
    $case_id = $ses['case_id'];

    // استعلام: نحفظ نص التوصية في جدول sessions ونضع الحالة PENDING (ينتظر رد الأطراف)
    // حفظ التوصية
    $conn->query("UPDATE sessions SET settlement_text='$safe', settlement_status='PENDING' WHERE session_id=$session_id");

    // استعلام: نغير حالة الشكوى لـ IN_MEDIATION بعد إصدار التوصية
    // تحديث حالة الشكوى
    $conn->query("UPDATE cases SET status='IN_MEDIATION' WHERE case_id=$case_id");

    echo "<script>alert('تم حفظ التوصية بنجاح');window.opener && window.opener.location.reload();window.close();if(window.history.length>1){history.back();}else{window.close();}</script>";

} else {
    echo "<script>alert('بيانات ناقصة');history.back();</script>";
}
?>
