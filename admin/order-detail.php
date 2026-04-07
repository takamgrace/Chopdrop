<?php
session_start(); require_once '../includes/config.php';
// Allow admin, vendor, or rider to view order detail
if (!isAdmin() && !isVendor() && !isRider()) { header('Location: '.SITE_URL.'/index.php'); exit; }
$id=(int)($_GET['id']??0);
$db=db();
$isVendor = isVendor();
$isRider  = isRider();
$vrid = getVendorRid();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    // Vendors: all management done from Dashboard
    if ($isVendor) { flash('error', 'All order management must be done from the Dashboard.'); header('Location: index.php'); exit; }
    
    // Riders: can only update status of their own assigned orders
    if ($isRider) {
        $currentUserId = (int)$_SESSION['user_id'];
        if (isset($_POST['status'])) {
            $status = $db->real_escape_string($_POST['status']);
            if (!in_array($status, ['in_transit','delivered'])) {
                flash('error', 'Riders can only mark orders as In Transit or Delivered.');
                header('Location: order-detail.php?id='.$id); exit;
            }
            $check = $db->query("SELECT id FROM orders WHERE id=$id AND rider_id=$currentUserId")->fetch_assoc();
            if ($check) {
                $db->query("UPDATE orders SET status='$status' WHERE id=$id");
                flash('success', 'Order status updated.');
            } else {
                flash('error', 'You are not assigned to this order.');
            }
        }
        header('Location: order-detail.php?id='.$id); exit;
    }
    
    // Admin: full control
    if (isset($_POST['status'])) {
        $status=$db->real_escape_string($_POST['status']);
        $db->query("UPDATE orders SET status='$status' WHERE id=$id");
        flash('success','Order status updated.');
    } elseif (isset($_POST['assign_rider'])) {
        $riderId = (int)$_POST['rider_id'];
        $val = ($riderId > 0) ? $riderId : "NULL";
        $assignedAt = ($riderId > 0) ? ", rider_assigned_at=NOW()" : ", rider_assigned_at=NULL";
        $db->query("UPDATE orders SET rider_id=$val $assignedAt WHERE id=$id");
        flash('success', 'Rider changed by admin.');
    }
    header('Location: order-detail.php?id='.$id); exit;
}

$order=$db->query("SELECT o.*,u.name uname,u.email uemail,u.phone uphone,r.name rname,r.phone rphone FROM orders o JOIN users u ON u.id=o.user_id JOIN restaurants r ON r.id=o.restaurant_id WHERE o.id=$id")->fetch_assoc();
if (!$order) { header('Location: orders.php'); exit; }

// Rider isolation: only see their own assigned order
if ($isRider) {
    $currentUserId = (int)$_SESSION['user_id'];
    if ($order['rider_id'] != $currentUserId) {
        flash('error', 'You are not assigned to this order.');
        header('Location: my-orders.php'); exit;
    }
}

// Vendor isolation check
if ($isVendor && $order['restaurant_id'] != $vrid) {
    flash('error', 'Unauthorized access.');
    header('Location: orders.php'); exit;
}

$items=$db->query("SELECT * FROM order_items WHERE order_id=$id")->fetch_all();
// Get all active riders for assignment
$riders = $db->query("SELECT id, name FROM users WHERE role='rider' AND is_active=1")->fetch_all();

$statuses=['pending','confirmed','preparing','ready','in_transit','delivered','cancelled'];
$steps=['pending'=>0,'confirmed'=>1,'preparing'=>2,'ready'=>3,'in_transit'=>4,'delivered'=>5];
$stepL=['Order Placed','Confirmed','Preparing','Ready','On the Way','Delivered'];
$stepI=['📋','✅','👨‍🍳','🎁','🚴','🏠'];
$cur=$steps[$order['status']]??0;
$sc=['pending'=>'bg-amber-900/60 text-amber-300','confirmed'=>'bg-blue-900/60 text-blue-300','preparing'=>'bg-violet-900/60 text-violet-300','ready'=>'bg-green-900/60 text-green-300','in_transit'=>'bg-orange-900/60 text-orange-300','delivered'=>'bg-emerald-900/60 text-emerald-300','cancelled'=>'bg-gray-900/60 text-gray-400'];
$pageTitle="Order #$id — " . ($isVendor ? 'Vendor' : 'Admin');
require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)]">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-8">
  <div class="flex items-center gap-4 mb-6 flex-wrap">
    <a href="orders.php" class="text-purple-400 hover:text-white transition-colors text-sm">← Orders</a>
    <h1 class="font-display text-2xl font-black text-white">Order #<?= $id ?></h1>
    <span class="<?= $sc[$order['status']]??'' ?> px-4 py-1.5 rounded-full text-sm font-bold"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span>
    <?php if($order['is_reported']): ?>
    <span class="bg-red-900/60 text-red-300 border border-red-500/40 px-5 py-2.5 rounded-full text-sm font-black uppercase tracking-widest flex items-center gap-2">
      🚩 Dispute Opened
    </span>
    <?php endif; ?>
  </div>

  <?php if($order['is_reported']): ?>
  <div class="bg-red-900/40 border-2 border-red-500/40 text-red-300 rounded-3xl px-8 py-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xl relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-br from-red-600/5 to-transparent pointer-events-none"></div>
    <div class="relative z-10">
      <div class="text-[10px] font-black uppercase tracking-widest text-red-400 mb-1">Customer Dispute Reported</div>
      <h2 class="font-display text-xl font-bold text-white">Non-Delivery Claim Filed</h2>
      <p class="text-red-400/80 text-sm mt-1">Reported on <?= date('d M Y, H:i', strtotime($order['report_at'])) ?></p>
    </div>
    <div class="relative z-10 glass px-5 py-3 rounded-2xl border border-red-500/30 text-xs font-bold text-white uppercase tracking-widest text-center">
      Investigation Required
    </div>
  </div>
  <?php endif; ?>
  <?php if($f=flash('success')): ?><div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-5 py-3 text-sm mb-5"><?= e($f) ?></div><?php endif; ?>

  <!-- Progress -->
  <?php if($order['status']!=='cancelled'): ?>
  <div class="glass rounded-2xl p-6 mb-6 border border-purple-700/30">
    <div class="relative">
      <div class="absolute top-5 left-0 right-0 h-0.5 bg-purple-700/50"></div>
      <div class="absolute top-5 left-0 h-0.5 bg-gradient-to-r from-gold-500 to-gold-400" style="width:<?= $cur>0?min(($cur/5)*100,100):0 ?>%;transition:width .5s"></div>
      <div class="relative flex justify-between">
        <?php for($i=0;$i<=5;$i++): $done=$i<=$cur; ?>
        <div class="flex flex-col items-center gap-2">
          <div class="w-10 h-10 rounded-full <?= $done?'btn-gold':'glass border border-purple-600/40' ?> flex items-center justify-center text-lg z-10"><?= $stepI[$i] ?></div>
          <div class="text-xs font-semibold <?= $done?'text-gold-300':'text-purple-500' ?> text-center hidden sm:block whitespace-nowrap"><?= $stepL[$i] ?></div>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
      <!-- Items -->
      <div class="glass rounded-2xl p-6 border border-purple-700/30">
        <h2 class="font-display font-bold text-white text-xl mb-5">Items Ordered</h2>
        <?php foreach($items as $item): ?>
        <div class="flex justify-between items-center py-3 border-b border-purple-700/20">
          <div><div class="font-semibold text-white"><?= e($item['name']) ?></div><div class="text-purple-400 text-xs"><?= money($item['price']) ?> × <?= $item['quantity'] ?></div></div>
          <div class="font-bold text-white"><?= money($item['price']*$item['quantity']) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="flex justify-between py-3 text-purple-400 text-sm"><span>Delivery fee</span><span><?= money($order['delivery_fee']) ?></span></div>
        <div class="flex justify-between pt-3 border-t border-purple-700/40 font-display font-bold text-xl">
          <span class="text-white">Total</span><span class="gold-text"><?= money($order['total_amount']) ?></span>
        </div>
      </div>

      <!-- Customer + Restaurant -->
      <div class="grid md:grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="glass rounded-2xl p-5 border border-purple-700/30">
          <h3 class="font-bold text-white mb-3">👤 Customer</h3>
          <div class="space-y-1.5 text-sm">
            <?php if($isVendor): ?>
              <div class="font-semibold text-white"><?= e($order['uname']) ?></div>
              <div class="text-purple-300"><?= e($order['uemail']) ?></div>
              <div class="text-purple-300"><?= e($order['uphone']?:'—') ?></div>
            <?php else: ?>
              <div class="font-semibold text-white">Customer #<?= $order['user_id'] ?></div>
              <div class="text-purple-500 italic text-xs">Identity hidden for Admin</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="glass rounded-2xl p-5 border border-purple-700/30">
          <h3 class="font-bold text-white mb-3">🏪 Restaurant</h3>
          <div class="space-y-1.5 text-sm">
            <div class="font-semibold text-white"><?= e($order['rname']) ?></div>
            <div class="text-purple-300"><?= e($order['rphone']) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions sidebar: Vendors = read-only; Admin = full control -->
    <div class="space-y-4">
      <?php if($isVendor): ?>
      <!-- VENDOR READ-ONLY PANEL -->
      <div class="glass rounded-2xl p-6 border border-blue-500/20 bg-blue-900/10">
        <div class="flex items-center gap-3 mb-4">
          <span class="text-2xl">ℹ️</span>
          <div>
            <h3 class="font-bold text-white">Order Management</h3>
            <p class="text-blue-300 text-xs">Dispatch orders from the Dashboard</p>
          </div>
        </div>
        <a href="index.php" class="btn-gold w-full rounded-xl py-3 font-bold text-center block shadow-lg shadow-gold/20 active:scale-95 transition-all">
          ← Go to Dashboard
        </a>
      </div>

      <!-- Rider info (read-only) -->
      <div class="glass rounded-2xl p-6 border border-purple-700/30">
        <h3 class="font-bold text-white mb-4">🏍️ Assigned Rider</h3>
        <?php if($order['rider_id']): ?>
          <?php 
          $assignedRider = array_filter($riders, fn($r) => $r['id'] == $order['rider_id']);
          $rName = !empty($assignedRider) ? reset($assignedRider)['name'] : 'Unknown Rider';
          ?>
          <div class="bg-emerald-900/40 border border-emerald-500/30 rounded-xl p-4 text-emerald-400">
            <span class="text-[10px] uppercase font-black block mb-1">Currently Assigned</span>
            <div class="font-bold text-white"><?= e($rName) ?></div>
          </div>
        <?php else: ?>
          <div class="text-purple-500 text-sm italic">No rider assigned yet. Dispatch from the Dashboard.</div>
        <?php endif; ?>
      </div>
      <?php else: ?>
        <div class="glass rounded-2xl p-6 border border-purple-700/30">
          <h3 class="font-bold text-white mb-4">Personnel</h3>
          <?php if($order['rider_id']): ?>
            <?php 
            $assignedRider = array_filter($riders, fn($r) => $r['id'] == $order['rider_id']);
            $rName = !empty($assignedRider) ? reset($assignedRider)['name'] : 'Unknown Rider';
            ?>
            <div class="text-sm">
              <span class="text-purple-400 uppercase text-[10px] font-black tracking-widest block mb-1">Delivering Rider</span>
              <div class="font-bold text-white"><?= e($rName) ?></div>
            </div>
          <?php else: ?>
            <div class="text-xs text-purple-500 italic">Not yet assigned by vendor</div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="glass rounded-2xl p-6 border border-purple-700/30">
        <h3 class="font-bold text-white mb-4">Order Details</h3>
        <div class="space-y-2.5 text-sm text-purple-300">
          <div>📅 <span class="text-white font-medium"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span></div>
          <div>💳 <span class="text-white font-medium"><?= ucfirst($order['payment_method']) ?></span></div>
          <div>📍 <span class="text-white font-medium"><?= e($order['delivery_address']) ?></span></div>
          <?php if($order['notes']): ?><div>📝 <span class="text-white font-medium"><?= e($order['notes']) ?></span></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
</div>
<?php require_once '../includes/footer.php'; ?>
