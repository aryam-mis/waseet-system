<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['department'] != "معالج قانوني"){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>لوحة المعالج القانوني</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Tahoma',sans-serif;
    background:#f8f6f1;
    color:#173d38;
    overflow-x:hidden;
}

/* =========================
   الشريط العلوي
========================= */

.navbar-custom{
    width:100%;
    height:90px;
    background:rgba(255,255,255,0.95);
    box-shadow:0 3px 15px rgba(0,0,0,0.06);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 50px;
    position:sticky;
    top:0;
    z-index:1000;
}

.logo-box{
    display:flex;
    align-items:center;
    gap:10px;
}

.logo-box img{
    width:55px;
    height:55px;
    object-fit:contain;
}

.logo-box h2{
    font-size:28px;
    font-weight:900;
    color:#173d38;
    margin:0;
}

.nav-links{
    display:flex;
    align-items:center;
    gap:32px;
}

.nav-links a{
    text-decoration:none;
    color:#173d38;
    font-size:16px;
    font-weight:700;
    transition:0.3s;
}

.nav-links a:hover{
    color:#0f2d29;
}

.logout-nav{
    background:#173d38;
    color:#fff;
    text-decoration:none;
    padding:11px 24px;
    border-radius:12px;
    font-weight:700;
    transition:0.3s;
}

.logout-nav:hover{
    background:#0f2d29;
    color:#fff;
}

/* =========================
   المحتوى
========================= */

.main-section{
    padding:80px 40px;
    min-height:650px;
    text-align:center;
}

.main-section h1{
    font-size:42px;
    margin-bottom:60px;
    font-weight:900;
    color:#173d38;
}

.cards-container{
    display:flex;
    justify-content:center;
    gap:28px;
    flex-wrap:wrap;
}

.card-link{
    text-decoration:none;
}

.custom-card{
    width:250px;
    height:190px;
    background:#fff;
    border-radius:24px;
    border:1px solid rgba(0,0,0,0.05);
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    transition:0.35s;
    box-shadow:0 8px 20px rgba(0,0,0,0.05);
}

.custom-card:hover{
    transform:translateY(-10px);
    box-shadow:0 20px 35px rgba(0,0,0,0.10);
}

.icon-circle{
    width:75px;
    height:75px;
    border-radius:50%;
    background:#ece8e1;
    display:flex;
    justify-content:center;
    align-items:center;
    margin-bottom:18px;
}

.custom-card i{
    font-size:38px;
    color:#173d38;
}

.custom-card p{
    font-size:19px;
    font-weight:800;
    color:#173d38;
}

/* =========================
   الفوتر
========================= */

.footer{
    background:#fff;
    padding:50px 70px 20px;
    border-top:1px solid #e2ded7;
}

.footer-content{
    display:grid;
    grid-template-columns:2fr 1fr 1fr 1fr;
    gap:40px;
}

.footer-logo{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:15px;
}

.footer-logo img{
    width:60px;
}

.footer-logo h3{
    margin:0;
    font-weight:900;
    color:#173d38;
}

.footer p{
    line-height:2;
    color:#60706d;
}

.footer h5{
    margin-bottom:18px;
    font-weight:900;
    color:#173d38;
}

.footer ul{
    list-style:none;
    padding:0;
}

.footer ul li{
    margin-bottom:12px;
}

.footer ul li a{
    text-decoration:none;
    color:#60706d;
}

.social-icons{
    display:flex;
    gap:12px;
    margin-top:18px;
}

.social-icons span{
    width:42px;
    height:42px;
    border-radius:50%;
    background:#ece8e1;
    display:flex;
    justify-content:center;
    align-items:center;
    color:#173d38;
    font-size:18px;
}

.footer-bottom{
    margin-top:35px;
    border-top:1px solid #e2ded7;
    padding-top:18px;
    text-align:center;color:#60706d;
}

/* =========================
   الجوال
========================= */

@media(max-width:900px){

.navbar-custom{
    flex-direction:column;
    height:auto;
    padding:20px;
    gap:20px;
}

.nav-links{
    flex-wrap:wrap;
    justify-content:center;
    gap:18px;
}

.footer-content{
    grid-template-columns:1fr;
    text-align:center;
}

.footer-logo{
    justify-content:center;
}

.social-icons{
    justify-content:center;
}

}

</style>

</head>

<body>

<!-- =========================
     الشريط العلوي
========================= -->

<header class="navbar-custom">

    <div class="logo-box">

        <img src="logo.png" alt="logo">

        <h2>وسيط</h2>

    </div>

    <nav class="nav-links">

        <a href="handler_dashboard.php">
            الرئيسية
        </a>

        <a href="legal_view_cases.php">
            مراجعة الشكاوى
        </a>

        <a href="create_session.php">
            عرض الجلسات
        </a>

        <a href="create_settlement.php">
            إصدار توصية
        </a>

    </nav>

    <a href="logout.php" class="logout-nav">
        تسجيل الخروج
    </a>

</header>

<!-- =========================
     المحتوى
========================= -->

<section class="main-section">

    <h1>

        مرحباً

        <?= $_SESSION['full_name'] ?? 'المعالج القانوني' ?>

    </h1>

    <div class="cards-container">

        <!-- مراجعة الشكاوى -->

        <a href="legal_view_cases.php" class="card-link">

            <div class="custom-card">

                <div class="icon-circle">

                    <i class="bi bi-journal-text"></i>

                </div>

                <p>مراجعة الشكاوى</p>

            </div>

        </a>

        <!-- عرض الجلسات -->

        <a href="create_session.php" class="card-link">

            <div class="custom-card">

                <div class="icon-circle">

                    <i class="bi bi-calendar-event"></i>

                </div>

                <p>عرض الجلسات</p>

            </div>

        </a>

        <!-- إصدار توصية -->

        <a href="create_settlement.php" class="card-link">

            <div class="custom-card">

                <div class="icon-circle">

                    <i class="bi bi-file-earmark-text"></i>

                </div>

                <p>إصدار توصية</p>

            </div>

        </a>

    </div>

</section>

<!-- =========================
     الفوتر
========================= -->

<footer class="footer">

    <div class="footer-content">

        <div>

            <div class="footer-logo">

                <img src="logo.png" alt="logo">

                <h3>

                    Waseet
                    <br>
                    وسيط

                </h3>

            </div>

            <p>

                منصة إلكترونية لإدارة النزاعات بكفاءة وشفافية،
                تسهم في تسريع الوصول إلى حلول عادلة وفعالة.

            </p>

            <div class="social-icons">

                <span>
                    <i class="bi bi-envelope"></i>
                </span>

                <span>
                    <i class="bi bi-twitter-x"></i>
                </span>

                <span>
                    <i class="bi bi-linkedin"></i>
                </span>

            </div>

        </div>

        <div>

            <h5>روابط المعالج</h5>

            <ul>

                <li>
                    <a href="handler_dashboard.php">
                        الرئيسية
                    </a>
                </li>

                <li>
                    <a href="legal_view_cases.php">
                        مراجعة الشكاوى
                    </a>
                </li>

                <li>
                    <a href="create_session.php">
                        عرض الجلسات
                    </a>
                </li>

                <li>
                    <a href="create_settlement.php">
                        إصدار توصية
                    </a>
                </li>

            </ul>

        </div>

        <div>

            <h5>الدعم والمساعدة</h5><ul>

                <li>
                    <a href="#">
                        الأسئلة الشائعة
                    </a>
                </li>

                <li>
                    <a href="#">
                        سياسة الخصوصية
                    </a>
                </li>

                <li>
                    <a href="#">
                        الشروط والأحكام
                    </a>
                </li>

            </ul>

        </div>

        <div>

            <h5>تواصل معنا</h5>

            <p>
                <i class="bi bi-envelope"></i>
                info@waseet.com
            </p>

            <p>
                <i class="bi bi-telephone"></i>
                +966 555555555
            </p>

            <p>
                <i class="bi bi-geo-alt"></i>
                المملكة العربية السعودية
            </p>

        </div>

    </div>

    <div class="footer-bottom">

        © وسيط - جميع الحقوق محفوظة

    </div>

</footer>

</body>
</html>