<?php

return [
    'api_key' => env('GEMINI_API_KEY', ''),

    // opsional: set model default agar konsisten dengan controllermu
    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),

    // opsi lain biarkan default (sesuai paket)
];
