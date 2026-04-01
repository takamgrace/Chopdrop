<?php
session_start();
require_once 'includes/config.php';
$id = (int)($_GET['id'] ?? 0);
$restaurant = db()->query("SELECT * FROM restaurants WHERE id=$id")->fetch_assoc();
if (!$restaurant) { header('Location: restaurants.php'); exit; }

// Redirect customers away from deactivated restaurants
if (isset($restaurant['is_active']) && !$restaurant['is_active'] && !isAdmin()) {
    flash('error', 'This restaurant is currently unavailable.');
    header('Location: restaurants.php'); exit;
}

$catFilter = $_GET['cat'] ?? '';
$cw = $catFilter ? "AND category='".db()->real_escape_string($catFilter)."'" : '';
$foods   = db()->query("SELECT * FROM foods WHERE restaurant_id=$id $cw ORDER BY category,name")->fetch_all(MYSQLI_ASSOC);
$cats    = db()->query("SELECT DISTINCT category FROM foods WHERE restaurant_id=$id")->fetch_all(MYSQLI_ASSOC);
$reviews = db()->query("SELECT rv.*,u.name uname FROM reviews rv JOIN users u ON u.id=rv.user_id WHERE rv.restaurant_id=$id ORDER BY rv.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$pageTitle = e($restaurant['name']) . ' — ChopDrop';
$isActive = !isset($restaurant['is_active']) || $restaurant['is_active'];
require_once 'includes/header.php';
?>

<!-- Restaurant Hero -->
<div class="relative h-[420px] overflow-hidden">
  <img src="<?= e($restaurant['image']) ?>" alt="<?= e($restaurant['name']) ?>" class="img-cover"/>
  <div class="absolute inset-0 bg-gradient-to-t from-purple-950 via-purple-950/60 to-transparent"></div>
  <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-8">
    <div class="flex items-end gap-4 flex-wrap">
      <div class="flex gap-5 items-end">
        <?php if(isset($restaurant['logo']) && $restaurant['logo']): ?>
        <img src="<?= e($restaurant['logo']) ?>" class="w-20 h-20 md:w-28 md:h-28 rounded-full border-[3px] border-white object-cover shadow-2xl bg-white hidden sm:block mb-1" alt="Logo"/>
        <?php endif; ?>
        <div>
          <div class="flex items-center gap-3 mb-3 flex-wrap">
            <span class="<?= $restaurant['is_open']?'bg-green-900/80 text-green-300 border-green-500/40':'bg-gray-900/80 text-gray-400 border-gray-600/40' ?> border text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">
              <?= $restaurant['is_open']?'● Open Now':'● Closed' ?>
            </span>
            <span class="glass px-3 py-1.5 rounded-full text-xs font-bold text-gold-300">★ <?= $restaurant['rating'] ?></span>
          </div>
          <h1 class="font-display text-4xl md:text-5xl font-black text-white drop-shadow-lg"><?= e($restaurant['name']) ?></h1>
          <p class="text-purple-200 mt-2 max-w-xl"><?= e($restaurant['description']) ?></p>
          <div class="flex gap-5 mt-3 text-sm text-purple-300 flex-wrap">
            <span>🕐 <?= e($restaurant['delivery_time']) ?> min</span>
            <span>🛵 <?= money($restaurant['delivery_fee']) ?></span>
            <span>📍 <?= e($restaurant['address']) ?></span>
            <span>📞 <?= e($restaurant['phone']) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-4">
  <?php if($f=flash('success')): ?><div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-5 py-3 text-sm mb-4"><?= e($f) ?></div><?php endif; ?>
  <?php if($f=flash('error')):   ?><div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-2xl px-5 py-3 text-sm mb-4"><?= e($f) ?></div><?php endif; ?>
</div>

<div class="max-w-7xl mx-auto px-5 pb-20 flex gap-8 items-start flex-col md:flex-row">

  <!-- Category Sidebar -->
  <div class="w-full md:w-48 flex-shrink-0">
    <div class="glass rounded-2xl p-4 sticky top-[80px]">
      <p class="text-xs font-bold text-purple-400 uppercase tracking-wider mb-3">Menu</p>
      <a href="restaurant.php?id=<?= $id ?>" class="block px-3 py-2.5 rounded-xl text-sm font-semibold mb-1 <?= !$catFilter?'btn-gold':'text-purple-300 hover:text-white hover:bg-purple-800/40' ?> transition-all">All Items</a>
      <?php foreach($cats as $c): ?>
      <a href="?id=<?= $id ?>&cat=<?= urlencode($c['category']) ?>" class="block px-3 py-2.5 rounded-xl text-sm font-medium mb-1 <?= $catFilter===$c['category']?'btn-gold':'text-purple-300 hover:text-white hover:bg-purple-800/40' ?> transition-all">
        <?= e($c['category']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Food Grid -->
  <div class="flex-1">
    <?php if(empty($foods)): ?>
    <div class="text-center py-20 text-purple-400">No items available currently.</div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach($foods as $i => $f): ?>
      <div class="glass rounded-2xl overflow-hidden card-hover group fade-up <?= !$f['is_available'] ? 'opacity-70' : '' ?>" style="animation-delay:<?= $i*.05 ?>s">
        <div class="relative h-44 overflow-hidden">
          <img src="<?= e($f['image']) ?>" alt="<?= e($f['name']) ?>" class="img-cover group-hover:scale-110 transition-transform duration-500 <?= !$f['is_available'] ? 'grayscale' : '' ?>"/>
          <div class="absolute inset-0 bg-gradient-to-t from-purple-950/60 to-transparent"></div>
          <span class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-bold text-gold-300"><?= e($f['category']) ?></span>
          <?php if(!$f['is_available']): ?>
          <div class="absolute inset-0 flex items-center justify-center bg-black/40">
            <span class="bg-red-600/90 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-full border border-white/20 shadow-xl">Sold Out</span>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-4">
          <h3 class="font-bold text-white mb-1"><?= e($f['name']) ?></h3>
          <p class="text-purple-300 text-xs leading-relaxed line-clamp-2 mb-3"><?= e($f['description']) ?></p>
          <div class="flex items-center justify-between">
            <span class="font-display font-bold text-gold-400 text-lg"><?= money($f['price']) ?></span>
            <?php if(!$f['is_available']): ?>
              <span class="text-red-500 text-[10px] font-black uppercase tracking-widest bg-red-500/10 px-3 py-1.5 rounded-xl border border-red-500/20">Unavailable</span>
            <?php elseif($restaurant['is_open']): ?>
            <form method="POST" action="cart.php">
              <input type="hidden" name="action"   value="add"/>
              <input type="hidden" name="food_id"  value="<?= $f['id'] ?>"/>
              <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>"/>
              <button type="submit" class="btn-gold rounded-full w-9 h-9 text-xl flex items-center justify-center flex-shrink-0">+</button>
            </form>
            <?php else: ?>
            <span class="text-purple-500 text-xs">Closed</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Reviews -->
    <div class="mt-12">
      <h3 class="font-display text-2xl font-bold text-white mb-6">Customer Reviews</h3>
      <?php if(empty($reviews)): ?>
      <div class="glass rounded-2xl p-8 text-center border border-purple-600/30">
        <div class="text-4xl mb-3 opacity-50">⭐</div>
        <h4 class="text-white font-bold mb-1">No reviews yet</h4>
        <p class="text-purple-300 text-sm">Be the first to rate this restaurant after a successful delivery!</p>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach($reviews as $rv): ?>
        <div class="glass rounded-2xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 btn-gold rounded-full flex items-center justify-center font-bold text-purple-900 flex-shrink-0"><?= strtoupper($rv['uname'][0]) ?></div>
            <div>
              <div class="font-semibold text-white text-sm"><?= e($rv['uname']) ?></div>
              <div class="text-gold-400 text-xs"><?= str_repeat('★',$rv['rating']) ?><span class="text-gray-500"><?= str_repeat('★',5-$rv['rating']) ?></span></div>
            </div>
            <div class="ml-auto text-purple-400 text-xs"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
          </div>
          <?php if($rv['comment']): ?><p class="text-purple-300 text-sm leading-relaxed"><?= e($rv['comment']) ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
