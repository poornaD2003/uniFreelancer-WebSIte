<?php
// index.php — Student Freelancer Platform
$stats = [
    ["value" => "500",  "suffix" => "+",   "label" => "Active Students"],
    ["value" => "1200", "suffix" => "+",   "label" => "Jobs Completed"],
    ["value" => "4.9",  "suffix" => "★",   "label" => "Client Rating"],
];
$categories = [
    ["icon" => "💻", "title" => "Development",  "desc" => "Web, Mobile & Software apps",    "count" => "142 gigs"],
    ["icon" => "🎨", "title" => "Design",        "desc" => "UI/UX, Graphic & Branding",      "count" => "98 gigs"],
    ["icon" => "✍️", "title" => "Writing",       "desc" => "Content, Copy & Research",       "count" => "76 gigs"],
    ["icon" => "🎓", "title" => "Tutoring",      "desc" => "Academic help & Skill sharing",  "count" => "54 gigs"],
];
$featured = [
    ["name" => "Aisha R.", "uni" => "MIT",        "skill" => "React Developer",     "rate" => "$28/hr", "rating" => 4.9, "jobs" => 34, "tags" => ["React","Node.js","Firebase"]],
    ["name" => "Carlos M.", "uni" => "Stanford",   "skill" => "UI/UX Designer",      "rate" => "$22/hr", "rating" => 5.0, "jobs" => 21, "tags" => ["Figma","Tailwind","Framer"]],
    ["name" => "Priya S.",  "uni" => "Oxford",     "skill" => "Content Strategist",  "rate" => "$18/hr", "rating" => 4.8, "jobs" => 47, "tags" => ["SEO","Copy","Research"]],
];
$steps = [
    ["n"=>"01","icon"=>"🔍","title"=>"Post a Job",        "desc"=>"Describe your project, timeline, and budget in under 2 minutes."],
    ["n"=>"02","icon"=>"🤝","title"=>"Match with Talent",  "desc"=>"Browse verified student profiles and connect with the perfect fit."],
    ["n"=>"03","icon"=>"🚀","title"=>"Get it Done",        "desc"=>"Collaborate, approve milestones, and release payment securely."],
];
?>
<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UniGigs — Student Freelance Marketplace</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="mesh"></div>



<!-- ── HERO ── -->
<section class="hero" style="max-width:1280px;margin:0 auto;padding:110px 7% 60px;display:flex;align-items:center;gap:4rem;">
  <div class="hero-left">
    <div class="hero-badge"><span class="pulse"></span> 500+ students actively freelancing</div>
    <h1>Find <em>Student Talent</em><br>for Any Project</h1>
    <p class="hero-sub">The exclusive freelance marketplace for university students. Hire skilled developers, designers, and writers — fast, affordable, and verified.</p>
    <div class="hero-btns">
      <a href="jobs.php" class="btn btn-primary">Browse Gigs ↗</a>
      <a href="register.php" class="btn btn-ghost">Start Freelancing</a>
    </div>
    <div class="hero-trust">
     
      <div class="trust-text"><strong>1,200+ projects</strong> delivered with a <strong>4.9★</strong> avg rating</div>
    </div>
  </div>
  <div class="hero-right">
    <div class="hero-main-card">
      <div class="card-profile-row">
        <div class="card-avatar">👩‍💻</div>
        <div>
          <div class="card-name">Aisha Rahman</div>
          <div class="card-uni">🎓 MIT · Computer Science</div>
          <div class="card-stars">★★★★★ 4.9 (34 reviews)</div>
        </div>
      </div>
      <div class="card-skill-tags">
        <span class="skill-tag">React</span>
        <span class="skill-tag">Node.js</span>
        <span class="skill-tag">Firebase</span>
        <span class="skill-tag">UI/UX</span>
      </div>
      <div class="card-rate-row">
        <div class="card-rate">$28 <span>/ hour</span></div>
        <button class="btn btn-primary btn-sm">Hire Now</button>
      </div>
    </div>
    <div class="float-card fc1">✅ Job Completed</div>
    <div class="float-card fc2">💬 3 new messages</div>
    <div class="float-card fc3">🔒 Secure Payments</div>
  </div>
</section>

<!-- ── SEARCH ── -->
<div class="search-section">
  <div class="search-bar">
    <input type="text" placeholder='Try "React developer", "logo design", "Python tutor"…'/>
    <div class="search-divider"></div>
    <select class="search-cat">
      <option>All Categories</option>
      <option>Development</option>
      <option>Design</option>
      <option>Writing</option>
      <option>Tutoring</option>
    </select>
    <a href="jobs.php" class="btn btn-primary">Search</a>
  </div>
  <div class="search-tags">
    <span>Popular:</span>
    <span class="search-tag">React</span>
    <span class="search-tag">Logo Design</span>
    <span class="search-tag">WordPress</span>
    <span class="search-tag">Python</span>
    <span class="search-tag">SEO Writing</span>
  </div>
</div>

<!-- ── CATEGORIES ── -->
<section class="section" id="categories">
  <div class="section-inner">
    <div class="section-head reveal">
      <span class="sec-tag">Explore</span>
      <h2 class="sec-title">Popular Categories</h2>
      <p class="sec-sub">From code to content — find the skill you need from verified student experts.</p>
    </div>
    <div class="cat-grid">
      <?php foreach($categories as $i=>$c): ?>
      <a href="jobs.php?cat=<?= strtolower($c['title']) ?>" style="display:block;text-decoration:none;color:inherit;">
        <div class="cat-card reveal" style="transition-delay:<?= $i*0.08 ?>s">
          <div class="cat-emoji"><?= $c['icon'] ?></div>
          <h3><?= $c['title'] ?></h3>
          <p><?= $c['desc'] ?></p>
          <span class="cat-count"><?= $c['count'] ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── STATS ── -->
<div class="stats-section">
  <div class="stats-inner">
    <?php foreach($stats as $s): ?>
    <div class="stat-item reveal">
      <div class="stat-num"><?= $s['value'] ?><?= $s['suffix'] ?></div>
      <div class="stat-lbl"><?= $s['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── FEATURED FREELANCERS ── -->
<section class="section" id="freelancers">
  <div class="section-inner">
    <div class="section-head reveal">
      <span class="sec-tag">Top Talent</span>
      <h2 class="sec-title">Featured Freelancers</h2>
      <p class="sec-sub">Hand-picked student experts ready to take on your next project.</p>
    </div>
    <div class="freelancer-grid">
      <?php foreach($featured as $i=>$f): ?>
      <div class="fl-card reveal" style="transition-delay:<?= $i*0.1 ?>s">
        <div class="fl-top">
          <div class="fl-av"><?= mb_substr($f['name'],0,1) ?>👤</div>
          <div class="fl-info">
            <h4><?= $f['name'] ?></h4>
            <div class="fl-uni">🎓 <?= $f['uni'] ?> · <?= $f['skill'] ?></div>
            <div class="fl-rating"><span class="st">★</span> <?= $f['rating'] ?> (<?= $f['jobs'] ?> jobs)</div>
          </div>
        </div>
        <div class="fl-tags">
          <?php foreach($f['tags'] as $t): ?>
          <span class="fl-tag"><?= $t ?></span>
          <?php endforeach; ?>
        </div>
        <div class="fl-footer">
          <div>
            <div class="fl-rate"><?= $f['rate'] ?></div>
            <div class="fl-jobs"><?= $f['jobs'] ?> jobs completed</div>
          </div>
          <button class="btn-line">View Profile</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── HOW IT WORKS ── -->
<section class="section how-section" id="how">
  <div class="section-inner">
    <div class="section-head reveal">
      <span class="sec-tag">Simple Process</span>
      <h2 class="sec-title">How It Works</h2>
      <p class="sec-sub">From posting to delivery — it's as easy as 1, 2, 3.</p>
    </div>
    <div class="steps-grid">
      <?php foreach($steps as $i=>$s): ?>
      <div class="step reveal" style="transition-delay:<?= $i*0.1 ?>s">
        <div class="step-num"><?= $s['n'] ?></div>
        <div class="step-icon"><?= $s['icon'] ?></div>
        <h3><?= $s['title'] ?></h3>
        <p><?= $s['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── CTA ── -->
<section class="cta-section">
  <div class="cta-box reveal">
    <h2>Ready to Build <em>Something Great?</em></h2>
    <p>Join hundreds of clients and students already thriving on UniGigs. Post your first job — it's free.</p>
    <div class="cta-btns">
      <a href="jobs.php" class="btn btn-primary">Browse All Gigs ↗</a>
      <a href="register.php" class="btn btn-ghost">Post a Job</a>
    </div>
  </div>
</section>

<!-- ── FOOTER ── -->
<footer>
  <div class="footer-logo">UniGigs<span>.</span></div>
  <p>The student freelance marketplace</p>
  <div class="footer-links">
    <a href="#">About</a><a href="#">Jobs</a><a href="#">Pricing</a>
    <a href="#">Privacy</a><a href="#">Terms</a><a href="#">Contact</a>
  </div>
  <p class="footer-copy">© <?= date('Y') ?> UniGigs. Built for students, by students. ❤️</p>
</footer>

<script>
// ── Navbar scroll
window.addEventListener('scroll',()=>{
  document.getElementById('nav').classList.toggle('scrolled',scrollY>40);
});

// ── Scroll reveal
const obs = new IntersectionObserver(entries=>{
  entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('visible'); obs.unobserve(e.target); } });
},{threshold:0.1, rootMargin:'0px 0px -60px 0px'});
document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));
</script>
</body>
</html>
