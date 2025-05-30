<?php
echo '<div id="mySidenav" class="sidenav">
   <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
   <a href="index.php">Home</a>
   <a href="contact.php">Contact Us</a>';

   if (isset($_SESSION["customer_id"])){
      $sql = "SELECT c.type_id, t.type_id, t.type FROM customers c, types t WHERE customer_id = ? AND c.type_id = t.type_id";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $_SESSION['customer_id']);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $type = $row['type'];
   } else {
      $type = "guest";
   }

   if ($type == "customer" || $type == "admin"){
      echo '<a href="profile.php">My Profile</a>';
      echo '<a href="orders.php">My Orders</a>';
   } elseif($type == "guest"){
      echo '<a href="account_stuff/login.php">Login</a>';
   }
   
   if ($type == "admin"){
      echo '<a href="admin/dashboard.php">Admin Page</a>';
   }
   
   if ($type == "customer" || $type == "admin"){
      echo '<a class="logout" href="account_stuff/logout.php">Logout</a>';
   }
echo '</div>';
?>

<script>
   function openNav() {
     document.getElementById("mySidenav").style.width = "250px";
   }
   
   function closeNav() {
     document.getElementById("mySidenav").style.width = "0";
   }
</script> 