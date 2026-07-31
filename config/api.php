<?php

return [

    'ek_auth_secret' => env('AUTHENTICATION_SECRET_KEY'),
    'ek_auth_client_id' => env('OAUTH_CLIENT_ID', '019f8db4-9a1f-703b-ae08-c84f4161fa93'),

    'ek_auth_service_id' => env('EK_AUTH_SERVICE_CLIENT_ID'),     // machine (client_credentials)
    'ek_auth_service_secret' => env('EK_AUTH_SERVICE_CLIENT_SECRET'),


    'ek_auth_client_secret' => env('OAUTH_CLIENT_SECRET', 'sQ5LxC2I4qlqS15eampCJUlYH4FlY9eJZMz2sfnr'),
    'current_system' => env('CURRENT_SYSTEM', 'Ek-Core'),

];
