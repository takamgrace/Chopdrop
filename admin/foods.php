<?php
session_start();
require_once '../includes/config.php';
requireAdminOrVendor();
$pageTitle = 'Menu Items — ChopDrop Admin';
$db  = db();
$isVendor = isVendor();
$vrid = getVendorRid();

// Force rid if vendor
if ($isVendor) {
    $rid = $vrid;
} else {
    $rid = (int)($_GET['restaurant_id'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Auth check for actions
    if ($action === 'add' || $action === 'edit' || $action === 'delete' || $action === 'toggle') {
        $targetId = (int)($_POST['id'] ?? 0);
        if ($isVendor) {
            if ($action === 'add') $_POST['restaurant_id'] = $rid;
            else {
                // Ensure the item belongs to the vendor
                $check = $db->query("SELECT id FROM foods WHERE id=$targetId AND restaurant_id=$rid")->fetch_assoc();
                if (!$check) { flash('error', 'Unauthorized access.'); header('Location: foods.php'); exit; }
            }
        }
    }

    if ($action === 'add') {
        $r   = (int)$_POST['restaurant_id'];
        $n   = $db->real_escape_string(trim($_POST['name']));
        $d   = $db->real_escape_string(trim($_POST['description']));
        $p   = (int)$_POST['price'];
        $c   = $db->real_escape_string(trim($_POST['category']));
        
        $img = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../images/' . $filename)) {
                    $img = 'images/' . $filename;
                }
            }
        }
        
        $db->query("INSERT INTO foods (restaurant_id,name,description,price,category,image) VALUES ($r,'$n','$d',$p,'$c','$img')");
        flash('success', 'Item added!');
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $db->query("UPDATE foods SET is_available = NOT is_available WHERE id=$id");
        flash('success', 'Availability updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM foods WHERE id=$id");
        flash('success', 'Item deleted.');
    } elseif ($action === 'edit') {
        $id  = (int)$_POST['id'];
        $n   = $db->real_escape_string(trim($_POST['name']));
        $d   = $db->real_escape_string(trim($_POST['description']));
        $p   = (int)$_POST['price'];
        $c   = $db->real_escape_string(trim($_POST['category']));
        
        $updateImage = "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../images/' . $filename)) {
                    $img = 'images/' . $filename;
                    $updateImage = ", image='$img'";
                }
            }
        }
        
        $db->query("UPDATE foods SET name='$n',description='$d',price=$p,category='$c' $updateImage WHERE id=$id");
        flash('success', 'Item updated.');
    }
    header('Location: foods.php' . ($rid && !$isVendor ? "?restaurant_id=$rid" : ''));
    exit;
}

$where       = $rid ? "WHERE f.restaurant_id=$rid" : '';
$foods       = $db->query("SELECT f.*,r.name rname FROM foods f JOIN restaurants r ON r.id=f.restaurant_id $where ORDER BY r.name,f.category,f.name")->fetch_all();
$restaurants = $db->query("SELECT id,name FROM restaurants ORDER BY name")->fetch_all();
$categories  = ['Main Dish','Pizza','Burger','Chicken','Starter','Sushi','Sashimi','Salad','Bowl','Sides','Dessert','Drinks','Curry','Rice','Bread','Noodles','Other'];

require_once '../includes/header.php';
?>

<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
  <?php include 'sidebar.php'; ?>
  <main class="flex-1 p-8 overflow-x-auto relative">

    <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
      <div>
        <h1 class="font-display text-4xl font-black text-white"><?= $isVendor ? 'My Menu' : 'Menu Items' ?></h1>
        <p class="text-purple-400 mt-1"><?= $isVendor ? 'Manage the dishes available at your restaurant' : 'Manage all food items on the platform' ?></p>
      </div>
      <div class="flex gap-4 items-center flex-wrap">
        <?php if (!$isVendor): ?>
        <form method="GET">
          <select name="restaurant_id" onchange="this.form.submit()"
            class="bg-purple-900/70 border border-purple-600/40 rounded-xl px-4 py-2.5 text-white text-sm focus:border-gold-500 transition-all outline-none">
            <option value="">All Restaurants</option>
            <?php foreach ($restaurants as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $rid === $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <?php endif; ?>
        <button onclick="document.getElementById('addModal').style.display='flex'"
          class="btn-gold rounded-2xl px-6 py-3 text-sm font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all">+ Add Dish</button>
      </div>
    </div>

    <?php if ($f = flash('success')): ?>
    <div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-6 py-4 text-sm mb-6 flex items-center gap-3">
      <span>✅</span> <?= e($f) ?>
    </div>
    <?php endif; ?>
    <?php if ($f = flash('error')): ?>
    <div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-2xl px-6 py-4 text-sm mb-6 flex items-center gap-3">
      <span>⚠️</span> <?= e($f) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <?php foreach ($foods as $f): ?>
      <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 card-hover group">
        <div class="relative h-44 overflow-hidden">
          <img src="<?= e($f['image'] ? '../'.$f['image'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80') ?>" alt="<?= e($f['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 <?= !$f['is_available'] ? 'grayscale opacity-50' : '' ?>"/>
          <div class="absolute inset-0 bg-gradient-to-t from-purple-950/80 to-transparent"></div>
          <div class="absolute top-4 right-4">
            <?php if($isVendor): ?>
            <form method="POST">
              <input type="hidden" name="action" value="toggle"/>
              <input type="hidden" name="id" value="<?= $f['id'] ?>"/>
              <button type="submit" class="<?= $f['is_available'] ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-400' ?> text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-lg border border-white/10 active:scale-95 transition-all">
                <?= $f['is_available'] ? 'Available' : 'Unavailable' ?>
              </button>
            </form>
            <?php else: ?>
            <div class="<?= $f['is_available'] ? 'bg-green-500/20 text-green-400 border-green-500/30' : 'bg-gray-700/20 text-gray-400 border-gray-600/30' ?> text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full border shadow-lg backdrop-blur-sm">
              <?= $f['is_available'] ? 'Available' : 'Unavailable' ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="absolute bottom-4 left-4">
            <span class="bg-black/60 backdrop-blur-md text-gold-400 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-lg border border-white/5"><?= e($f['category']) ?></span>
          </div>
        </div>
        <div class="p-6">
          <div class="flex justify-between items-start gap-4 mb-2">
            <h3 class="font-display font-bold text-white text-lg leading-tight truncate"><?= e($f['name']) ?></h3>
            <span class="font-display font-black text-gold-400 text-lg"><?= money($f['price']) ?></span>
          </div>
          <?php if(!$isVendor): ?><p class="text-purple-400 text-[10px] font-bold uppercase tracking-widest mb-2 flex items-center gap-1"><span>🏪</span> <?= e($f['rname']) ?></p><?php endif; ?>
          <p class="text-purple-300 text-sm leading-relaxed mb-6 line-clamp-2 min-h-[40px]"><?= e($f['description']) ?></p>
          
          <div class="flex gap-3 pt-5 border-t border-purple-700/30">
            <button type="button" onclick="openEdit(<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>)"
              class="flex-1 glass py-2.5 rounded-xl text-xs font-black text-purple-200 hover:text-white hover:bg-gold-500/20 transition-all border border-transparent hover:border-gold-500/30">Edit Details</button>
            <form method="POST" onsubmit="return confirm('Delete this item permanently?')" class="flex-shrink-0">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= $f['id'] ?>"/>
              <button type="submit" class="w-10 h-10 flex items-center justify-center bg-red-900/30 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($foods)): ?>
      <div class="col-span-full glass rounded-3xl py-20 text-center">
        <div class="text-5xl mb-4 opacity-30">🍽️</div>
        <p class="text-purple-400 font-bold">No food items found in this section.</p>
        <button onclick="document.getElementById('addModal').style.display='flex'" class="text-gold-400 text-sm font-bold mt-2 hover:underline">+ Add your first item</button>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- ADD MODAL -->
<div id="addModal" style="display:none" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-5">
  <div class="glass rounded-3xl w-full max-w-lg p-8 max-h-[90vh] overflow-y-auto border border-purple-600/40">
    <div class="flex justify-between items-center mb-8">
      <h2 class="font-display text-2xl font-black text-white">Add Menu Item</h2>
      <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="text-purple-400 hover:text-white text-2xl transition-transform hover:rotate-90">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="action" value="add"/>
      
      <?php if ($isVendor): ?>
        <input type="hidden" name="restaurant_id" value="<?= $rid ?>"/>
      <?php else: ?>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Restaurant</label>
        <select name="restaurant_id" required class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all">
          <option value="">Select restaurant...</option>
          <?php foreach ($restaurants as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $rid === $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Dish Name</label>
        <input type="text" name="name" required placeholder="e.g. Traditional Ndolé"
          class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm placeholder-purple-600 outline-none focus:border-gold-500 transition-all"/>
      </div>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Short Description</label>
        <textarea name="description" rows="2" placeholder="Describe the ingredients or taste..."
          class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm placeholder-purple-600 outline-none focus:border-gold-500 transition-all resize-none"></textarea>
      </div>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Food Image</label>
        <div class="relative group">
          <input type="file" name="image" accept="image/*"
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-xs file:hidden cursor-pointer"/>
          <div class="absolute right-4 top-1/2 -translate-y-1/2 text-purple-400 pointer-events-none">📁 Browse</div>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Price (XAF)</label>
          <input type="number" name="price" required min="0" placeholder="0"
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all"/>
        </div>
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Category</label>
          <select name="category" class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all">
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat ?>"><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="btn-gold w-full rounded-2xl py-4 font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all mt-4">Publish Item</button>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" style="display:none" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-5">
  <div class="glass rounded-3xl w-full max-w-lg p-8 max-h-[90vh] overflow-y-auto border border-purple-600/40">
    <div class="flex justify-between items-center mb-8">
      <h2 class="font-display text-2xl font-black text-white">Edit Dish Details</h2>
      <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="text-purple-400 hover:text-white text-2xl transition-transform hover:rotate-90">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="id" id="editId"/>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Dish Name</label>
        <input type="text" name="name" id="editName" required
          class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all"/>
      </div>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Description</label>
        <textarea name="description" id="editDescription" rows="3"
          class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all resize-none"></textarea>
      </div>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Update Image (Optional)</label>
        <input type="file" name="image" accept="image/*"
          class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-xs file:hidden cursor-pointer"/>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Price (XAF)</label>
          <input type="number" name="price" id="editPrice" required min="0"
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all"/>
        </div>
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Category</label>
          <select name="category" id="editCat"
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all">
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat ?>"><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="btn-gold w-full rounded-2xl py-4 font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all mt-4">Save Changes</button>
    </form>
  </div>
</div>

<script>
function openEdit(food) {
  document.getElementById('editId').value          = food.id;
  document.getElementById('editName').value        = food.name;
  document.getElementById('editDescription').value = food.description;
  document.getElementById('editPrice').value       = food.price;
  document.getElementById('editCat').value         = food.category;
  document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php require_once '../includes/footer.php'; ?>
