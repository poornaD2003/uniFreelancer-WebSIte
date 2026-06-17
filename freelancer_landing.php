<?php
// You can add PHP logic here (e.g. form handling, DB queries)
$name = "Alex Morgan";
$title = "Student Web Developer";
$tagline = "Building modern web experiences — one line at a time.";
$email = "alex@example.com";
$gigs = [
    ["icon" => "🌐", "title" => "Landing Page", "desc" => "Pixel-perfect, responsive landing pages that convert visitors into clients.", "price" => "29"],
    ["icon" => "⚛️", "title" => "React Web App", "desc" => "Dynamic single-page applications built with React and modern tooling.", "price" => "79"],
    ["icon" => "🔌", "title" => "REST API", "desc" => "Scalable backend APIs using Node.js or PHP with clean documentation.", "price" => "59"],
    ["icon" => "🛒", "title" => "E-Commerce Site", "desc" => "Full-featured online stores with cart, checkout, and payment integration.", "price" => "99"],
    ["icon" => "🎨", "title" => "UI/UX Design", "desc" => "Clean, modern Figma designs turned into production-ready code.", "price" => "39"],
    ["icon" => "🐞", "title" => "Bug Fixing", "desc" => "Fast diagnosis and fixing of bugs in your existing web projects.", "price" => "19"],
];
$skills = [
    ["name" => "HTML & CSS", "pct" => 95],
    ["name" => "JavaScript", "pct" => 88],
    ["name" => "React", "pct" => 80],
    ["name" => "PHP & MySQL", "pct" => 75],
    ["name" => "Node.js", "pct" => 70],
];
$testimonials = [
    ["name" => "Sarah K.", "role" => "Startup Founder", "avatar" => "SK", "text" => "Alex delivered a stunning landing page in just 2 days. Communication was great and the quality exceeded my expectations!", "stars" => 5],
    ["name" => "James T.", "role" => "Small Business Owner", "avatar" => "JT", "text" => "Built our entire e-commerce site from scratch. Very professional for a student developer — highly recommend!", "stars" => 5],
    ["name" => "Priya M.", "role" => "Blogger", "avatar" => "PM", "text" => "Fixed bugs in my WordPress site quickly and explained everything clearly. Will definitely hire again.", "stars" => 4],
];

// Handle contact form
$msg_sent = false;
$msg_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $from_name  = htmlspecialchars(trim($_POST['from_name'] ?? ''));
    $from_email = htmlspecialchars(trim($_POST['from_email'] ?? ''));
    $message    = htmlspecialchars(trim($_POST['message'] ?? ''));
    if ($from_name && $from_email && $message) {
        $headers = "From: $from_email\r\nReply-To: $from_email";
        // mail($email, "New Contact from $from_name", $message, $headers);
        $msg_sent = true;
    } else {
        $msg_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $name ?> — Freelance Web Developer</title>
<style>
  :root {
    --primary: #6c63ff;
    --primary-light: #857dff;
    --accent: #ff6584;
    --dark: #1a1a2e;
    --text: #333;
    --muted: #666;
    --bg: #f9f9fb;
    --white: #fff;
    --card-shadow: 0 8px 32px rgba(108,99,255,0.10);
    --radius: 16px;
    --transition: 0.35s cubic-bezier(.4,0,.2,1);
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  html { scroll-behavior: smooth; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); overflow-x: hidden; }

  /* ── NAVBAR ── */
  nav {
    position: fixed; top:0; width:100%; z-index:999;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(108,99,255,0.08);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 6%; height: 68px;
    transition: box-shadow var(--transition);
  }
  nav.scrolled { box-shadow: 0 4px 24px rgba(108,99,255,0.12); }
  .nav-logo { font-size: 1.4rem; font-weight: 800; color: var(--primary); letter-spacing:-0.5px; }
  .nav-logo span { color: var(--accent); }
  .nav-links { display:flex; gap:2rem; list-style:none; }
  .nav-links a { text-decoration:none; color:var(--text); font-weight:500; font-size:0.95rem; position:relative; transition: color var(--transition); }
  .nav-links a::after { content:''; position:absolute; bottom:-3px; left:0; width:0; height:2px; background:var(--primary); transition: width var(--transition); }
  .nav-links a:hover { color:var(--primary); }
  .nav-links a:hover::after { width:100%; }
  .nav-cta { background: var(--primary); color:#fff!important; padding:8px 20px; border-radius:50px; transition: background var(--transition), transform var(--transition)!important; }
  .nav-cta:hover { background: var(--primary-light)!important; transform: translateY(-1px)!important; }
  .nav-cta::after { display:none!important; }

  /* ── HERO ── */
  #hero {
    min-height: 100vh; display:flex; align-items:center; justify-content:center;
    padding: 100px 6% 60px;
    background: linear-gradient(135deg, #f0eeff 0%, #fff 50%, #fff0f4 100%);
    position: relative; overflow: hidden;
  }
  .hero-bg-blob {
    position:absolute; border-radius:50%; filter:blur(80px); opacity:0.35; pointer-events:none;
  }
  .blob1 { width:420px;height:420px;background:var(--primary);top:-80px;right:-80px; }
  .blob2 { width:280px;height:280px;background:var(--accent);bottom:-40px;left:5%; }
  .hero-content { max-width:640px; z-index:1; }
  .hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:#ede9ff; color:var(--primary); border-radius:50px;
    padding:6px 16px; font-size:0.82rem; font-weight:600; margin-bottom:1.5rem;
    animation: fadeDown 0.6s ease both;
  }
  .hero-badge .dot { width:8px;height:8px;border-radius:50%;background:var(--primary);animation:pulse 1.5s infinite; }
  h1 { font-size: clamp(2.2rem,5vw,3.6rem); font-weight:800; line-height:1.15; margin-bottom:1rem;
       animation: fadeUp 0.7s 0.1s ease both; }
  h1 span { color:var(--primary); }
  .typing-wrap { font-size:1.25rem; color:var(--muted); margin-bottom:1rem; min-height:2rem; animation: fadeUp 0.7s 0.2s ease both; }
  #typing { color:var(--primary); font-weight:700; }
  .cursor { display:inline-block; width:2px; height:1.2em; background:var(--primary); margin-left:2px; vertical-align:middle; animation:blink 0.75s steps(1) infinite; }
  .hero-desc { color:var(--muted); font-size:1.05rem; line-height:1.7; margin-bottom:2rem; animation: fadeUp 0.7s 0.3s ease both; }
  .hero-btns { display:flex; gap:1rem; flex-wrap:wrap; animation: fadeUp 0.7s 0.4s ease both; }
  .btn { display:inline-flex; align-items:center; gap:8px; padding:14px 32px; border-radius:50px;
         font-size:0.95rem; font-weight:600; text-decoration:none; transition: all var(--transition); cursor:pointer; border:none; }
  .btn-primary { background:var(--primary); color:#fff; box-shadow:0 4px 20px rgba(108,99,255,0.35); }
  .btn-primary:hover { background:var(--primary-light); transform:translateY(-2px); box-shadow:0 8px 28px rgba(108,99,255,0.45); }
  .btn-outline { background:transparent; color:var(--primary); border:2px solid var(--primary); }
  .btn-outline:hover { background:var(--primary); color:#fff; transform:translateY(-2px); }
  .hero-stats { display:flex; gap:2.5rem; margin-top:2.5rem; animation: fadeUp 0.7s 0.5s ease both; }
  .stat h3 { font-size:1.8rem; font-weight:800; color:var(--primary); }
  .stat p { font-size:0.82rem; color:var(--muted); margin-top:2px; }
  .hero-img { z-index:1; flex-shrink:0; animation: float 4s ease-in-out infinite; }
  .hero-card {
    background:#fff; border-radius:24px; padding:2rem; box-shadow:var(--card-shadow);
    width:260px; text-align:center; position:relative;
  }
  .hero-avatar { width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));
    display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1rem; }
  .hero-card h4 { font-weight:700; font-size:1rem; }
  .hero-card p { color:var(--muted); font-size:0.85rem; margin:4px 0 1rem; }
  .rating { color:#f7b731; font-size:1rem; }
  .badge-row { display:flex; gap:6px; justify-content:center; flex-wrap:wrap; }
  .badge { background:#ede9ff; color:var(--primary); padding:4px 10px; border-radius:50px; font-size:0.75rem; font-weight:600; }
  .hero-inner { display:flex; align-items:center; justify-content:space-between; gap:3rem; width:100%; max-width:1200px; }

  /* Section common */
  section { padding: 90px 6%; }
  .section-tag { display:inline-block; background:#ede9ff; color:var(--primary); padding:5px 16px; border-radius:50px; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.75rem; }
  .section-title { font-size:clamp(1.8rem,3.5vw,2.5rem); font-weight:800; margin-bottom:0.5rem; }
  .section-sub { color:var(--muted); font-size:1rem; max-width:500px; }
  .section-head { margin-bottom:3rem; }
  .reveal { opacity:0; transform:translateY(36px); transition: opacity 0.6s ease, transform 0.6s ease; }
  .reveal.visible { opacity:1; transform:none; }

  /* ── GIGS ── */
  #gigs { background:#fff; }
  .gigs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.5rem; }
  .gig-card {
    background:var(--bg); border-radius:var(--radius); padding:1.8rem;
    border:1.5px solid #eee; transition: all var(--transition); cursor:pointer; position:relative; overflow:hidden;
  }
  .gig-card::before {
    content:''; position:absolute; inset:0; background:linear-gradient(135deg,var(--primary),var(--accent));
    opacity:0; transition: opacity var(--transition);
  }
  .gig-card:hover { transform:translateY(-6px); border-color:var(--primary); box-shadow:0 12px 40px rgba(108,99,255,0.18); }
  .gig-card:hover::before { opacity:0.04; }
  .gig-icon { font-size:2.4rem; margin-bottom:1rem; display:block; position:relative; z-index:1; }
  .gig-card h3 { font-size:1.1rem; font-weight:700; margin-bottom:0.5rem; position:relative; z-index:1; }
  .gig-card p { color:var(--muted); font-size:0.9rem; line-height:1.6; margin-bottom:1.2rem; position:relative; z-index:1; }
  .gig-footer { display:flex; align-items:center; justify-content:space-between; position:relative; z-index:1; }
  .gig-price { font-weight:800; color:var(--primary); font-size:1rem; }
  .gig-price span { font-size:0.75rem; color:var(--muted); font-weight:400; }
  .gig-btn { background:var(--primary); color:#fff; border:none; padding:8px 18px; border-radius:50px; font-size:0.82rem; font-weight:600; cursor:pointer; transition:all var(--transition); }
  .gig-btn:hover { background:var(--primary-light); transform:scale(1.05); }

  /* ── ABOUT ── */
  #about { background: linear-gradient(135deg,#f0eeff,#fff8f0); }
  .about-inner { display:flex; gap:4rem; align-items:center; flex-wrap:wrap; }
  .about-img-wrap { flex:0 0 280px; }
  .about-img {
    width:280px;height:320px;border-radius:24px;
    background:linear-gradient(135deg,var(--primary),var(--accent));
    display:flex;align-items:center;justify-content:center;font-size:5rem;
    box-shadow:0 16px 48px rgba(108,99,255,0.25); position:relative;
  }
  .about-img::after { content:''; position:absolute;bottom:-12px;right:-12px;width:100%;height:100%;
    border-radius:24px;border:2px dashed rgba(108,99,255,0.25);z-index:-1; }
  .about-text { flex:1; min-width:280px; }
  .about-text h2 { font-size:clamp(1.6rem,3vw,2.2rem); font-weight:800; margin-bottom:1rem; }
  .about-text p { color:var(--muted); line-height:1.8; margin-bottom:1.5rem; }
  .skills { display:flex; flex-direction:column; gap:0.9rem; }
  .skill-row { display:flex; flex-direction:column; gap:4px; }
  .skill-label { display:flex; justify-content:space-between; font-size:0.88rem; font-weight:600; }
  .skill-bar { height:8px; border-radius:50px; background:#e8e8f0; overflow:hidden; }
  .skill-fill { height:100%; border-radius:50px; background:linear-gradient(90deg,var(--primary),var(--accent)); width:0; transition:width 1.2s cubic-bezier(.4,0,.2,1); }
  .student-tag { display:inline-flex; align-items:center; gap:6px; background:#fff3cd; color:#856404; padding:6px 14px; border-radius:50px; font-size:0.82rem; font-weight:700; margin-bottom:1rem; }

  /* ── TESTIMONIALS ── */
  #testimonials { background:#fff; }
  .testi-carousel { position:relative; overflow:hidden; }
  .testi-track { display:flex; gap:1.5rem; transition:transform 0.5s cubic-bezier(.4,0,.2,1); }
  .testi-card {
    min-width:320px; flex:0 0 320px; background:var(--bg); border-radius:var(--radius);
    padding:1.8rem; border:1.5px solid #eee; transition:all var(--transition);
  }
  .testi-card:hover { box-shadow:var(--card-shadow); border-color:var(--primary); }
  .testi-stars { color:#f7b731; font-size:1.1rem; margin-bottom:0.8rem; }
  .testi-text { color:var(--muted); line-height:1.7; font-size:0.95rem; margin-bottom:1.2rem; font-style:italic; }
  .testi-author { display:flex; align-items:center; gap:12px; }
  .testi-avatar { width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));
    display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.85rem; }
  .testi-info h4 { font-size:0.95rem; font-weight:700; }
  .testi-info p { font-size:0.8rem; color:var(--muted); }
  .testi-controls { display:flex; gap:1rem; margin-top:2rem; align-items:center; }
  .testi-btn { width:44px;height:44px;border-radius:50%;border:2px solid var(--primary);background:transparent;
    color:var(--primary);font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;
    transition:all var(--transition); }
  .testi-btn:hover { background:var(--primary); color:#fff; }
  .testi-dots { display:flex; gap:6px; }
  .testi-dot { width:8px;height:8px;border-radius:50%;background:#ddd;cursor:pointer;transition:all var(--transition); }
  .testi-dot.active { background:var(--primary); width:22px; border-radius:50px; }

  /* ── CONTACT ── */
  #contact { background:linear-gradient(135deg,var(--dark) 0%,#16213e 100%); color:#fff; }
  #contact .section-tag { background:rgba(108,99,255,0.25); color:#c4bfff; }
  #contact .section-title { color:#fff; }
  #contact .section-sub { color:rgba(255,255,255,0.6); }
  .contact-inner { display:flex; gap:4rem; flex-wrap:wrap; }
  .contact-info { flex:1; min-width:240px; }
  .contact-info p { color:rgba(255,255,255,0.65); line-height:1.8; margin-bottom:2rem; }
  .contact-item { display:flex; align-items:center; gap:12px; margin-bottom:1rem; color:rgba(255,255,255,0.8); font-size:0.95rem; }
  .contact-item span:first-child { width:40px;height:40px;border-radius:50%;background:rgba(108,99,255,0.2);
    display:flex;align-items:center;justify-content:center;font-size:1.1rem; }
  .social-links { display:flex; gap:1rem; margin-top:2rem; }
  .social-link { width:44px;height:44px;border-radius:50%;border:1.5px solid rgba(255,255,255,0.15);
    display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:1rem;
    transition:all var(--transition); }
  .social-link:hover { background:var(--primary); border-color:var(--primary); transform:translateY(-3px); }
  .contact-form { flex:1; min-width:280px; }
  .form-group { margin-bottom:1.2rem; }
  .form-group label { display:block; font-size:0.85rem; color:rgba(255,255,255,0.7); margin-bottom:6px; font-weight:500; }
  .form-group input, .form-group textarea {
    width:100%; padding:12px 16px; border-radius:12px;
    background:rgba(255,255,255,0.07); border:1.5px solid rgba(255,255,255,0.12);
    color:#fff; font-size:0.95rem; font-family:inherit; transition:all var(--transition);
    outline:none;
  }
  .form-group input::placeholder, .form-group textarea::placeholder { color:rgba(255,255,255,0.35); }
  .form-group input:focus, .form-group textarea:focus { border-color:var(--primary); background:rgba(108,99,255,0.1); }
  .form-group textarea { resize:vertical; min-height:120px; }
  .msg-success { background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:12px 16px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; }
  .msg-error { background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:12px 16px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; }

  /* ── FOOTER ── */
  footer { background:#0f0f1a; color:rgba(255,255,255,0.4); text-align:center; padding:1.5rem; font-size:0.85rem; }
  footer span { color:var(--accent); }

  /* ── ANIMATIONS ── */
  @keyframes fadeUp { from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:none} }
  @keyframes fadeDown { from{opacity:0;transform:translateY(-16px)}to{opacity:1;transform:none} }
  @keyframes float { 0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)} }
  @keyframes blink { 0%,100%{opacity:1}50%{opacity:0} }
  @keyframes pulse { 0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.3);opacity:0.7} }
  @keyframes shimmer { 0%{background-position:-200%}100%{background-position:200%} }

  /* ── RESPONSIVE ── */
  @media(max-width:768px) {
    .hero-inner { flex-direction:column; text-align:center; }
    .hero-img { display:none; }
    .hero-btns, .hero-stats { justify-content:center; }
    .about-inner { flex-direction:column; align-items:center; }
    .contact-inner { flex-direction:column; }
    .nav-links { display:none; }
  }
</style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav id="navbar">
  <div class="nav-logo"><?= explode(' ',$name)[0] ?><span>.</span></div>
  <ul class="nav-links">
    <li><a href="#hero">Home</a></li>
    <li><a href="#gigs">Gigs</a></li>
    <li><a href="#about">About</a></li>
    <li><a href="#testimonials">Reviews</a></li>
    <li><a href="#contact" class="nav-cta">Hire Me</a></li>
  </ul>
</nav>

<!-- ── HERO ── -->
<section id="hero">
  <div class="hero-bg-blob blob1"></div>
  <div class="hero-bg-blob blob2"></div>
  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-badge"><span class="dot"></span> Open to freelance projects</div>
      <h1>Hi, I'm <span><?= $name ?></span> 👋</h1>
      <div class="typing-wrap">I build <span id="typing"></span><span class="cursor"></span></div>
      <p class="hero-desc"><?= $tagline ?> Student developer turning ideas into clean, fast, real web solutions.</p>
      <div class="hero-btns">
        <a href="#gigs" class="btn btn-primary">🚀 View My Gigs</a>
        <a href="#contact" class="btn btn-outline">💬 Let's Talk</a>
      </div>
      <div class="hero-stats">
        <div class="stat"><h3>20+</h3><p>Projects Done</p></div>
        <div class="stat"><h3>15+</h3><p>Happy Clients</p></div>
        <div class="stat"><h3>4.9★</h3><p>Avg Rating</p></div>
      </div>
    </div>
    <div class="hero-img">
      <div class="hero-card">
        <div class="hero-avatar">👨‍💻</div>
        <h4><?= $name ?></h4>
        <p><?= $title ?></p>
        <div class="rating">★★★★★</div>
        <div class="badge-row" style="margin-top:0.8rem">
          <span class="badge">React</span>
          <span class="badge">PHP</span>
          <span class="badge">JS</span>
          <span class="badge">Node</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── GIGS ── -->
<section id="gigs">
  <div class="section-head reveal">
    <span class="section-tag">My Services</span>
    <h2 class="section-title">What I Offer</h2>
    <p class="section-sub">Fiverr-quality work at student-friendly prices. Pick a gig that fits your needs.</p>
  </div>
  <div class="gigs-grid">
    <?php foreach($gigs as $i=>$g): ?>
    <div class="gig-card reveal" style="transition-delay:<?= $i*0.08 ?>s">
      <span class="gig-icon"><?= $g['icon'] ?></span>
      <h3><?= $g['title'] ?></h3>
      <p><?= $g['desc'] ?></p>
      <div class="gig-footer">
        <div class="gig-price">Starting at $<?= $g['price'] ?><br><span>/ project</span></div>
        <button class="gig-btn" onclick="document.getElementById('contact').scrollIntoView({behavior:'smooth'})">Order Now</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── ABOUT ── -->
<section id="about">
  <div class="about-inner">
    <div class="about-img-wrap reveal">
      <div class="about-img">👨‍🎓</div>
    </div>
    <div class="about-text reveal">
      <span class="section-tag">About Me</span>
      <div class="student-tag">🎓 Computer Science Student</div>
      <h2>Passionate Developer &amp; Creative Problem Solver</h2>
      <p>I'm a 3rd-year Computer Science student who loves turning ideas into real digital products. From sleek landing pages to full-stack web apps, I bring clean code and thoughtful design to every project.</p>
      <p>I've worked with startups, small businesses, and bloggers — delivering projects on time, on budget, and beyond expectations.</p>
      <div class="skills">
        <?php foreach($skills as $s): ?>
        <div class="skill-row">
          <div class="skill-label"><span><?= $s['name'] ?></span><span><?= $s['pct'] ?>%</span></div>
          <div class="skill-bar"><div class="skill-fill" data-pct="<?= $s['pct'] ?>"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── TESTIMONIALS ── -->
<section id="testimonials">
  <div class="section-head reveal">
    <span class="section-tag">Reviews</span>
    <h2 class="section-title">What Clients Say</h2>
    <p class="section-sub">Real feedback from real people I've worked with.</p>
  </div>
  <div class="testi-carousel reveal">
    <div class="testi-track" id="testiTrack">
      <?php foreach($testimonials as $t): ?>
      <div class="testi-card">
        <div class="testi-stars"><?= str_repeat('★', $t['stars']) ?><?= str_repeat('☆', 5-$t['stars']) ?></div>
        <p class="testi-text">"<?= $t['text'] ?>"</p>
        <div class="testi-author">
          <div class="testi-avatar"><?= $t['avatar'] ?></div>
          <div class="testi-info"><h4><?= $t['name'] ?></h4><p><?= $t['role'] ?></p></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="testi-controls">
      <button class="testi-btn" id="prevBtn">&#8592;</button>
      <button class="testi-btn" id="nextBtn">&#8594;</button>
      <div class="testi-dots" id="testiDots">
        <?php foreach($testimonials as $i=>$t): ?>
        <div class="testi-dot <?= $i===0?'active':'' ?>" data-idx="<?= $i ?>"></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── CONTACT ── -->
<section id="contact">
  <div class="contact-inner">
    <div class="contact-info reveal">
      <span class="section-tag">Contact</span>
      <h2 class="section-title">Let's Work Together</h2>
      <p>Have a project in mind? I'd love to hear about it. Drop me a message and I'll get back to you within 24 hours.</p>
      <div class="contact-item"><span>📧</span><span><?= $email ?></span></div>
      <div class="contact-item"><span>🌍</span><span>Available Worldwide (Remote)</span></div>
      <div class="contact-item"><span>⚡</span><span>Usually replies within 24 hours</span></div>
      <div class="social-links">
        <a href="#" class="social-link" title="GitHub">⌨</a>
        <a href="#" class="social-link" title="LinkedIn">💼</a>
        <a href="#" class="social-link" title="Twitter">🐦</a>
        <a href="#" class="social-link" title="Fiverr">🎯</a>
      </div>
    </div>
    <div class="contact-form reveal">
      <?php if($msg_sent): ?>
        <div class="msg-success">✅ Message sent! I'll get back to you soon.</div>
      <?php elseif($msg_error): ?>
        <div class="msg-error">❌ Please fill in all fields.</div>
      <?php endif; ?>
      <form method="POST" action="#contact">
        <div class="form-group">
          <label>Your Name</label>
          <input type="text" name="from_name" placeholder="John Smith" required/>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="from_email" placeholder="john@example.com" required/>
        </div>
        <div class="form-group">
          <label>Your Message</label>
          <textarea name="message" placeholder="Tell me about your project..."></textarea>
        </div>
        <button type="submit" name="contact_submit" class="btn btn-primary" style="width:100%;justify-content:center">
          🚀 Send Message
        </button>
      </form>
    </div>
  </div>
</section>

<!-- ── FOOTER ── -->
<footer>
  <p>© <?= date('Y') ?> <span><?= $name ?></span> · Crafted with ❤️ &amp; lots of ☕</p>
</footer>

<script>
// ── Navbar scroll effect
window.addEventListener('scroll',()=>{
  document.getElementById('navbar').classList.toggle('scrolled',scrollY>50);
});

// ── Typing effect
const phrases = ['Fast Websites','React Apps','REST APIs','E-Commerce Stores','Clean UI/UX'];
let pi=0,ci=0,del=false;
const el=document.getElementById('typing');
function type(){
  const w=phrases[pi];
  el.textContent=del?w.slice(0,ci--):w.slice(0,ci++);
  if(!del&&ci>w.length){setTimeout(()=>del=true,1400);setTimeout(type,120);return;}
  if(del&&ci<0){del=false;pi=(pi+1)%phrases.length;}
  setTimeout(type,del?60:110);
}
type();

// ── Scroll reveal
const obs=new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(e.isIntersecting){e.target.classList.add('visible');}
  });
},{threshold:0.12});
document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));

// ── Skill bars animate on view
const skillObs=new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(e.isIntersecting){
      e.target.querySelectorAll('.skill-fill').forEach(bar=>{
        bar.style.width=bar.dataset.pct+'%';
      });
    }
  });
},{threshold:0.3});
const aboutEl=document.getElementById('about');
if(aboutEl)skillObs.observe(aboutEl);

// ── Testimonial carousel
let cur=0;
const track=document.getElementById('testiTrack');
const cards=track.querySelectorAll('.testi-card');
const dots=document.querySelectorAll('.testi-dot');
function goTo(idx){
  cur=Math.max(0,Math.min(idx,cards.length-1));
  track.style.transform=`translateX(calc(-${cur}*(320px + 1.5rem)))`;
  dots.forEach((d,i)=>d.classList.toggle('active',i===cur));
}
document.getElementById('prevBtn').onclick=()=>goTo(cur-1);
document.getElementById('nextBtn').onclick=()=>goTo(cur+1);
dots.forEach(d=>d.addEventListener('click',()=>goTo(+d.dataset.idx)));
setInterval(()=>goTo((cur+1)%cards.length),4000);
</script>
</body>
</html>
