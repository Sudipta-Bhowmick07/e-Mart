<?php
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$products = [];
$message = "";

// DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $del = sqlsrv_query($conn, "DELETE FROM ClothingItems WHERE ID = ?", [$id]);
    $message = $del ? "<div class='message success'>✅ Deleted successfully</div>" : "<div class='message error'>❌ Delete failed</div>";
}

// INSERT / UPDATE
if (isset($_POST['upload'])) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['product_name'];
    $category = $_POST['category'];
$quantity = $_POST['quantity'] ?? '';
    $price = $_POST['price'];
    $desc = $_POST['desc'];

    $imgPath = "";
    if (!empty($_FILES['img']['name'])) {
        $imgPath = "uploads/" . basename($_FILES['img']['name']);
        move_uploaded_file($_FILES['img']['tmp_name'], $imgPath);
    }

    if ($id) {
        if ($imgPath) {
            $sql = "UPDATE ClothingItems SET ProductName=?, Category=?, Quantity=?, Price=?, Description=?, ImagePath=? WHERE ID=?";
            $params = [$name, $category, $quantity, $price, $desc, $imgPath, $id];
        } else {
            $sql = "UPDATE ClothingItems SET ProductName=?, Category=?, Quantity=?, Price=?, Description=? WHERE ID=?";
            $params = [$name, $category, $quantity, $price, $desc, $id];
        }
        $stmt = sqlsrv_query($conn, $sql, $params);
        $message = $stmt ? "<div class='message success'>✅ Updated successfully</div>" : "<div class='message error'>❌ Update failed</div>";
    } else {
        $sql = "INSERT INTO ClothingItems (ProductName, Category, Quantity, Price, Description, ImagePath) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$name, $category, $quantity, $price, $desc, $imgPath];
        $stmt = sqlsrv_query($conn, $sql, $params);
        $message = $stmt ? "<div class='message success'>✅ Uploaded successfully</div>" : "<div class='message error'>❌ Upload failed</div>";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = sqlsrv_query($conn, "SELECT * FROM ClothingItems WHERE ID = ?", [$id]);
    $editRow = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>e-Mart Admin - Products</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 80px;
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
            min-height: 100vh;
        }
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: #fff;
            border-bottom: 2px solid #f2f2f2;
            padding: 15px 50px;
            display: flex;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            gap: 50px;
        }
        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0074cc;
        }
        .navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 25px;
            margin-left: 800px;
        }
        .navbar ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        .navbar ul li a:hover {
            color: #0074cc;
        }
         .navbar ul li a i {
            margin-right: 6px;
        }
        .container {
            max-width: 750px;
            margin: 40px auto;
            background: #fffde7;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border: 2px solid #fdf0a0;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: grid;
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            background: #28a745;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background: #218838;
        }
        .message {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
        .success { color: green; }
        .error { color: red; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background: #f0f0f0;
        }
        a.btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .edit-btn { background: #007bff; color: white; }
        .delete-btn { background: #dc3545; color: white; }
    </style>
</head>
<body>

<!-- ✅ Navbar -->
<div class="navbar">
    <div class="logo">e-Mart</div>
    <ul>
        <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="admin_upload.php"><i class="fas fa-store"></i> Products</a></li>
        <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <li><a href="index.php"><i class="fas fa-box"></i> Shop</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- ✅ Admin Upload Form -->
<div class="container">
    <h2><?php echo isset($_GET['edit']) ? "Edit" : "Upload"; ?> Clothing Item</h2>

    <?php echo $message; ?>

    <form method="POST" action="admin_upload.php" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $editRow['ID'] ?? ''; ?>">

        <label>Product Name</label>
        <input type="text" name="product_name" required value="<?php echo $editRow['ProductName'] ?? ''; ?>">

        <label>Category</label>
        <select name="category" required>
            <option value="">-- Select Category --</option>
            <option value="Pant" <?php if (($editRow['Category'] ?? '') == 'Pant') echo 'selected'; ?>>Pant</option>
            <option value="Shirt" <?php if (($editRow['Category'] ?? '') == 'Shirt') echo 'selected'; ?>>Shirt</option>
            <option value="t-Shirt" <?php if (($editRow['Category'] ?? '') == 't-Shirt') echo 'selected'; ?>>t-Shirt</option>
            <option value="Hoodie" <?php if (($editRow['Category'] ?? '') == ' Hoodie') echo 'selected'; ?>>Hoodie</option>
            <option value="Shorts" <?php if (($editRow['Category'] ?? '') == ' Shorts') echo 'selected'; ?>>Shorts</option>

        </select>

        <label>Quantity</label>
        <input type="number" name="quantity" required value="<?php echo $editRow['Quantity'] ?? ''; ?>">

        <label>Price (₹)</label>
        <input type="number" name="price" required value="<?php echo $editRow['Price'] ?? ''; ?>">

        <label>Description</label>
        <textarea name="desc" rows="4"><?php echo $editRow['Description'] ?? ''; ?></textarea>

        <label>Product Image <?php if (isset($editRow['ImagePath'])) echo "(leave empty to keep current image)"; ?></label>
        <input type="file" name="img">

        <input type="submit" name="upload" value="<?php echo isset($editRow) ? "Update Item" : "Upload Item"; ?>">
    </form>

    <!-- Products Table -->
    <h2 style="margin-top: 40px;">📦 All Clothing Items</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>₹ Price</th>
            <th>Image</th>
            <th>Actions</th>
        </tr>
        <?php
        $query = sqlsrv_query($conn, "SELECT * FROM ClothingItems");
        while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>
                <td>{$row['ID']}</td>
                <td>{$row['ProductName']}</td>
                <td>{$row['Category']}</td>
                <td>{$row['Quantity']}</td>
                <td>{$row['Price']}</td>
                <td><img src='{$row['ImagePath']}' width='60'></td>
                <td>
                    <a href='admin_upload.php?edit={$row['ID']}' class='btn edit-btn'>Edit</a>
                    <a href='admin_upload.php?delete={$row['ID']}' class='btn delete-btn' onclick=\"return confirm('Delete this item?')\">Delete</a>
                </td>
            </tr>";
        }
        ?>
    </table>
</div>

</body>
</html>
