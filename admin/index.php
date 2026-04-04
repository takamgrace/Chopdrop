<?php
session_start(); require_once '../includes/config.php'; requireAdminOrVendor();
$pageTitle='Dashboard — ChopDrop Admin';
$db = db();
$isVendor = isVendor();
$rid = getVendorRid();

// ─── VENDOR-ONLY POST HANDLER ─────────────────────────────────────────────────
// Only ONE action allowed for vendors: "Mark as Ready + Assign Rider" simultaneously
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isVendor) {
    $oid     = (int)($_POST['order_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $riderId = (int)($_POST['rider_id'] ?? 0);

    if (!$oid) { flash('error', 'Select a valid order.'); header('Location: index.php'); exit; }

    // Auth: order must belong to this restaurant
    $order = $db->query("SELECT id, status, rider_id, rider_assigned_at FROM orders WHERE id=$oid AND restaurant_id=$rid")->fetch_assoc();
    if (!$order) { flash('error', 'Unauthorized access.'); header('Location: index.php'); exit; }

    if ($action === 'confirm') {
        $db->query("UPDATE orders SET status='confirmed' WHERE id=$oid");
        flash('success', "Order #$oid confirmed!");
    } elseif ($action === 'prepare') {
        $db->query("UPDATE orders SET status='preparing' WHERE id=$oid");
        flash('success', "Order #$oid is now being prepared.");
    } elseif ($action === 'dispatch') {
        if (!$riderId) { flash('error', 'Please select a rider.'); header('Location: index.php'); exit; }
        
        // Cannot re-dispatch already assigned orders (1-min window handled in backend)
        if ($order['rider_id']) {
            $secs = $order['rider_assigned_at'] ? time() - strtotime($order['rider_assigned_at']) : 999;
            if ($secs > 60) {
                flash('error', 'Already dispatched. Admin only reassignment after 1 min.');
                header('Location: index.php'); exit;
            }
        }
        
        // Rider must be ONLINE and ACTIVE (global pool)
        $rCheck = $db->query("SELECT id FROM users WHERE id=$riderId AND role='rider' AND is_active=1 AND is_online=1")->fetch_assoc();
        if (!$rCheck) { flash('error', 'Invalid or offline rider selected.'); header('Location: index.php'); exit; }

        $db->query("UPDATE orders SET status='ready', rider_id=$riderId, rider_assigned_at=NOW() WHERE id=$oid");
        flash('success', "Order #$oid ready and dispatched!");
    } elseif ($action === 'cancel') {
        $db->query("UPDATE orders SET status='cancelled' WHERE id=$oid");
        flash('success', "Order #$oid cancelled.");
    }
    header('Location: index.php'); exit;
}

$riders = $isVendor ? $db->query("SELECT id, name FROM users WHERE role='rider' AND is_active=1 AND is_online=1")->fetch_all(MYSQLI_ASSOC) : [];

// Base query conditions
$whereRest      = $isVendor ? "WHERE o.restaurant_id=$rid" : "";
$whereRestNoWhere = $isVendor ? "AND o.restaurant_id=$rid" : "";

$stats = [
    'orders'     => $db->query("SELECT COUNT(*) c FROM orders o $whereRest")->fetch_assoc()['c'],
    'revenue'    => $db->query("SELECT COALESCE(SUM(total_amount),0) c FROM orders o WHERE status='delivered' $whereRestNoWhere")->fetch_assoc()['c'],
    'pending'    => $db->query("SELECT COUNT(*) c FROM orders o WHERE status='pending' $whereRestNoWhere")->fetch_assoc()['c'],
    'unassigned' => $db->query("SELECT COUNT(*) c FROM orders o WHERE status IN ('pending','confirmed','preparing') AND (rider_id IS NULL OR rider_id=0) $whereRestNoWhere")->fetch_assoc()['c'],
    'rests'      => $isVendor ? 1 : $db->query("SELECT COUNT(*) c FROM restaurants")->fetch_assoc()['c'],
    'vendors'    => $isVendor ? 1 : $db->query("SELECT COUNT(*) c FROM users WHERE role='vendor'")->fetch_assoc()['c'],
    'menu_items' => $db->query("SELECT COUNT(*) c FROM foods " . ($isVendor ? "WHERE restaurant_id=$rid" : ""))->fetch_assoc()['c'],
];

if ($isVendor) {
    // Vendor sees actionable orders: pending/confirmed/preparing (not yet ready/dispatched)
    $actionableOrders = $db->query("SELECT o.*,u.name uname,u.phone uphone FROM orders o 
        JOIN users u ON u.id=o.user_id
        WHERE o.restaurant_id=$rid AND o.status IN ('pending','confirmed','preparing') AND (o.rider_id IS NULL OR o.rider_id=0)
        ORDER BY o.created_at ASC")->fetch_all(MYSQLI_ASSOC);

    // All recent orders (read-only view)
    $recentOrders = $db->query("SELECT o.*,u.name uname, rider.name rider_name FROM orders o 
        JOIN users u ON u.id=o.user_id 
        LEFT JOIN users rider ON rider.id=o.rider_id
        WHERE o.restaurant_id=$rid
        ORDER BY o.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
} else {
    $recentVendors = $db->query("SELECT u.*, r.name as rname, r.id as rid FROM users u 
        LEFT JOIN restaurants r ON r.id=u.restaurant_id 
        WHERE u.role='vendor' ORDER BY u.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
}

$sc = ['pending'=>'bg-amber-900/70 text-amber-300 border-amber-500/30','confirmed'=>'bg-blue-900/70 text-blue-300 border-blue-500/30','preparing'=>'bg-violet-900/70 text-violet-300 border-violet-500/30','ready'=>'bg-green-900/70 text-green-300 border-green-500/30','in_transit'=>'bg-orange-900/70 text-orange-300 border-orange-500/30','delivered'=>'bg-emerald-900/70 text-emerald-300 border-emerald-500/30','cancelled'=>'bg-gray-900/70 text-gray-400 border-gray-600/30'];

require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
  <?php include 'sidebar.php'; ?>
  <main class="flex-1 p-8 overflow-x-auto relative">
    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="font-display text-4xl font-black text-white"><?= $isVendor ? 'Order Control Centre' : 'Dashboard' ?></h1>
        <p class="text-purple-400 mt-1"><?= $isVendor ? 'Mark orders ready and dispatch riders — all from here' : 'Vendor Strategy — ChopDrop Administration' ?></p>
      </div>
      <?php if($isVendor): ?>
      <div class="glass px-5 py-3 rounded-2xl flex items-center gap-3 border border-gold-500/20 shadow-lg shadow-gold/5">
        <div class="w-10 h-10 rounded-full bg-gold-500 flex items-center justify-center text-xl">🏬</div>
        <div>
          <div class="text-[10px] font-bold text-gold-400 uppercase tracking-widest leading-none mb-1">My Restaurant</div>
          <div class="text-sm font-bold text-white"><?= e($_SESSION['name']) ?></div>
        </div>
      </div>
      <?php endif; ?>
    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <?php
      if ($isVendor) {
        $cards=[
          ['📥','New Orders (Inbox)',number_format($stats['unassigned']),'from-amber-600 to-amber-700'],
          ['📦','Total Orders',number_format($stats['orders']),'from-purple-600 to-purple-700'],
          ['💰','Revenue (Delivered)',money($stats['revenue']),'from-gold-600 to-gold-700'],
          ['🍽️','Menu Items',$stats['menu_items'],'from-blue-600 to-blue-700'],
        ];
      } else {
        $cards=[
          ['🤝','Total Vendors',$stats['vendors'],'from-blue-600 to-blue-700'],
          ['🏪','Active Shops',$stats['rests'],'from-gold-600 to-gold-700'],
          ['🍽️','Global Menu Items',$stats['menu_items'],'from-purple-600 to-purple-700'],
          ['✨','Operational Focus','Pure Vendor','from-emerald-600 to-emerald-700'],
        ];
      }
      foreach($cards as [$icon,$label,$val,$grad]): ?>
      <div class="glass rounded-3xl p-6 border border-purple-700/30 shadow-xl card-hover">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br <?= $grad ?> flex items-center justify-center text-2xl mb-4 shadow-lg"><?= $icon ?></div>
        <div class="text-purple-400 text-xs font-bold uppercase tracking-widest mb-2 opacity-70"><?= $label ?></div>
        <div class="font-display text-3xl font-black text-white"><?= $val ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if($isVendor): ?>

    <!-- ===================== ACTIONABLE ORDERS — DISPATCH ZONE ===================== -->
    <?php if(!empty($actionableOrders)): ?>
    <div class="glass rounded-3xl overflow-hidden border-2 border-amber-500/30 shadow-2xl mb-8">
      <div class="px-8 py-5 bg-amber-900/20 border-b border-amber-500/20 flex items-center gap-4">
        <span class="text-2xl animate-pulse">⚡</span>
        <div>
          <h2 class="font-display font-black text-white text-xl">Orders Awaiting Dispatch</h2>
          <p class="text-amber-400 text-xs font-semibold mt-0.5">Select a rider and click Dispatch to mark as Ready and send to delivery</p>
        </div>
        <span class="ml-auto bg-amber-500 text-amber-950 text-xs font-black px-3 py-1 rounded-full"><?= count($actionableOrders) ?> Pending</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-amber-700/20 bg-amber-950/20">
              <?php foreach(['Order','Customer','Address','Amount','Payment','Action'] as $h): ?>
              <th class="px-6 py-4 text-left text-[10px] font-black text-amber-400 uppercase tracking-widest whitespace-nowrap"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach($actionableOrders as $o): ?>
          <tr class="border-b border-amber-700/10 hover:bg-amber-900/10 transition-all duration-300">
            <td class="px-6 py-5">
              <div class="text-gold-400 font-black">#<?= $o['id'] ?></div>
              <div class="text-[10px] text-amber-400"><?= date('d M, H:i', strtotime($o['created_at'])) ?></div>
            </td>
            <td class="px-6 py-5">
              <div class="text-white font-bold"><?= e($o['uname']) ?></div>
              <?php if($o['uphone']): ?><div class="text-[10px] text-emerald-400 font-semibold">📞 <?= e($o['uphone']) ?></div><?php endif; ?>
            </td>
            <td class="px-6 py-5 text-purple-300 text-xs max-w-[180px]"><?= e($o['delivery_address'] ?? '—') ?></td>
            <td class="px-6 py-5 font-black text-white"><?= money($o['total_amount']) ?></td>
            <td class="px-6 py-5 text-purple-300 text-xs"><?= ucfirst($o['payment_method']) ?></td>
            <td class="px-6 py-5">
              <div class="flex flex-col gap-2">
                <?php if($o['status'] === 'pending'): ?>
                <div class="flex gap-2">
                  <form method="POST">
                    <input type="hidden" name="action" value="confirm"/>
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-400 text-blue-950 font-black text-[10px] px-4 py-2 rounded-xl transition-all shadow-lg active:scale-95 whitespace-nowrap">Confirm Order</button>
                  </form>
                  <form method="POST" onsubmit="return confirm('Cancel this order?')">
                    <input type="hidden" name="action" value="cancel"/>
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                    <button type="submit" class="bg-red-500/20 hover:bg-red-500 text-red-500 hover:text-white font-black text-[10px] px-4 py-2 rounded-xl transition-all border border-red-500/20 active:scale-95">Cancel</button>
                  </form>
                </div>
                <?php elseif($o['status'] === 'confirmed'): ?>
                <div class="flex gap-2">
                  <form method="POST">
                    <input type="hidden" name="action" value="prepare"/>
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                    <button type="submit" class="bg-violet-500 hover:bg-violet-400 text-violet-950 font-black text-[10px] px-4 py-2 rounded-xl transition-all shadow-lg active:scale-95 whitespace-nowrap">Start Preparing</button>
                  </form>
                  <form method="POST" onsubmit="return confirm('Cancel this order?')">
                    <input type="hidden" name="action" value="cancel"/>
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                    <button type="submit" class="bg-red-500/20 hover:bg-red-500 text-red-500 hover:text-white font-black text-[10px] px-4 py-2 rounded-xl transition-all border border-red-500/20 active:scale-95">Cancel</button>
                  </form>
                </div>
                <?php elseif($o['status'] === 'preparing'): ?>
                <?php if(!empty($riders)): ?>
                <form method="POST" class="flex gap-2 items-center" onsubmit="return this.rider_id.value > 0 || (alert('Please select a rider!'), false)">
                  <input type="hidden" name="action" value="dispatch"/>
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                  <select name="rider_id" class="bg-emerald-950/50 border border-emerald-500/40 rounded-xl px-3 py-2 text-white text-[10px] font-bold w-32 outline-none focus:border-gold-500 transition-all">
                    <option value="0">— Pick Rider —</option>
                    <?php foreach($riders as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-emerald-950 font-black text-[10px] px-4 py-2 rounded-xl transition-all shadow-lg active:scale-95 whitespace-nowrap">Dispatch</button>
                </form>
                <?php else: ?>
                <div class="text-[9px] text-red-400 font-bold bg-red-900/10 px-3 py-1.5 rounded-lg border border-red-500/10 mb-1">⚠️ No online riders.</div>
                <?php endif; ?>
                <form method="POST" onsubmit="return confirm('Cancel this order?')">
                  <input type="hidden" name="action" value="cancel"/>
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                  <button type="submit" class="text-red-500 hover:text-red-400 font-bold text-[10px] uppercase tracking-widest text-center w-full py-1 transition-all">Cancel Order</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php else: ?>
    <div class="glass rounded-2xl px-6 py-4 text-emerald-400 text-sm font-semibold mb-8 flex items-center gap-3 border border-emerald-500/20">
      <span class="text-xl">✅</span> All orders are dispatched. No pending actions required.
    </div>
    <?php endif; ?>

    <!-- ===================== RECENT ORDERS — READ ONLY VIEW ===================== -->
    <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 shadow-2xl">
      <div class="flex items-center justify-between px-8 py-6 border-b border-purple-700/30 bg-purple-950/20">
        <h2 class="font-display font-bold text-white text-xl">Recent Orders — Overview</h2>
        <a href="orders.php" class="text-gold-400 text-sm font-bold uppercase tracking-widest hover:text-white transition-colors flex items-center gap-2">
          View All <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-purple-700/30 bg-purple-900/10">
              <?php foreach(['Customer','Amount','Status','Rider','Date'] as $h): ?>
              <th class="px-8 py-4 text-left text-xs font-bold text-purple-400 uppercase tracking-widest"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach($recentOrders as $o): ?>
          <tr class="border-b border-purple-700/20 hover:bg-purple-800/10 transition-all duration-300">
            <td class="px-8 py-5">
              <div class="text-white font-bold"><?= e($o['uname']) ?></div>
              <div class="text-[10px] text-purple-500">#<?= $o['id'] ?></div>
            </td>
            <td class="px-8 py-5 font-black text-white"><?= money($o['total_amount']) ?></td>
            <td class="px-8 py-5">
              <span class="<?= $sc[$o['status']]??'' ?> border px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                <?= str_replace('_',' ',$o['status']) ?>
              </span>
            </td>
            <td class="px-8 py-5 text-xs">
              <?php if($o['rider_name']): ?>
                <div class="font-bold text-white"><?= e($o['rider_name']) ?></div>
                <div class="text-[9px] text-emerald-500 font-black uppercase">Assigned</div>
              <?php else: ?>
                <div class="text-purple-600 font-black uppercase text-[9px]">Unassigned</div>
              <?php endif; ?>
            </td>
            <td class="px-8 py-5 text-purple-400 text-xs font-semibold"><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($recentOrders)): ?>
          <tr><td colspan="5" class="px-8 py-16 text-center text-purple-500 font-bold">No orders yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php else: /* ADMIN VIEW */ ?>
    <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 shadow-2xl">
      <div class="flex items-center justify-between px-8 py-6 border-b border-purple-700/30 bg-purple-950/20">
        <h2 class="font-display font-bold text-white text-xl">Platform Vendors</h2>
        <a href="users.php" class="text-gold-400 text-sm font-bold uppercase tracking-widest hover:text-white transition-colors flex items-center gap-2">
          View All <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-purple-700/30 bg-purple-900/10">
              <?php foreach(['Account','Email','Managed Shop','Joined','Action'] as $h): ?>
              <th class="px-8 py-4 text-left text-xs font-bold text-purple-400 uppercase tracking-widest"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach($recentVendors as $v): ?>
          <tr class="border-b border-purple-700/20 hover:bg-purple-800/10 transition-all duration-300">
            <td class="px-8 py-5">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center font-black text-blue-950 text-xs flex-shrink-0">
                  <?= strtoupper(mb_substr($v['name'],0,1)) ?>
                </div>
                <div class="text-white font-bold whitespace-nowrap"><?= e($v['name']) ?></div>
              </div>
            </td>
            <td class="px-8 py-5 text-purple-300 text-xs font-semibold"><?= e($v['email']) ?></td>
            <td class="px-8 py-5">
              <?php if($v['rname']): ?>
                <a href="restaurants.php?edit=<?= $v['rid'] ?>" class="text-gold-400 font-black hover:underline text-xs"><?= e($v['rname']) ?></a>
              <?php else: ?>
                <span class="text-purple-600 text-[10px] font-black uppercase tracking-widest">No Shop Linked</span>
              <?php endif; ?>
            </td>
            <td class="px-8 py-5 text-xs font-bold text-purple-500"><?= date('d M, Y', strtotime($v['created_at'])) ?></td>
            <td class="px-8 py-5">
              <a href="users.php?q=<?= urlencode($v['email']) ?>" class="glass px-4 py-2 rounded-xl text-xs font-black text-purple-200 hover:text-white hover:bg-gold-500/20 transition-all border border-transparent hover:border-gold-500/30">
                Oversight
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php require_once '../includes/footer.php'; ?>
