<?php

return [
    'application_name' => env(
        'GOOGLE_SHEETS_APP_NAME',
        'Bulls Media Sheets Importer'
    ),

    'credentials_path' => env(
        'GOOGLE_SHEETS_CREDENTIALS',
        'storage/app/private/google/service-account.json'
    ),
];
