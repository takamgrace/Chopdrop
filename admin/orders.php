<?php
session_start(); require_once '../includes/config.php'; requireAdminOrVendor();
$pageTitle='Orders — ChopDrop Admin';
$db=db();
$isVendor = isVendor();
$rid = getVendorRid();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    // All vendor order management is done from the Dashboard (index.php)
    if ($isVendor) { flash('error', 'Manage all orders from the Dashboard.'); header('Location: index.php'); exit; }
    // Admin-only POST actions
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $oid=(int)$_POST['order_id'];
        $status=db()->real_escape_string($_POST['status']);
        $db->query("UPDATE orders SET status='$status' WHERE id=$oid");
        flash('success',"Order #$oid updated to ".ucfirst(str_replace('_',' ',$status)).".");
    }
    $qs=isset($_GET['status'])?'?status='.$_GET['status']:'';
    header('Location: orders.php'.$qs); exit;
}

$filter=$_GET['status']??'';
$disputed = isset($_GET['disputed']);
$whereStatus = $filter ? "AND o.status='".db()->real_escape_string($filter)."'" : "";
$whereDispute = $disputed ? "AND o.is_reported=1" : "";
$whereRest = $isVendor ? "AND o.restaurant_id=$rid" : "";

$orders=db()->query("SELECT o.*,u.name uname,u.phone uphone,r.name rname, rider.name rider_name FROM orders o 
    JOIN users u ON u.id=o.user_id 
    JOIN restaurants r ON r.id=o.restaurant_id 
    LEFT JOIN users rider ON rider.id=o.rider_id
    WHERE 1=1 $whereStatus $whereDispute $whereRest
    ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Vendor read-only query — no riders needed
$statuses=['pending','confirmed','preparing','ready','in_transit','delivered','cancelled'];
$sc=['pending'=>'bg-amber-900/70 text-amber-300 border-amber-500/30','confirmed'=>'bg-blue-900/70 text-blue-300 border-blue-500/30','preparing'=>'bg-violet-900/70 text-violet-300 border-violet-500/30','ready'=>'bg-green-900/70 text-green-300 border-green-500/30','in_transit'=>'bg-orange-900/70 text-orange-300 border-orange-500/30','delivered'=>'bg-emerald-900/70 text-emerald-300 border-emerald-500/30','cancelled'=>'bg-gray-900/70 text-gray-400 border-gray-600/30'];

require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-8 overflow-x-auto relative">
  <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1 class="font-display text-4xl font-black text-white"><?= $isVendor ? 'Customer Orders' : 'All Platform Orders' ?></h1>
      <p class="text-purple-400 mt-1"><?= count($orders) ?> record<?= count($orders)!==1?'s':'' ?> found in this category</p>
    </div>
    <?php if($isVendor): ?>
    <div class="flex items-center gap-3 glass px-5 py-3 rounded-2xl border border-gold-500/10 shadow-lg">
      <span class="text-xl">🏪</span>
      <div>
        <div class="text-[10px] font-black text-gold-400 uppercase tracking-widest leading-none mb-1">Store</div>
        <div class="text-sm font-bold text-white"><?= e($_SESSION['name']) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>

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

  <?php if($isVendor): ?>
  <div class="bg-blue-900/30 border border-blue-500/30 rounded-2xl px-6 py-4 text-blue-300 text-sm font-semibold mb-6 flex items-center gap-3">
    <span class="text-xl">ℹ️</span>
    <span>This is a <strong>read-only order history</strong>. To dispatch orders, go to <a href="index.php" class="text-gold-400 font-black hover:underline">the Dashboard</a>.</span>
  </div>
  <?php endif; ?>

  <div class="flex gap-2 flex-wrap mb-8">
    <a href="orders.php" class="<?= (!$filter && !$disputed)?'btn-gold':'glass text-purple-400 hover:text-white' ?> px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg transition-all">All Orders</a>
    <a href="?disputed=1" class="<?= $disputed?'bg-red-600 text-white':'glass text-red-400 hover:text-white' ?> px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg transition-all flex items-center gap-2">
      🚩 Disputed
    </a>
    <?php foreach($statuses as $s): ?>
    <a href="?status=<?= $s ?>" class="<?= ($filter===$s && !$disputed)?'btn-gold':'glass text-purple-400 hover:text-white' ?> px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg transition-all">
      <?= str_replace('_',' ',$s) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-purple-700/30 bg-purple-900/20">
            <?php 
            $cols = ['Customer',$isVendor?'Details':'Restaurant','Total','Status','Personnel','Date'];
            if($isVendor) $cols[] = 'Manage';
            foreach($cols as $h): ?>
            <th class="px-6 py-5 text-left text-[10px] font-black text-purple-400 uppercase tracking-widest whitespace-nowrap"><?= $h ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach($orders as $o): 
          $displayName = $isVendor ? e($o['uname']) : "Customer #".$o['user_id'];
          // 1-minute reassignment window check
          $canReassign = true;
          if ($o['rider_id'] && $o['rider_assigned_at']) {
              $canReassign = (time() - strtotime($o['rider_assigned_at'])) <= 60;
          }
        ?>
        <tr class="border-b border-purple-700/20 hover:bg-purple-800/10 transition-all duration-300">
          <td class="px-6 py-5">
            <a href="order-detail.php?id=<?= $o['id'] ?>" class="text-gold-400 font-black hover:underline flex items-center gap-1.5">
              <?= $displayName ?>
              <svg class="w-3 h-3 opacity-40 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            <?php if($isVendor && $o['uphone']): ?>
            <div class="text-[10px] text-emerald-400 font-semibold mt-0.5">📞 <?= e($o['uphone']) ?></div>
            <?php endif; ?>
            <div class="text-[10px] text-purple-400 font-semibold uppercase tracking-tighter mt-0.5"><?= e($o['delivery_address'] ?? 'No Address') ?></div>
          </td>
          <td class="px-6 py-5 text-purple-300">
            <div class="font-semibold"><?= $isVendor ? ucfirst($o['payment_method']) : e($o['rname']) ?></div>
            <?php if($isVendor): ?><div class="text-[10px] text-purple-500 uppercase font-black">Payment</div><?php endif; ?>
          </td>
          <td class="px-6 py-5 font-black text-white"><?= money($o['total_amount']) ?></td>
          <td class="px-6 py-5">
            <div class="flex flex-col gap-1.5">
              <span class="<?= $sc[$o['status']]??'' ?> border px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest whitespace-nowrap text-center">
                <?= str_replace('_',' ',$o['status']) ?>
              </span>
              <?php if($o['is_reported']): ?>
              <span class="bg-red-900/60 text-red-300 border border-red-500/40 px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest whitespace-nowrap text-center">
                🚩 Disputed
              </span>
              <?php endif; ?>
            </div>
          </td>
          <td class="px-6 py-5 text-xs">
            <?php if($o['rider_name']): ?>
              <div class="font-bold text-white"><?= e($o['rider_name']) ?></div>
              <?php if($canReassign && $isVendor): ?>
                <div class="text-[9px] text-amber-400 font-black uppercase tracking-tighter">⏱ Reassignable</div>
              <?php else: ?>
                <div class="text-[9px] text-emerald-500 font-black uppercase tracking-tighter">✓ Assigned</div>
              <?php endif; ?>
            <?php else: ?>
              <div class="text-purple-600 font-black uppercase tracking-widest text-[9px]">Unassigned</div>
            <?php endif; ?>
          </td>
          <td class="px-6 py-5 text-purple-400 text-xs font-semibold whitespace-nowrap">
            <?= date('d M, Y', strtotime($o['created_at'])) ?><br/>
            <span class="text-[10px] opacity-70"><?= date('H:i', strtotime($o['created_at'])) ?></span>
          </td>
          <!-- Read-only — actions moved to Dashboard -->
          <?php if(!$isVendor): ?>
          <td class="px-6 py-5">
            <a href="order-detail.php?id=<?= $o['id'] ?>" class="glass px-3 py-2 rounded-xl text-[10px] font-black text-purple-300 hover:text-white hover:bg-gold-500/20 border border-transparent hover:border-gold-500/30 transition-all">View</a>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($orders)): ?>
        <tr>
          <td colspan="7" class="px-6 py-24 text-center">
            <div class="text-6xl mb-4 opacity-20">📦</div>
            <p class="text-purple-400 font-bold">No orders found matching your criteria.</p>
            <a href="orders.php" class="text-gold-400 text-sm font-bold mt-2 inline-block hover:underline">View All Orders</a>
          </td>
        </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>
<?php require_once '../includes/footer.php'; ?>
