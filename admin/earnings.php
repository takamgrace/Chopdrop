<?php
session_start(); require_once '../includes/config.php'; requireAdminOrVendor();
$pageTitle = 'Monthly Earnings — ChopDrop Admin';
$db = db();
$isVendor = isVendor();
$rid = getVendorRid();

// ─── QUERY EARNINGS DATA ──────────────────────────────────────────────────────
$where = $isVendor ? "AND restaurant_id=$rid" : "";
$sql = "SELECT 
            YEAR(created_at) as y, 
            MONTH(created_at) as m, 
            COUNT(*) as total_orders, 
            SUM(total_amount - delivery_fee) as food_revenue,
            SUM(delivery_fee) as total_fees
        FROM orders 
        WHERE status='delivered' $where 
        GROUP BY y, m 
        ORDER BY y DESC, m DESC";

$result = $db->query($sql);
$monthlyStats = $result->fetch_all(MYSQLI_ASSOC);

// Calculation for stats
$totalRevenue = 0; $totalOrders = 0; $currentMonthRevenue = 0;
$currentYear = (int)date('Y'); $currentMonth = (int)date('m');

foreach ($monthlyStats as $stat) {
    $totalRevenue += (int)$stat['food_revenue'];
    $totalOrders += (int)$stat['total_orders'];
    if ((int)$stat['y'] === $currentYear && (int)$stat['m'] === $currentMonth) $currentMonthRevenue = (int)$stat['food_revenue'];
}

// ─── CHART PREP (LAST 6 MONTHS) ───────────────────────────────────────────────
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $months[] = ['y' => (int)date('Y', $time), 'm' => (int)date('m', $time), 'label' => date('M', $time)];
}

$chartDatasets = [];
if ($isVendor) {
    // Single Restaurant Trend (Line Chart)
    $data = [];
    foreach ($months as $mon) {
        $val = 0;
        foreach ($monthlyStats as $s) {
            if ($s['y'] == $mon['y'] && $s['m'] == $mon['m']) { $val = (int)$s['food_revenue']; break; }
        }
        $data[] = $val;
    }
    $chartDatasets[] = [
        'label' => $_SESSION['name'] ?? 'My Restaurant',
        'data' => $data,
        'borderColor' => '#fbbf24',
        'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
        'fill' => true,
        'borderWidth' => 4,
        'tension' => 0.4,
        'pointRadius' => 6,
        'pointBackgroundColor' => '#fbbf24'
    ];
} else {
    // Multi-Restaurant Comparison for Admin
    $resSql = "SELECT r.name, YEAR(o.created_at) as y, MONTH(o.created_at) as m, SUM(o.total_amount - o.delivery_fee) as rev 
               FROM orders o JOIN restaurants r ON r.id = o.restaurant_id 
               WHERE o.status = 'delivered' 
               GROUP BY r.id, y, m";
    $rawRes = $db->query($resSql)->fetch_all(MYSQLI_ASSOC);
    
    // Group rawRes by restaurant
    $byRest = [];
    foreach ($rawRes as $row) { $byRest[$row['name']][] = $row; }
    
    $colors = ['#fbbf24', '#818cf8', '#34d399', '#f87171', '#60a5fa', '#c084fc', '#f472b6', '#a78bfa'];
    $idx = 0;
    foreach ($byRest as $name => $rstats) {
        $data = [];
        foreach ($months as $mon) {
            $val = 0;
            foreach ($rstats as $s) {
                if ($s['y'] == $mon['y'] && $s['m'] == $mon['m']) { $val = (int)$s['rev']; break; }
            }
            $data[] = $val;
        }
        $color = $colors[$idx % count($colors)];
        $chartDatasets[] = [
            'label' => $name,
            'data' => $data,
            'borderColor' => $color,
            'backgroundColor' => $color . '10', // Low transparency fill
            'borderWidth' => 3,
            'tension' => 0.4,
            'pointRadius' => 4,
            'pointHoverRadius' => 7,
            'fill' => false // Better for comparison of lines
        ];
        $idx++;
    }
}

// Performance Breakdown Table logic
$restaurantBreakdown = [];
if (!$isVendor) {
    $resSqlTable = "SELECT r.name as rname, YEAR(o.created_at) as y, MONTH(o.created_at) as m, COUNT(*) as orders, SUM(o.total_amount - o.delivery_fee) as revenue 
               FROM orders o JOIN restaurants r ON r.id = o.restaurant_id 
               WHERE o.status = 'delivered' 
               GROUP BY r.id, y, m ORDER BY y DESC, m DESC, revenue DESC";
    $restaurantBreakdown = $db->query($resSqlTable)->fetch_all(MYSQLI_ASSOC);
}

require_once '../includes/header.php';
?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
  <?php include 'sidebar.php'; ?>
  
  <main class="flex-1 p-8 overflow-x-auto relative">
    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="font-display text-4xl font-black text-white">Earnings Report</h1>
        <p class="text-purple-400 mt-1"><?= $isVendor ? 'Financial performance of your restaurant' : 'Platform-wide revenue highlights' ?></p>
      </div>
      <div class="glass px-5 py-3 rounded-2xl flex items-center gap-3 border border-purple-700/30">
        <div class="text-xl">📅</div>
        <div>
            <div class="text-[10px] font-bold text-purple-400 uppercase tracking-widest">Reporting Period</div>
            <div class="text-sm font-bold text-white"><?= date('M Y', strtotime("-5 months")) ?> — <?= date('M Y') ?></div>
        </div>
      </div>
    </div>

    <!-- Stat Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
      <div class="glass rounded-3xl p-6 border border-purple-700/30 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 right-0 p-4 opacity-10 text-6xl">💰</div>
        <div class="text-purple-400 text-xs font-bold uppercase tracking-widest mb-2">Total Food Revenue</div>
        <div class="font-display text-3xl font-black text-white"><?= money($totalRevenue) ?></div>
        <div class="text-[10px] text-emerald-400 font-bold mt-2">Lifetime earnings from delivered orders</div>
      </div>
      <div class="glass rounded-3xl p-6 border border-gold-500/30 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 right-0 p-4 opacity-10 text-6xl">📊</div>
        <div class="text-purple-400 text-xs font-bold uppercase tracking-widest mb-2">Current Month (<?= date('F') ?>)</div>
        <div class="font-display text-3xl font-black text-gold-400"><?= money($currentMonthRevenue) ?></div>
        <div class="text-[10px] text-purple-300 font-bold mt-2">Real-time update for <?= date('Y') ?></div>
      </div>
      <div class="glass rounded-3xl p-6 border border-purple-700/30 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 right-0 p-4 opacity-10 text-6xl">📦</div>
        <div class="text-purple-400 text-xs font-bold uppercase tracking-widest mb-2">Successful Deliveries</div>
        <div class="font-display text-3xl font-black text-white"><?= number_format($totalOrders) ?></div>
        <div class="text-[10px] text-blue-400 font-bold mt-2">Orders contributing to revenue</div>
      </div>
    </div>

    <!-- Multi-Series Comparison Chart -->
    <div class="glass rounded-3xl p-8 mb-10 border border-purple-700/20 shadow-2xl relative overflow-hidden">
      <div class="absolute top-0 right-0 w-64 h-64 bg-gold-400/5 rounded-full blur-3xl -mr-32 -mt-32"></div>
      <h2 class="font-display font-bold text-white text-xl mb-8 flex items-center gap-2">
        <span class="w-2 h-6 bg-gold-500 rounded-full"></span> 
        <?= $isVendor ? 'Monthly Revenue Trend' : 'Restaurant Performance Comparison Trend' ?>
      </h2>
      <div class="h-96 relative">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>

    <!-- Monthly Table (Overall) -->
    <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 shadow-2xl mb-10">
      <div class="px-8 py-6 border-b border-purple-700/30 bg-purple-950/20">
        <h2 class="font-display font-bold text-white text-xl">Earnings History (Overall)</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-purple-700/30 bg-purple-900/10">
              <th class="px-8 py-4 text-left text-xs font-bold text-purple-400 uppercase tracking-widest">Period</th>
              <th class="px-8 py-4 text-left text-xs font-bold text-purple-400 uppercase tracking-widest">Orders</th>
              <th class="px-8 py-4 text-left text-xs font-bold text-purple-400 uppercase tracking-widest">Food Revenue</th>
              <?php if(!$isVendor): ?><th class="px-8 py-4 text-left text-xs font-bold text-purple-400 uppercase tracking-widest">Delivery Fees</th><?php endif; ?>
              <th class="px-8 py-4 text-left text-xs font-bold text-purple-400 uppercase tracking-widest">Performance</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($monthlyStats as $stat): 
            $dateObj = DateTime::createFromFormat('!m', $stat['m']);
            $monthName = $dateObj->format('F');
          ?>
          <tr class="border-b border-purple-700/20 hover:bg-purple-800/10 transition-all duration-300">
            <td class="px-8 py-5">
              <div class="text-white font-bold"><?= $monthName ?></div>
              <div class="text-[10px] text-purple-500"><?= $stat['y'] ?></div>
            </td>
            <td class="px-8 py-5 text-white font-medium"><?= number_format($stat['total_orders']) ?></td>
            <td class="px-8 py-5 font-black text-gold-400"><?= money((int)$stat['food_revenue']) ?></td>
            <?php if(!$isVendor): ?><td class="px-8 py-5 text-purple-300"><?= money((int)$stat['total_fees']) ?></td><?php endif; ?>
            <td class="px-8 py-5 text-xs">
              <span class="text-purple-400 bg-purple-900/20 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-purple-500/20">
                <?= (int)$stat['food_revenue'] >= ($totalRevenue/max(count($monthlyStats),1)) ? '⭐ High performance' : 'Standard' ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if(!$isVendor && !empty($restaurantBreakdown)): ?>
    <!-- Restaurant Comparison Breakdown Table -->
    <div class="glass rounded-3xl overflow-hidden border border-gold-500/20 shadow-2xl">
      <div class="px-8 py-6 border-b border-gold-500/20 bg-gold-950/20 flex items-center justify-between">
        <h2 class="font-display font-bold text-white text-xl">Monthly Restaurant Performance Comparison</h2>
        <span class="text-[10px] font-black text-gold-400 bg-gold-500/10 px-3 py-1 rounded-full uppercase tracking-widest border border-gold-500/20">Admin Data</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gold-950/10">
            <tr>
              <th class="px-8 py-4 text-left text-xs font-bold text-gold-400 uppercase tracking-widest">Restaurant Name</th>
              <th class="px-8 py-4 text-left text-xs font-bold text-gold-400 uppercase tracking-widest">Month</th>
              <th class="px-8 py-4 text-left text-xs font-bold text-gold-400 uppercase tracking-widest">Orders</th>
              <th class="px-8 py-4 text-left text-xs font-bold text-gold-400 uppercase tracking-widest">Food Earnings</th>
              <th class="px-8 py-4 text-left text-xs font-bold text-gold-400 uppercase tracking-widest">Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($restaurantBreakdown as $rb): 
            $dateObj = DateTime::createFromFormat('!m', $rb['m']);
            $isWinner = (int)$rb['revenue'] >= 100000;
          ?>
          <tr class="border-b border-gold-900/10 hover:bg-gold-800/10 transition-all">
            <td class="px-8 py-5 text-white font-black"><?= e($rb['rname']) ?></td>
            <td class="px-8 py-5 text-purple-300 text-xs font-bold uppercase"><?= $dateObj->format('M') ?> <?= $rb['y'] ?></td>
            <td class="px-8 py-5 text-white"><?= number_format($rb['orders']) ?></td>
            <td class="px-8 py-5 font-black text-gold-400"><?= money((int)$rb['revenue']) ?></td>
            <td class="px-8 py-5">
              <?php if($isWinner): ?>
                <span class="text-emerald-400 bg-emerald-900/20 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-500/20">🏆 Market Leader</span>
              <?php else: ?>
                <span class="text-purple-400 bg-purple-900/10 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-purple-500/10">Competing</span>
              <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($months, 'label')) ?>,
            datasets: <?= json_encode($chartDatasets) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { 
                    display: <?= $isVendor ? 'false' : 'true' ?>,
                    position: 'top',
                    align: 'end',
                    labels: { color: '#a78bfa', font: { weight: 'bold', size: 11 }, usePointStyle: true, padding: 25 }
                },
                tooltip: {
                    backgroundColor: '#1e1b4b',
                    titleColor: '#fbbf24',
                    bodyColor: '#fff',
                    padding: 15,
                    borderColor: 'rgba(251, 191, 36, 0.3)',
                    borderWidth: 1,
                    cornerRadius: 12,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat().format(context.parsed.y) + ' XAF';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                    ticks: { color: '#a78bfa', font: { weight: 'bold' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#a78bfa', font: { weight: 'bold' } }
                }
            }
        }
    });
});
</script>
