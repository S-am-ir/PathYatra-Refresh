<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
apiRequest(); $user = sessionUser();
jsonSuccess(['authenticated' => $user !== null, 'user' => $user, 'csrf_token' => csrfToken()]);
