<?= json_encode([
    'code'      => $code,
    'username'  => $user->username,
    'ipAddress' => $ipAddress,
    'userAgent' => $userAgent,
    'date'      => $date,
]);
