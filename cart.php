<?php

session_start();
//session_destroy();

$page = 'index.php';

$db = new mysqli('localhost', 'root', '', 'cart');




if (isset($_GET['add'])) {
  $add = $db->real_escape_string((int)$_GET['add']);
  $query = "SELECT id, quantity FROM producten WHERE id = " . $add;
  $result = $db->query($query);

  while ($qt_row = $result->fetch_assoc()) {
      if (!isset($_SESSION['cart_' . $add])) {
        $_SESSION['cart_' . $add] = 0;
        }
        if ($qt_row['quantity'] > $_SESSION['cart_' . $add])  {
          $_SESSION['cart_' . $add] ++;
          header('location: ' . $page);
        } else {
          echo "<script>alert('The chosen pruduct is no longer availeble in stok!');
                window.location='index.php';
                </script>";
        }
      }

}

if (isset($_GET['remove'])) {
  $_SESSION['cart_' . (int)$_GET['remove']] --;
  header('location: ' . $page);
}

if (isset($_GET['delete'])) {
  $_SESSION['cart_' . (int)$_GET['delete']] = 0;
  header('location: ' . $page);
}

$paypalitems = function () use ($db) {

  $num = 0;
  foreach ($_SESSION as $cartsession => $value) {
    if ($value != 0) {
      if (substr($cartsession, 0, 5) == 'cart_') {
        $idc = substr($cartsession, 5, strlen($cartsession) - 5);
        $idc = $db->real_escape_string((int)$idc);
        $query = "SELECT id, name, price, shipping FROM producten WHERE id=" . $idc;
        $result = $db->query($query);
        while ($result->num_rows == true && $row = $result->fetch_assoc()) {
          $num ++;
          echo '<input type="hidden" name="item_number_' . $num . '" value="' . $idc . '">';
          echo '<input type="hidden" name="item_name_' . $num . '" value="' . $row['name'] . '">';
          echo '<input type="hidden" name="amount_' . $num . '" value="' . $row['price'] . '">';
          echo '<input type="hidden" name="shipping_' . $num . '" value="' . $row['shipping'] . '">';
          echo '<input type="hidden" name="shipping2_' . $num . '" value="' . $row['shipping'] . '">';
          echo '<input type="hidden" name="quantity_' . $num . '" value="' . $value . '">';

        }
      }
    }
  }
};


$cart = function () use ($db) {

  $query = "SELECT id, name, description, price, img FROM producten WHERE quantity > 0 ORDER BY id DESC";
  $result = $db->query($query);
  if ($result->num_rows == 0) {
    echo 'There is no products avialeble.';
  } else {
    while ($row = $result->fetch_assoc()) {
      $id = $row['id'];
      $name = $row['name'];
      $description = $row['description'];
      $price = number_format($row['price'], 2, ',', '.');
      $img = $row['img'];


      echo '<p>' . '<img src="'. $img .'" width="10%" height="15%">' . '<br>' . $name . '<br>' . $description . '<br>' . '&euro; ' . $price . '<br><a href = "cart.php?add='. $id .'">Add to cart</a></p>';
    }
  }
};

$ccart = function () use ($db, $paypalitems) {
  $total = 0;
  foreach($_SESSION as $cartsession => $value) {
    if ($value > 0) {
      if (substr($cartsession , 0, 5) == 'cart_') {

        $idc = substr($cartsession, 5, (strlen($cartsession) - 5));
        $idc = $db->real_escape_string((int)$idc);
        $query = "SELECT id, name, price FROM producten WHERE id=" . $idc;
        $result = $db->query($query);
          while ($result->num_rows == true && $row = $result->fetch_assoc()) {
            $sub = number_format($row['price'] * $value, 2);
            echo $row['name'] . ' x ' . $value . ' @ &euro; ' . number_format($row['price'], 2, ',', '.') . ' = &euro; ' . number_format($sub, 2, ',', '.') . ' <a href="cart.php?add=' . $idc . '">[+]</a> <a href="cart.php?remove=' . $idc . '">[-]</a> <a href="cart.php?delete=' . $idc . '">[Delete]</a><br>';
          }
        }
      $total += $sub;
    }
  }
  if ($total == 0) {
    echo 'Your cart is empty';
  } else {
    echo '<br> Total af te rekenen bedrag: &euro; ' . number_format($total, 2, ',', '.') . '<br><br>';

    ?>

    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
      <input type="hidden" name="cmd" value="_cart">
      <input type="hidden" name="upload" value="1">
      <input type="hidden" name="business" value="crisada37@gmail.com">

      <?php $paypalitems(); ?>

      <input type="hidden" name="currency_code" value="EURO">
      <input type="hidden" name="amount" value="<?php echo $total; ?>">
      <input type="image" src="http://www.paypal.com/en_US/i/btn/x-click-but03.gif" name="submit"  alt="Make pauments with Paypal - it's fast, free and secrure">
    </form>

    <?php

    echo '<hr>';
  }
}

?>
