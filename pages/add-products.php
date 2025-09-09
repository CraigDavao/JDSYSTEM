<?php 
session_start(); 
require_once __DIR__ . '/../connection/connection.php';  

$allowedGroups  = ['kid','baby','newborn','accessories']; 
$allowedGenders = ['girls','boys','unisex']; 
$allowedSubs    = [
    // kid + baby
    'sets','tops','bottoms','sleepwear','dresses-jumpsuits','accessories',
    // newborn
    'bodysuits','essentials','sets',
    // accessories main
    'hair-accessories','bags-hats','bow-ties','toys-gifts'
];

$errors = []; 
$success = '';  

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $category_group = $_POST['category_group'] ?? 'kid';
    $gender = $_POST['gender'] ?? null;
    $subcategory = $_POST['subcategory'] ?? null;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $sale_price = isset($_POST['sale_price']) ? (float)$_POST['sale_price'] : null; // new
    $is_active = isset($_POST['is_active']) ? 1 : 1;

    if ($name === '') $errors[] = "Name is required.";
    if (!in_array($category_group, $allowedGroups)) $errors[] = "Invalid category group.";
    
    // Accessories are always unisex
    if($category_group === 'accessories') $gender = 'unisex';

    if ($gender !== null && $gender !== '' && !in_array($gender, $allowedGenders)) $errors[] = "Invalid gender.";
    if ($subcategory !== null && $subcategory !== '' && !in_array($subcategory, $allowedSubs)) $errors[] = "Invalid subcategory.";
    
    // sale price cannot be higher than regular price
    if($sale_price !== null && $sale_price >= $price) $errors[] = "Sale price must be less than regular price.";

    $image = null; 
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . '/../uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);
        $targetFile = $targetDir . $image;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $errors[] = "Failed to upload image.";
            $image = null;
        }
    }

    if (empty($errors)) {
        if ($category_group === 'kid' && $gender === 'girls') $category = 'girls';
        elseif ($category_group === 'kid' && $gender === 'boys') $category = 'boys';
        elseif ($category_group === 'baby' && $gender === 'girls') $category = 'baby-girls';
        elseif ($category_group === 'baby' && $gender === 'boys') $category = 'baby-boys';
        elseif ($category_group === 'newborn') $category = 'newborn';
        elseif ($category_group === 'accessories') $category = 'accessories';
        else $category = $gender ?: $category_group;

        $sql = "INSERT INTO products (name, category, price, sale_price, image, category_group, gender, subcategory, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("ssddssssi",
                $name,
                $category,
                $price,
                $sale_price,
                $image,
                $category_group,
                $gender,
                $subcategory,
                $is_active
            );

            if ($stmt->execute()) {
                $success = "Product added successfully.";
                $name = $price = $sale_price = $image = '';
                $category_group = 'kid';
                $gender = $subcategory = '';
            } else {
                $errors[] = "Execute failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Add Product</title>
<style>
body { font-family: Arial, sans-serif; margin: 40px; background:#fff; color:#111; }
form { max-width:760px; margin: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
label { display:block; font-weight:600; margin-bottom:6px; }
.full { grid-column: 1 / -1; }
input, select { padding:10px; border:1px solid #ccc; border-radius:6px; width:100%; box-sizing:border-box; }
button { padding:12px 16px; background:#111; color:#fff; border:none; border-radius:6px; cursor:pointer; }
.notes { font-size:13px; color:#666; margin-top:6px; }
.msg { padding:10px; border-radius:6px; margin-bottom:12px; }
.err { background:#ffecec; color:#b00000; border:1px solid #f5c6c6; }
.ok  { background:#e6ffed; color:#066; border:1px solid #bfe7c2; }
</style>
</head>

<body>
<h2>Add New Product</h2>

<?php if (!empty($errors)): ?>
<div class="msg err full">
    <ul>
    <?php foreach($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="msg ok full"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="full">
        <label for="name">Product Name</label>
        <input id="name" name="name" type="text" required value="<?= htmlspecialchars($name ?? '') ?>">
    </div>

    <div>
        <label for="category_group">Category Group</label>
        <select id="category_group" name="category_group" required>
            <option value="kid" <?= (isset($category_group) && $category_group==='kid') ? 'selected' : '' ?>>Kid</option>
            <option value="baby" <?= (isset($category_group) && $category_group==='baby') ? 'selected' : '' ?>>Baby</option>
            <option value="newborn" <?= (isset($category_group) && $category_group==='newborn') ? 'selected' : '' ?>>Newborn</option>
            <option value="accessories" <?= (isset($category_group) && $category_group==='accessories') ? 'selected' : '' ?>>Accessories</option>
        </select>
    </div>

    <div>
        <label for="gender">Gender</label>
        <select id="gender" name="gender">
            <option value="">-- none / unisex --</option>
            <option value="girls" <?= (isset($gender) && $gender==='girls') ? 'selected' : '' ?>>Girls</option>
            <option value="boys"  <?= (isset($gender) && $gender==='boys')  ? 'selected' : '' ?>>Boys</option>
            <option value="unisex" <?= (isset($gender) && $gender==='unisex') ? 'selected' : '' ?>>Unisex</option>
        </select>
    </div>

    <div>
        <label for="subcategory">Subcategory</label>
        <select id="subcategory" name="subcategory">
            <option value="">-- choose subcategory --</option>
        </select>
    </div>

    <div>
        <label for="price">Price (₱)</label>
        <input id="price" name="price" type="number" step="0.01" required value="<?= htmlspecialchars($price ?? '') ?>">
    </div>

    <div>
        <label for="sale_price">Sale Price (₱, optional)</label>
        <input id="sale_price" name="sale_price" type="number" step="0.01" value="<?= htmlspecialchars($sale_price ?? '') ?>">
        <div class="notes">Leave empty if not on sale. Must be less than regular price.</div>
    </div>

    <div>
        <label for="image">Image (optional)</label>
        <input id="image" name="image" type="file" accept="image/*">
        <div class="notes">Uploaded file will be saved to <code>/uploads/</code>.</div>
    </div>

    <div class="full">
        <label>
            <input type="checkbox" name="is_active" value="1" <?= (isset($is_active) && $is_active==1) ? 'checked' : 'checked' ?>>
            Active (visible on site)
        </label>
    </div>

    <div class="full">
        <button type="submit">Add Product</button>
    </div>
</form>

<script>
const subMap = {
    kid: {
        girls: ['sets','tops','bottoms','sleepwear','dresses-jumpsuits','accessories'],
        boys:  ['sets','tops','bottoms','sleepwear','accessories'],
        unisex:['sets','tops','bottoms','sleepwear','accessories']
    },
    baby: {
        girls: ['sets','tops','bottoms','sleepwear','dresses-jumpsuits','accessories'],
        boys:  ['sets','tops','bottoms','sleepwear','accessories'],
        unisex:['sets','tops','bottoms','sleepwear','dresses-jumpsuits','accessories']
    },
    newborn: {
        unisex: ['bodysuits','essentials','sets']
    },
    accessories: {
        unisex: ['hair-accessories','bags-hats','bow-ties','toys-gifts']
    }
};

const groupSelect = document.getElementById('category_group');
const genderSelect = document.getElementById('gender');
const subSelect = document.getElementById('subcategory');

function populateSubs() {
    const group = groupSelect.value;
    let gender = genderSelect.value || 'unisex';

    if(group === 'accessories') gender = 'unisex';

    const list = (subMap[group] && subMap[group][gender]) ? subMap[group][gender] : [];

    const current = subSelect.value;
    subSelect.innerHTML = '<option value="">-- choose subcategory --</option>';
    list.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.text = s.replace(/-/g,' ').replace(/\b\w/g, l => l.toUpperCase());
        if(s === current) opt.selected = true;
        subSelect.appendChild(opt);
    });
}

groupSelect.addEventListener('change', populateSubs);
genderSelect.addEventListener('change', populateSubs);
window.addEventListener('DOMContentLoaded', populateSubs);
</script>
</body>
</html>
