<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'food';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_errno) {
    die("Connect failed: " . $conn->connect_error);
}


$type_result = $conn->query("SELECT type_id, type_name FROM type ORDER BY type_name ASC");
$types = [];
while ($row = $type_result->fetch_assoc()) {
    $types[] = $row;
}
$type_result->free();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menu_name = trim($_POST['menu_name'] ?? '');
    $menu_price = isset($_POST['menu_price']) && is_numeric($_POST['menu_price']) ? floatval($_POST['menu_price']) : 0;
    $type_id = trim($_POST['type_id'] ?? '');
    $menu_id = trim($_POST['menu_id'] ?? '');

    if ($menu_name && $type_id) {
        if ($menu_id) {
            
            $stmt = $conn->prepare("UPDATE menu SET menu_name=?, menu_price=?, type_id=? WHERE menu_id=?");
            $stmt->bind_param("sdss", $menu_name, $menu_price, $type_id, $menu_id);
        } else {
           
            $stmt = $conn->prepare("INSERT INTO menu (menu_id, menu_name, menu_price, type_id) VALUES (?, ?, ?, ?)");
            
            $new_id = 'M' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt->bind_param("ssds", $new_id, $menu_name, $menu_price, $type_id);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}


if (isset($_GET['delete'])) {
    $menu_id = trim($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM menu WHERE menu_id=?");
    $stmt->bind_param("s", $menu_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}


$editing = null;
if (isset($_GET['edit'])) {
    $menu_id = trim($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM menu WHERE menu_id=?");
    $stmt->bind_param("s", $menu_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editing = $res->fetch_assoc();
    $stmt->close();
}


$result = $conn->query("SELECT m.*, t.type_name 
                        FROM menu m 
                        LEFT JOIN type t ON m.type_id = t.type_id
                        ORDER BY menu_id DESC");
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Menu CRUD</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #ddd; padding: 8px; }
th { background: #f2f2f2; }
form { margin-top: 20px; border: 1px solid #ddd; padding: 10px; }
input, select { padding: 6px; margin: 5px 0; width: 100%; }
button { padding: 6px 10px; }
a { padding: 4px 8px; border: 1px solid #999; margin: 0 2px; text-decoration: none; }
</style>
</head>
<body>
<h1>เมนูอาหาร</h1>

<h2><?= $editing ? "แก้ไขเมนู" : "เพิ่มเมนูใหม่" ?></h2>
<form method="post">
    <input type="hidden" name="menu_id" value="<?= $editing ? $editing['menu_id'] : '' ?>">
    <label>ชื่อเมนู:
        <input type="text" name="menu_name" value="<?= $editing ? htmlspecialchars($editing['menu_name']) : '' ?>" required>
    </label>
    <label>ราคา:
        <input type="number" step="0.01" name="menu_price" value="<?= $editing ? htmlspecialchars($editing['menu_price']) : '' ?>" required>
    </label>
    <label>ประเภท:
        <select name="type_id" required>
            <option value="">-- เลือกประเภท --</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= $t['type_id'] ?>" <?= $editing && $editing['type_id'] == $t['type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['type_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit"><?= $editing ? "บันทึก" : "เพิ่ม" ?></button>
    <?php if ($editing): ?>
        <a href="<?= $_SERVER['PHP_SELF'] ?>">ยกเลิก</a>
    <?php endif; ?>
</form>

<h2>รายการเมนู</h2>
<h3><a href="home.html">กลับสู่หน้าแรก</a></h3>
<table>
<tr>
    <th>รหัสเมนู</th>
    <th>ชื่อเมนู</th>
    <th>ราคา</th>
    <th>ประเภท</th>
    <th>จัดการ</th>
</tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['menu_id'] ?></td>
    <td><?= htmlspecialchars($row['menu_name']) ?></td>
    <td><?= number_format($row['menu_price'], 2) ?></td>
    <td><?= htmlspecialchars($row['type_name']) ?></td>
    <td>
        <a href="?edit=<?= $row['menu_id'] ?>">แก้ไข</a>
        <a href="?delete=<?= $row['menu_id'] ?>" onclick="return confirm('ลบเมนูนี้หรือไม่?')">ลบ</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
<?php
$result->free();
$conn->close();
?>
