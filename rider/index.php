<?php
session_start();
require_once '../includes/config.php';
requireRider();
$db = db();
$riderId = $_SESSION['user_id'];
$pageTitle = 'Rider Dashboard — ChopDrop';

// Get rider's status and restaurant_id
$riderData = $db->query("SELECT name, restaurant_id, is_online FROM users WHERE id=$riderId")->fetch_assoc();
$rid = (int)($riderData['restaurant_id'] ?? 0);
$isOnline = (bool)($riderData['is_online'] ?? false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $oid = (int)($_POST['order_id'] ?? 0);

    if ($action === 'start') {
        // Start delivery for an ASSIGNED order that is ready
        $db->query("UPDATE orders SET status='in_transit' WHERE id=$oid AND status='ready' AND rider_id=$riderId");
        flash('success', "Order #$oid picked up! You are now In Transit.");
    } elseif ($action === 'deliver') {
        // Mark as 'delivered'
        $db->query("UPDATE orders SET status='delivered' WHERE id=$oid AND rider_id=$riderId");
        flash('success', "Order #$oid delivered! Great job!");
    } elseif ($action === 'toggle_status') {
        // Toggle online/offline status
        $newStatus = $isOnline ? 0 : 1;
        $db->query("UPDATE users SET is_online=$newStatus WHERE id=$riderId");
        flash('success', "Status updated! You are now " . ($newStatus ? 'Online' : 'Offline') . ".");
    }
    header('Location: index.php');
    exit;
}

// Stats filtered by assignment
$stats = $db->query("SELECT 
    (SELECT COUNT(*) FROM orders WHERE status='ready' AND rider_id=$riderId) as available,
    (SELECT COUNT(*) FROM orders WHERE rider_id=$riderId AND status='in_transit') as active,
    (SELECT COUNT(*) FROM orders WHERE rider_id=$riderId AND status='delivered') as completed
")->fetch_assoc();

// Assigned orders waiting for pickup (Ready)
$available = $db->query("SELECT o.*, r.name rname, r.address raddress, u.name uname FROM orders o 
    JOIN restaurants r ON r.id=o.restaurant_id 
    JOIN users u ON u.id=o.user_id
    WHERE o.status='ready' AND o.rider_id=$riderId
    ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// My active orders (In Transit)
$active = $db->query("SELECT o.*, r.name rname, r.address raddress, u.name uname FROM orders o 
    JOIN restaurants r ON r.id=o.restaurant_id 
    JOIN users u ON u.id=o.user_id
    WHERE o.rider_id=$riderId AND o.status='in_transit' AND o.restaurant_id=$rid
    ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Recent delivery history
$history = $db->query("SELECT o.*, r.name rname, u.name uname FROM orders o 
    JOIN restaurants r ON r.id=o.restaurant_id 
    JOIN users u ON u.id=o.user_id
    WHERE o.rider_id=$riderId AND o.status='delivered' AND o.restaurant_id=$rid
    ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once '../includes/header.php';
?>

<div class="min-h-screen bg-purple-950 text-white relative overflow-hidden">
  <!-- Decorative background elements -->
  <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gold-400/5 rounded-full blur-[120px] -mr-48 -mt-48"></div>
  <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-purple-600/10 rounded-full blur-[100px] -ml-24 -mb-24"></div>

  <div class="max-w-7xl mx-auto px-5 md:px-10 py-12 relative z-10">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
      <div>
        <h1 class="font-display text-4xl md:text-5xl font-black text-white leading-tight">Rider <span class="text-gold-400">Hub</span></h1>
        <p class="text-purple-300 mt-2 font-medium">Hello, <span class="text-white font-bold"><?= e($_SESSION['name']) ?></span>. Ready for your next delivery?</p>
      </div>
      <div class="flex flex-wrap items-center gap-4">
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
            <div>
              <div class="text-[10px] font-black text-purple-400 uppercase tracking-widest leading-none mb-1">My Status</div>
              <div class="text-sm font-black <?= $isOnline ? 'text-emerald-400' : 'text-red-400' ?>"><?= $isOnline ? 'Online' : 'Offline' ?></div>
            </div>
          </button>
        </form>

        <div class="glass px-6 py-4 rounded-3xl border border-white/5 shadow-2xl flex items-center gap-4">
          <div class="w-12 h-12 bg-gold-400/10 rounded-2xl flex items-center justify-center text-2xl">💰</div>
          <div>
            <div class="text-[10px] font-black text-purple-400 uppercase tracking-widest leading-none mb-1">Total Earnings</div>
            <div class="text-xl font-black text-gold-400"><?= money($stats['completed'] * 500) ?></div>
          </div>
        </div>
        <a href="../logout.php" class="bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white px-6 py-4 rounded-3xl transition-all duration-300 flex items-center gap-2 font-black text-[10px] uppercase tracking-widest border border-red-500/20">
          <span>Logout</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </a>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-12">
      <div class="glass rounded-[32px] p-8 border border-white/5 shadow-2xl hover:border-gold-400/20 transition-all group">
        <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">🎁</div>
        <div class="text-4xl font-black text-white mb-1"><?= $stats['available'] ?></div>
        <div class="text-xs font-black text-purple-400 uppercase tracking-widest">Ready for Pickup</div>
      </div>
      <div class="glass rounded-[32px] p-8 border border-white/5 shadow-2xl hover:border-blue-400/20 transition-all group">
        <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">🏍️</div>
        <div class="text-4xl font-black text-white mb-1"><?= $stats['active'] ?></div>
        <div class="text-xs font-black text-purple-400 uppercase tracking-widest">In Transit</div>
      </div>
      <div class="glass rounded-[32px] p-8 border border-white/5 shadow-2xl hover:border-emerald-400/20 transition-all group">
        <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">✅</div>
        <div class="text-4xl font-black text-white mb-1"><?= $stats['completed'] ?></div>
        <div class="text-xs font-black text-purple-400 uppercase tracking-widest">Completed</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
      <!-- ACTIVE DELIVERIES -->
      <section>
        <div class="flex items-center gap-3 mb-8">
          <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center text-xl">🚚</div>
          <h2 class="font-display text-2xl font-black">Active Shipments</h2>
        </div>
        
        <div class="space-y-6">
          <?php foreach ($active as $o): ?>
          <div class="glass rounded-[32px] p-8 border border-blue-500/20 shadow-2xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
            <div class="flex justify-between items-start mb-6">
              <div>
                <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1 block">In Transit</span>
                <h3 class="text-2xl font-black text-white">#<?= $o['id'] ?></h3>
              </div>
              <div class="text-xl font-black text-gold-400"><?= money($o['total_amount']) ?></div>
            </div>
            
            <div class="space-y-4 mb-8">
              <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-purple-900/50 flex items-center justify-center text-xs">🏪</div>
                <div>
                  <div class="text-[10px] font-black text-purple-500 uppercase tracking-widest">Pickup From</div>
                  <div class="font-bold text-white"><?= e($o['rname']) ?></div>
                  <div class="text-xs text-purple-300"><?= e($o['raddress']) ?></div>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-purple-900/50 flex items-center justify-center text-xs">📍</div>
                <div>
                  <div class="text-[10px] font-black text-purple-500 uppercase tracking-widest">Deliver To</div>
                  <div class="font-bold text-white"><?= e($o['uname']) ?></div>
                  <div class="text-xs text-purple-300"><?= e($o['delivery_address']) ?></div>
                </div>
              </div>
            </div>

            <form method="POST">
              <input type="hidden" name="action" value="deliver"/>
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
              <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white rounded-2xl py-5 font-black uppercase tracking-widest text-xs shadow-xl shadow-emerald-500/20 active:scale-95 transition-all">Mark as Delivered</button>
            </form>
          </div>
          <?php endforeach; ?>
          <?php if (empty($active)): ?>
          <div class="glass rounded-[32px] p-12 text-center border border-white/5">
            <div class="text-5xl opacity-20 mb-4">🧊</div>
            <p class="text-purple-400 font-bold uppercase tracking-widest text-xs">No active deliveries</p>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- ASSIGNED ORDERS -->
      <section>
        <div class="flex items-center gap-3 mb-8">
          <div class="w-10 h-10 bg-gold-400/20 rounded-xl flex items-center justify-center text-xl">⚡</div>
          <h2 class="font-display text-2xl font-black">Assigned to Me</h2>
        </div>

        <div class="space-y-6">
          <?php foreach ($available as $o): ?>
          <div class="glass rounded-[32px] p-6 border border-white/5 hover:border-gold-400/20 transition-all group">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="font-black text-white text-lg">#<?= $o['id'] ?></h3>
                <p class="text-[10px] font-black text-purple-400 uppercase tracking-widest"><?= e($o['rname']) ?></p>
              </div>
              <div class="font-black text-gold-400"><?= money($o['total_amount']) ?></div>
            </div>
            
            <div class="text-xs text-purple-300 mb-6 bg-purple-900/40 p-3 rounded-xl">
              <span class="font-bold text-white">To:</span> <?= e($o['delivery_address']) ?>
            </div>

            <form method="POST">
              <input type="hidden" name="action" value="start"/>
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
              <button type="submit" class="w-full glass py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-purple-200 hover:bg-gold-400 hover:text-purple-950 border border-white/5 hover:border-gold-400 transition-all active:scale-95">Start Delivery</button>
            </form>
          </div>
          <?php endforeach; ?>
          <?php if (empty($available)): ?>
          <div class="glass rounded-[32px] p-12 text-center border border-white/5">
            <div class="text-5xl opacity-20 mb-4">😴</div>
            <p class="text-purple-400 font-bold uppercase tracking-widest text-xs">No new assignments at the moment.</p>
          </div>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <!-- History -->
    <section class="mt-20">
      <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 bg-purple-600/20 rounded-xl flex items-center justify-center text-xl">📜</div>
        <h2 class="font-display text-2xl font-black">Recent Deliveries</h2>
      </div>
      <div class="glass rounded-[40px] border border-white/5 overflow-hidden">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b border-white/5 bg-white/5">
              <th class="p-6 text-[10px] font-black text-purple-400 uppercase tracking-widest">Order</th>
              <th class="p-6 text-[10px] font-black text-purple-400 uppercase tracking-widest">Restaurant</th>
              <th class="p-6 text-[10px] font-black text-purple-400 uppercase tracking-widest">Customer</th>
              <th class="p-6 text-[10px] font-black text-purple-400 uppercase tracking-widest">Payout</th>
              <th class="p-6 text-[10px] font-black text-purple-400 uppercase tracking-widest text-right">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $o): ?>
            <tr class="border-b border-white/5 hover:bg-white/5 transition-all">
              <td class="p-6 font-black text-gold-400">#<?= $o['id'] ?></td>
              <td class="p-6 text-white font-bold text-xs"><?= e($o['rname']) ?></td>
              <td class="p-6 text-purple-300 text-xs"><?= e($o['uname']) ?></td>
              <td class="p-6 font-black text-emerald-400"><?= money(500) ?></td>
              <td class="p-6 text-right text-purple-500 text-xs font-bold"><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
            <tr><td colspan="5" class="p-12 text-center text-purple-500 font-bold italic">No history yet. Start delivering!</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
