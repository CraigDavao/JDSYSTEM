<?php
session_start();
require_once __DIR__ . '/../connection/connection.php';

// Allowed lists (server-side validation)
$allowedGroups  = ['kid','baby','newborn'];
$allowedGenders = ['girls','boys','unisex'];
$allowedSubs    = [
  'sets','tops','bottoms','sleepwear','dresses-jumpsuits',
  'accessories','bodysuits','essentials' // add more as needed
];

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $category_group = $_POST['category_group'] ?? 'kid';
    $gender = $_POST['gender'] ?? null;
    $subcategory = $_POST['subcategory'] ?? null;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $is_active = isset($_POST['is_active']) ? 1 : 1;

    // validate
    if ($name === '') $errors[] = "Name is required.";
    if (!in_array($category_group, $allowedGroups)) $errors[] = "Invalid category group.";
    if ($gender !== null && !in_array($gender, $allowedGenders)) $errors[] = "Invalid gender.";
    if ($subcategory !== null && $subcategory !== '' && !in_array($subcategory, $allowedSubs)) $errors[] = "Invalid subcategory.";

    // handle image upload
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
        // Build legacy category string (keeps old pages working)
        // e.g. kid + girls -> 'girls', baby + girls -> 'baby-girls'
        if ($category_group === 'kid' && $gender === 'girls') $category = 'girls';
        elseif ($category_group === 'kid' && $gender === 'boys') $category = 'boys';
        elseif ($category_group === 'baby' && $gender === 'girls') $category = 'baby-girls';
        elseif ($category_group === 'baby' && $gender === 'boys') $category = 'baby-boys';
        else $category = ($category_group === 'newborn' ? 'newborn' : ($gender ?: $category_group));

        // Insert
        $sql = "INSERT INTO products (name, category, price, image, category_group, gender, subcategory, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            // bind types: s = string, d = double, i = integer
            $stmt->bind_param("ssdssssi",
                $name,
                $category,
                $price,
                $image,
                $category_group,
                $gender,
                $subcategory,
                $is_active
            );

            if ($stmt->execute()) {
                $success = "Product added successfully.";
                // reset form values (optional)
                $name = $price = $image = '';
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
        <!-- default list; JS updates options based on gender -->
        <option value="sets">Sets</option>
        <option value="tops">Tops</option>
        <option value="bottoms">Bottoms</option>
        <option value="sleepwear">Sleepwear & Underwear</option>
        <option value="dresses-jumpsuits">Dresses & Jumpsuits</option>
        <option value="accessories">Accessories</option>
        <option value="bodysuits">Bodysuits & Sleepsuits</option>
        <option value="essentials">Essentials</option>
      </select>
    </div>

    <div>
      <label for="price">Price (₱)</label>
      <input id="price" name="price" type="number" step="0.01" required value="<?= htmlspecialchars($price ?? '') ?>">
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
    // UX: show reasonable subcategory options depending on gender
    const subMap = {
      girls: ['sets','tops','bottoms','sleepwear','dresses-jumpsuits','accessories'],
      boys:  ['sets','tops','bottoms','sleepwear','accessories'],
      unisex: ['sets','tops','bottoms','sleepwear','accessories','essentials','bodysuits']
    };

    const genderSelect = document.getElementById('gender');
    const subSelect = document.getElementById('subcategory');

    function populateSubs(forGender){
      // keep current selection if still available
      const current = subSelect.value;
      // clear
      subSelect.innerHTML = '<option value="">-- choose subcategory --</option>';
      const list = subMap[forGender] || ['sets','tops','bottoms','sleepwear','dresses-jumpsuits','accessories','bodysuits','essentials'];
      list.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        // make readable label:
        opt.text = s.replace(/-/g,' ').replace(/\b\w/g, l => l.toUpperCase());
        if (s === current) opt.selected = true;
        subSelect.appendChild(opt);
      });
    }

    genderSelect.addEventListener('change', () => {
      const g = genderSelect.value;
      populateSubs(g);
    });

    // initialize on page load if a gender is selected
    (function(){
      const g = genderSelect.value;
      if (g) populateSubs(g);
    })();
  </script>
</body>
</html>
