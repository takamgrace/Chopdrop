<?php
session_start(); require_once 'includes/config.php'; requireLogin();
$pageTitle='My Orders — ChopDrop';
$uid=(int)$_SESSION['user_id'];
$orders=db()->query("SELECT o.*,r.name rname,r.image rimage, (SELECT rating FROM reviews WHERE order_id=o.id LIMIT 1) as user_rating FROM orders o JOIN restaurants r ON r.id=o.restaurant_id WHERE o.user_id=$uid ORDER BY o.created_at DESC")->fetch_all();
require_once 'includes/header.php';
?>
<div class="max-w-4xl mx-auto px-5 py-12">
  <p class="text-gold-400 text-sm font-bold uppercase tracking-widest mb-2">History</p>
  <h1 class="font-display text-4xl font-black text-white mb-8">My Orders <span class="text-purple-400 text-2xl">(<?= count($orders) ?>)</span></h1>

  <?php if(empty($orders)): ?>
  <div class="glass rounded-3xl text-center py-24">
    <div class="text-6xl mb-4">📋</div>
    <h2 class="font-display text-2xl font-bold text-white mb-3">No orders yet</h2>
    <p class="text-purple-300 mb-6">Your order history will appear here.</p>
    <a href="index.php" class="btn-gold rounded-2xl px-8 py-3.5 text-sm inline-block">Browse Food</a>
  </div>
  <?php else: ?>
  <div class="space-y-4">
    <?php
    $sc=['pending'=>'bg-amber-900/60 text-amber-300','confirmed'=>'bg-blue-900/60 text-blue-300','preparing'=>'bg-violet-900/60 text-violet-300','ready'=>'bg-green-900/60 text-green-300','in_transit'=>'bg-orange-900/60 text-orange-300','delivered'=>'bg-emerald-900/60 text-emerald-300','cancelled'=>'bg-gray-900/60 text-gray-400'];
    foreach($orders as $o):
    ?>
    <a href="order.php?id=<?= $o['id'] ?>" class="block">
      <div class="glass rounded-3xl p-5 flex items-center gap-5 card-hover flex-wrap">
        <div class="w-16 h-16 rounded-2xl overflow-hidden flex-shrink-0">
          <img src="<?= e($o['rimage']) ?>" alt="<?= e($o['rname']) ?>" class="img-cover"/>
        </div>
        <div class="flex-1 min-w-[160px]">
          <div class="font-display font-bold text-white">Order #<?= $o['id'] ?></div>
          <div class="text-purple-300 text-sm"><?= e($o['rname']) ?></div>
          <div class="text-purple-400 text-xs mt-1"><?= date('d M Y · H:i', strtotime($o['created_at'])) ?></div>
          
          <?php if($o['status']==='delivered' && !$o['user_rating']): ?>
          <div class="text-gold-400 text-xs font-bold mt-2 underline">⭐ Click to Rate details!</div>
          <?php elseif($o['user_rating']): ?>
          <div class="text-gold-400 text-xs mt-2"><?= str_repeat('★', $o['user_rating']) ?><span class="text-gray-400"><?= str_repeat('★', 5-$o['user_rating']) ?></span></div>
          <?php endif; ?>
        </div>
        <div class="text-right">
          <div class="font-display font-bold text-gold-400 text-xl"><?= money($o['total_amount']) ?></div>
          <span class="<?= $sc[$o['status']]??'' ?> px-3 py-1 rounded-full text-xs font-bold mt-1 inline-block"><?= ucfirst(str_replace('_',' ',$o['status'])) ?></span>
        </div>
        <div class="text-purple-400 text-lg">›</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
