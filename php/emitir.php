<?php
// Emite un CFE con la API de NextData (PHP 8 con cURL).
// Uso: NEXTDATA_API_KEY=nd_test_... php php/emitir.php solicitudes/efactura-111.json

$base = getenv('NEXTDATA_API_URL') ?: 'https://api.nextdata.uy';
$key = getenv('NEXTDATA_API_KEY') ?: exit("Definí NEXTDATA_API_KEY (empezá con una clave nd_test_).\n");
$archivo = $argv[1] ?? exit("Indicá el archivo de la solicitud, por ejemplo solicitudes/eticket-101.json\n");

function api(string $base, string $key, string $path, ?array $body = null): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $key", 'Content-Type: application/json'],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $datos = json_decode($respuesta ?: '{}', true) ?? [];
    if ($codigo >= 400) {
        // 401: clave inactiva. 409/422: leé "message". No cambies la idempotencyKey para reintentar.
        exit("NextData $codigo: " . ($datos['message'] ?? $respuesta) . "\n");
    }
    return $datos;
}

$solicitud = json_decode(file_get_contents($archivo), true);
$ready = api($base, $key, '/v1/dev/ready');
echo 'Ambiente: ' . $ready['environment'] . ' · puede emitir: ' . var_export($ready['canIssue'], true) . "\n";
if (!$ready['canIssue']) exit("Completá certificado, CAE y datos de la empresa antes de emitir.\n");

$cfe = api($base, $key, '/v1/dev/invoices/issue', $solicitud);
echo 'Emitido · estado: ' . $cfe['status'] . "\n";

// El PDF oficial (firmado, con CAE y QR) viene en la misma respuesta.
if (!empty($cfe['pdfBase64'])) {
    $nombre = $cfe['pdfFileName'] ?? ('CFE_' . $cfe['outboxId'] . '.pdf');
    file_put_contents($nombre, base64_decode($cfe['pdfBase64']));
    echo "PDF guardado en $nombre\n";
}
