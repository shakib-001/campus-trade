<?php
require_once __DIR__ . '/../models/ProductRequest.php';

/**
 * app/controllers/RequestController.php
 * Business logic for the buy/rent request workflow: sending, accepting,
 * rejecting, completing, and cancelling requests.
 */
class RequestController {

    public static function send($productId, $buyerId, $meetupLocation, $meetupTime) {
        $meetupLocation = trim($meetupLocation);

        if (empty($meetupLocation) || empty($meetupTime)) {
            return ['success' => false, 'errors' => ["Please provide both a meetup location and time."]];
        }

        if (ProductRequest::hasPendingRequest($productId, $buyerId)) {
            return ['success' => false, 'errors' => ["You already have a pending request for this item."]];
        }

        ProductRequest::create($productId, $buyerId, $meetupLocation, $meetupTime);
        return ['success' => true, 'redirect' => 'my_requests.php', 'flash' => 'Request sent! The seller will review it.'];
    }

    /** Handles accept/reject/complete — verifies the request belongs to this seller first. */
    public static function updateStatus($requestId, $sellerId, $action) {
        $req = ProductRequest::belongsToSeller($requestId, $sellerId);

        if (!$req) {
            return ['success' => false, 'flash' => null]; // silently ignore — not this seller's request
        }

        if ($action === 'accept' && $req['status'] === 'pending') {
            ProductRequest::accept($requestId, $req['product_id']);
            return ['success' => true, 'flash' => 'Request accepted.'];
        }
        if ($action === 'reject' && $req['status'] === 'pending') {
            ProductRequest::reject($requestId);
            return ['success' => true, 'flash' => 'Request rejected.'];
        }
        if ($action === 'complete' && $req['status'] === 'accepted') {
            ProductRequest::complete($requestId, $req['product_id']);
            return ['success' => true, 'flash' => 'Transaction completed! Item marked as sold.'];
        }

        return ['success' => false, 'flash' => null]; // action didn't match the request's current status
    }

    public static function cancel($requestId, $buyerId) {
        ProductRequest::cancel($requestId, $buyerId);
        return ['success' => true, 'flash' => 'Request cancelled.'];
    }
}
?>
