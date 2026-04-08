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

$restaurants = db()->query("SELECT * FROM restaurants WHERE is_open=1 AND (is_active IS NULL OR is_active=1) ORDER BY rating DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$foods       = db()->query("SELECT f.*,r.name AS rname, r.id AS restaurant_id FROM foods f JOIN restaurants r ON r.id=f.restaurant_id WHERE $foodWhere ORDER BY RAND() LIMIT 12")->fetch_all(MYSQLI_ASSOC);
$categories  = db()->query("SELECT DISTINCT category FROM foods ORDER BY category")->fetch_all(MYSQLI_ASSOC);

require_once 'includes/header.php';
?>

<!-- ═══════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════ -->
<section class="relative min-h-[92vh] flex items-center overflow-hidden">
  <!-- Background image with overlay -->
  <div class="absolute inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1600&q=85"
         alt="Fine dining" class="img-cover opacity-30"/>
    <div class="absolute inset-0 bg-gradient-to-r from-purple-950 via-purple-950/90 to-transparent"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-purple-950 via-transparent to-purple-950/40"></div>
  </div>

  <!-- Decorative orbs -->
  <div class="absolute top-20 right-1/3 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute bottom-20 right-10 w-64 h-64 bg-gold-500/10 rounded-full blur-3xl pointer-events-none"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-5 py-20 grid md:grid-cols-2 gap-12 items-center">
    <div class="fade-up">
      <!-- Badge -->
      <div class="inline-flex items-center gap-2 glass rounded-full px-4 py-2 text-xs font-bold text-gold-300 mb-6 shimmer">
        <span class="w-2 h-2 bg-gold-400 rounded-full"></span>
        Now delivering in Douala 
      </div>

      <h1 class="font-display text-5xl md:text-7xl font-black leading-none mb-6">
        <span class="text-white">Food That</span><br/>
        <span class="gold-text">Drops</span><br/>
        <span class="text-white">Different.</span>
      </h1>

      <p class="text-purple-200 text-lg leading-relaxed mb-8 max-w-md">
        Premium restaurants. Real-time tracking. Delivered hot to your door in under 35 minutes. This is ChopDrop.
      </p>

      <!-- Search -->
      <form method="GET" class="flex gap-3 flex-wrap mb-8">
        <div class="flex-1 min-w-[220px] relative">
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input name="q" value="<?= e($search) ?>" placeholder="Search dishes or restaurants..."
            class="w-full pl-11 pr-4 py-4 bg-white/10 border border-purple-500/40 rounded-2xl text-white placeholder-purple-300 text-sm backdrop-blur-sm focus:border-gold-400"/>
        </div>
        <button type="submit" class="btn-gold rounded-2xl px-8 py-4 text-sm whitespace-nowrap">Search</button>
      </form>

      <!-- Stats -->
      <div class="flex gap-8 flex-wrap">
        <?php foreach([['50+','Restaurants'],['2,400+','Happy Customers'],['28 min','Avg Delivery'],['4.9★','Rating']] as [$n,$l]): ?>
        <div>
          <div class="font-display text-2xl font-bold gold-text"><?= $n ?></div>
          <div class="text-purple-400 text-xs mt-0.5"><?= $l ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Hero food cards stack -->
    <div class="hidden md:grid grid-cols-2 gap-4 fade-up" style="animation-delay:.2s">
      <?php
      $heroFoods = array_slice($foods, 0, 4);
      foreach($heroFoods as $i => $f):
        $delay = $i * 0.1;
      ?>
      <div class="glass rounded-2xl overflow-hidden card-hover fade-up" style="animation-delay:<?= .2+$delay ?>s">
        <div class="h-36 overflow-hidden relative">
          <img src="<?= e($f['image'] ? $f['image'] : 'images/default_food.jpg') ?>" alt="<?= e($f['name']) ?>" class="img-cover hover:scale-110 transition-transform duration-500"/>
          <div class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-purple-950/80 to-transparent"></div>
        </div>
        <div class="p-3">
          <div class="font-semibold text-sm text-white truncate"><?= e($f['name']) ?></div>
          <div class="text-gold-400 font-bold text-sm mt-1"><?= money($f['price']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Scroll indicator -->
  <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-purple-400 text-xs animate-bounce">
    <span>Scroll</span>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
  </div>
</section>

<!-- Flash messages -->
<div class="max-w-7xl mx-auto px-5 mt-6">
  <?php if($f=flash('success')): ?>
  <div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-5 py-3 text-sm font-medium"><?= e($f) ?></div>
  <?php endif; ?>
  <?php if($f=flash('error')): ?>
  <div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-2xl px-5 py-3 text-sm font-medium"><?= e($f) ?></div>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════
     HOW IT WORKS
════════════════════════════════════════════ -->
<section class="max-w-7xl mx-auto px-5 py-20">
  <div class="text-center mb-12">
    <p class="text-gold-400 text-sm font-bold uppercase tracking-widest mb-2">Simple &amp; Fast</p>
    <h2 class="font-display text-4xl font-bold text-white">How ChopDrop Works</h2>
  </div>
  <div class="grid md:grid-cols-4 gap-6">
    <?php
    $steps=[['📍','Set Location','Tell us where you are and we surface the best nearby restaurants.','1'],
            ['🍽️','Pick Your Dish','Browse stunning menus and build your perfect order.','2'],
            ['💳','Pay Instantly','MoMo, Orange Money, card or cash — your choice.','3'],
            ['🚴','Track Live','Watch your rider bring it to you in real time.','4']];
    foreach($steps as $i => [$icon,$title,$desc,$num]):
    ?>
    <div class="glass rounded-3xl p-6 text-center card-hover fade-up relative overflow-hidden" style="animation-delay:<?= $i*.1 ?>s">
      <div class="absolute -top-4 -right-4 font-display text-8xl font-black text-purple-800/30 select-none"><?= $num ?></div>
      <div class="text-4xl mb-4"><?= $icon ?></div>
      <h3 class="font-bold text-white mb-2"><?= $title ?></h3>
      <p class="text-purple-300 text-sm leading-relaxed"><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     FEATURED RESTAURANTS
════════════════════════════════════════════ -->
<section class="max-w-7xl mx-auto px-5 pb-20">
  <div class="flex items-end justify-between mb-8">
    <div>
      <p class="text-gold-400 text-sm font-bold uppercase tracking-widest mb-1">Top Picks</p>
      <h2 class="font-display text-4xl font-bold text-white">Featured Restaurants</h2>
    </div>
    <a href="restaurants.php" class="glass px-5 py-2.5 rounded-full text-sm font-semibold text-gold-300 hover:text-white transition-colors hidden md:flex items-center gap-2">
      View All <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php foreach($restaurants as $i => $r): ?>
    <a href="restaurant.php?id=<?= $r['id'] ?>" class="block fade-up" style="animation-delay:<?= $i*.07 ?>s">
      <div class="glass rounded-3xl overflow-hidden card-hover group">
        <div class="relative h-48 overflow-hidden">
          <img src="<?= e($r['image'] ? $r['image'] : 'images/default_restaurant.jpg') ?>" alt="<?= e($r['name']) ?>"
               class="img-cover group-hover:scale-110 transition-transform duration-500"/>
          <div class="absolute inset-0 bg-gradient-to-t from-purple-950/80 via-transparent to-transparent"></div>
          <!-- Open badge -->
          <div class="absolute top-3 left-3">
            <span class="<?= $r['is_open']?'bg-green-900/80 text-green-300 border-green-500/50':'bg-gray-900/80 text-gray-400 border-gray-600/50' ?> border text-xs font-bold px-3 py-1 rounded-full backdrop-blur-sm">
              <?= $r['is_open']?'● Open':'● Closed' ?>
            </span>
          </div>
          <!-- Rating -->
          <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-bold text-gold-300 flex items-center gap-1">
            ★ <?= $r['rating'] ?>
          </div>
          <!-- Name on image -->
          <div class="absolute bottom-3 left-3 right-3">
            <h3 class="font-display font-bold text-white text-lg leading-tight"><?= e($r['name']) ?></h3>
          </div>
        </div>
        <div class="p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="flex gap-2 flex-wrap">
              <?php foreach(array_slice(explode(',', $r['cuisine']),0,2) as $cuisine): ?>
              <span class="bg-purple-800/50 text-purple-200 text-xs px-2.5 py-1 rounded-lg"><?= trim(e($cuisine)) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="flex items-center justify-between text-xs text-purple-300 mb-2">
            <span class="flex items-center gap-1">🕐 <?= e($r['delivery_time']) ?> min</span>
            <span class="flex items-center gap-1">🛵 <?= money($r['delivery_fee']) ?></span>
          </div>
          <div class="text-xs text-purple-400 border-t border-purple-700/30 pt-2 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="truncate"><?= e(explode(',', $r['address'])[0]) ?></span>
          </div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     POPULAR DISHES
════════════════════════════════════════════ -->
<section class="py-20 bg-purple-900/30">
  <div class="max-w-7xl mx-auto px-5">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
      <div>
        <p class="text-gold-400 text-sm font-bold uppercase tracking-widest mb-1">Chef's Selection</p>
        <h2 class="font-display text-4xl font-bold text-white"><?= $search ? 'Results for "'.e($search).'"' : 'Popular Dishes' ?></h2>
      </div>
      <!-- Category filters -->
      <div class="flex gap-2 flex-wrap">
        <a href="index.php" class="<?= !$catFilter?'btn-gold':'glass text-purple-200 hover:text-white' ?> px-4 py-2 rounded-full text-xs font-bold transition-all">All</a>
        <?php foreach($categories as $cat): ?>
        <a href="?cat=<?= urlencode($cat['category']) ?>" class="<?= $catFilter===$cat['category']?'btn-gold':'glass text-purple-200 hover:text-white' ?> px-4 py-2 rounded-full text-xs font-bold transition-all"><?= e($cat['category']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if(empty($foods)): ?>
    <div class="text-center py-20 text-purple-400">
      <div class="text-5xl mb-4">🔍</div>
      <p class="text-lg">No dishes found for "<b class="text-white"><?= e($search) ?></b>"</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php foreach($foods as $i => $f): ?>
      <div class="glass rounded-3xl overflow-hidden card-hover fade-up group <?= !$f['is_available'] ? 'opacity-70' : '' ?>" style="animation-delay:<?= $i*.05 ?>s">
        <div class="relative h-44 overflow-hidden">
          <img src="<?= e($f['image'] ? $f['image'] : 'images/default_food.jpg') ?>" alt="<?= e($f['name']) ?>"
               class="img-cover group-hover:scale-110 transition-transform duration-500 <?= !$f['is_available'] ? 'grayscale' : '' ?>"/>
          <div class="absolute inset-0 bg-gradient-to-t from-purple-950/60 to-transparent"></div>
          <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-bold text-gold-300">
            <?= e($f['category']) ?>
          </div>
          <?php if(!$f['is_available']): ?>
          <div class="absolute inset-0 flex items-center justify-center bg-black/40">
            <span class="bg-red-600/90 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-full border border-white/20 shadow-xl">Sold Out</span>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-4">
          <h3 class="font-bold text-white mb-1 truncate"><?= e($f['name']) ?></h3>
          <p class="text-purple-300 text-xs leading-relaxed mb-1 line-clamp-2"><?= e($f['description']) ?></p>
          <a href="restaurant.php?id=<?= $f['restaurant_id'] ?>" class="inline-flex items-center gap-1 text-purple-400 text-xs mb-3 hover:text-gold-300 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <?= e($f['rname']) ?>
          </a>
            <div class="flex items-center justify-between">
            <span class="font-display font-bold text-gold-400 text-lg"><?= money($f['price']) ?></span>
            <?php if(!$f['is_available']): ?>
              <span class="text-red-500 text-[10px] font-black uppercase tracking-widest bg-red-500/10 px-3 py-1.5 rounded-xl border border-red-500/20">Unavailable</span>
            <?php else: ?>
            <form method="POST" action="cart.php" class="ajax-cart-form">
              <input type="hidden" name="action"   value="add"/>
              <input type="hidden" name="food_id"  value="<?= $f['id'] ?>"/>
              <input type="hidden" name="is_ajax"  value="1"/>
              <button type="submit" class="btn-gold rounded-full w-9 h-9 text-xl flex items-center justify-center flex-shrink-0 active:scale-90 transition-transform">+</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     CTA BANNER
════════════════════════════════════════════ -->
<section class="max-w-7xl mx-auto px-5 py-20">
  <div class="relative rounded-3xl overflow-hidden">
    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=85" alt="Food" class="absolute inset-0 img-cover opacity-20"/>
    <div class="absolute inset-0 bg-gradient-to-r from-purple-900/95 via-purple-800/80 to-purple-900/95"></div>
    <div class="relative z-10 p-12 md:p-16 text-center">
      <p class="text-gold-400 text-sm font-bold uppercase tracking-widest mb-3">Limited Time</p>
      <h2 class="font-display text-4xl md:text-5xl font-black text-white mb-4">
        First Order <span class="gold-text">20% Off</span>
      </h2>
      <p class="text-purple-200 mb-8 max-w-lg mx-auto">Create your account today and get 20% off your first ChopDrop order. Premium food, premium savings.</p>
      <div class="flex gap-4 justify-center flex-wrap">
        <a href="register.php" class="btn-gold rounded-2xl px-8 py-4 text-base">Get Started Free</a>
        <a href="restaurants.php" class="glass rounded-2xl px-8 py-4 text-base font-semibold text-white hover:bg-purple-700/40 transition-colors">Browse Menu</a>
      </div>
    </div>
  </div>
</section>

<!-- Toast Notification Container -->
<div id="toast-container" class="fixed bottom-8 right-8 z-[100] flex flex-col gap-3"></div>

<style>
  .toast-enter { transform: translateX(100%); opacity: 0; }
  .toast-visible { transform: translateX(0); opacity: 1; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
  .toast-exit { transform: translateX(100%); opacity: 0; transition: all 0.3s ease-in; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toastContainer = document.getElementById('toast-container');
  const cartCounters = document.querySelectorAll('.cart-count-badge'); // Update header if badges have this class

  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-600/90' : 'bg-red-600/90';
    const icon = type === 'success' ? '✅' : '⚠️';
    
    toast.className = `${bgColor} text-white px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-md border border-white/10 flex items-center gap-3 min-w-[280px] toast-enter`;
    toast.innerHTML = `<span class="text-xl">${icon}</span> <span class="font-bold text-sm">${message}</span>`;
    
    toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add('toast-visible'), 10);
    
    setTimeout(() => {
      toast.classList.add('toast-exit');
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }

  document.querySelectorAll('.ajax-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const btn = this.querySelector('button');
      
      btn.disabled = true;
      btn.classList.add('opacity-50', 'shimmer');

      fetch('cart.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast(data.message);
          // Update cart counts in header if exists
          const badges = document.querySelectorAll('[id^="cartCount"], .cart-count-display');
          badges.forEach(b => {
            b.textContent = data.cartCount;
            b.classList.add('animate-bounce');
            setTimeout(() => b.classList.remove('animate-bounce'), 1000);
          });
        } else {
          if (data.redirect) window.location.href = data.redirect;
          else showToast(data.message, 'error');
        }
      })
      .catch(err => showToast('Failed to add item.', 'error'))
      .finally(() => {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'shimmer');
      });
    });
  });
});
</script>

<!-- ═══════════════════════════════════════════
     AI CONCIERGE CHATBOT
════════════════════════════════════════════ -->
<div class="fixed bottom-8 right-8 z-[90]">
  <!-- Chat Button -->
  <button id="chatbot-toggle" class="w-16 h-16 btn-gold rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all relative group">
    <svg class="w-8 h-8 group-hover:rotate-12 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
    <div class="absolute -top-1 -right-1 w-5 h-5 bg-emerald-500 border-4 border-purple-950 rounded-full"></div>
    <!-- Tooltip -->
    <div class="absolute bottom-full right-1/2 translate-x-1/2 mb-4 px-4 py-2 bg-gold-400 text-purple-950 text-[10px] font-black uppercase tracking-widest rounded-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none translate-y-2 group-hover:translate-y-0 shadow-xl">
      Ask AI Concierge
    </div>
  </button>

  <!-- Chat Window -->
  <div id="chatbot-window" class="hidden absolute bottom-20 left-0 w-[350px] md:w-[400px] h-[500px] glass rounded-[40px] border border-gold-500/20 shadow-[0_20px_60px_rgba(0,0,0,0.5)] flex flex-col overflow-hidden animate-fade-up">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-gold-500 to-gold-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-purple-950 rounded-2xl flex items-center justify-center text-xl shadow-inner">✨</div>
        <div>
          <div class="text-purple-950 font-black text-sm leading-tight">AI Concierge</div>
          <div class="text-purple-900/60 text-[9px] font-bold uppercase tracking-widest flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-emerald-600 rounded-full animate-pulse"></span> Online Now
          </div>
        </div>
      </div>
      <button id="chatbot-close" class="text-purple-950/50 hover:text-purple-950 transition-colors text-xl">✕</button>
    </div>

    <!-- Messages -->
    <div id="chatbot-messages" class="flex-1 p-6 overflow-y-auto space-y-4 scroll-smooth">
      <div class="flex gap-3">
        <div class="w-8 h-8 rounded-xl bg-gold-500/20 flex items-center justify-center text-sm flex-shrink-0">🍱</div>
        <div class="bg-white/10 p-4 rounded-2xl rounded-tl-none text-xs text-purple-200 border border-white/5 leading-relaxed">
          Hello! I'm your **ChopDrop AI Concierge**. <br/><br/>
          I can help you find the perfect meal based on your budget or craving. What are you in the mood for tonight?
        </div>
      </div>
    </div>

    <!-- Suggested Prompts -->
    <div id="chatbot-suggestions" class="px-6 py-3 flex gap-2 overflow-x-auto no-scrollbar border-t border-white/5 bg-white/5">
      <button class="suggestion-btn glass whitespace-nowrap px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest text-gold-400 border-gold-500/20 hover:bg-gold-500 hover:text-purple-950 transition-all">Recommend Sushi</button>
      <button class="suggestion-btn glass whitespace-nowrap px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest text-gold-400 border-gold-500/20 hover:bg-gold-500 hover:text-purple-950 transition-all">Local Favorites</button>
      <button class="suggestion-btn glass whitespace-nowrap px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest text-gold-400 border-gold-500/20 hover:bg-gold-500 hover:text-purple-950 transition-all">Dinner under 5000</button>
    </div>

    <!-- Input -->
    <form id="chatbot-form" class="p-4 bg-white/5 border-t border-white/10 flex gap-2">
      <input type="text" id="chatbot-input" placeholder="Type your craving..." class="flex-1 bg-white/10 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white placeholder-purple-400 outline-none focus:border-gold-500 transition-all" autocomplete="off"/>
      <button type="submit" class="w-10 h-10 btn-gold rounded-xl flex items-center justify-center shadow-lg active:scale-90 transition-transform">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('chatbot-toggle');
  const closeBtn = document.getElementById('chatbot-close');
  const windowEl = document.getElementById('chatbot-window');
  const messagesEl = document.getElementById('chatbot-messages');
  const formEl = document.getElementById('chatbot-form');
  const inputEl = document.getElementById('chatbot-input');

  toggleBtn.addEventListener('click', () => windowEl.classList.toggle('hidden'));
  closeBtn.addEventListener('click', () => windowEl.classList.add('hidden'));

  function addMessage(text, type = 'bot') {
    const div = document.createElement('div');
    div.className = `flex gap-3 ${type === 'user' ? 'flex-row-reverse' : ''}`;
    
    const bg = type === 'bot' ? 'bg-white/10 border-white/5' : 'bg-gold-500/10 border-gold-500/20';
    const radius = type === 'bot' ? 'rounded-tl-none' : 'rounded-tr-none';
    
    div.innerHTML = `
      <div class="w-8 h-8 rounded-xl bg-white/5 flex items-center justify-center text-sm flex-shrink-0">
        ${type === 'bot' ? '<svg class="w-4 h-4 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>' : '👤'}
      </div>
      <div class="${bg} p-4 rounded-2xl ${radius} text-xs ${type === 'bot' ? 'text-purple-200' : 'text-white'} border leading-relaxed max-w-[80%]">
        ${text.replace(/\n/g, '<br/>')}
      </div>
    `;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  formEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = inputEl.value.trim();
    if (!msg) return;

    inputEl.value = '';
    addMessage(msg, 'user');

    // Typing indicator
    const typing = document.createElement('div');
    typing.className = 'flex gap-3 animate-pulse';
    typing.innerHTML = '<div class="w-8 h-8 rounded-xl bg-white/5"></div><div class="bg-white/10 p-4 rounded-2xl rounded-tl-none text-[10px] text-purple-400 border border-white/5 italic">Concierge is thinking...</div>';
    messagesEl.appendChild(typing);
    messagesEl.scrollTop = messagesEl.scrollHeight;

    try {
      const res = await fetch('ajax_chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg })
      });
      const data = await res.json();
      typing.remove();
      addMessage(data.reply || "I'm sorry, I couldn't process that. Please try again.");
    } catch (err) {
      typing.remove();
      addMessage("Connection to Concierge lost. Check your internet.");
    }
  });

  document.querySelectorAll('.suggestion-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      inputEl.value = btn.textContent;
      formEl.dispatchEvent(new Event('submit'));
    });
  });
});
</script>

<?php require_once 'includes/footer.php'; ?>
