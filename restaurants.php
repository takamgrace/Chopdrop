<?php
session_start();
require_once 'includes/config.php';
$pageTitle = 'Restaurants — ChopDrop';
$search = trim($_GET['q'] ?? '');
$s = db()->real_escape_string($search);
$activeFilter = "(is_active IS NULL OR is_active=1)";
$where = $search ? "WHERE (name LIKE '%$s%' OR cuisine LIKE '%$s%') AND $activeFilter" : "WHERE $activeFilter";
$restaurants = db()->query("SELECT * FROM restaurants $where ORDER BY is_open DESC, rating DESC")->fetch_all();
require_once 'includes/header.php';
?>

<div class="relative overflow-hidden">
  <div class="absolute inset-0 opacity-20">
    <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1400&q=70" class="img-cover"/>
    <div class="absolute inset-0 bg-gradient-to-b from-purple-950 via-purple-950/80 to-purple-950"></div>
  </div>
  <div class="relative z-10 max-w-7xl mx-auto px-5 py-16">
    <p class="text-gold-400 text-sm font-bold uppercase tracking-widest mb-2">Discover</p>
    <h1 class="font-display text-5xl font-black text-white mb-4">All Restaurants</h1>
    <p class="text-purple-300 mb-8">The finest dining experiences, delivered to your door.</p>
    <form method="GET" class="flex gap-3 max-w-lg">
      <input name="q" value="<?= e($search) ?>" placeholder="Search by name or cuisine..."
        class="flex-1 bg-white/10 border border-purple-500/40 rounded-2xl px-5 py-3.5 text-white placeholder-purple-400 text-sm backdrop-blur-sm"/>
      <button type="submit" class="btn-gold rounded-2xl px-6 py-3.5 text-sm whitespace-nowrap">Search</button>
    </form>
  </div>
</div>

<section class="max-w-7xl mx-auto px-5 py-12">
  <?php if(empty($restaurants)): ?>
  <div class="text-center py-20 text-purple-400">
    <div class="text-5xl mb-4">🏪</div>
    <p>No restaurants found<?= $search?' for "'.e($search).'"':'' ?>.</p>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach($restaurants as $i => $r): ?>
    <a href="restaurant.php?id=<?= $r['id'] ?>" class="block fade-up" style="animation-delay:<?= $i*.06 ?>s">
      <div class="glass rounded-3xl overflow-hidden card-hover group">
        <div class="relative h-52 overflow-hidden">
          <img src="<?= e($r['image']) ?>" alt="<?= e($r['name']) ?>" class="img-cover group-hover:scale-110 transition-transform duration-500"/>
          <div class="absolute inset-0 bg-gradient-to-t from-purple-950/90 via-purple-950/20 to-transparent"></div>
          <div class="absolute top-3 left-3 flex gap-2">
            <span class="<?= $r['is_open']?'bg-green-900/80 text-green-300 border-green-500/40':'bg-gray-900/80 text-gray-400 border-gray-600/40' ?> border text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">
              <?= $r['is_open']?'● Open':'● Closed' ?>
            </span>
          </div>
          <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm px-2.5 py-1.5 rounded-full text-xs font-bold text-gold-300 flex items-center gap-1">
            ★ <?= $r['rating'] ?>
          </div>
          <div class="absolute bottom-0 left-0 right-0 p-4 flex items-end gap-3 bg-gradient-to-t from-[#0f172a]/90 via-[#0f172a]/60 to-transparent">
            <?php if(isset($r['logo']) && $r['logo']): ?>
            <img src="<?= e($r['logo']) ?>" class="w-12 h-12 rounded-full border-2 border-white object-cover shadow-lg flex-shrink-0 bg-white" alt="Logo"/>
            <?php else: ?>
            <div class="w-12 h-12 rounded-full border-2 border-white bg-purple-800 flex items-center justify-center text-white font-display font-bold text-xl shadow-lg flex-shrink-0"><?= strtoupper($r['name'][0]) ?></div>
            <?php endif; ?>
            <div>
              <h3 class="font-display font-bold text-white text-lg leading-tight drop-shadow-md mb-0.5"><?= e($r['name']) ?></h3>
              <p class="text-white/80 text-[11px] line-clamp-1 drop-shadow-md"><?= e($r['description']) ?></p>
            </div>
          </div>
        </div>
        <div class="p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="flex gap-2 flex-wrap">
              <?php foreach(array_slice(explode(',', $r['cuisine']),0,2) as $c): ?>
              <span class="bg-purple-800/50 text-purple-200 text-xs px-2.5 py-1 rounded-lg"><?= trim(e($c)) ?></span>
              <?php endforeach; ?>
            </div>
            <span class="text-purple-400 text-xs">📍 <?= e(explode(',', $r['address'])[0]) ?></span>
          </div>
          <div class="flex items-center justify-between text-xs pt-3 border-t border-purple-700/30">
            <span class="text-purple-300 flex items-center gap-1">🕐 <?= e($r['delivery_time']) ?> min</span>
            <span class="text-purple-300 flex items-center gap-1">🛵 <?= money($r['delivery_fee']) ?></span>
            <span class="text-purple-300">📞 <?= e($r['phone']) ?></span>
          </div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php require_once 'includes/footer.php'; ?>
