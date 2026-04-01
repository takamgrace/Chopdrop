<?php require_once 'includes/config.php'; $db=db(); 
echo "FOODS SCHEMA:\n";
$res=$db->query("DESCRIBE foods"); while($row=$res->fetch_assoc()) { print_r($row); } 
?>
