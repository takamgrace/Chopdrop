<?php require_once 'includes/config.php'; $db=db(); 
echo "RESTAURANTS:\n";
$res1=$db->query("DESCRIBE restaurants"); while($row=$res1->fetch_assoc()) { print_r($row); } 
echo "\nUSERS:\n";
$res2=$db->query("DESCRIBE users"); while($row=$res2->fetch_assoc()) { print_r($row); } 
?>
