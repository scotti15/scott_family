<?php

$sessionFilter = $_GET['filter'] ?? 'all';

$limit = null;
$rangeWhere = "";

if ($sessionFilter === "last1") $limit = 1;
if ($sessionFilter === "last3") $limit = 3;
if ($sessionFilter === "last5") $limit = 5;

$sessionJoin = "";
$params = [':user_id' => $user_id];

if ($sessionFilter === "range") {
    $from = (int)($_GET['from'] ?? 0);
    $to   = (int)($_GET['to'] ?? 0);

    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }

    $rangeWhere = "
        AND s.session_id BETWEEN :from_session AND :to_session
    ";

    $params[':from_session'] = $from;
    $params[':to_session'] = $to;
}

if ($limit !== null) {
    $sessionJoin = "
        JOIN (
            SELECT session_id
            FROM dart_sessions
            WHERE user_id = :user_id_inner
            ORDER BY created_at DESC
            LIMIT $limit
        ) recent_sessions
        ON s.session_id = recent_sessions.session_id
    ";

    $params[':user_id_inner'] = $user_id;
}