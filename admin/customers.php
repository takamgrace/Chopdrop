<?php
session_start(); require_once '../includes/config.php'; requireVendor();
$db = db();
$rid = getVendorRid();
$pageTitle = 'My Customers — ChopDrop Admin';

$search = trim($_GET['q'] ?? '');
$s = $db->real_escape_string($search);
$whereSearch = $search ? "AND (u.name LIKE '%$s%' OR u.email LIKE '%$s%')" : "";

// Find unique customers who ordered from this restaurant
$customers = $db->query("
    SELECT u.*, 
           COUNT(o.id) as total_orders, 
           SUM(o.total_amount) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u
    JOIN orders o ON o.user_id = u.id
    WHERE o.restaurant_id = $rid $whereSearch
    GROUP BY u.id
    ORDER BY last_order_date DESC
")->fetch_all();

require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-8 overflow-x-auto relative">
  <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <h1 class="font-display text-4xl font-black text-white">My Customers</h1>
      <p class="text-purple-400 mt-1">Users who have dined with <?= e($_SESSION['name']) ?></p>
    </div>
    <form method="GET" class="flex gap-3">
      <div class="relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-purple-500">🔍</span>
        <input name="q" value="<?= e($search) ?>" placeholder="Search customers..." 
          class="bg-purple-900/40 border border-purple-700/30 rounded-2xl pl-12 pr-4 py-3 text-white text-sm placeholder-purple-600 focus:border-gold-500 transition-all outline-none w-64 shadow-lg"/>
      </div>
      <button type="submit" class="btn-gold rounded-2xl px-6 py-3 text-sm font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all">Search</button>
    </form>
  </div>

  <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-purple-700/30 bg-purple-900/20">
            <?php foreach(['Customer','Email','Orders','Spent','Last Order','Action'] as $h): ?>
            <th class="px-6 py-5 text-left text-[10px] font-black text-purple-400 uppercase tracking-widest whitespace-nowrap"><?= $h ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach($customers as $u): ?>
        <tr class="border-b border-purple-700/20 hover:bg-purple-800/10 transition-all duration-300">
          <td class="px-6 py-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-purple-500/20 text-purple-400 rounded-2xl flex items-center justify-center font-black shadow-lg text-sm flex-shrink-0">
                <?= strtoupper(mb_substr($u['name'],0,1)) ?>
              </div>
              <div class="text-white font-bold whitespace-nowrap"><?= e($u['name']) ?></div>
            </div>
          </td>
          <td class="px-6 py-5 text-purple-300 text-xs font-semibold"><?= e($u['email']) ?></td>
          <td class="px-6 py-5 font-black text-white text-center text-lg"><?= $u['total_orders'] ?></td>
          <td class="px-6 py-5 font-black text-gold-400"><?= money($u['total_spent']) ?></td>
          <td class="px-6 py-5 text-purple-400 text-xs font-semibold whitespace-nowrap"><?= date('d M, Y', strtotime($u['last_order_date'])) ?></td>
          <td class="px-6 py-5">
            <a href="orders.php?q=<?= urlencode($u['email']) ?>" class="glass px-4 py-2 rounded-xl text-[10px] font-black uppercase text-purple-200 hover:text-white hover:bg-gold-500/20 transition-all border border-transparent hover:border-gold-500/30">
              View Order History
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($customers)): ?>
        <tr><td colspan="6" class="px-6 py-24 text-center">
          <div class="text-6xl mb-4 opacity-20">👥</div>
          <p class="text-purple-400 font-bold">No customers found for your restaurant.</p>
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>

<?php require_once '../includes/footer.php'; ?>
