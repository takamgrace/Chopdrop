<?php
session_start(); require_once 'includes/config.php'; requireLogin();
$id=(int)($_GET['id']??0); $uid=(int)$_SESSION['user_id'];
$order=db()->query("SELECT o.*,r.name rname,r.phone rphone, u.name uname FROM orders o JOIN restaurants r ON r.id=o.restaurant_id JOIN users u ON u.id=o.user_id WHERE o.id=$id AND o.user_id=$uid")->fetch_assoc();
if (!$order) { header('Location: orders.php'); exit; }
$orderItems=db()->query("SELECT * FROM order_items WHERE order_id=$id")->fetch_all();
$steps=['pending'=>0,'confirmed'=>1,'preparing'=>2,'ready'=>3,'in_transit'=>4,'delivered'=>5];
$stepL=['Order Placed','Confirmed','Being Prepared','Ready','On the Way','Delivered'];
$stepI=['📋','✅','👨‍🍳','🎁','🚴','🏠'];
$cur=$steps[$order['status']]??0;

$review=db()->query("SELECT * FROM reviews WHERE order_id=$id AND user_id=$uid LIMIT 1")->fetch_assoc();
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action==='rate' && $order['status'] === 'delivered' && !$review) {
        $rating = (int)$_POST['rating'];
        $comment = db()->real_escape_string(trim($_POST['comment'] ?? ''));
        $rid = (int)$order['restaurant_id'];
        if ($rating >= 1 && $rating <= 5) {
            db()->query("INSERT INTO reviews (user_id, restaurant_id, order_id, rating, comment) VALUES ($uid, $rid, $id, $rating, '$comment')");
            db()->query("UPDATE restaurants SET rating = (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE restaurant_id = $rid) WHERE id = $rid");
            flash('success', 'Thank you for your review!');
            header("Location: order.php?id=$id"); exit;
        }
    } elseif ($action==='report_issue' && $order['status'] === 'delivered' && !$order['is_reported']) {
        db()->query("UPDATE orders SET is_reported=1, report_at=NOW() WHERE id=$id AND user_id=$uid");
        flash('success', 'Your report has been submitted. Our team will investigate immediately.');
        header("Location: order.php?id=$id"); exit;
    }
}

$pageTitle="Order #$id — ChopDrop";
require_once 'includes/header.php';
?>
<div class="max-w-4xl mx-auto px-5 py-12">
  <?php if($f=flash('success')): ?><div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-5 py-3 text-sm mb-6"><?= e($f) ?></div><?php endif; ?>

  <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <p class="text-gold-400 text-sm font-bold uppercase tracking-widest mb-1">Tracking</p>
      <h1 class="font-display text-4xl font-black text-white">Order #<?= $id ?></h1>
      <p class="text-purple-300 text-sm mt-1"><?= e($order['rname']) ?> · <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
    </div>
    <?php
    $sc=['pending'=>'bg-amber-900/60 text-amber-300 border-amber-500/40','confirmed'=>'bg-blue-900/60 text-blue-300 border-blue-500/40','preparing'=>'bg-violet-900/60 text-violet-300 border-violet-500/40','ready'=>'bg-green-900/60 text-green-300 border-green-500/40','in_transit'=>'bg-orange-900/60 text-orange-300 border-orange-500/40','delivered'=>'bg-green-900/60 text-emerald-300 border-emerald-500/40','cancelled'=>'bg-gray-900/60 text-gray-400 border-gray-600/40'];
    ?>
    <div class="flex items-center gap-3">
      <?php if(!in_array($order['status'], ['pending', 'cancelled'])): ?>
      <button onclick="downloadReceipt(event)" class="btn-gold px-4 py-2.5 rounded-full text-sm font-bold shadow-lg flex items-center gap-2">
        📥 Save PDF Receipt
      </button>
      <?php endif; ?>

      <?php if($order['is_reported']): ?>
      <span class="bg-red-900/60 text-red-300 border border-red-500/40 px-5 py-2.5 rounded-full text-sm font-black uppercase tracking-widest flex items-center gap-2">
        🚩 Dispute Opened
      </span>
      <?php else: ?>
        <span class="<?= $sc[$order['status']]??'' ?> border px-5 py-2.5 rounded-full text-sm font-bold"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span>
        <?php if($order['status'] === 'delivered'): ?>
        <form method="POST" onsubmit="return confirm('Report this order as not delivered? Our team will investigate immediately.')">
          <input type="hidden" name="action" value="report_issue"/>
          <button type="submit" class="border border-red-500/30 text-red-400 hover:bg-red-500 hover:text-white transition-all px-4 py-2.5 rounded-full text-xs font-black uppercase tracking-widest">
            Report Issue
          </button>
        </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Progress bar -->
  <?php if($order['status']!=='cancelled'): ?>
  <div class="glass rounded-3xl p-8 mb-8">
    <div class="relative">
      <!-- Track line -->
      <div class="absolute top-5 left-0 right-0 h-0.5 bg-purple-700/50"></div>
      <div class="absolute top-5 left-0 h-0.5 bg-gradient-to-r from-gold-500 to-gold-400 transition-all duration-700"
           style="width:<?= $cur>0?min(($cur/5)*100,100):0 ?>%"></div>
      <!-- Steps -->
      <div class="relative flex justify-between">
        <?php for($i=0;$i<=5;$i++): $done=$i<=$cur; ?>
        <div class="flex flex-col items-center gap-3">
          <div class="w-10 h-10 rounded-full <?= $done?'btn-gold':'glass border border-purple-600/40' ?> flex items-center justify-center text-lg z-10 transition-all">
            <?= $stepI[$i] ?>
          </div>
          <div class="text-xs font-semibold <?= $done?'text-gold-300':'text-purple-500' ?> text-center hidden sm:block"><?= $stepL[$i] ?></div>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Cancellation Refund Notice -->
  <?php if($order['status']==='cancelled'): ?>
  <div class="glass rounded-3xl p-8 mb-8 border-2 border-red-500/40 text-center relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-red-900/20 to-transparent pointer-events-none"></div>
    <div class="relative z-10">
      <div class="text-5xl mb-4">❌</div>
      <h2 class="font-display text-2xl font-bold text-white mb-3">Order Cancelled</h2>
      <p class="text-purple-200 text-base mb-2">We're sorry your order was cancelled.</p>
      <p class="text-green-300 font-semibold text-lg mb-2">💰 A full refund of <strong><?= money($order['total_amount']) ?></strong> will be sent back to you.</p>
      <p class="text-purple-400 text-sm mb-6">The refund will be processed to your original payment method (<strong class="text-white"><?= strtoupper($order['payment_method']) ?></strong>) within <strong class="text-white">3–5 business days</strong>. You will receive a confirmation once processed.</p>
      <button onclick="downloadRefund(event)" class="inline-flex items-center gap-2 bg-green-700/80 hover:bg-green-600 text-white border border-green-500/60 px-6 py-3 rounded-2xl text-sm font-bold transition-all shadow-md">
        📄 Download Refund Confirmation PDF
      </button>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid lg:grid-cols-3 gap-6">
    <!-- Items -->
    <div class="lg:col-span-2 glass rounded-3xl p-6">
      <h2 class="font-display font-bold text-white text-xl mb-5">Items Ordered</h2>
      <?php foreach($orderItems as $item): ?>
      <div class="flex justify-between items-center py-3.5 border-b border-purple-700/20">
        <div>
          <div class="font-semibold text-white"><?= e($item['name']) ?></div>
          <div class="text-purple-400 text-xs"><?= money($item['price']) ?> × <?= $item['quantity'] ?></div>
        </div>
        <div class="font-bold text-white"><?= money($item['price']*$item['quantity']) ?></div>
      </div>
      <?php endforeach; ?>
      <div class="flex justify-between py-3 text-purple-400 text-sm"><span>Delivery fee</span><span><?= money($order['delivery_fee']) ?></span></div>
      <div class="flex justify-between pt-3 border-t border-purple-700/40 font-display font-bold text-xl">
        <span class="text-white">Total Paid</span>
        <span class="gold-text"><?= money($order['total_amount']) ?></span>
      </div>
    </div>

    <!-- Details -->
    <div class="space-y-4">
      <div class="glass rounded-3xl p-6">
        <h3 class="font-bold text-white mb-4">Delivery Info</h3>
        <div class="space-y-2.5 text-sm">
          <div class="text-purple-300">📍 <span class="text-white font-medium"><?= e($order['delivery_address']) ?></span></div>
          <div class="text-purple-300">💳 <span class="text-white font-medium"><?= ucfirst($order['payment_method']) ?></span></div>
          <div class="text-purple-300">🏪 <span class="text-white font-medium"><?= e($order['rname']) ?></span></div>
          <?php if($order['notes']): ?><div class="text-purple-300">📝 <span class="text-white font-medium"><?= e($order['notes']) ?></span></div><?php endif; ?>
        </div>
      </div>
      
      <!-- Rating Section -->
      <?php if($order['status'] === 'delivered'): ?>
        <?php if(!$review): ?>
        <div class="glass rounded-3xl p-6 border-gold-500/40 border">
          <h3 class="font-display font-bold text-white mb-3 text-lg">Rate Your Experience</h3>
          <form method="POST">
            <input type="hidden" name="action" value="rate"/>
            <div class="flex gap-2 mb-4 cursor-pointer text-3xl" id="star-rating">
              <span class="star text-gray-400 hover:text-gold-400" data-val="1">★</span>
              <span class="star text-gray-400 hover:text-gold-400" data-val="2">★</span>
              <span class="star text-gray-400 hover:text-gold-400" data-val="3">★</span>
              <span class="star text-gray-400 hover:text-gold-400" data-val="4">★</span>
              <span class="star text-gray-400 hover:text-gold-400" data-val="5">★</span>
            </div>
            <input type="hidden" name="rating" id="rating-val" value="5" required/>
            <textarea name="comment" rows="2" placeholder="Leave a comment (optional)..." class="w-full bg-purple-900/50 border border-purple-600/40 rounded-xl px-4 py-3 text-white text-sm placeholder-purple-500 mb-3"></textarea>
            <button type="submit" class="btn-gold w-full py-3 rounded-xl text-sm font-bold shadow-gold">Submit Review</button>
          </form>
        </div>
        <script>
          const stars = document.querySelectorAll('.star');
          const input = document.getElementById('rating-val');
          function updateStars(val) {
            stars.forEach(s => {
              if(s.dataset.val <= val) { s.classList.add('text-gold-400'); s.classList.remove('text-gray-400'); }
              else { s.classList.add('text-gray-400'); s.classList.remove('text-gold-400'); }
            });
          }
          stars.forEach(s => {
            s.addEventListener('click', () => {
              input.value = s.dataset.val;
              updateStars(input.value);
            });
          });
          updateStars(5);
        </script>
        <?php else: ?>
        <div class="glass rounded-3xl p-6 border border-purple-600/30">
          <h3 class="font-bold text-white mb-2">Your Review</h3>
          <div class="text-gold-400 text-lg mb-2"><?= str_repeat('★', $review['rating']) ?><span class="text-gray-400"><?= str_repeat('★', 5 - $review['rating']) ?></span></div>
          <?php if($review['comment']): ?><p class="text-purple-300 text-sm leading-relaxed border-l-2 border-gold-500/50 pl-3">"<?= e($review['comment']) ?>"</p><?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <a href="orders.php" class="glass w-full py-3 rounded-2xl text-center text-sm font-semibold text-purple-200 hover:text-white transition-colors block">← All Orders</a>
      <a href="index.php" class="btn-gold w-full py-3 rounded-2xl text-center text-sm font-bold block">Order Again</a>
    </div>
  </div>
</div>

<!-- Hidden Receipt Template for PDF -->
<div style="overflow: hidden; height: 0; width: 0;">
  <div id="receipt-content" style="width: 800px; padding: 60px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #111; background: #fff;">
    <div style="text-align: center; border-bottom: 3px solid #6b21a8; padding-bottom: 20px; margin-bottom: 30px;">
      <h1 style="font-size: 32px; font-weight: 900; color: #6b21a8; margin: 0; letter-spacing: 2px;">CHOPDROP</h1>
      <p style="color: #666; font-size: 14px; margin-top: 5px;">Food Delivery Platform</p>
    </div>
    
    <div style="display: flex; justify-content: space-between; margin-bottom: 40px;">
      <div>
        <h3 style="font-size: 16px; color: #888; margin: 0 0 5px 0;">BILLED TO</h3>
        <p style="font-size: 18px; font-weight: bold; margin: 0;"><?= e($order['uname']) ?></p>
        <p style="color: #444; margin: 5px 0 0 0;"><?= e($order['delivery_address']) ?></p>
      </div>
      <div style="text-align: right;">
        <h3 style="font-size: 16px; color: #888; margin: 0 0 5px 0;">RECEIPT #</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 0; color: #6b21a8;"><?= $id ?></p>
        <p style="color: #444; margin: 5px 0 0 0;"><?= date('F j, Y, h:i A', strtotime($order['created_at'])) ?></p>
      </div>
    </div>
    
    <div style="margin-bottom: 40px; background: #f8fafc; padding: 15px; border-left: 4px solid #6b21a8;">
      <p style="margin: 0; font-size: 16px;"><strong>Restaurant:</strong> <?= e($order['rname']) ?></p>
      <p style="margin: 5px 0 0 0; color: #666;">Phone: <?= e($order['rphone']) ?></p>
    </div>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
      <thead>
        <tr style="border-bottom: 2px solid #cbd5e1;">
          <th style="text-align: left; padding: 15px 10px; color: #64748b; font-size: 14px;">ITEM DESCRIPTION</th>
          <th style="text-align: center; padding: 15px 10px; color: #64748b; font-size: 14px;">QTY</th>
          <th style="text-align: right; padding: 15px 10px; color: #64748b; font-size: 14px;">PRICE</th>
          <th style="text-align: right; padding: 15px 10px; color: #64748b; font-size: 14px;">TOTAL</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($orderItems as $item): ?>
        <tr style="border-bottom: 1px solid #e2e8f0;">
          <td style="padding: 15px 10px; font-weight: bold;"><?= e($item['name']) ?></td>
          <td style="text-align: center; padding: 15px 10px;"><?= $item['quantity'] ?></td>
          <td style="text-align: right; padding: 15px 10px; color: #64748b;"><?= money($item['price']) ?></td>
          <td style="text-align: right; padding: 15px 10px; font-weight: bold;"><?= money($item['price'] * $item['quantity']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    
    <div style="display: flex; justify-content: space-between;">
      <div style="width: 50%;">
        <h3 style="font-size: 16px; color: #888; margin: 0 0 5px 0;">PAYMENT METHOD</h3>
        <p style="font-size: 16px; font-weight: bold; margin: 0;"><?= strtoupper($order['payment_method']) ?></p>
        <?php if($order['notes']): ?>
        <p style="margin: 15px 0 0 0; color: #666; font-style: italic;">"<?= e($order['notes']) ?>"</p>
        <?php endif; ?>
      </div>
      <div style="width: 40%;">
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 10px;">
          <span style="color: #64748b;">Subtotal:</span>
          <span style="font-weight: bold;"><?= money($order['total_amount'] - $order['delivery_fee']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #6b21a8; padding-bottom: 10px; margin-bottom: 15px;">
          <span style="color: #64748b;">Delivery Fee:</span>
          <span style="font-weight: bold;"><?= money($order['delivery_fee']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="font-size: 20px; font-weight: 900; color: #6b21a8;">TOTAL PAID</span>
          <span style="font-size: 24px; font-weight: 900; color: #6b21a8;"><?= money($order['total_amount']) ?></span>
        </div>
      </div>
    </div>
    
    <div style="text-align: center; margin-top: 80px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
      <p style="color: #888; font-size: 14px;">Support: contact@chopdrop.cm • Thank you for your business!</p>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadReceipt(event) {
  const element = document.getElementById('receipt-content');
  const opt = {
    margin:       0,
    filename:     'ChopDrop_Receipt_#<?= $id ?>.pdf',
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2, useCORS: true },
    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
  };
  const btn = event.currentTarget;
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '⏳ Generating PDF...';
  btn.disabled = true;
  html2pdf().set(opt).from(element).save().then(() => {
    btn.innerHTML = originalHtml;
    btn.disabled = false;
  });
}

function downloadRefund(event) {
  const element = document.getElementById('refund-content');
  const opt = {
    margin:       0,
    filename:     'ChopDrop_Refund_Confirmation_#<?= $id ?>.pdf',
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2, useCORS: true },
    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
  };
  const btn = event.currentTarget;
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '⏳ Generating PDF...';
  btn.disabled = true;
  html2pdf().set(opt).from(element).save().then(() => {
    btn.innerHTML = originalHtml;
    btn.disabled = false;
  });
}
</script>

<!-- Hidden Refund Confirmation PDF Template -->
<?php if($order['status']==='cancelled'): ?>
<div style="overflow: hidden; height: 0; width: 0;">
  <div id="refund-content" style="width: 800px; padding: 60px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #111; background: #fff;">
    <!-- Header -->
    <div style="text-align: center; border-bottom: 3px solid #dc2626; padding-bottom: 20px; margin-bottom: 30px;">
      <h1 style="font-size: 32px; font-weight: 900; color: #6b21a8; margin: 0; letter-spacing: 2px;">CHOPDROP</h1>
      <p style="color: #666; font-size: 14px; margin-top: 5px;">Food Delivery Platform</p>
      <div style="margin-top: 16px; background: #fef2f2; border: 2px solid #fca5a5; border-radius: 12px; padding: 10px 20px; display: inline-block;">
        <span style="color: #dc2626; font-weight: 900; font-size: 18px; letter-spacing: 1px;">REFUND CONFIRMATION</span>
      </div>
    </div>

    <!-- Customer & Order Info -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 40px;">
      <div>
        <h3 style="font-size: 14px; color: #888; margin: 0 0 5px 0; text-transform: uppercase;">Customer</h3>
        <p style="font-size: 18px; font-weight: bold; margin: 0;"><?= e($order['uname']) ?></p>
        <p style="color: #444; margin: 5px 0 0 0;"><?= e($order['delivery_address']) ?></p>
      </div>
      <div style="text-align: right;">
        <h3 style="font-size: 14px; color: #888; margin: 0 0 5px 0; text-transform: uppercase;">Cancellation Ref.</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 0; color: #dc2626;">#<?= $id ?></p>
        <p style="color: #444; margin: 5px 0 0 0;"><?= date('F j, Y, h:i A') ?></p>
      </div>
    </div>

    <!-- Restaurant -->
    <div style="margin-bottom: 30px; background: #f8fafc; padding: 15px; border-left: 4px solid #dc2626;">
      <p style="margin: 0; font-size: 15px;"><strong>Cancelled Order From:</strong> <?= e($order['rname']) ?></p>
      <p style="margin: 5px 0 0 0; color: #666;">Original Order Date: <?= date('F j, Y, h:i A', strtotime($order['created_at'])) ?></p>
    </div>

    <!-- Items -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
      <thead>
        <tr style="border-bottom: 2px solid #cbd5e1;">
          <th style="text-align: left; padding: 12px 10px; color: #64748b; font-size: 13px;">ITEM</th>
          <th style="text-align: center; padding: 12px 10px; color: #64748b; font-size: 13px;">QTY</th>
          <th style="text-align: right; padding: 12px 10px; color: #64748b; font-size: 13px;">AMOUNT</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($orderItems as $item): ?>
        <tr style="border-bottom: 1px solid #e2e8f0;">
          <td style="padding: 12px 10px; font-weight: 600;"><?= e($item['name']) ?></td>
          <td style="text-align: center; padding: 12px 10px;"><?= $item['quantity'] ?></td>
          <td style="text-align: right; padding: 12px 10px; font-weight: bold;"><?= money($item['price'] * $item['quantity']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Refund amount box -->
    <div style="background: #f0fdf4; border: 2px solid #16a34a; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 40px;">
      <p style="font-size: 14px; color: #166534; margin: 0 0 8px 0; font-weight: 600;">FULL REFUND AMOUNT</p>
      <p style="font-size: 42px; font-weight: 900; color: #16a34a; margin: 0;"><?= money($order['total_amount']) ?></p>
      <p style="font-size: 13px; color: #166534; margin: 10px 0 0 0;">Refund will be processed to: <strong><?= strtoupper($order['payment_method']) ?></strong> within 3–5 business days.</p>
    </div>

    <!-- Footer -->
    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0;">
      <p style="color: #888; font-size: 13px;">This document confirms your order has been cancelled and a full refund is being processed.</p>
      <p style="color: #888; font-size: 13px; margin-top: 5px;">Questions? Contact us at support@chopdrop.cm</p>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

