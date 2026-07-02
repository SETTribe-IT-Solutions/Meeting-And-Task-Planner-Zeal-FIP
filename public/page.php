<?php
// public/page.php — Public portal page viewer (no login required)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

// Language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'mr'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $p = $_GET; unset($p['lang']);
    header('Location: ' . $_SERVER['PHP_SELF'] . (!empty($p) ? '?' . http_build_query($p) : '')); exit();
}
$currentLang = $_SESSION['lang'] ?? 'en';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['slug'] ?? '')));
$allowedSlugs = ['about-district','notices','reports','contact','help','administration'];
if (!in_array($slug, $allowedSlugs)) {
    header('Location: ../modules/users/login.php'); exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT title, icon, content, updated_at FROM portal_pages WHERE slug = ? LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();
if (!$page) { header('Location: ../modules/users/login.php'); exit(); }

$BASE = APP_URL;
$navLinks = [
    ['href' => $BASE . '/modules/users/login.php',      'icon' => 'bi-house-door',        'label' => 'Home'],
    ['href' => $BASE . '/public/page.php?slug=about-district', 'icon' => 'bi-info-circle','label' => 'About District', 'slug' => 'about-district'],
    ['href' => $BASE . '/public/page.php?slug=administration', 'icon' => 'bi-building',   'label' => 'Administration', 'slug' => 'administration'],
    ['href' => $BASE . '/public/page.php?slug=notices', 'icon' => 'bi-megaphone',         'label' => 'Notices',        'slug' => 'notices'],
    ['href' => $BASE . '/public/page.php?slug=reports', 'icon' => 'bi-file-earmark-text', 'label' => 'Reports',        'slug' => 'reports'],
    ['href' => $BASE . '/public/page.php?slug=contact', 'icon' => 'bi-telephone',         'label' => 'Contact',        'slug' => 'contact'],
    ['href' => $BASE . '/public/page.php?slug=help',    'icon' => 'bi-question-circle',   'label' => 'Help',           'slug' => 'help'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page['title']); ?> — Latur District Administration</title>
<script>(function(){var s={'-1':'13px','0':'16px','1':'19px'};document.documentElement.style.fontSize=s[localStorage.getItem('fontSize')||'0']||'16px';}());</script>
<link href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --navy-primary:#003366; --navy-dark:#00254d; --navy-light:#004080;
    --gold-accent:#DAA520; --bg-cream:#f5f0e8; --bg-body:#eee8d5;
    --border-gray:#c5b99c; --text-dark:#333; --text-muted:#666;
    --link-blue:#0645AD; --gov-maroon:#800000;
    --header-gradient:linear-gradient(180deg,#003366 0%,#004080 100%);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(rgba(238,232,213,.82),rgba(238,232,213,.82)),url('../assets/image_e15bb67f.png') center/cover fixed;color:var(--text-dark);min-height:100vh;display:flex;flex-direction:column;overflow-x:hidden}
body.theme-dark{--navy-primary:#0f172a;--navy-dark:#020617;--navy-light:#1e293b;--bg-cream:#111827;--text-dark:#f8fafc;--text-muted:#cbd5e1;--border-gray:#475569;--link-blue:#93c5fd;--header-gradient:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);background:linear-gradient(rgba(15,23,42,.9),rgba(15,23,42,.9)),url('../assets/image_e15bb67f.png') center/cover fixed}
body.theme-contrast{--navy-primary:#000;--navy-dark:#000;--bg-cream:#000;--text-dark:#fff;--text-muted:#fff;--border-gray:#fff;--link-blue:#ff0;background:#000}
a{color:var(--link-blue);text-decoration:none} a:hover{text-decoration:underline}
/* Tricolor */
.tricolor-stripe{height:6px;display:flex}
.tricolor-stripe .s{flex:1;background:#FF9933}.tricolor-stripe .w{flex:1;background:#fff}.tricolor-stripe .g{flex:1;background:#138808}
/* Accessibility bar */
.access-bar{background:#f0ebe0;border-bottom:1px solid var(--border-gray);padding:4px 0;font-size:.72rem}
body.theme-dark .access-bar{background:#1e293b} body.theme-contrast .access-bar{background:#111;border-color:#fff}
.access-inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;justify-content:space-between;align-items:center}
.access-left{color:var(--text-muted)}
.access-right{display:flex;align-items:center;gap:8px}
.access-right span{color:var(--text-muted);font-weight:500}
.font-btn{background:#fff;border:1px solid #ccc;border-radius:3px;padding:2px 8px;cursor:pointer;font-size:.7rem;color:var(--text-dark);transition:background .2s}
.font-btn:hover{background:#e8e2d6} .font-btn.active{background:var(--navy-primary);color:#fff;border-color:var(--navy-primary)}
body.theme-dark .font-btn{background:#1e293b;border-color:#475569;color:#e2e8f0} body.theme-dark .font-btn.active{background:#3b82f6;border-color:#3b82f6}
body.theme-contrast .font-btn{background:#000;border-color:#fff;color:#fff} body.theme-contrast .font-btn.active{background:#ff0;color:#000;border-color:#ff0}
.theme-btn{width:18px;height:18px;border-radius:50%;border:2px solid #999;cursor:pointer;transition:transform .2s}
.theme-btn:hover{transform:scale(1.15)} .theme-btn.active{outline:2px solid var(--navy-primary);outline-offset:2px}
.t-default{background:#fff} .t-dark{background:#333} .t-contrast{background:#000;border-color:#FFD700}
.lang-switch{display:flex;border:1px solid #bbb;border-radius:4px;overflow:hidden;margin-left:6px}
.lang-btn{padding:2px 10px;font-size:.7rem;background:#fff;border:none;border-right:1px solid #ddd;cursor:pointer;color:var(--text-dark);font-weight:500;text-decoration:none;display:inline-block}
.lang-btn:last-child{border-right:none} .lang-btn.active{background:var(--navy-primary);color:#fff}
.lang-btn:hover:not(.active){background:#f0ebe0;text-decoration:none}
/* Header */
.gov-header{background:var(--header-gradient);color:#fff;padding:14px 0;box-shadow:0 2px 8px rgba(0,0,0,.3)}
.header-inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;align-items:center;gap:18px}
.header-emblem img{height:72px;width:72px;border-radius:50%;object-fit:cover;background:#fff;border:2px solid rgba(255,255,255,.85)}
.header-text .hindi-title{font-family:'Noto Sans Devanagari',sans-serif;font-size:.82rem;color:rgba(255,255,255,.85);font-weight:500}
.header-text .main-title{font-size:1.45rem;font-weight:700;margin:2px 0;text-shadow:0 1px 3px rgba(0,0,0,.3)}
.header-text .sub-title{font-size:.78rem;color:rgba(255,255,255,.8)}
.header-right .swachh-badge{display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);padding:4px 12px;border-radius:20px;font-size:.68rem;color:rgba(255,255,255,.85)}
/* Nav */
.gov-nav{background:var(--navy-dark);border-bottom:3px solid var(--gold-accent)}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;align-items:center;flex-wrap:wrap}
.nav-link-item{color:rgba(255,255,255,.9);padding:10px 14px;font-size:.78rem;font-weight:500;text-decoration:none;border-bottom:3px solid transparent;transition:all .2s;display:inline-flex;align-items:center;gap:5px;margin-bottom:-3px}
.nav-link-item:hover,.nav-link-item.active{background:rgba(255,255,255,.08);color:#fff;text-decoration:none;border-bottom-color:var(--gold-accent)}
/* Breadcrumb */
.breadcrumb-bar{background:rgba(255,255,255,.6);border-bottom:1px solid var(--border-gray);padding:6px 0;font-size:.75rem}
body.theme-dark .breadcrumb-bar{background:rgba(30,41,59,.8)} body.theme-contrast .breadcrumb-bar{background:#111;color:#fff}
.breadcrumb-inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;align-items:center;gap:6px;color:var(--text-muted)}
.breadcrumb-inner a{color:var(--link-blue)} .breadcrumb-inner .sep{color:#aaa}
/* Main */
.page-main{flex:1;max-width:900px;margin:0 auto;padding:28px 20px;width:100%}
.page-card{background:#fff;border:1px solid var(--border-gray);border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden}
body.theme-dark .page-card{background:#111827;border-color:#374151}
body.theme-contrast .page-card{background:#000;border-color:#fff;color:#fff}
.page-card-header{background:var(--navy-primary);color:#fff;padding:18px 28px;display:flex;align-items:center;gap:14px}
.page-card-header .pg-icon{font-size:2rem;color:var(--gold-accent)}
.page-card-header h1{font-size:1.25rem;font-weight:700;margin:0}
.page-card-header .updated{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:3px}
.page-card-body{padding:28px}
/* Content styles */
.page-content h4{color:var(--navy-primary);font-size:1rem;font-weight:700;margin:20px 0 8px;padding-bottom:4px;border-bottom:2px solid var(--gold-accent)}
.page-content h4:first-child{margin-top:0}
.page-content p{line-height:1.75;color:var(--text-dark);margin-bottom:12px;font-size:.9rem}
.page-content ul{margin:0 0 14px 20px;padding:0}
.page-content ul li{font-size:.9rem;line-height:1.7;color:var(--text-dark);margin-bottom:4px}
.page-content table{width:100%;border-collapse:collapse;font-size:.85rem;margin-bottom:16px}
.page-content table thead th{background:var(--navy-primary);color:#fff;padding:10px 14px;text-align:left;font-weight:600}
.page-content table tbody tr:nth-child(even){background:#f9f6f0}
body.theme-dark .page-content table tbody tr:nth-child(even){background:#1e293b}
.page-content table tbody td{padding:10px 14px;border-bottom:1px solid #e5d9b5;vertical-align:top;line-height:1.6}
.page-content table tbody td:first-child{white-space:nowrap;font-weight:500;color:var(--navy-primary);min-width:90px}
body.theme-dark .page-content h4{color:#93c5fd} body.theme-dark .page-content p,.page-content ul li{color:var(--text-dark)}
body.theme-dark .page-content table thead th{background:#1e3a5f}
body.theme-dark .page-content table tbody td{border-color:#374151;color:var(--text-dark)}
body.theme-contrast .page-content h4{color:#ff0} body.theme-contrast .page-content table thead th{background:#111}
body.theme-contrast .page-content table tbody tr:nth-child(even){background:#111}
.back-link{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;color:var(--link-blue);margin-bottom:16px;font-weight:500}
.back-link:hover{text-decoration:underline}
/* Footer */
.gov-footer{background:var(--navy-dark);color:rgba(255,255,255,.75);padding:16px 0;font-size:.72rem;margin-top:auto}
.footer-inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
.footer-tricolor{height:4px;display:flex}
.footer-tricolor .s{flex:1;background:#FF9933}.footer-tricolor .w{flex:1;background:#fff}.footer-tricolor .g{flex:1;background:#138808}
@media(max-width:1100px){.header-text .main-title{font-size:1.2rem}.header-emblem img{height:60px;width:60px}.nav-link-item{padding:10px 10px;font-size:.74rem}}
@media(max-width:600px){.header-inner{flex-direction:column;text-align:center;gap:10px}.nav-inner{justify-content:center}.nav-link-item{padding:8px 10px;font-size:.72rem}}
</style>
</head>
<body>
<div class="tricolor-stripe"><div class="s"></div><div class="w"></div><div class="g"></div></div>

<div class="access-bar">
  <div class="access-inner">
    <div class="access-left"><i class="bi bi-clock"></i> <?php echo date('d M Y, h:i A'); ?></div>
    <div class="access-right">
      <span>Text Size:</span>
      <button class="font-btn" id="fS" title="Small">A-</button>
      <button class="font-btn" id="fD" title="Default">A</button>
      <button class="font-btn" id="fL" title="Large">A+</button>
      <span>|</span><span>Color:</span>
      <button class="theme-btn t-default" id="thDef" title="Default"></button>
      <button class="theme-btn t-dark"    id="thDrk" title="Dark"></button>
      <button class="theme-btn t-contrast" id="thCon" title="Contrast"></button>
      <div class="lang-switch">
        <a href="?slug=<?php echo $slug; ?>&lang=en" class="lang-btn <?php echo $currentLang==='en'?'active':''; ?>">ENG</a>
        <a href="?slug=<?php echo $slug; ?>&lang=mr" class="lang-btn <?php echo $currentLang==='mr'?'active':''; ?>">मराठी</a>
      </div>
    </div>
  </div>
</div>

<header class="gov-header">
  <div class="header-inner">
    <div class="header-emblem"><img src="<?php echo $BASE; ?>/assets/photo_1763098684.jpg" alt="Latur Emblem"></div>
    <div class="header-text">
      <div class="hindi-title">जिल्हा प्रशासन लातूर · महाराष्ट्र शासन</div>
      <div class="main-title">District Administration Latur</div>
      <div class="sub-title">Meeting &amp; Task Planner · Government of Maharashtra</div>
    </div>
    <div class="header-right"><div class="swachh-badge"><i class="bi bi-recycle"></i> Digital India Initiative</div></div>
  </div>
</header>

<nav class="gov-nav">
  <div class="nav-inner">
    <?php foreach ($navLinks as $nl): ?>
    <a href="<?php echo htmlspecialchars($nl['href']); ?>"
       class="nav-link-item <?php echo (isset($nl['slug']) && $nl['slug']===$slug) ? 'active' : ''; ?>">
      <i class="bi <?php echo $nl['icon']; ?>"></i> <?php echo $nl['label']; ?>
    </a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="breadcrumb-bar">
  <div class="breadcrumb-inner">
    <a href="<?php echo $BASE; ?>/modules/users/login.php"><i class="bi bi-house-door"></i> Home</a>
    <span class="sep">›</span>
    <span><?php echo htmlspecialchars($page['title']); ?></span>
  </div>
</div>

<div class="page-main">
  <a href="<?php echo $BASE; ?>/modules/users/login.php" class="back-link"><i class="bi bi-arrow-left-circle"></i> Back to Home</a>
  <div class="page-card">
    <div class="page-card-header">
      <i class="bi <?php echo htmlspecialchars($page['icon']); ?> pg-icon"></i>
      <div>
        <h1><?php echo htmlspecialchars($page['title']); ?></h1>
        <div class="updated">Last updated: <?php echo date('d M Y', strtotime($page['updated_at'])); ?> · Latur District Administration</div>
      </div>
    </div>
    <div class="page-card-body">
      <article class="page-content">
        <?php echo $page['content']; ?>
      </article>
    </div>
  </div>
</div>

<footer class="gov-footer">
  <div class="footer-inner">
    <span>© <?php echo date('Y'); ?> District Administration, Latur. All Rights Reserved.</span>
    <span>Designed &amp; Developed by National Informatics Centre (NIC)</span>
  </div>
</footer>
<div class="footer-tricolor"><div class="s"></div><div class="w"></div><div class="g"></div></div>

<script>
(function(){
  var sizes={'-1':'13px','0':'16px','1':'19px'};
  var btns=[document.getElementById('fS'),document.getElementById('fD'),document.getElementById('fL')];
  var lvls=['-1','0','1'];
  function applySize(l){document.documentElement.style.fontSize=sizes[l]||'16px';localStorage.setItem('fontSize',l);btns.forEach(function(b,i){if(b)b.classList.toggle('active',lvls[i]===l);});}
  applySize(localStorage.getItem('fontSize')||'0');
  btns.forEach(function(b,i){if(b)b.addEventListener('click',function(){applySize(lvls[i]);});});
  var thMap={default:document.getElementById('thDef'),dark:document.getElementById('thDrk'),contrast:document.getElementById('thCon')};
  function applyTheme(t){var sel=thMap[t]?t:'default';document.body.classList.remove('theme-dark','theme-contrast');if(sel!=='default')document.body.classList.add('theme-'+sel);Object.keys(thMap).forEach(function(k){if(thMap[k])thMap[k].classList.toggle('active',k===sel);});localStorage.setItem('portalTheme',sel);}
  Object.keys(thMap).forEach(function(t){if(thMap[t])thMap[t].addEventListener('click',function(){applyTheme(t);});});
  applyTheme(localStorage.getItem('portalTheme')||'default');
}());
</script>
</body>
</html>
