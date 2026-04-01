<?php // includes/footer.php ?>
<footer class="bg-purple-900/60 border-t border-purple-700/30 mt-24">
  <div class="max-w-7xl mx-auto px-5 py-16">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">
      <div class="col-span-2 md:col-span-1">
        <div class="font-display text-2xl font-bold mb-3">
          <span class="gold-text">Chop</span><span class="text-white">Drop</span>
        </div>
        <p class="text-purple-300 text-sm leading-relaxed">Luxury food delivery connecting you to the finest restaurants in Douala . Fast, fresh, flawless.</p>
        
      </div>
      <div>
        <h4 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">Explore</h4>
        <ul class="space-y-2.5 text-sm text-purple-300">
          <li class="hover:text-gold-400 cursor-pointer transition-colors"> <a href="<?= SITE_URL ?>/index.php" class="<?= $currentPage==='index'?'text-gold-400':'text-purple-200' ?>">Home</a></li>
          <li class="hover:text-gold-400 cursor-pointer transition-colors"><a href="<?= SITE_URL ?>/restaurants.php" class="<?= $currentPage==='restaurants'?'text-gold-400':'text-purple-200' ?>">All Restaurants</a></li>
          
          <li class="hover:text-gold-400 cursor-pointer transition-colors">Offers &amp; Deals</li>
          <li class="hover:text-gold-400 cursor-pointer transition-colors"><a href="<?= SITE_URL ?>/admin/foods.php" class="<?= $currentPage==='foods'?'text-gold-400':'text-purple-200' ?>">Popular Dishes</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">Partners</h4>
        <ul class="space-y-2.5 text-sm text-purple-300">
          <li class="hover:text-gold-400 cursor-pointer transition-colors"><a href="<?= SITE_URL ?>/restaurants.php" class="<?= $currentPage==='restaurants'?'text-gold-400':'text-purple-200' ?>">List Your Restaurants</a></li>
         
          <li class="hover:text-gold-400 cursor-pointer transition-colors">Become a Rider</li>
          <li class="hover:text-gold-400 cursor-pointer transition-colors">Vendor Dashboard</li>
        </ul>
      </div>
      <div>
        <h4 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">Support</h4>
        <ul class="space-y-2.5 text-sm text-purple-300">
          <li><a href="<?= SITE_URL ?>/orders.php" class="hover:text-gold-400 transition-colors">Track My Order</a></li>
          <li class="hover:text-gold-400 cursor-pointer transition-colors">Help Centre</li>
          <li class="hover:text-gold-400 cursor-pointer transition-colors">Contact Us</li>
          <li class="hover:text-gold-400 cursor-pointer transition-colors">Privacy Policy</li>
        </ul>
      </div>
    </div>
    <!-- Payment methods -->
    <div class="border-t border-purple-700/40 pt-8 flex flex-wrap items-center justify-between gap-4">
      <p class="text-purple-400 text-sm">© <?= date('Y') ?> ChopDrop. All rights reserved. Made with ❤️ in Cameroon by TAKAM Grace.</p>
      <div class="flex items-center gap-3">
        <span class="text-purple-400 text-xs">We accept:</span>
        <?php foreach(['MTN MoMo','Orange Money','Visa','Cash'] as $p): ?>
        <span class="glass px-3 py-1.5 rounded-lg text-xs text-gold-300 font-semibold"><?= $p ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</footer>
</body>
</html>
