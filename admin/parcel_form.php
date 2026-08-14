<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$parcelId = int_or_null($_GET['id'] ?? null);
$errors = [];
$parcel = [
    'customer_name' => '',
    'customer_phone' => '',
    'customer_address' => '',
    'customer_latitude' => '',
    'customer_longitude' => '',
    'notes' => '',
    'status' => 'Pending',
    'assigned_rider_id' => null,
];

if ($parcelId) {
    $stmt = db()->prepare('SELECT * FROM parcels WHERE id = ? LIMIT 1');
    $stmt->execute([$parcelId]);
    $parcel = $stmt->fetch() ?: $parcel;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $parcel = [
        'customer_name' => trim((string) ($_POST['customer_name'] ?? '')),
        'customer_phone' => trim((string) ($_POST['customer_phone'] ?? '')),
        'customer_address' => trim((string) ($_POST['customer_address'] ?? '')),
        'customer_latitude' => $_POST['customer_latitude'] !== '' ? (float) $_POST['customer_latitude'] : null,
        'customer_longitude' => $_POST['customer_longitude'] !== '' ? (float) $_POST['customer_longitude'] : null,
        'notes' => trim((string) ($_POST['notes'] ?? '')),
        'status' => in_array($_POST['status'] ?? 'Pending', ['Pending', 'Out for Delivery', 'Delivered', 'Failed Delivery'], true) ? $_POST['status'] : 'Pending',
        'assigned_rider_id' => int_or_null($_POST['assigned_rider_id'] ?? null),
    ];

    if ($parcel['customer_name'] === '' || $parcel['customer_address'] === '') {
        $errors[] = 'Customer name and address are required.';
    }

    if (!$errors) {
        if ($parcelId) {
            $stmt = db()->prepare('UPDATE parcels SET customer_name = ?, customer_phone = ?, customer_address = ?, customer_latitude = ?, customer_longitude = ?, notes = ?, status = ?, assigned_rider_id = ? WHERE id = ?');
            $stmt->execute([$parcel['customer_name'], $parcel['customer_phone'], $parcel['customer_address'], $parcel['customer_latitude'], $parcel['customer_longitude'], $parcel['notes'], $parcel['status'], $parcel['assigned_rider_id'], $parcelId]);
            flash('success', 'Parcel updated.');
        } else {
            $tracking = random_tracking_number();
            $stmt = db()->prepare('INSERT INTO parcels (tracking_number, customer_name, customer_phone, customer_address, customer_latitude, customer_longitude, notes, status, assigned_rider_id, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$tracking, $parcel['customer_name'], $parcel['customer_phone'], $parcel['customer_address'], $parcel['customer_latitude'], $parcel['customer_longitude'], $parcel['notes'], $parcel['status'], $parcel['assigned_rider_id'], current_user()['id']]);
            flash('success', 'Parcel created with tracking number ' . $tracking . '.');
        }
        redirect('admin/parcels.php');
    }
    set_old($parcel);
}

$riders = db()->query('SELECT r.id, u.name FROM riders r INNER JOIN users u ON u.id = r.user_id ORDER BY u.name ASC')->fetchAll();
$pageTitle = $parcelId ? 'Edit Parcel' : 'Create Parcel';
include __DIR__ . '/../includes/header.php';
?>

<style>
/* 自动搜索下拉框样式强化 */
.autocomplete-container {
    position: relative;
    width: 100%;
}
.autocomplete-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    z-index: 9999 !important; /* 确保强行浮在最上层 */
    max-height: 250px;
    overflow-y: auto;
    display: none;
    margin-top: 4px;
}
.suggestion-item {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.4;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}
.suggestion-item:last-child {
    border-bottom: none;
}
.suggestion-item:hover {
    background-color: #f1f5f9;
    color: #2563eb;
}
</style>

<div class="panel">
    <?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Customer Name<input type="text" name="customer_name" value="<?= e(old('customer_name', $parcel['customer_name'] ?? '')) ?>" required></label>
        <label>Customer Phone<input type="text" name="customer_phone" value="<?= e(old('customer_phone', $parcel['customer_phone'] ?? '')) ?>"></label>
        
        <label>Delivery Address
            <div class="autocomplete-container" style="margin-top: 4px;">
                <input type="text" id="customer_address" name="customer_address" value="<?= e(old('customer_address', $parcel['customer_address'] ?? '')) ?>" placeholder="Type an address (e.g. sunway, klcc...)" autocomplete="off" required style="width: 100%;">
                <div id="address_suggestions" class="autocomplete-suggestions"></div>
            </div>
        </label>

        <div class="card-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <label>Latitude<input type="text" id="customer_latitude" name="customer_latitude" value="<?= e((string) old('customer_latitude', (string) ($parcel['customer_latitude'] ?? ''))) ?>"></label>
            <label>Longitude<input type="text" id="customer_longitude" name="customer_longitude" value="<?= e((string) old('customer_longitude', (string) ($parcel['customer_longitude'] ?? ''))) ?>"></label>
        </div>

        <label>Status
            <select name="status">
                <?php foreach (['Pending', 'Out for Delivery', 'Delivered', 'Failed Delivery'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= (($parcel['status'] ?? '') === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Assign Rider
            <select name="assigned_rider_id">
                <option value="">Unassigned</option>
                <?php foreach ($riders as $rider): ?>
                    <option value="<?= (int) $rider['id'] ?>" <?= ((string) ($parcel['assigned_rider_id'] ?? '') === (string) $rider['id']) ? 'selected' : '' ?>><?= e($rider['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Notes<textarea name="notes"><?= e(old('notes', $parcel['notes'] ?? '')) ?></textarea></label>

        <div class="inline-actions">
            <button class="primary-button" type="submit"><?= $parcelId ? 'Update Parcel' : 'Save Parcel' ?></button>
            <a class="secondary-button" href="<?= url('admin/parcels.php') ?>">Back</a>
        </div>
    </form>
</div>

<!-- 自动搜索建议 (Autocomplete) 交互逻辑 -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const addressInput = document.getElementById('customer_address');
    const suggestionsBox = document.getElementById('address_suggestions');
    const latInput = document.getElementById('customer_latitude');
    const lngInput = document.getElementById('customer_longitude');

    let debounceTimer;

    addressInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = addressInput.value.trim();

        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        // 显示搜索中状态提示
        suggestionsBox.innerHTML = '<div class="suggestion-item" style="color:#64748b;">Searching location...</div>';
        suggestionsBox.style.display = 'block';

        debounceTimer = setTimeout(() => {
            // 使用 Photon API（基于 OpenStreetMap 的免费高速地名搜索）
            fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    
                    if (!data.features || data.features.length === 0) {
                        suggestionsBox.innerHTML = '<div class="suggestion-item" style="color:#94a3b8;">No results found</div>';
                        return;
                    }

                    data.features.forEach(feature => {
                        const props = feature.properties;
                        const coords = feature.geometry.coordinates; // [lng, lat]

                        // 拼凑地名描述
                        const name = props.name || '';
                        const street = props.street || '';
                        const city = props.city || props.town || props.state || '';
                        const country = props.country || '';
                        
                        let fullAddress = [name, street, city, country].filter(Boolean).join(', ');

                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.textContent = fullAddress;

                        div.addEventListener('click', () => {
                            addressInput.value = fullAddress;
                            latInput.value = parseFloat(coords[1]).toFixed(6); // lat
                            lngInput.value = parseFloat(coords[0]).toFixed(6); // lng
                            suggestionsBox.style.display = 'none';
                        });

                        suggestionsBox.appendChild(div);
                    });

                    suggestionsBox.style.display = 'block';
                })
                .catch(err => {
                    console.error('API Error:', err);
                    suggestionsBox.innerHTML = '<div class="suggestion-item" style="color:#ef4444;">Unable to search address</div>';
                });
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!addressInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>