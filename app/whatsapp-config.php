<?php
declare(strict_types=1);
// Deliberately independent from Kauri. Nothing is connected unless enabled explicitly.
return [
    'enabled' => getenv('LD_WHATSAPP_ENABLED') === 'true',
    'token' => getenv('LD_META_WHATSAPP_TOKEN') ?: '',
    'phone_number_id' => getenv('LD_META_WHATSAPP_PHONE_NUMBER_ID') ?: '',
    'waba_id' => getenv('LD_META_WHATSAPP_WABA_ID') ?: '',
    'app_secret' => getenv('LD_META_APP_SECRET') ?: '',
    'verify_token' => getenv('LD_META_WEBHOOK_VERIFY_TOKEN') ?: '',
];
