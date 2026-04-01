<?php
// cart.php
session_start();
require_once 'includes/config.php';

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? SITE_URL.'/cart.php';
$foodId   = (int)($_POST['food_id'] ?? $_GET['food_id'] ?? 0);

if (in_array($action,['add','remove','update','clear'])) {
    if (!isLoggedIn()) { 
        if (isset($_POST['is_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please login first.', 'redirect' => SITE_URL.'/login.php']);
            exit;
        }
        header('Location: '.SITE_URL.'/login.php?redirect='.urlencode($redirect)); exit; 
    }
    if (isAdmin()) { 
        if (isset($_POST['is_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Admins cannot order food.']);
            exit;
        }
        flash('error', 'Admins cannot order food.'); header('Location: '.SITE_URL.'/index.php'); exit; 
    }
    $uid = (int)$_SESSION['user_id'];
    if ($action==='add' && $foodId) {
        $food = db()->query("SELECT * FROM foods WHERE id=$foodId AND is_available=1")->fetch_assoc();
        if ($food) {
            db()->query("INSERT INTO cart (user_id,food_id,quantity) VALUES ($uid,$foodId,1) ON DUPLICATE KEY UPDATE quantity=quantity+1");
            if (isset($_POST['is_ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $food['name'].' added to cart!', 'cartCount' => cartCount()]);
                exit;
            }
            flash('success', $food['name'].' added to your cart!');
        }
    } elseif ($action==='remove' && $foodId) {
        db()->query("DELETE FROM cart WHERE user_id=$uid AND food_id=$foodId");
    } elseif ($action==='update' && $foodId) {
        $qty=(int)($_POST['quantity']??1);
        if ($qty<1) db()->query("DELETE FROM cart WHERE user_id=$uid AND food_id=$foodId");
        else db()->query("UPDATE cart SET quantity=$qty WHERE user_id=$uid AND food_id=$foodId");
    } elseif ($action==='clear') {
        $clearRid = (int)($_POST['restaurant_id'] ?? $_GET['restaurant_id'] ?? 0);
        if ($clearRid) {
            db()->query("DELETE FROM cart WHERE user_id=$uid AND food_id IN (SELECT id FROM foods WHERE restaurant_id=$clearRid)");
            flash('success','Items from restaurant removed from cart.');
        } else {
            db()->query("DELETE FROM cart WHERE user_id=$uid");
            flash('success','Cart cleared.');
        }
        $redirect = SITE_URL.'/cart.php';
    }
    header('Location: '.$redirect); exit;
}

requireLogin();
if (isAdmin()) { flash('error', 'Admins cannot view cart or order food.'); header('Location: index.php'); exit; }
$pageTitle   = 'My Cart — ChopDrop';
$items       = cartItems();
$subtotal    = cartTotal();
require_once 'includes/header.php';

// Group items by restaurant
$groups = [];
foreach ($items as $item) {
    $rid = $item['restaurant_id'];
    if (!isset($groups[$rid])) {
        $groups[$rid] = [
            'restaurant_name' => $item['restaurant_name'], 
            'items' => [], 
            'subtotal' => 0,
            'delivery_fee' => $item['delivery_fee'] ?? 500
        ];
    }
    $groups[$rid]['items'][] = $item;
    $groups[$rid]['subtotal'] += $item['price'] * $item['quantity'];
}
?>

<div class="max-w-6xl mx-auto px-5 py-12">
  <div class="mb-10">
    <p class="text-gold-400 text-xs font-black uppercase tracking-widest mb-2">My Gourmet Basket</p>
    <h1 class="font-display text-4xl md:text-5xl font-black text-white leading-tight">Your Cart <span class="text-purple-400 opacity-50">(<?= count($items) ?> items)</span></h1>
    <?php if(count($groups) > 1): ?>
    <div class="mt-4 glass px-5 py-3 rounded-2xl border border-gold-500/10 inline-flex items-center gap-3">
      <span class="text-xl">🏪</span>
      <p class="text-purple-300 text-sm font-medium">Unified order for <strong class="text-white"><?= count($groups) ?> restaurants</strong> detected.</p>
    </div>
    <?php endif; ?>
  </div>

  <?php if($f=flash('success')): ?>
  <div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-3xl px-6 py-4 text-sm mb-8 flex items-center gap-3 animate-slide-up">
    <span>✅</span> <?= e($f) ?>
  </div>
  <?php endif; ?>
  <?php if($f=flash('error')): ?>
  <div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-3xl px-6 py-4 text-sm mb-8 flex items-center gap-3 animate-slide-up">
    <span>⚠️</span> <?= e($f) ?>
  </div>
  <?php endif; ?>

  <?php if(empty($items)): ?>
  <div class="text-center py-24 glass rounded-3xl border border-white/5 shadow-2xl">
    <div class="text-7xl mb-6 opacity-30">🛒</div>
    <h2 class="font-display text-3xl font-black text-white mb-3">Your cart is empty</h2>
    <p class="text-purple-400 font-medium mb-8">Ready to discover something delicious?</p>
    <a href="index.php" class="btn-gold rounded-3xl px-10 py-4 text-sm font-black uppercase tracking-widest shadow-xl shadow-gold/20 active:scale-95 transition-all">Browse Restaurants</a>
  </div>

  <?php else: ?>

  <!-- Items List (Grouped by Restaurant) -->
  <div class="flex gap-10 flex-col lg:flex-row items-start">
    <div class="flex-1 w-full space-y-12">
      <?php 
      $totalSubtotal = 0;
      $totalDelivery = 0;
      foreach ($groups as $rid => $group):
        $deliveryFee = $group['delivery_fee'];
        $totalSubtotal += $group['subtotal'];
        $totalDelivery += $deliveryFee;
      ?>
      <div class="restaurant-group">
        <!-- Restaurant header -->
        <div class="flex items-center justify-between mb-5 flex-wrap gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 glass rounded-2xl flex items-center justify-center text-2xl border border-white/10 shadow-lg">🏪</div>
            <div>
              <h2 class="font-display text-2xl font-black text-white"><?= e($group['restaurant_name']) ?></h2>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest"><?= count($group['items']) ?> item<?= count($group['items'])!==1?'s':'' ?></span>
                <span class="w-1 h-1 bg-purple-700 rounded-full"></span>
                <span class="text-[10px] font-black text-gold-400 uppercase tracking-widest">Delivery: <?= money($deliveryFee) ?></span>
              </div>
            </div>
          </div>
          <form method="POST">
            <input type="hidden" name="action" value="clear"/>
            <input type="hidden" name="restaurant_id" value="<?= $rid ?>"/>
            <button class="text-red-400 text-[10px] font-black uppercase tracking-widest hover:text-red-300 transition-all hover:bg-red-500/10 px-3 py-1.5 rounded-lg border border-red-500/20">Empty Restaurant Items</button>
          </form>
        </div>

        <div class="glass rounded-[32px] overflow-hidden border border-purple-700/30 shadow-2xl">
          <?php foreach($group['items'] as $item): ?>
          <div class="flex items-center gap-6 px-8 py-6 border-b border-purple-700/20 last:border-0 hover:bg-purple-900/10 transition-colors">
            <div class="w-20 h-20 rounded-2xl overflow-hidden flex-shrink-0 shadow-xl">
              <img src="<?= e($item['image'] ? $item['image'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80') ?>" alt="<?= e($item['name']) ?>" class="w-full h-full object-cover"/>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-bold text-lg text-white truncate"><?= e($item['name']) ?></div>
              <div class="text-gold-400 font-black text-base mt-1"><?= money($item['price']) ?> <span class="text-[10px] text-purple-500 opacity-70">ea</span></div>
            </div>
            <div class="flex items-center gap-4 flex-shrink-0">
              <div class="flex items-center glass rounded-2xl p-1 border border-white/5">
                <form method="POST" class="inline">
                  <input type="hidden" name="action" value="update"/>
                  <input type="hidden" name="food_id" value="<?= $item['food_id'] ?>"/>
                  <input type="hidden" name="quantity" value="<?= $item['quantity']-1 ?>"/>
                  <button class="w-10 h-10 rounded-xl text-white font-black text-xl hover:bg-white/10 transition-all flex items-center justify-center active:scale-75">−</button>
                </form>
                <span class="text-white font-black w-10 text-center text-lg"><?= $item['quantity'] ?></span>
                <form method="POST" class="inline">
                  <input type="hidden" name="action" value="update"/>
                  <input type="hidden" name="food_id" value="<?= $item['food_id'] ?>"/>
                  <input type="hidden" name="quantity" value="<?= $item['quantity']+1 ?>"/>
                  <button class="w-10 h-10 bg-gold-400 rounded-xl font-black text-xl text-purple-900 flex items-center justify-center hover:bg-gold-300 transition-all active:scale-75 shadow-lg shadow-gold/10">+</button>
                </form>
              </div>
              <form method="POST" class="inline">
                <input type="hidden" name="action" value="remove"/>
                <input type="hidden" name="food_id" value="<?= $item['food_id'] ?>"/>
                <button class="w-10 h-10 flex items-center justify-center text-purple-500 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-all">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Unified Checkout Summary and Form -->
    <div class="w-full lg:w-[400px] flex-shrink-0 space-y-8">
      <!-- Grand Summary -->
      <div class="glass rounded-[40px] p-8 border border-gold-500/20 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-gold-400/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
        <h3 class="font-display font-black text-white text-2xl mb-8">Order Summary</h3>
        <div class="space-y-5">
          <div class="flex justify-between items-center">
            <span class="text-sm font-bold text-purple-300">Items Subtotal</span>
            <span class="text-white font-black"><?= money($totalSubtotal) ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm font-bold text-purple-300">Delivery Fees (x<?= count($groups) ?>)</span>
            <div class="text-right">
              <div class="text-white font-black"><?= money($totalDelivery) ?></div>
              <div class="text-[9px] text-gold-500 font-bold uppercase tracking-tighter">Unified Discount: 0</div>
            </div>
          </div>
          <div class="pt-6 mt-4 border-t border-purple-700/50">
            <div class="flex justify-between items-end">
              <div>
                <span class="text-xs font-black text-gold-400 uppercase tracking-widest block mb-1">Total Payable</span>
                <span class="text-4xl font-display font-black text-white"><?= money($totalSubtotal + $totalDelivery) ?></span>
              </div>
              <div class="text-right">
                <span class="text-emerald-400 text-xs font-bold block mb-1">✓ Verified Prices</span>
                <span class="text-[10px] text-purple-600 block">Inc. all platform taxes</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Unified Checkout Form -->
      <form method="POST" action="checkout.php" class="glass rounded-[40px] p-8 space-y-6 border border-white/5 shadow-2xl">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 bg-purple-600/20 rounded-xl flex items-center justify-center text-xl">🚚</div>
          <h3 class="font-display font-black text-white text-xl">Quick Checkout</h3>
        </div>
        
        <div>
          <label class="block text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2 ml-1">Delivery Destination</label>
          <textarea name="address" rows="3" required placeholder="Street name, neighborhood, building..."
            class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl px-5 py-4 text-white text-sm placeholder-purple-700 resize-none focus:border-gold-500 transition-all outline-none"></textarea>
        </div>
        
        <div class="grid grid-cols-1 gap-6">
          <div>
            <label class="block text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2 ml-1">Payment Channel</label>
            <div class="relative">
              <select name="payment" class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl px-5 py-4 text-white text-sm focus:border-gold-500 transition-all outline-none appearance-none cursor-pointer">
                <option value="momo">📱 MTN Mobile Money</option>
                <option value="orange">🟠 Orange Money</option>
                <option value="card">💳 Visa / MasterCard</option>
                <option value="cash" selected>💵 Cash on Delivery</option>
              </select>
              <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-purple-500 font-bold">▼</div>
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2 ml-1">Order Notes (Optional)</label>
            <input name="notes" placeholder="Any special instructions?"
              class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl px-5 py-4 text-white text-sm placeholder-purple-700 focus:border-gold-500 transition-all outline-none"/>
          </div>
        </div>

        <button type="submit" class="btn-gold w-full rounded-2xl py-5 text-sm font-black uppercase tracking-widest shadow-xl shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
          Authorize Shipment — <?= money($totalSubtotal + $totalDelivery) ?>
        </button>
        
        <div class="bg-purple-900/30 rounded-xl p-4 border border-white/5">
          <p class="text-[10px] text-center text-purple-400 leading-relaxed italic">
            Placement of this order creates <strong class="text-white"><?= count($groups) ?> dispatch request<?= count($groups)>1?'s':'' ?></strong>. 
            Delivery speeds vary by restaurant location.
          </p>
        </div>
      </form>
    </div>
  </div>

  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
