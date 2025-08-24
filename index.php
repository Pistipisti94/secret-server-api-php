<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once "SecretService.php";

$service = new SecretService();
$method = $_SERVER["REQUEST_METHOD"];
$uri = explode("/", trim($_SERVER["REQUEST_URI"], "/"));

// Ellenőrizzük, hogy legalább 4 rész van-e az útvonalban
// Példa: /secret-server-php/api/v1/secrets
if (count($uri) >= 4 && $uri[1] === "api" && $uri[2] === "v1") {

    // POST: Titok létrehozása
    if ($method === "POST" && $uri[3] === "secrets") {
        $data = json_decode(file_get_contents("php://input"), true);
        $secret = $data["secret"] ?? null;
        $maxReads = $data["maxReads"] ?? 1;
        $ttlSeconds = $data["ttlSeconds"] ?? 3600;

        echo json_encode($service->create($secret, $maxReads, $ttlSeconds));
        exit;
    }

    // GET: Titok lekérése token alapján
    if ($method === "GET" && $uri[3] === "secrets" && isset($uri[4])) {
        $token = $uri[4];
        echo json_encode($service->get($token));
        exit;
    }
}

http_response_code(404);
echo json_encode(["error" => "Érvénytelen végpont"]);
