<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
$cartCount   = cartCount();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdminPage = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= e($pageTitle ?? 'ChopDrop') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        white: '#0f172a',
        purple: {
          100:'#0f0720', 200:'#1e293b', 300:'#334155', 400:'#475569', 
          500:'#9333ea', 600:'#d8b4fe', 700:'#e9d5ff', 
          800:'#f3e8ff', 900:'#e0f2fe',
          950:'#ffffff'
        },
        gold: {
          900:'#451a03', 700:'#b45309', 600:'#d97706',
          500:'#f59e0b', 400:'#fbbf24', 300:'#fcd34d',
          200:'#fde68a', 100:'#fffbeb'
        },
        pink: {
          500:'#f472b6', 400:'#f9a8d4', 300:'#fbcfe8', 200:'#fce7f3'
        },
        blue: {
          950:'#fce7f3', 900:'#bae6fd', 800:'#1e40af', 500:'#3b82f6', 400:'#60a5fa'
        }
      },
      fontFamily: {
        display: ['"Playfair Display"','serif'],
        sans:    ['"Plus Jakarta Sans"','sans-serif']
      },
      backgroundImage: {
        'luxury-grad': 'linear-gradient(135deg,#ffffff 0%,#fce7f3 50%,#e0f2fe 100%)',
        'gold-grad':   'linear-gradient(135deg,#f59e0b,#f9a8d4,#f59e0b)',
      },
      boxShadow: {
        'glow':   '0 0 40px rgba(244,114,182,0.25)',
        'gold':   '0 4px 20px rgba(245,158,11,0.3)',
        'luxury': '0 20px 60px rgba(0,0,0,0.05)',
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Plus Jakarta Sans', sans-serif; }
  .font-display { font-family: 'Playfair Display', serif; }
  .glass { background: rgba(255,255,255,0.7); backdrop-filter: blur(14px); border: 1px solid rgba(244,114,182,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.03); }
  .glass-dark { background: rgba(255,255,255,0.9); backdrop-filter: blur(16px); border: 1px solid rgba(59,130,246,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.05); }
  .card-hover { transition: transform .25s ease, box-shadow .25s ease; }
  .card-hover:hover { transform: translateY(-6px); box-shadow: 0 20px 60px rgba(244,114,182,0.15); }
  .gold-text { background: linear-gradient(135deg,#f59e0b,#f9a8d4,#f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
  .btn-gold { background: linear-gradient(135deg,#f59e0b,#fbbf24); color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.2); font-weight: 700; transition: all .2s; }
  .btn-gold:hover { background: linear-gradient(135deg,#fbbf24,#f9a8d4); box-shadow: 0 6px 24px rgba(245,158,11,0.4); transform: translateY(-1px); }
  .btn-purple { background: linear-gradient(135deg,#3b82f6,#9333ea); color: #fff; font-weight: 700; transition: all .2s; }
  .btn-purple:hover { box-shadow: 0 6px 24px rgba(59,130,246,0.4); transform: translateY(-1px); }
  .img-cover { width:100%; height:100%; object-fit:cover; }
  input:focus, select:focus, textarea:focus { outline: none; border-color: #f9a8d4 !important; box-shadow: 0 0 0 3px rgba(244,114,182,0.2) !important; }
  @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
  @keyframes shimmer { 0%,100%{opacity:.6} 50%{opacity:1} }
  .fade-up { animation: fadeUp .5s ease both; }
  .shimmer { animation: shimmer 2s ease infinite; }
  ::-webkit-scrollbar { width:6px; } ::-webkit-scrollbar-track { background:#fce7f3; }
  ::-webkit-scrollbar-thumb { background:#f472b6; border-radius:3px; }
  .status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 14px; border-radius:50px; font-size:.72rem; font-weight:700; letter-spacing:.3px; }
</style>
</head>
<body class="bg-gradient-to-br from-purple-950 via-blue-950 to-purple-900 text-white min-h-screen">

<!-- TOP NAV -->
<nav class="sticky top-0 z-50 glass-dark border-b border-purple-700/30">
  <div class="max-w-7xl mx-auto px-5 flex items-center justify-between h-[68px]">

    <!-- Logo -->
    <a href="<?= SITE_URL ?>/index.php" class="flex items-center gap-2 group">
      <div class="w-9 h-9 rounded-xl btn-gold flex items-center justify-content-center p-1 group-hover:scale-110 transition-transform">
        <svg viewBox="0 0 24 24" fill="none" class="w-full h-full">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="#fff"/>
          <path d="M8 12c0-2.21 1.79-4 4-4s4 1.79 4 4-1.79 4-4 4-4-1.79-4-4z" fill="#fff"/>
          <path d="M12 6v2M12 16v2M6 12h2M16 12h2" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <span class="font-display font-900 text-xl tracking-tight">
        <span class="gold-text">Chop</span><span class="text-white">Drop</span>
      </span>
    </a>

    <!-- Links -->
    <ul class="hidden md:flex items-center gap-8 text-sm font-semibold">
      <?php if(!isAdmin() && !isVendor() && !isRider()): ?>
      <li><a href="<?= SITE_URL ?>/index.php" class="<?= $currentPage==='index'?'text-gold-400':'text-purple-200 hover:text-white' ?> transition-colors">Home</a></li>
      <li><a href="<?= SITE_URL ?>/restaurants.php" class="<?= $currentPage==='restaurants'?'text-gold-400':'text-purple-200 hover:text-white' ?> transition-colors">Restaurants</a></li>
      <?php if(isLoggedIn()): ?>
      <li><a href="<?= SITE_URL ?>/orders.php" class="<?= $currentPage==='orders'?'text-gold-400':'text-purple-200 hover:text-white' ?> transition-colors">My Orders</a></li>
      <?php endif; ?>
      <?php endif; ?>
    </ul>

    <!-- Right actions (Desktop) -->
    <div class="hidden md:flex items-center gap-3">
      <?php if(isLoggedIn()): ?>
        <?php if(!isAdmin() && !isVendor() && !isRider()): ?>
        <a href="<?= SITE_URL ?>/cart.php" class="relative btn-gold rounded-full px-5 py-2 text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7 4H2v2h2.22l3.48 8.26A2 2 0 0 0 9.6 18H18a2 2 0 0 0 1.92-1.45L22 8H7V4zM9.6 16a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm8.8 0a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1z"/></svg>
          Cart
          <span id="cartCount" class="bg-purple-900 text-gold-400 text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center cart-count-display"><?= $cartCount ?></span>
        </a>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/logout.php" class="glass px-4 py-2 rounded-full text-sm font-semibold text-purple-200 hover:text-white transition-colors">Logout</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="glass px-4 py-2 rounded-full text-sm font-semibold text-purple-200 hover:text-white transition-colors">Login</a>
        <a href="<?= SITE_URL ?>/register.php" class="btn-gold rounded-full px-5 py-2 text-sm">Join Free</a>
      <?php endif; ?>
    </div>

    <!-- Mobile Toggler -->
    <div class="flex md:hidden items-center gap-3">
      <?php if(isLoggedIn() && !isAdmin()): ?>
      <a href="<?= SITE_URL ?>/cart.php" class="relative text-gold-400">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M7 4H2v2h2.22l3.48 8.26A2 2 0 0 0 9.6 18H18a2 2 0 0 0 1.92-1.45L22 8H7V4zM9.6 16a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm8.8 0a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1z"/></svg>
        <?php if($cartCount>0): ?><span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-[18px] h-[18px] flex items-center justify-center rounded-full"><?= $cartCount ?></span><?php endif; ?>
      </a>
      <?php endif; ?>
      <button id="mobileMenuBtn" class="text-purple-200 p-2">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </div>

  <!-- Mobile Menu Dropdown -->
  <div id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-[#ffffff]/95 backdrop-blur-xl border-b border-purple-700/10 z-40">
    <div class="flex flex-col px-5 py-4 gap-4 text-base font-semibold">
      <?php if(!isAdmin()): ?>
      <a href="<?= SITE_URL ?>/index.php" class="<?= $currentPage==='index'?'text-gold-400':'text-purple-200' ?>">Home</a>
      <a href="<?= SITE_URL ?>/restaurants.php" class="<?= $currentPage==='restaurants'?'text-gold-400':'text-purple-200' ?>">Restaurants</a>
      <?php if(isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/orders.php" class="<?= $currentPage==='orders'?'text-gold-400':'text-purple-200' ?>">My Orders</a>
        <a href="<?= SITE_URL ?>/logout.php" class="text-red-400 border-t border-purple-700/30 pt-3 mt-1">Logout</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="text-purple-200 border-t border-purple-700/30 pt-3 mt-1">Login</a>
        <a href="<?= SITE_URL ?>/register.php" class="text-gold-400">Join Free</a>
      <?php endif; ?>
      <?php else: ?>
      <a href="<?= SITE_URL ?>/logout.php" class="text-red-400 border-t border-purple-700/30 pt-3 mt-1">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
  document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    document.getElementById('mobileMenu').classList.toggle('hidden');
  });
</script>
