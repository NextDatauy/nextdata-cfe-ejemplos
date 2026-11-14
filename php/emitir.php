<?php
// Emite un CFE con la API de NextData (PHP 8 con cURL).
// Uso: NEXTDATA_API_KEY=nd_test_... php php/emitir.php solicitudes/efactura-111.json

$base = rtrim(getenv('NEXTDATA_API_URL') ?: 'https://api.nextdata.uy', '/');
$url = parse_url($base);
// La clave nunca viaja sin cifrar: solo https (o tu propia PC para pruebas locales).
if (($url['scheme'] ?? '') !== 'https' && !in_array($url['host'] ?? '', ['localhost', '127.0.0.1', '[::1]'], true)) {
    exit("NEXTDATA_API_URL tiene que usar https.\n");
}

$key = getenv('NEXTDATA_API_KEY');
if (!$key) exit("Definí NEXTDATA_API_KEY (empezá con una clave nd_test_).\n");
$archivo = $argv[1] ?? null;
if (!$archivo) exit("Indicá el archivo de la solicitud, por ejemplo solicitudes/eticket-101.json\n");

function api(string $base, string $key, string $path, ?array $body = null): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => false, // una redirección nunca recibe tu clave
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $key", 'Content-Type: application/json'],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $respuesta = curl_exec($ch);
    if ($respuesta === false) exit('Error de conexión: ' . curl_error($ch) . "\n");
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
if (!is_array($solicitud)) exit("El archivo de la solicitud no es JSON válido.\n");

$ready = api($base, $key, '/v1/dev/ready');
echo 'Ambiente: ' . ($ready['environment'] ?? '?') . ' · puede emitir: ' . var_export($ready['canIssue'] ?? false, true) . "\n";
if (empty($ready['canIssue'])) exit("Completá certificado, CAE y datos de la empresa antes de emitir.\n");

$cfe = api($base, $key, '/v1/dev/invoices/issue', $solicitud);
echo 'Emitido · estado: ' . ($cfe['status'] ?? '?') . "\n";

// El PDF oficial (firmado, con CAE y QR) viene en la misma respuesta.
if (!empty($cfe['pdfBase64'])) {
    // Solo el nombre del archivo: nunca se escribe fuera de la carpeta actual.
    $nombre = preg_replace('/[^\w.-]/', '_', basename((string) ($cfe['pdfFileName'] ?? ('CFE_' . ($cfe['outboxId'] ?? 'nuevo') . '.pdf'))));
    file_put_contents($nombre, base64_decode($cfe['pdfBase64'], true));
    @chmod($nombre, 0600);
    echo "PDF guardado en $nombre\n";
}
