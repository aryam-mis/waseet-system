<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>نظام وسيط</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>

:root{
--main:#01251a;
--dark:#01251a;
--light:#01251a;
--accent:#01251a;
}

body{
background:
linear-gradient(rgba(241, 241, 241, 0.50),rgba(238,243,255,0.5)),
url("b.jpeg") center/cover no-repeat fixed;
font-family:'Cairo',sans-serif;
color:var(--dark);
font-size:20px;
}

.navbar{
background:rgba(255,255,255,0.95);
backdrop-filter:blur(12px);
box-shadow:0 6px 25px rgba(0,0,0,0.1);
padding:15px 0;
transition:0.3s;
}

.navbar.scrolled{
box-shadow:0 12px 35px rgba(0,0,0,0.2);
}

.navbar-brand{
font-size:28px;
font-weight:bold;
color:var(--main)!important;
}

.nav-link{
font-size:20px;
margin:0 12px;
font-weight:600;
color:var(--dark)!important;
position:relative;
cursor:pointer;
}

.nav-link::after{
content:"";
position:absolute;
bottom:-6px;
right:0;
width:0%;
height:3px;
background:linear-gradient(45deg,var(--main),var(--accent));
transition:0.3s;
border-radius:10px;
}

.nav-link:hover::after{
width:100%;
}

.btn-main{
background:linear-gradient(45deg,var(--main),var(--accent));
color:#fff;
border-radius:30px;
padding:10px 25px;
border:none;
font-size:18px;
}

.btn-main:hover{
transform:scale(1.08);
box-shadow:0 10px 30px rgba(0,0,0,0.2);
}

.hero{
height:100vh;
display:flex;
align-items:center;
justify-content:center;
text-align:center;
position:relative;
overflow:hidden;
}

.hero::before{
content:"";
position:absolute;
width:500px;
height:500px;
background:radial-gradient(circle,var(--accent),transparent 70%);
filter:blur(80px);
opacity:0.25;
animation:moveGlow 6s infinite alternate ease-in-out;
}

@keyframes moveGlow{
from{transform:translate(-50px,-50px);}
to{transform:translate(50px,50px);}
}

.hero-icon{
font-size:90px;
display:inline-block;
color:var(--main);
animation:floatIcon 3s ease-in-out infinite;
filter:drop-shadow(0 10px 20px rgba(0,0,0,0.2));
}

@keyframes floatIcon{
0%{transform:translateY(0);}
50%{transform:translateY(-15px);}
100%{transform:translateY(0);}
}

.hero h1{
font-size:80px;
font-weight:bold;
background:linear-gradient(45deg,var(--main),var(--accent));
-webkit-background-clip:text;
color:transparent;
animation:fadeDown 1s ease;
}

.hero p{
font-size:28px;
color:#555;
animation:fadeUp 1.2s ease;
}

@keyframes fadeDown{
from{opacity:0; transform:translateY(-50px);}
to{opacity:1; transform:translateY(0);}
}

@keyframes fadeUp{
from{opacity:0; transform:translateY(50px);}
to{opacity:1; transform:translateY(0);}
}

.section-title{
font-size:34px;
font-weight:bold;
text-align:center;
margin:80px 0 40px;
opacity:0;
transform:translateY(40px);
transition:0.6s;
}

.section-title.show{
opacity:1;
transform:translateY(0);
}

.service-box{
padding:40px;
border-radius:25px;
background:#fff;
text-align:center;
transition:0.5s;
opacity:0;
transform:translateY(60px);
}

.service-box.show{
opacity:1;
transform:translateY(0);
}

.service-box:hover{
transform:translateY(-15px) scale(1.06);
box-shadow:0 25px 60px rgba(0,0,0,0.2);
}

.service-box i{
font-size:50px;
background:linear-gradient(45deg,var(--main),var(--accent));
-webkit-background-clip:text;
color:transparent;
}

.modal-content{
border-radius:25px;
padding:35px;
background:rgba(255,255,255,0.82);
backdrop-filter:blur(20px);
-webkit-backdrop-filter:blur(20px);
box-shadow:0 25px 60px rgba(0,0,0,0.18);
border:1px solid rgba(255,255,255,0.4);
}

.contact-item{
display:flex;
align-items:center;
gap:15px;
padding:15px;
border-radius:15px;
transition:0.3s;
}

.contact-item:hover{
background:rgba(79,124,255,0.1);
transform:translateX(-5px);
}

.contact-item i{
font-size:28px;
color:var(--accent);
}

.contact-text{
font-size:20px;
font-weight:600;
}

.navbar-brand{
display:flex;
align-items:center;
gap:10px;
font-size:28px;
font-weight:800;
color:var(--main)!important;
}

.navbar-brand img{
width:10px;
height:10px;
object-fit:contain;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.1);
transition:0.3s;
}

.navbar-brand img:hover{
transform:scale(1.1) rotate(3deg);
}

</style>
</head>

<body>

<nav class="navbar navbar-expand-lg fixed-top">
<div class="container">
<a class="navbar-brand d-flex align-items-center gap-2">
    <img src="logo.png" alt="logo" style="width:60px;height:80px;object-fit:contain;">
    <span>وسيط</span>
</a>

<ul class="navbar-nav mx-auto">
<li class="nav-item"><a class="nav-link" href="#">الرئيسية</a></li>
<li class="nav-item"><a class="nav-link" href="#services">الخدمات</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="modal" data-bs-target="#aboutModal">من نحن</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="modal" data-bs-target="#contactModal">تواصل</a></li>
</ul>

<div>
<a href="login.php" class="btn btn-outline-dark mx-2">تسجيل الدخول</a>
<a href="register.php" class="btn btn-main">انشاء حساب جديد</a>
</div>
</div>
</nav>

<div class="hero">
<div>
<div class="hero-icon"></div>
<h1>نظام وسيط</h1>
<p>نظام احترافي لإدارة النزاعات بكفاءة عالية</p>
</div>
</div>

<div class="container">

<h3 id="services" class="section-title">الخدمات</h3>

<div class="row">
<div class="col-md-3 mb-4">
<div class="service-box">
<i class="bi bi-folder"></i>
<p>إدارة القضايا</p>
</div>
</div>

<div class="col-md-3 mb-4">
<div class="service-box">
<i class="bi bi-calendar-event"></i>
<p>الجلسات</p>
</div>
</div>

<div class="col-md-3 mb-4">
<div class="service-box">
<i class="bi bi-cash-stack"></i>
<p>التسويات</p>
</div>
</div>

<div class="col-md-3 mb-4">
<div class="service-box">
<i class="bi bi-file-earmark-check"></i>
<p>القرارات</p>
</div>
</div>
</div>

</div>

<div class="modal fade" id="contactModal">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content" style="background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.6);border-radius:28px;padding:44px;box-shadow:0 20px 50px rgba(0,0,0,0.12);">

<div class="d-flex justify-content-between align-items-center mb-4">
<h4 style="font-weight:800;color:#01251a;margin:0;">تواصل معنا</h4>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div style="background:transparent;border-radius:14px;padding:16px 20px;margin-bottom:14px;display:flex;align-items:center;gap:14px;border:1px solid rgba(1,37,26,0.12);">
<i class="bi bi-envelope-fill" style="font-size:22px;color:#01251a;"></i>
<span style="font-weight:700;font-size:18px;color:#000;">info@waseet.com</span>
</div>

<div style="background:transparent;border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:14px;border:1px solid rgba(1,37,26,0.12);">
<i class="bi bi-telephone-fill" style="font-size:22px;color:#01251a;"></i>
<span style="font-weight:700;font-size:18px;color:#000;">+966 555555555</span>
</div>

</div>
</div>
</div>

<div class="modal fade" id="aboutModal">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content" style="background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.6);border-radius:28px;padding:44px;box-shadow:0 20px 50px rgba(0,0,0,0.12);">

<div class="d-flex justify-content-between align-items-center mb-4">
<h3 style="font-weight:900;color:#01251a;margin:0;font-size:28px;letter-spacing:-0.5px;">من نحن</h3>
<button class="btn-close" data-bs-dismiss="modal" style="transform:scale(1.2);"></button>
</div>

<div style="background:rgba(1,37,26,0.06);border-radius:18px;padding:24px 28px;margin-bottom:24px;border:1px solid rgba(1,37,26,0.08);">
<p style="font-weight:700;font-size:19px;color:#000;line-height:2.3;margin:0;">
وسيط نظام متكامل لإدارة النزاعات بذكاء وشفافية ، يهدف إلى تسريع حل النزاعات وتحقيق العدالة بأسلوب احترافي وفعّال داخل بيئة العمل .
</p>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">

<div style="background:rgba(1,37,26,0.06);border-radius:18px;padding:24px 16px;text-align:center;border:1px solid rgba(1,37,26,0.08);">
<div style="width:52px;height:52px;background:linear-gradient(135deg,#01251a,#025c3e);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
<svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
<path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5 5 0 006.516 3.117M6 7l3 9M6 7h12l-1.5 9M15 7l-3 9m6-9l-1.5 9m0 0a5 5 0 01-6.516 3.117"/>
</svg>
</div>
<div style="font-weight:800;color:#000;font-size:16px;">عدالة وشفافية</div>
</div>

<div style="background:rgba(1,37,26,0.06);border-radius:18px;padding:24px 16px;text-align:center;border:1px solid rgba(1,37,26,0.08);">
<div style="width:52px;height:52px;background:linear-gradient(135deg,#01251a,#025c3e);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
<svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
</svg>
</div>
<div style="font-weight:800;color:#000;font-size:16px;">وساطة احترافية</div>
</div>

<div style="background:rgba(1,37,26,0.06);border-radius:18px;padding:24px 16px;text-align:center;border:1px solid rgba(1,37,26,0.08);">
<div style="width:52px;height:52px;background:linear-gradient(135deg,#01251a,#025c3e);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
<svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
<path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
</svg>
</div>
<div style="font-weight:800;color:#000;font-size:16px;">سرية تامة</div>
</div>

</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

window.addEventListener("scroll",()=>{
document.querySelector(".navbar").classList.toggle("scrolled",window.scrollY>50);
});

const observer=new IntersectionObserver(entries=>{
entries.forEach(entry=>{
if(entry.isIntersecting){
entry.target.classList.add("show");
}
});
});

document.querySelectorAll('.service-box,.section-title').forEach(el=>observer.observe(el));

</script>

</body>
</html>