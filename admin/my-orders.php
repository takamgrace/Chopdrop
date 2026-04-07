<?php
session_start(); require_once '../includes/config.php'; requireRider();
$db = db();
$riderId = (int)$_SESSION['user_id'];
$pageTitle = 'My Deliveries — ChopDrop';

// Get rider's current online status
$riderData = $db->query("SELECT is_online FROM users WHERE id=$riderId")->fetch_assoc();
$isOnline = (bool)($riderData['is_online'] ?? false);

// Handle status update by rider
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (isset($_POST['order_id'], $_POST['status'])) {
        $oid = (int)$_POST['order_id'];
        $status = $db->real_escape_string($_POST['status']);
        // Rider can only update their own assigned orders
        $check = $db->query("SELECT id FROM orders WHERE id=$oid AND rider_id=$riderId")->fetch_assoc();
        if ($check) {
            $db->query("UPDATE orders SET status='$status' WHERE id=$oid");
            flash('success', "Order #$oid marked as " . ucfirst(str_replace('_', ' ', $status)) . ".");
        } else {
            flash('error', 'Unauthorized.');
        }
    } elseif ($action === 'toggle_status') {
        $newStat = $isOnline ? 0 : 1;
        $db->query("UPDATE users SET is_online=$newStat WHERE id=$riderId");
        flash('success', "You are now " . ($newStat ? 'Online' : 'Offline') . ".");
    }
    header('Location: my-orders.php'); exit;
}

$orders = $db->query("
    SELECT o.*, u.name uname, u.phone uphone, u.email uemail,
           r.name rname, r.address raddress
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN restaurants r ON r.id = o.restaurant_id
    WHERE o.rider_id = $riderId
    ORDER BY FIELD(o.status,'in_transit','ready','confirmed','preparing','delivered','cancelled'), o.created_at DESC
")->fetch_all();

$sc = [
    'pending'    => 'bg-amber-900/70 text-amber-300 border-amber-500/30',
    'confirmed'  => 'bg-blue-900/70 text-blue-300 border-blue-500/30',
    'preparing'  => 'bg-violet-900/70 text-violet-300 border-violet-500/30',
    'ready'      => 'bg-green-900/70 text-green-300 border-green-500/30',
    'in_transit' => 'bg-orange-900/70 text-orange-300 border-orange-500/30',
    'delivered'  => 'bg-emerald-900/70 text-emerald-300 border-emerald-500/30',
    'cancelled'  => 'bg-gray-900/70 text-gray-400 border-gray-600/30',
];

// ─── Monthly Earnings ─────────────────────────────────────────────────────────
$currentMonth  = date('Y-m');
$thisMonthEarn = $db->query("SELECT COALESCE(SUM(delivery_fee),0) total, COUNT(*) cnt FROM orders WHERE rider_id=$riderId AND status='delivered' AND DATE_FORMAT(created_at,'%Y-%m')='$currentMonth'")->fetch_assoc();
$totalEarn     = $db->query("SELECT COALESCE(SUM(delivery_fee),0) total, COUNT(*) cnt FROM orders WHERE rider_id=$riderId AND status='delivered'")->fetch_assoc();
$activeOrders  = $db->query("SELECT COUNT(*) cnt FROM orders WHERE rider_id=$riderId AND status IN ('ready','in_transit')")->fetch_assoc()['cnt'];

// Last 3 months earnings breakdown
$monthlyBreakdown = $db->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') as month, 
           COALESCE(SUM(delivery_fee),0) as earnings, 
           COUNT(*) as deliveries
    FROM orders 
    WHERE rider_id=$riderId AND status='delivered'
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
    ORDER BY month DESC
    LIMIT 3
")->fetch_all();

require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
<aside class="w-full md:w-56 bg-purple-900/80 border-b md:border-b-0 md:border-r border-purple-700/30 flex-shrink-0 flex flex-col md:min-h-[calc(100vh-68px)]">
  <div class="p-4 md:p-5 border-b border-purple-700/30 flex justify-between items-center bg-purple-950/50">
    <span class="text-white font-bold text-sm">🚴 Rider Panel</span>
    <a href="../logout.php" class="text-purple-400 text-sm font-semibold hover:text-white transition-colors block md:hidden">Logout</a>
  </div>
  <nav class="flex-1 py-3 flex overflow-x-auto md:flex-col gap-2 md:gap-0 px-3 md:px-0">
    <a href="my-orders.php" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-white bg-purple-700/50 border-r-2 border-gold-400">
      📦 My Deliveries
    </a>
  </nav>
  <div class="p-5 border-t border-purple-700/30 hidden md:block">
    <p class="text-purple-400 text-xs font-semibold mb-2"><?= e($_SESSION['name']) ?></p>
    <a href="../logout.php" class="text-purple-400 text-sm font-semibold hover:text-white transition-colors">← Logout</a>
  </div>
</aside>
<main class="flex-1 p-8 overflow-x-auto relative">
  <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1 class="font-display text-4xl font-black text-white">My Deliveries</h1>
      <p class="text-purple-400 mt-1"><?= count($orders) ?> order<?= count($orders)!==1?'s':'' ?> assigned to you</p>
    </div>
    
    <!-- Status Toggle -->
    <form method="POST" class="flex-shrink-0">
      <input type="hidden" name="action" value="toggle_status"/>
      <button type="submit" class="glass px-6 py-4 rounded-3xl border <?= $isOnline ? 'border-emerald-500/30' : 'border-red-500/20' ?> shadow-2xl flex items-center gap-3 transition-all hover:scale-[1.02] active:scale-[0.98]">
        <div class="relative">
          <div class="w-3 h-3 rounded-full <?= $isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' ?>"></div>
          <?php if($isOnline): ?>
          <div class="absolute inset-0 w-3 h-3 rounded-full bg-emerald-500 animate-ping opacity-20"></div>
          <?php endif; ?>
        </div>
        <div class="text-left">
          <div class="text-[10px] font-black text-purple-400 uppercase tracking-widest leading-none mb-1">My Status</div>
          <div class="text-sm font-black <?= $isOnline ? 'text-emerald-400' : 'text-red-400' ?>"><?= $isOnline ? 'Online' : 'Offline' ?></div>
        </div>
      </button>
    </form>
  </div>

  <!-- ═══════════════ EARNINGS DASHBOARD ═══════════════ -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">

    <div class="glass rounded-3xl p-6 border border-purple-700/30 shadow-xl">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 flex items-center justify-center text-2xl mb-4 shadow-lg">📊</div>
      <div class="text-purple-400 text-xs font-bold uppercase tracking-widest mb-1 opacity-70">All-Time Earnings</div>
      <div class="font-display text-3xl font-black text-white"><?= money($totalEarn['total']) ?></div>
      <div class="text-purple-300 text-xs font-bold mt-1"><?= $totalEarn['cnt'] ?> total deliveries</div>
    </div>
    <div class="glass rounded-3xl p-6 border border-purple-700/30 shadow-xl">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-600 to-orange-700 flex items-center justify-center text-2xl mb-4 shadow-lg">🚴</div>
      <div class="text-purple-400 text-xs font-bold uppercase tracking-widest mb-1 opacity-70">Active Now</div>
      <div class="font-display text-3xl font-black text-white"><?= $activeOrders ?></div>
      <div class="text-orange-400 text-xs font-bold mt-1">In progress</div>
    </div>
  </div>

  <!-- Monthly Breakdown -->
  <?php if(!empty($monthlyBreakdown)): ?>
  <div class="glass rounded-2xl p-6 border border-purple-700/30 mb-8">
    <h3 class="font-bold text-white mb-4">📅 Monthly Earnings History</h3>
    <div class="space-y-3">
      <?php foreach($monthlyBreakdown as $mb): 
        $monthLabel = date('F Y', strtotime($mb['month'].'-01'));
        $isCurrentMonth = ($mb['month'] === $currentMonth);
      ?>
      <div class="flex items-center justify-between bg-purple-900/30 rounded-xl px-5 py-3 border border-purple-700/20">
        <div>
          <div class="text-white font-bold text-sm"><?= $monthLabel ?></div>
          <div class="text-purple-400 text-[10px] font-bold uppercase tracking-widest"><?= $mb['deliveries'] ?> deliveries</div>
        </div>
        <div class="text-right">
          <div class="font-black text-lg <?= $isCurrentMonth ? 'text-gold-400' : 'text-white' ?>"><?= money($mb['earnings']) ?></div>
          <?php if($isCurrentMonth): ?><div class="text-[9px] text-gold-500 font-black uppercase tracking-widest">Current</div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if($f=flash('success')): ?>
  <div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-6 py-4 text-sm mb-6 flex items-center gap-3">
    <span>✅</span> <?= e($f) ?>
  </div>
  <?php endif; ?>
  <?php if($f=flash('error')): ?>
  <div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-2xl px-6 py-4 text-sm mb-6 flex items-center gap-3">
    <span>⚠️</span> <?= e($f) ?>
  </div>
  <?php endif; ?>

  <?php if(empty($orders)): ?>
  <div class="glass rounded-3xl p-16 text-center border border-purple-700/30">
    <div class="text-7xl mb-4 opacity-20">🚴</div>
    <p class="text-purple-400 font-bold text-lg">No deliveries assigned to you yet.</p>
    <p class="text-purple-600 text-sm mt-1">Check back when a restaurant dispatches an order your way!</p>
  </div>
  <?php else: ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
  <?php foreach($orders as $o): ?>
  <div class="glass rounded-3xl border border-purple-700/30 shadow-xl overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="px-6 py-4 bg-purple-950/40 border-b border-purple-700/20 flex items-center justify-between">
      <div>
        <a href="order-detail.php?id=<?= $o['id'] ?>" class="text-gold-400 font-black text-lg hover:underline">
          Order #<?= $o['id'] ?>
        </a>
        <div class="text-[10px] text-purple-400 font-semibold uppercase tracking-widest mt-0.5"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></div>
      </div>
      <span class="<?= $sc[$o['status']]??'' ?> border px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest whitespace-nowrap">
        <?= str_replace('_', ' ', $o['status']) ?>
      </span>
    </div>

    <!-- Customer Contact — prominently shown -->
    <div class="px-6 py-5 bg-emerald-950/30 border-b border-emerald-700/20">
      <div class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2">📞 Customer Contact</div>
      <div class="text-white font-bold text-base mb-1"><?= e($o['uname']) ?></div>
      <?php if($o['uphone']): ?>
      <a href="tel:<?= e($o['uphone']) ?>" class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-2 rounded-xl text-sm font-bold hover:bg-emerald-500 hover:text-white transition-all active:scale-95">
        📞 <?= e($o['uphone']) ?>
      </a>
      <?php else: ?>
      <span class="text-purple-500 text-xs italic">No phone on file</span>
      <?php endif; ?>
    </div>

    <!-- Delivery Info -->
    <div class="px-6 py-4 border-b border-purple-700/20 flex-1">
      <div class="text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2">📍 Delivery Address</div>
      <div class="text-white text-sm font-semibold"><?= e($o['delivery_address'] ?? 'Not specified') ?></div>
      <div class="text-purple-400 text-xs mt-2">🏪 From: <span class="text-white font-semibold"><?= e($o['rname']) ?></span></div>
      <div class="text-purple-400 text-xs mt-0.5">💳 Payment: <span class="text-white font-semibold"><?= ucfirst($o['payment_method']) ?></span></div>
      <div class="text-gold-400 font-black text-base mt-2"><?= money($o['total_amount']) ?></div>
    </div>

    <!-- Status Actions for rider -->
    <?php if(!in_array($o['status'], ['delivered','cancelled'])): ?>
    <div class="px-6 py-4 bg-purple-950/20">
      <div class="text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2">Update Status</div>
      <form method="POST" class="flex gap-2">
        <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
        <select name="status" class="flex-1 bg-purple-900/60 border border-purple-600/30 rounded-xl px-3 py-2.5 text-white text-sm font-bold outline-none focus:border-gold-500 transition-all">
          <?php foreach(['in_transit','delivered'] as $s): ?>
          <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-gold-500/10 text-gold-400 rounded-xl hover:bg-gold-500 hover:text-purple-950 transition-all shadow-lg active:scale-95">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
</div>
<?php require_once '../includes/footer.php'; ?>
