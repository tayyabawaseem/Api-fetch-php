<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Validate input
    $errors = [];
    $pickup_location = $_POST['pickup_location'] ?? '';
    $dropoff_location = $_POST['dropoff_location'] ?? '';
    $stop = $_POST['stop'] ?? '';
    $pickup_date = $_POST['pickup_date'] ?? '';
    $pickup_time = $_POST['pickup_time'] ?? '';
    $return_trip = isset($_POST['return_trip']) ? true : false;
    $passenger_count = $_POST['passenger_count'] ?? 1;

    // Basic validation
    if (empty($pickup_location)) $errors[] = 'Pickup location required.';
    if (empty($dropoff_location)) $errors[] = 'Dropoff location required.';
    if (empty($pickup_date)) $errors[] = 'Pickup date required.';
    if (empty($pickup_time)) $errors[] = 'Pickup time required.';
    if (empty($passenger_count) || $passenger_count < 1) $errors[] = 'Passenger count must be at least 1.';

    if (!empty($errors)) {
        foreach ($errors as $e) echo "<p style='color:red;'>$e</p>";
        exit;
    }

    // Step 2: Get OAuth token
    $tokenUrl = "https://api.mylimobiz.com/v0/oauth2/token";
    $tokenData = json_encode([
        "grant_type" => "client_credentials",
        "client_id" => "ca_customer_mayfairbv",
        "client_secret" => "pmudJrHpv7zscw2vbdlJJFFWcIq4BLygs8gvwzW26ESWhJua67",
    ]);

    $tokenHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $tokenData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $tokenHeaders);

    $tokenResponse = curl_exec($ch);
    $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tokenHttpCode != 200) {
        echo "Failed to get access token.";
        exit;
    }

    $tokenJson = json_decode($tokenResponse, true);
    $accessToken = $tokenJson['access_token'] ?? null;

    if (!$accessToken) {
        echo "Access token not found in response.";
        exit;
    }

    // Step 3: Call rate_lookup API
    $rateUrl = "https://api.mylimobiz.com/v0/companies/mayfairbv/rate_lookup";
    $rateData = json_encode([
        'pickup' => [
            'instructions' => '',
            'address' => ['name' => $pickup_location],
        ],
        'dropoff' => [
            'address' => ['name' => $dropoff_location],
        ],
        'scheduled_pickup_at' => $pickup_date . "T" . date("H:i:s", strtotime($pickup_time)),
        'stop' => $stop,
        'return_trip' => $return_trip,
        'passenger_count' => (int) $passenger_count,
        'service_type' => 'point_to_point',
    ]);

    $rateHeaders = [
        'Authorization: Bearer ' . $accessToken, // or use your hardcoded token if needed
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    $ch = curl_init($rateUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rateData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $rateHeaders);

    $rateResponse = curl_exec($ch);
    $rateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($rateHttpCode != 200) {
        echo "Rate lookup API call failed. Status: $rateHttpCode";
        exit;
    }

    $vehicles = json_decode($rateResponse, true);

    if (empty($vehicles)) {
        echo "No vehicle data found.";
        exit;
    }

    // Display response (for debugging)
    echo "<h3>Available Vehicles:</h3><pre>";
    print_r($vehicles);
    echo "</pre>";
}
?>
