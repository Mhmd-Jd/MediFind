<?php
// Include database connection
require_once 'db_connect.php';
session_start(); // Start session to access pharmacy info

// Check if pharmacy is logged in; otherwise, stop script execution
if (!isset($_SESSION['pharmacy_id'])) {
    die("⚠ Session not set. Please log in as a pharmacy.");
}

// Get the currently logged-in pharmacy's ID from session
$pharmacy_id = $_SESSION['pharmacy_id'];

// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form input data
    $medicine_name = $_POST['medicine_name'] ?? '';
    $quantity_in_stock = $_POST['quantity_in_stock'] ?? '';
    $price = $_POST['price'] ?? '';
    $formulation = $_POST['formulation'] ?? '';
    $generic = $_POST['generic'] ?? '';
    // Handle multiple checkbox input for side effects, convert to comma-separated string
    $sideEffects = isset($_POST['sideEffects']) ? implode(', ', $_POST['sideEffects']) : '';
    // Checkbox for delivery availability, default to 0 (false) if not checked
    $deliver_available = isset($_POST['deliver_available']) ? 1 : 0;

    // Handle image upload
    $target_dir = "uploads/"; // Target folder for uploaded images
    $image = $_FILES['image']['name']; // Get uploaded file name
    $target_file = $target_dir . basename($image); // Full path to save the image

    // Move uploaded file to target directory
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        // Insert into medicines table
        $sql = "INSERT INTO medicines 
                (medicine_name, quantity_in_stock, price, formulation, generic, sideEffects, deliver_available, image, pharmacy_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Use prepared statement to prevent SQL injectiom
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdssssisi", $medicine_name, $quantity_in_stock, $price, $formulation, $generic, $sideEffects, $deliver_available, $target_file, $pharmacy_id);

        if ($stmt->execute()) {
            $message = "✅ Medicine added successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
    } else {
        $message = "❌ Failed to upload image.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medicine | MediFind</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: url('images/addmedicine_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.03)"/><circle cx="20" cy="80" r="0.5" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(1deg);
            }
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 40px;
            border-radius: 24px;
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            border-radius: 24px 24px 0 0;
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {

            0%,
            100% {
                background-position: 0% 0%;
            }

            50% {
                background-position: 100% 0%;
            }
        }

        .form-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .form-header h2 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            position: relative;
        }

        .form-header p {
            color: #6b7280;
            font-size: 16px;
            font-weight: 400;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 24px;
            position: relative;
        }

        .input-group.full-width {
            grid-column: 1 / -1;
        }

        .input-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group label i {
            color: #667eea;
            font-size: 16px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            font-size: 15px;
            font-weight: 500;
            color: #374151;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .input-group input:focus,
        .input-group textarea:focus,
        .input-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .input-group input:hover:not(:focus),
        .input-group textarea:hover:not(:focus),
        .input-group select:hover:not(:focus) {
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6b7280;
            font-weight: 500;
        }

        .file-input-label:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            color: #667eea;
        }

        .file-input-label i {
            font-size: 24px;
        }

        .side-effects-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }

        .side-effect-row {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            align-items: center;
        }

        .side-effect-row input {
            flex: 1;
            margin-bottom: 0;
        }

        .side-effect-btn {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .add-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .remove-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .remove-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkbox-wrapper:hover {
            background: rgba(102, 126, 234, 0.05);
            border-color: #667eea;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #667eea;
            cursor: pointer;
        }

        .checkbox-wrapper label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            color: #374151;
        }

        .buttons {
            display: flex;
            gap: 16px;
            margin-top: 35px;
        }

        .btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .save-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .cancel-btn {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }

        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            text-align: center;
            position: relative;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .message.error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Custom scrollbar */
        .form-container::-webkit-scrollbar {
            width: 6px;
        }

        .form-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .form-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 3px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .form-container {
                padding: 30px 25px;
                max-height: 95vh;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-header h2 {
                font-size: 28px;
            }

            .buttons {
                flex-direction: column;
            }

            .side-effect-row {
                flex-wrap: wrap;
            }

            .side-effect-btn {
                width: 40px;
                height: 40px;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 20px 15px;
            }

            .form-header h2 {
                font-size: 24px;
            }

            .input-group input,
            .input-group textarea,
            .input-group select {
                padding: 14px 16px;
                font-size: 14px;
            }
        }

        /* Loading animation for form submission */
        .loading .btn {
            position: relative;
            color: transparent;
        }

        .loading .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-pills"></i> Add Medicine</h2>
            <p>Fill in the details to add a new medicine to your inventory</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="add_medicine.php" method="post" enctype="multipart/form-data" id="medicineForm">
            <div class="form-grid">
                <div class="input-group">
                    <label><i class="fas fa-capsules"></i> Medicine Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="medicine_name" required placeholder="Enter medicine name">
                    </div>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-box"></i> Quantity in Stock</label>
                    <div class="input-wrapper">
                        <input type="number" name="quantity_in_stock" required placeholder="0" min="0">
                    </div>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-dollar-sign"></i> Price</label>
                    <div class="input-wrapper">
                        <input type="text" name="price" required placeholder="0.00">
                    </div>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-flask"></i> Formulation</label>
                    <div class="input-wrapper">
                        <input type="text" name="formulation" required placeholder="e.g., Tablet, Syrup, Injection">
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label><i class="fas fa-dna"></i> Generic Name</label>
                <div class="input-wrapper">
                    <input type="text" name="generic" required placeholder="Enter generic name">
                </div>
            </div>

            <div class="input-group full-width">
                <label><i class="fas fa-exclamation-triangle"></i> Side Effects</label>
                <div class="side-effects-container">
                    <div id="sideEffectsWrapper">
                        <div class="side-effect-row">
                            <input type="text" name="sideEffects[]" placeholder="Enter side effect" required>
                            <button type="button" class="side-effect-btn add-btn" onclick="addSideEffectField()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label><i class="fas fa-truck"></i> Delivery Options</label>
                <div class="checkbox-wrapper">
                    <input type="checkbox" name="deliver_available" id="delivery">
                    <label for="delivery">Delivery Available</label>
                </div>
            </div>

            <div class="input-group full-width">
                <label><i class="fas fa-image"></i> Upload Image</label>
                <div class="file-input-wrapper">
                    <input type="file" name="image" id="imageUpload" required accept="image/*">
                    <label for="imageUpload" class="file-input-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Click to upload image or drag and drop</span>
                    </label>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="btn save-btn">
                    <i class="fas fa-save"></i> Save Medicine
                </button>
                <button type="button" class="btn cancel-btn" onclick="window.location.href='pharmacy_dashboard.php';">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>

    <script>
        function addSideEffectField() {
            const wrapper = document.getElementById('sideEffectsWrapper');

            const newField = document.createElement('div');
            newField.className = 'side-effect-row';

            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'sideEffects[]';
            input.placeholder = 'Enter side effect';
            input.required = true;

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'side-effect-btn remove-btn';
            removeButton.innerHTML = '<i class="fas fa-minus"></i>';
            removeButton.onclick = function () {
                wrapper.removeChild(newField);
            };

            newField.appendChild(input);
            newField.appendChild(removeButton);
            wrapper.appendChild(newField);

            // Add animation
            newField.style.opacity = '0';
            newField.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                newField.style.transition = 'all 0.3s ease';
                newField.style.opacity = '1';
                newField.style.transform = 'translateY(0)';
            }, 10);
        }

        // File upload preview
        document.getElementById('imageUpload').addEventListener('change', function (e) {
            const label = document.querySelector('.file-input-label span');
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                label.textContent = `Selected: ${fileName}`;
                label.parentElement.style.borderColor = '#10b981';
                label.parentElement.style.background = 'rgba(16, 185, 129, 0.05)';
                label.parentElement.style.color = '#10b981';
            }
        });

        // Form submission animation
        document.getElementById('medicineForm').addEventListener('submit', function () {
            document.querySelector('.save-btn').classList.add('loading');
        });

        // Add input animations
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function () {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Drag and drop for file upload
        const fileLabel = document.querySelector('.file-input-label');
        const fileInput = document.getElementById('imageUpload');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileLabel.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileLabel.style.borderColor = '#667eea';
            fileLabel.style.background = 'rgba(102, 126, 234, 0.1)';
        }

        function unhighlight(e) {
            fileLabel.style.borderColor = '#d1d5db';
            fileLabel.style.background = '#f9fafb';
        }

        fileLabel.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;

            const fileName = files[0]?.name;
            if (fileName) {
                document.querySelector('.file-input-label span').textContent = `Selected: ${fileName}`;
            }
        }
    </script>
</body>

</html>