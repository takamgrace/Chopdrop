<?php

// TEMPORARY — remove after fixing
echo "PHP is working. Host=" . getenv('DB_HOST');
exit;


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
<section class="max-w-7xl mx-auto px-5 py-20 pb-32">
  <div class="relative rounded-[40px] overflow-hidden bg-purple-900 shadow-2xl">
    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=85" alt="Food" class="absolute inset-0 img-cover opacity-20"/>
    <div class="absolute inset-0 bg-gradient-to-r from-purple-950 via-purple-900/80 to-purple-950"></div>
    <div class="relative z-10 p-12 md:p-20 text-center">
      <div class="inline-flex items-center gap-2 bg-gold-400 text-purple-950 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 shadow-lg">Limited Time Offer</div>
      <h2 class="font-display text-4xl md:text-6xl font-black text-white mb-6">First Order <span class="gold-text">20% Off</span></h2>
      <p class="text-purple-200 mb-10 max-w-xl mx-auto text-lg leading-relaxed">Join the ChopDrop family today and enjoy premium food delivery with a 20% discount on your first order.</p>
      <div class="flex gap-4 justify-center flex-wrap">
        <a href="register.php" class="btn-gold rounded-3xl px-10 py-5 text-lg shadow-2xl hover:scale-105 active:scale-95 transition-all">Get Started Free</a>
        <a href="restaurants.php" class="glass rounded-3xl px-10 py-5 text-lg font-bold text-white hover:bg-purple-700/40 transition-colors border border-white/10">Browse Menu</a>
      </div>
    </div>
  </div>
</section>

<!-- Toast Notifications -->
<div id="toast-container" class="fixed bottom-8 right-8 z-[100] flex flex-col gap-3 pointer-events-none child-pointer-events-auto"></div>

<!-- AI SUGGESTION CHATBOT -->
<div id="chatbot-container" class="fixed bottom-6 right-6 z-[100] flex flex-col items-end gap-4 pointer-events-none">
  <div id="chat-window" class="glass w-[350px] h-[500px] rounded-[32px] overflow-hidden flex flex-col border border-gold-500/20 shadow-2xl transition-all duration-500 origin-bottom-right scale-0 opacity-0 pointer-events-auto">
    <div class="bg-gradient-to-r from-purple-900 to-purple-800 p-5 flex items-center justify-between border-b border-white/5">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gold-500 rounded-full flex items-center justify-center text-xl shadow-lg ring-2 ring-gold-500/20">✨</div>
        <div>
          <div class="text-white font-black text-sm leading-none">ChopDrop Assistant</div>
          <div class="text-[10px] text-gold-400 font-bold uppercase tracking-widest mt-1 mr-[-5px]">AI Concierge</div>
        </div>
      </div>
      <button id="close-chat" class="text-purple-300 hover:text-white transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="chat-messages" class="flex-1 overflow-y-auto p-5 space-y-4 custom-scrollbar bg-purple-950/20">
      <div class="bg-purple-900/40 text-purple-100 p-4 rounded-2xl rounded-tl-none text-sm border border-purple-700/30">
        Hi! I'm the ChopDrop Assistant. Ask me for recommendations!
      </div>
    </div>
    <div class="p-5 bg-purple-900/20 border-t border-white/5">
      <form id="chat-form" class="relative">
        <input type="text" id="chat-input" placeholder="Type your craving..." class="w-full bg-purple-900/60 border border-purple-700/30 rounded-2xl pl-5 pr-12 py-4 text-white text-sm focus:border-gold-500 outline-none shadow-inner"/>
        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-gold-500 rounded-xl flex items-center justify-center text-purple-950 shadow-lg hover:scale-105 active:scale-95 transition-all">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        </button>
      </form>
    </div>
  </div>
  <button id="toggle-chat" class="w-16 h-16 bg-gold-500 rounded-full flex items-center justify-center text-3xl shadow-2xl hover:scale-110 hover:rotate-12 active:scale-90 transition-all pointer-events-auto relative group">
    <span class="z-10 animate-float">🍱</span>
    <div class="absolute inset-0 bg-gold-500 rounded-full animate-ping opacity-20 group-hover:hidden"></div>
  </button>
</div>

<style>
  #chat-window.active { transform: scale(1); opacity: 1; pointer-events: auto; }
  #chat-window { transform: scale(0); opacity: 0; pointer-events: none; transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); transform-origin: bottom right; }
  .child-pointer-events-auto > * { pointer-events: auto; }
  .toast-enter { transform: translateX(100%); opacity: 0; }
  .toast-visible { transform: translateX(0); opacity: 1; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
  .toast-exit { transform: translateX(100%); opacity: 0; transition: all 0.3s ease-in; }
  .custom-scrollbar::-webkit-scrollbar { width: 4px; }
  .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.2); border-radius: 10px; }
  .typing-dot { animation: typing 1.4s infinite; }
  @keyframes typing { 0%, 20% { opacity: .2; } 50% { opacity: 1; } 100% { opacity: .2; } }
  .animate-float { animation: float 3s ease-in-out infinite; }
  @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('toggle-chat'), closeBtn = document.getElementById('close-chat'), chatWin = document.getElementById('chat-window'), chatForm = document.getElementById('chat-form'), chatInp = document.getElementById('chat-input'), chatMsgs = document.getElementById('chat-messages');
  if(toggleBtn && chatWin){ toggleBtn.onclick=(e)=>{ e.stopPropagation(); chatWin.classList.toggle('active'); }; closeBtn.onclick=()=>chatWin.classList.remove('active'); }
  function addMessage(text, isUser=false){ const msg=document.createElement('div'); msg.className=isUser?'bg-gold-500 text-purple-950 p-4 rounded-2xl rounded-tr-none text-sm ml-8 font-semibold':'bg-purple-900/40 text-purple-100 p-4 rounded-2xl rounded-tl-none text-sm mr-8 border border-purple-700/30'; msg.innerHTML=text.replace(/\n/g,'<br>'); chatMsgs.appendChild(msg); chatMsgs.scrollTop=chatMsgs.scrollHeight; }
  if(chatForm){ chatForm.onsubmit=async(e)=>{ e.preventDefault(); const m=chatInp.value.trim(); if(!m)return; addMessage(m,true); chatInp.value=''; const t=document.createElement('div'); t.className='bg-purple-900/40 text-purple-100 p-4 rounded-2xl text-xs flex gap-1 w-fit'; t.innerHTML='<span class="typing-dot">●</span><span class="typing-dot" style="animation-delay:0.2s">●</span><span class="typing-dot" style="animation-delay:0.4s">●</span>'; chatMsgs.appendChild(t); chatMsgs.scrollTop=chatMsgs.scrollHeight; try{ const r=await fetch('ajax_chatbot.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:m})}); const d=await r.json(); t.remove(); if(d.reply)addMessage(d.reply); else addMessage('Error.'); }catch(err){ t.remove(); addMessage('Error.'); } }; }
  const toastContainer = document.getElementById('toast-container');
  function showToast(message, type='success'){ if(!toastContainer)return; const toast=document.createElement('div'); const bgColor=type==='success'?'bg-emerald-600/90':'bg-red-600/90'; toast.className=`${bgColor} text-white px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-md border border-white/10 flex items-center gap-3 min-w-[280px] toast-enter`; icon=type==='success'?'✅':'⚠️'; toast.innerHTML=`<span class="text-xl">${icon}</span> <span class="font-bold text-sm">${message}</span>`; toastContainer.appendChild(toast); setTimeout(()=>toast.classList.add('toast-visible'),10); setTimeout(()=>{toast.classList.add('toast-exit');setTimeout(()=>toast.remove(),300);},4000); }
  document.querySelectorAll('.ajax-cart-form').forEach(form => { form.addEventListener('submit', function(e) { e.preventDefault(); const formData=new FormData(this); const btn=this.querySelector('button'); btn.disabled=true; btn.classList.add('opacity-50','shimmer'); fetch('cart.php',{method:'POST',body:formData}).then(res=>res.json()).then(data=>{ if(data.success){ showToast(data.message); const badges=document.querySelectorAll('[id^="cartCount"], .cart-count-display'); badges.forEach(b=>{b.textContent=data.cartCount; b.classList.add('animate-bounce'); setTimeout(()=>b.classList.remove('animate-bounce'),1000);}); } else { if(data.redirect)window.location.href=data.redirect; else showToast(data.message,'error'); } }).catch(err=>showToast('Error.','error')).finally(()=>{btn.disabled=false; btn.classList.remove('opacity-50','shimmer');}); }); });
});
</script>

<?php require_once 'includes/footer.php'; ?>
