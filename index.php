<?php
session_start();
require_once 'includes/config.php';

$pageTitle = 'ChopDrop — Luxury Food Delivery';

$search    = trim($_GET['q'] ?? '');
$catFilter = $_GET['cat'] ?? '';
$s = db()->real_escape_string($search);
$c = db()->real_escape_string($catFilter);

$foodWhere = "1=1";
if ($s) $foodWhere .= " AND (f.name LIKE '%$s%' OR f.description LIKE '%$s%')";
if ($c) $foodWhere .= " AND f.category='$c'";

$restaurants = db()->query("SELECT * FROM restaurants WHERE is_open=1 AND (is_active IS NULL OR is_active=1) ORDER BY rating DESC LIMIT 8");
$restaurants = $restaurants ? $restaurants->fetch_all() : [];

$foods = db()->query("SELECT f.*, r.name AS rname, r.id AS restaurant_id FROM foods f JOIN restaurants r ON r.id=f.restaurant_id WHERE $foodWhere ORDER BY RAND() LIMIT 12");
$foods = $foods ? $foods->fetch_all() : [];

$categories = db()->query("SELECT DISTINCT category FROM foods ORDER BY category");
$categories = $categories ? $categories->fetch_all() : [];

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<div class="relative overflow-hidden pt-16 pb-24 md:pt-24 md:pb-32">
  <div class="max-w-7xl mx-auto px-5 relative z-10">
    <div class="max-w-3xl">
      <h1 class="font-display text-5xl md:text-7xl font-black text-white leading-[1.1] mb-6 fade-up">
        Gourmet <span class="gold-text">Chop</span>, <br/>
        Delivered <span class="text-purple-400">Flawlessly</span>.
      </h1>
      <p class="text-purple-200 text-lg md:text-xl font-medium mb-10 max-w-xl fade-up" style="animation-delay: 0.1s">
        Experience the finest restaurants in Douala & Yaoundé with our premium delivery service. Luxury at your doorstep.
      </p>
      
      <form action="restaurants.php" method="GET" class="flex flex-col sm:flex-row gap-3 max-w-2xl fade-up" style="animation-delay: 0.2s">
        <div class="flex-1 relative">
          <span class="absolute left-5 top-1/2 -translate-y-1/2 text-xl">🔍</span>
          <input type="text" name="q" placeholder="Search for sushi, burgers, or Ndolé..." 
            class="w-full bg-white/10 border border-purple-500/30 rounded-2xl pl-14 pr-6 py-4 text-white placeholder-purple-400 focus:border-gold-500 focus:ring-1 focus:ring-gold-500/20 transition-all outline-none backdrop-blur-md"/>
        </div>
        <button type="submit" class="btn-gold rounded-2xl px-10 py-4 font-black uppercase tracking-widest shadow-xl shadow-gold/20 active:scale-95 transition-all">
          Explore
        </button>
      </form>
    </div>
  </div>
  
  <!-- Background Elements -->
  <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-purple-600/10 rounded-full blur-[120px] -mr-48 -mt-48 animate-pulse"></div>
  <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-gold-400/5 rounded-full blur-[100px] -ml-24 -mb-24"></div>
</div>

<!-- Categories -->
<section class="max-w-7xl mx-auto px-5 mb-24 fade-up" style="animation-delay: 0.3s">
  <div class="flex items-center justify-between mb-8">
    <h2 class="font-display text-3xl font-black text-white">Popular Cravings</h2>
    <a href="restaurants.php" class="text-gold-400 font-bold hover:text-white transition-colors flex items-center gap-2 group text-sm uppercase tracking-widest">
      See All <span class="group-hover:translate-x-1 transition-transform">→</span>
    </a>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
    <?php 
    $catIcons = ['Pizza'=>'🍕','Burger'=>'🍔','Sushi'=>'🍣','Asian'=>'🥢','Local'=>'🥘','Healthy'=>'🥗','Dessert'=>'🍰','Chicken'=>'🍗','Curry'=>'🍛','Drinks'=>'🍹'];
    foreach($categories as $cat): 
      $c = $cat['category'];
      $icon = $catIcons[$c] ?? '🍱';
    ?>
    <a href="restaurants.php?cat=<?= urlencode($c) ?>" class="glass rounded-3xl p-6 text-center border-purple-500/10 hover:border-gold-500/40 hover:bg-gold-500/5 transition-all group">
      <div class="text-4xl mb-3 group-hover:scale-110 transition-transform"><?= $icon ?></div>
      <div class="text-sm font-bold text-purple-200 group-hover:text-white"><?= e($c) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Featured Restaurants -->
<section class="max-w-7xl mx-auto px-5 mb-24 fade-up" style="animation-delay: 0.4s">
  <div class="flex items-center justify-between mb-10">
    <div>
      <h2 class="font-display text-3xl font-black text-white mb-2">Editor’s Choice</h2>
      <p class="text-purple-400 font-medium">Top-rated dining experiences this week</p>
    </div>
  </div>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach($restaurants as $r): ?>
    <a href="restaurant.php?id=<?= $r['id'] ?>" class="block group">
      <div class="glass rounded-[40px] overflow-hidden border-purple-500/10 card-hover">
        <div class="relative h-60 overflow-hidden">
          <img src="<?= e($r['image']) ?>" alt="<?= e($r['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"/>
          <div class="absolute inset-0 bg-gradient-to-t from-purple-950/90 via-transparent to-transparent"></div>
          
          <div class="absolute top-5 right-5 glass px-3 py-1.5 rounded-2xl text-xs font-bold text-gold-300 flex items-center gap-1.5 backdrop-blur-md">
            ★ <?= $r['rating'] ?>
          </div>
          
          <?php if($r['logo']): ?>
          <img src="<?= e($r['logo']) ?>" class="absolute bottom-5 left-8 w-14 h-14 rounded-2xl border-2 border-white/20 shadow-xl bg-white/10 backdrop-blur-md" alt="Logo"/>
          <?php endif; ?>
        </div>
        
        <div class="p-8">
          <h3 class="font-display text-2xl font-black text-white mb-2"><?= e($r['name']) ?></h3>
          <p class="text-purple-400 text-sm line-clamp-1 mb-5">📍 <?= e($r['address']) ?></p>
          
          <div class="flex items-center justify-between pt-5 border-t border-purple-500/10">
            <div class="flex items-center gap-4">
              <span class="text-[10px] font-black uppercase tracking-widest text-purple-200">🛵 <?= money($r['delivery_fee']) ?></span>
              <span class="text-[10px] font-black uppercase tracking-widest text-purple-200">🕐 <?= e($r['delivery_time']) ?> mins</span>
            </div>
            <div class="bg-gold-500/10 text-gold-400 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-gold-500/20">Open</div>
          </div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Promotional Banner -->
<section class="max-w-7xl mx-auto px-5 mb-24">
  <div class="glass rounded-[50px] p-10 md:p-16 relative overflow-hidden flex flex-col md:flex-row items-center gap-10 border border-gold-500/20">
    <div class="relative z-10 flex-1 text-center md:text-left">
      <h2 class="font-display text-4xl md:text-5xl font-black text-white mb-6">Unrivaled <span class="gold-text">Rewards</span>.</h2>
      <p class="text-purple-200 text-lg font-medium mb-8 max-w-md">Join our premium circle and enjoy zero delivery fees on your first 3 orders from selected luxury partners.</p>
      <a href="register.php" class="btn-gold rounded-full px-12 py-4 text-sm font-black uppercase tracking-widest inline-block">Join the platform</a>
    </div>
    <div class="relative z-10 w-full max-w-md">
      <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&q=80" 
           class="w-full h-80 object-cover rounded-[40px] shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-500 border-4 border-white/10"/>
    </div>
    <!-- Decor -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-gold-400/10 rounded-full blur-[100px] -mr-48 -mt-48"></div>
  </div>
</section>

<!-- Trending Dishes -->
<section class="max-w-7xl mx-auto px-5 mb-32 fade-up" style="animation-delay: 0.5s">
  <div class="text-center mb-16">
    <h2 class="font-display text-4xl font-black text-white mb-4 italic">Trending Dishes</h2>
    <div class="h-1.5 w-24 bg-gold-500 mx-auto rounded-full"></div>
  </div>
  
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php foreach($foods as $f): ?>
    <div class="glass rounded-3xl overflow-hidden border border-white/5 card-hover group">
      <div class="relative h-48">
        <img src="<?= e($f['image']) ?>" alt="<?= e($f['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"/>
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        <div class="absolute top-4 left-4">
          <span class="bg-black/60 backdrop-blur-md text-[10px] font-black text-white uppercase tracking-widest px-3 py-1.5 rounded-xl border border-white/20"><?= e($f['category']) ?></span>
        </div>
      </div>
      <div class="p-6">
        <h3 class="font-bold text-white mb-1 truncate"><?= e($f['name']) ?></h3>
        <p class="text-purple-400 text-[10px] font-black uppercase tracking-widest mb-4"><?= e($f['rname']) ?></p>
        <div class="flex items-center justify-between">
          <span class="font-display font-bold text-gold-400 text-xl"><?= money($f['price']) ?></span>
          <form method="POST" action="cart.php">
            <input type="hidden" name="action" value="add"/>
            <input type="hidden" name="food_id" value="<?= $f['id'] ?>"/>
            <button type="submit" class="w-10 h-10 btn-gold rounded-2xl flex items-center justify-center text-2xl shadow-lg shadow-gold/10 hover:shadow-gold/30 active:scale-90 transition-all">
              +
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>