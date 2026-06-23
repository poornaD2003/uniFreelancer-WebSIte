<?php

include 'includes/db.php';

$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

$stats = [
    ["value" => "500",  "suffix" => "+",   "label" => "Active Students"],
    ["value" => "1200", "suffix" => "+",   "label" => "Jobs Completed"],
    ["value" => "4.9",  "suffix" => "★",   "label" => "Client Rating"],
];
$categories = [
    ["icon" => "💻", "title" => "Development",  "desc" => "Web, Mobile & Software apps"],
    ["icon" => "🎨", "title" => "Design",        "desc" => "UI/UX, Graphic & Branding"],
    ["icon" => "✍️", "title" => "Writing",       "desc" => "Content, Copy & Research"],
    ["icon" => "🎓", "title" => "Tutoring",      "desc" => "Academic help & Skill sharing"],
];
$featured_query = "
    SELECT u.id, u.fullname, u.profile_pic, sp.university_name, sp.faculty, sp.department, sp.club_affiliations,
           (SELECT COUNT(*) FROM orders WHERE student_id = u.id AND status = 'completed') as jobs_completed,
           (SELECT MIN(price) FROM gigs WHERE student_id = u.id) as min_rate
    FROM users u
    JOIN student_profiles sp ON u.id = sp.user_id
    WHERE u.role = 'student' AND u.status = 'active'
    ORDER BY u.created_at DESC
    LIMIT 10
";

$featured_result = $conn->query($featured_query);

$top_gigs_query = "
    SELECT g.id,g.title,g.image, g.description, g.price, u.fullname, sp.university_name
    FROM gigs g
    JOIN users u ON g.student_id = u.id
    JOIN student_profiles sp ON u.id = sp.user_id
    WHERE g.status = 'approve'
    ORDER BY g.created_at DESC
    LIMIT 8
";

$top_gigs_result = $conn->query($top_gigs_query);

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
  <form action="jobs.php" method="GET" class="search-bar">
    <input type="text" name="search" placeholder='Try "React developer", "logo design", "Python tutor"…'/>
    <div class="search-divider"></div>
    <select name="category" class="search-cat">
      <option value="">All Categories</option>
      <option value="development">Development</option>
      <option value="design">Design</option>
      <option value="writing">Writing</option>
      <option value="tutoring">Tutoring</option>
    </select>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  
  <div class="search-tags">
    <span>Popular:</span>
    <a href="jobs.php?search=React" class="search-tag" style="text-decoration:none;">React</a>
    <a href="jobs.php?search=Logo Design" class="search-tag" style="text-decoration:none;">Logo Design</a>
    <a href="jobs.php?search=WordPress" class="search-tag" style="text-decoration:none;">WordPress</a>
    <a href="jobs.php?search=Python" class="search-tag" style="text-decoration:none;">Python</a>
    <a href="jobs.php?search=SEO" class="search-tag" style="text-decoration:none;">SEO Writing</a>
  </div>
</div>

<section class="section how-section" id="how">
  <div class="section-inner">
    <div class="section-head reveal">
      <span class="sec-tag">Top Gigs</span>
      <h2 class="sec-title">Popular Gigs</h2>
      <p class="sec-sub">Here are some of the best students on the platform.</p>
    </div>
    
    <div class="freelancer-grid" style="margin-top: 3rem;">
      <?php 
      if ($top_gigs_result && $top_gigs_result->num_rows > 0): 
        $j = 0;
        while($g = $top_gigs_result->fetch_assoc()): 
          // මිල ගණන් සහ විස්තර සකසා ගැනීම
          $gig_price = ($g['price'] > 0) ? "Rs. " . number_format($g['price'], 0) : "Flexible";
          $short_desc = (strlen($g['description']) > 90) ? mb_substr($g['description'], 0, 90) . '...' : $g['description'];
          
          // 🛠️ IMAGE FIX: SQL Query එකේ තියෙන g.image එකට අනුව පාර (Path) සකස් කිරීම
          $img_path = (!empty($g['image']) && $g['image'] !== 'default.png')
              ? "uploads/" . htmlspecialchars($g['image'])
              : "images/hero_illustration.png"; // Image එකක් නැතිනම් වැටෙන Default පින්තූරය
              
          // Avatar එක සඳහා නමේ මුල් අකුර
          $initial_name = !empty($g['fullname']) ? strtoupper(mb_substr($g['fullname'], 0, 1)) : "👤";
      ?>
        
        <div class="gig-card reveal" style="transition-delay:<?= $j*0.1 ?>s">
            <div class="card-img-wrap">
                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($g['title']); ?>" class="card-cover" style="width: 100%; height: 200px; object-fit: cover;">
                <button class="heart-btn" aria-label="Save to favourites">
                    <i class="far fa-heart"></i>
                </button>
            </div>

            <div class="card-body">
              
                <a href="freelancer_gig.php?id=<?php echo $g['id']; ?>" style="text-decoration:none;">
                    <h3 class="card-title" style="margin: 0.5rem 0; font-size: 16px; font-weight: 600; color: #333;">
                        <?php echo htmlspecialchars($g['title']); ?>
                    </h3>
                </a>
                
                <p style="font-size: 13px; color: #666; line-height: 1.4; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($short_desc); ?>
                </p>

                <div class="card-rating" style="margin-bottom: 1rem; font-size: 13px;">
                    <span class="stars" style="color: #f1c40f;"><i class="fas fa-star"></i></span>
                    <span class="rating-num" style="font-weight: 600;">4.8</span>
                </div>

                <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 0.75rem;">
                    <div class="seller-info" style="display: flex; align-items: center; gap: 8px;">
                        <div class="seller-av" style="width: 28px; height: 28px; background: #e0e0e0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                            <?php echo $initial_name; ?>
                        </div>
                        <span class="seller-name" style="font-size: 13px; font-weight: 500;">
                            <?php echo htmlspecialchars($g['fullname']); ?>
                            <i class="fas fa-check-circle verified" style="color: #3498db; font-size: 11px; margin-left: 2px;"></i>
                        </span>
                    </div>
                    <div class="card-price" style="text-align: right;">
                        <span class="price-label" style="display: block; font-size: 11px; color: #888;">Starting at</span>
                        <span class="price-value" style="font-size: 15px; font-weight: 700; color: #2ecc71;"><?php echo $gig_price; ?></span>
                    </div>
                </div>
            </div>
        </div>

      <?php 
        $j++;
        endwhile; 
      else:
      ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--color-text-secondary); padding: 2rem;">No top active gigs found.</p>
      <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 3.5rem; margin-bottom: 3.5rem;">
        <a href="jobs.php" class="btn-line" style="display: inline-block; padding: 12px 36px; text-decoration: none; font-weight: 600; font-size: 14.5px; transition: all 0.3s;">
            Explore All Gigs <i class="ti ti-arrow-right" style="vertical-align: middle; margin-left: 4px;"></i>
        </a>
    </div>
  </div>
</section>

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
      <a href="jobs.php?category=<?= urlencode(strtolower($c['title'])) ?>" style="display:block;text-decoration:none;color:inherit;">
        <div class="cat-card reveal" style="transition-delay:<?= $i*0.08 ?>s">
          <div class="cat-emoji"><?= $c['icon'] ?></div>
          <h3><?= $c['title'] ?></h3>
          <p><?= $c['desc'] ?></p>
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
      <?php 
      if ($featured_result && $featured_result->num_rows > 0): 
        $i = 0;
        while($f = $featured_result->fetch_assoc()): 
          // Tags සකසා ගැනීම (Department සහ Faculty එක එකතු කර)
          $tags = array_filter([$f['department'], $f['faculty']]);
          if(!empty($f['club_affiliations'])) {
              $clubs = explode(',', $f['club_affiliations']);
              $tags = array_merge($tags, array_slice($clubs, 0, 2)); // උපරිම ක්ලබ් 2ක් පමණක් ටැග් වලට ගනී
          }
          
          // මිල ගණන් සහ ජොබ්ස් ගණන නිවැරදිව සැකසීම
          $rate_display = ($f['min_rate'] > 0) ? "Rs. " . number_format($f['min_rate']) : "Flexible";
          $jobs_count = $f['jobs_completed'] ? $f['jobs_completed'] : 0;
          $initial = mb_substr($f['fullname'], 0, 1);
      ?>
      <div class="fl-card reveal" style="transition-delay:<?= $i*0.1 ?>s">
        <div class="fl-top">
          <div class="fl-av">
            <?php if($f['profile_pic'] !== 'default.png'): ?>
                <img src="uploads/<?= $f['profile_pic'] ?>" alt="<?= $f['fullname'] ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
            <?php else: ?>
                <?= $initial ?>👤
            <?php endif; ?>
          </div>
          <div class="fl-info">
            <h4><?= htmlspecialchars($f['fullname']) ?></h4>
            <div class="fl-uni">🎓 <?= htmlspecialchars($f['university_name']) ?></div>
            <div class="fl-rating"><span class="st">★</span> 4.9 (<?= $jobs_count ?> jobs)</div>
          </div>
        </div>
        <div class="fl-tags">
          <?php foreach($tags as $t): ?>
          <span class="fl-tag"><?= htmlspecialchars(trim($t)) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="fl-footer">
          <div>
            <div class="fl-rate"><?= $rate_display ?></div>
            <div class="fl-jobs"><?= $jobs_count ?> jobs completed</div>
          </div>
          <a href="profile.php?id=<?= $f['id'] ?>" class="btn-line" style="text-decoration: none; text-align: center;">View Profile</a>
        </div>
      </div>
      <?php 
        $i++;
        endwhile; 
      else:
      ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--color-text-secondary); padding: 2rem;">No featured student experts found active.</p>
      <?php endif; ?>
    </div>

    <!-- ── SHOW MORE BUTTON ── -->
    <div style="text-align: center; margin-top: 3.5rem;">
        <a href="jobs.php" class="btn-line" style="display: inline-block; padding: 12px 36px; text-decoration: none; font-weight: 600; font-size: 14.5px; transition: all 0.3s;">
            Show More <i class="ti ti-arrow-right" style="vertical-align: middle; margin-left: 4px;"></i>
        </a>
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
