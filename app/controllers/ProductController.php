<?php
require_once __DIR__ . '/../models/Product.php';

/**
 * app/controllers/ProductController.php
 * Business logic for posting/editing listings — validation, image handling,
 * then delegating the actual save to the Product model.
 */
class ProductController {

    private static function validate($title, $categoryId, $price) {
        $errors = [];
        if (empty($title) || empty($categoryId) || empty($price)) {
            $errors[] = "Title, category, and price are required.";
        }
        if (strlen($title) > 150) {
            $errors[] = "Title must be under 150 characters.";
        }
        if (!is_numeric($price) || $price < 0) {
            $errors[] = "Please enter a valid price.";
        }
        return $errors;
    }

    /** Validates + moves an uploaded image. Returns [filename or null, error or null]. */
    private static function handleUpload($file, $uploadDir) {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return [null, null]; // no file uploaded — not an error, image stays optional
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return [null, "Only JPG, PNG, or WEBP images are allowed."];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return [null, "Image must be under 5MB."];
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('item_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $imageName)) {
            return [null, "Failed to upload image. Please try again."];
        }
        return [$imageName, null];
    }

    public static function create($sellerId, $data, $file, $uploadDir) {
        $title = trim($data['title'] ?? '');
        $categoryId = $data['category_id'] ?? '';
        $description = trim($data['description'] ?? '');
        $price = $data['price'] ?? '';
        $condition = $data['item_condition'] ?? 'used';
        $listingType = $data['listing_type'] ?? 'sell';

        $errors = self::validate($title, $categoryId, $price);

        [$imageName, $uploadError] = self::handleUpload($file, $uploadDir);
        if ($uploadError) {
            $errors[] = $uploadError;
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        Product::create($sellerId, $categoryId, $title, $description, $price, $condition, $listingType, $imageName);
        return ['success' => true, 'redirect' => 'my_listings.php', 'flash' => 'Item posted successfully!'];
    }

    public static function update($productId, $existingProduct, $data, $file, $uploadDir) {
        $title = trim($data['title'] ?? '');
        $categoryId = $data['category_id'] ?? '';
        $description = trim($data['description'] ?? '');
        $price = $data['price'] ?? '';
        $condition = $data['item_condition'] ?? 'used';
        $listingType = $data['listing_type'] ?? 'sell';

        $errors = self::validate($title, $categoryId, $price);

        $imageName = $existingProduct['image']; // keep existing image unless a new one is uploaded
        if (!empty($file) && $file['error'] === UPLOAD_ERR_OK) {
            [$newImageName, $uploadError] = self::handleUpload($file, $uploadDir);
            if ($uploadError) {
                $errors[] = $uploadError;
            } elseif ($newImageName) {
                if ($existingProduct['image'] && file_exists($uploadDir . '/' . $existingProduct['image'])) {
                    unlink($uploadDir . '/' . $existingProduct['image']);
                }
                $imageName = $newImageName;
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        Product::update($productId, $title, $categoryId, $description, $price, $condition, $listingType, $imageName);
        return ['success' => true, 'redirect' => 'my_listings.php', 'flash' => 'Item updated successfully!'];
    }
}
?>
