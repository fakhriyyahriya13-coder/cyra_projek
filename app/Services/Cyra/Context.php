<?php
/*
 * Dialogflow context persistence helpers.
 * Extracted from app/cyra/webhook.php to keep the webhook endpoint small.
 */

/* =========================================================
   CONTEXT DIALOGFLOW
========================================================= */
function getSessionKey($request)
{
    $session = $request['session'] ?? '';

    if ($session === '') {
        return 'default';
    }

    return preg_replace('/[^a-zA-Z0-9_\-]/', '_', md5($session));
}

function getStateFile($request)
{
    $dir = dirname(__DIR__, 3) . '/storage/framework/session_state';

    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $stateName = getSessionKey($request) . '.json';
    $stateFile = $dir . '/' . $stateName;
    $legacyFile = dirname(__DIR__, 2) . '/cyra/session_state/' . $stateName;

    if (!file_exists($stateFile) && file_exists($legacyFile)) {
        @copy($legacyFile, $stateFile);
    }

    return $stateFile;
}

function contextNameToType($contextName)
{
    $name = strtolower((string)$contextName);

    if (strpos($name, 'ctx_jadwal_kuliah') !== false) {
        return 'jadwal_kuliah';
    }

    if (strpos($name, 'ctx_mata_kuliah') !== false) {
        return 'mata_kuliah';
    }

    if (strpos($name, 'ctx_uts') !== false) {
        return 'uts';
    }

    if (strpos($name, 'ctx_uas') !== false) {
        return 'uas';
    }

    return null;
}

function savePendingContext($request, $type, $parameters = [])
{
    if (!$type) {
        return;
    }

    $data = [
        'type' => $type,
        'parameters' => $parameters,
        'expired_at' => time() + (10 * 60)
    ];

    @file_put_contents(getStateFile($request), json_encode($data, JSON_UNESCAPED_UNICODE));
}

function getSavedPendingContext($request)
{
    $file = getStateFile($request);

    if (!file_exists($file)) {
        return null;
    }

    $data = json_decode((string)@file_get_contents($file), true);

    if (!$data || !isset($data['type'])) {
        return null;
    }

    if (($data['expired_at'] ?? 0) < time()) {
        @unlink($file);
        return null;
    }

    return $data;
}

function clearPendingContext($request)
{
    $file = getStateFile($request);

    if (file_exists($file)) {
        @unlink($file);
    }
}

function makeContext($request, $contextName, $lifespan = 5, $parameters = [])
{
    $session = $request['session'] ?? '';
    $type = contextNameToType($contextName);

    // Simpan juga di file lokal agar tetap jalan meskipun context Dialogflow tidak ikut terkirim ke webhook.
    savePendingContext($request, $type, $parameters);

    if ($session === '') {
        return [];
    }

    $ctx = [
        "name" => $session . "/contexts/" . $contextName,
        "lifespanCount" => $lifespan
    ];

    if (!empty($parameters)) {
        $ctx["parameters"] = $parameters;
    }

    return [$ctx];
}

function getActiveContextData($request)
{
    $contexts = $request['queryResult']['outputContexts'] ?? [];

    foreach ($contexts as $ctx) {
        $type = contextNameToType($ctx['name'] ?? '');

        if ($type) {
            return [
                'type' => $type,
                'parameters' => $ctx['parameters'] ?? []
            ];
        }
    }

    $saved = getSavedPendingContext($request);

    if ($saved) {
        return $saved;
    }

    return ['type' => null, 'parameters' => []];
}

function getActiveContextType($request)
{
    $data = getActiveContextData($request);
    return $data['type'] ?? null;
}

function getActiveContextParameters($request)
{
    $data = getActiveContextData($request);
    return $data['parameters'] ?? [];
}
