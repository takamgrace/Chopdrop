<?php
// admin/sidebar.php
$adminPage = basename($_SERVER['PHP_SELF'],'.php');
$is_admin = isAdmin();
$is_vendor = isVendor();

$links = [['index', '🏠', 'Dashboard']];

if ($is_admin) {
    $links[] = ['users', '🤝', 'Vendors'];
    $links[] = ['restaurants', '🏛️', 'Manage Shops'];
    $links[] = ['riders', '🚴', 'Manage Riders'];
}

if ($is_vendor) {
    $links[] = ['orders', '📦', 'My Orders'];
    $links[] = ['foods', '🍽️', 'Menu Items'];
    $links[] = ['riders', '🚴', 'Global Riders'];
    $links[] = ['customers', '👥', 'My Customers'];
    $links[] = ['restaurants', '🏛️', 'My Shop'];
}
?>
<aside class="w-full md:w-56 bg-purple-900/80 border-b md:border-b-0 md:border-r border-purple-700/30 flex-shrink-0 flex flex-col md:min-h-[calc(100vh-68px)]">
  <div class="p-4 md:p-5 border-b border-purple-700/30 flex justify-between items-center bg-purple-950/50">

    <a href="../logout.php" class="text-purple-400 text-sm font-semibold hover:text-white transition-colors block md:hidden">Logout</a>
  </div>
  <nav class="flex-1 py-3 flex overflow-x-auto md:flex-col gap-2 md:gap-0 px-3 md:px-0 hide-scrollbar border-b border-purple-700/30 md:border-none">
    <?php foreach($links as [$page,$icon,$label]): $active=$adminPage===$page; ?>
    <a href="<?= $page ?>.php" class="flex items-center gap-2 md:gap-3 px-4 py-2.5 md:py-3 text-sm font-semibold transition-all rounded-xl md:rounded-none flex-shrink-0
      <?= $active?'text-white bg-purple-700/50 md:border-r-2 border-gold-400 shadow-md md:shadow-none':'text-purple-400 hover:text-white hover:bg-purple-800/30' ?>">
      <span><?= $icon ?></span><?= $label ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="p-5 border-t border-purple-700/30 hidden md:block">
    <a href="../logout.php" class="text-purple-400 text-sm font-semibold hover:text-white transition-colors">← Logout</a>
  </div>
</aside>
<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
