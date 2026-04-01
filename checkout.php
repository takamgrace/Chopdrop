<?php
// checkout.php
session_start(); require_once 'includes/config.php'; requireLogin();
if (isAdmin()) { flash('error', 'Admins cannot order food.'); header('Location: index.php'); exit; }
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: cart.php'); exit; }

$uid        = (int)$_SESSION['user_id'];
$address    = trim($_POST['address'] ?? '');
$payment    = $_POST['payment'] ?? 'cash';
$notes      = trim($_POST['notes'] ?? '');

if (!$address) { flash('error','Delivery address required.'); header('Location: cart.php'); exit; }

// Fetch ALL items from the user's cart (already includes delivery_fee from config.php update)
$allItems = cartItems();

if (empty($allItems)) { flash('error','Your cart is empty.'); header('Location: cart.php'); exit; }

// Group items by restaurant for multi-order processing
$groups = [];
foreach ($allItems as $item) {
    $rid = (int)$item['restaurant_id'];
    if (!isset($groups[$rid])) {
        $groups[$rid] = [
            'items' => [],
            'delivery_fee' => (int)($item['delivery_fee'] ?? 500)
        ];
    }
    $groups[$rid]['items'][] = $item;
}

$db = db();
$a  = $db->real_escape_string($address);
$p  = $db->real_escape_string($payment);
$n  = $db->real_escape_string($notes);
$orderIds = [];

$db->begin_transaction();

try {
    foreach ($groups as $rid => $groupData) {
        $items = $groupData['items'];
        $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
        $fee = $groupData['delivery_fee'];
        $total = $subtotal + $fee;

        // Create order for this specific restaurant
        $db->query("INSERT INTO orders (user_id,restaurant_id,total_amount,delivery_fee,delivery_address,payment_method,notes,status) 
                    VALUES ($uid,$rid,$total,$fee,'$a','$p','$n','pending')");
        $oid = $db->insert_id;
        $orderIds[] = $oid;

        // Save order items
        foreach ($items as $item) {
            $fid = (int)$item['food_id'];
            $nm  = $db->real_escape_string($item['name']);
            $pr  = (int)$item['price'];
            $qty = (int)$item['quantity'];
            $db->query("INSERT INTO order_items (order_id,food_id,name,price,quantity) VALUES ($oid,$fid,'$nm',$pr,$qty)");
        }
    }

    // Success! Clear the ENTIRE cart for this user
    $db->query("DELETE FROM cart WHERE user_id=$uid");
    $db->commit();
    
    $count = count($orderIds);
    $msg = "Success! $count order" . ($count > 1 ? "s have" : " has") . " been placed. 🛍️";
    flash('success', $msg);
    
    // Redirect to orders history
    header('Location: orders.php');
    exit;

} catch (Exception $e) {
    $db->rollback();
    flash('error', 'An error occurred while placing your orders: ' . $e->getMessage());
    header('Location: cart.php');
    exit;
}

