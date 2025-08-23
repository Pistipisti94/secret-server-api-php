<?php

class SecretService {
    private $file;

    public function __construct() {
        $this->file = __DIR__ . "/secrets.json";

        // Ha nincs fájl, hozzuk létre üres JSON-nal
        if (!file_exists($this->file)) {
            file_put_contents($this->file, json_encode([]));
        }
    }

    // Titok létrehozása
    public function create($secret, $maxReads = 1, $ttlSeconds = 3600) {
        if (!$secret || $maxReads < 1) {
            return ["error" => "Érvénytelen adatok"];
        }

        $data = json_decode(file_get_contents($this->file), true);
        $token = bin2hex(random_bytes(8));
        $expiresAt = time() + $ttlSeconds;

        $data[$token] = [
            "secret" => $secret,
            "remainingReads" => $maxReads,
            "expiresAt" => $expiresAt
        ];

        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));

        return ["token" => $token];
    }

    // Titok lekérése
    public function get($token) {
        $data = json_decode(file_get_contents($this->file), true);

        if (!isset($data[$token])) {
            return ["error" => "Titok nem található"];
        }

        $entry = $data[$token];

        // Ha lejárt
        if (time() > $entry["expiresAt"]) {
            unset($data[$token]);
            file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));
            return ["error" => "A titok lejárt"];
        }

        // Ha már nincs olvasás
        if ($entry["remainingReads"] <= 0) {
            unset($data[$token]);
            file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));
            return ["error" => "A titok már nem elérhető"];
        }

        // Csökkentjük az olvasások számát
        $data[$token]["remainingReads"] -= 1;
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));

        return ["secret" => $entry["secret"]];
    }
}
