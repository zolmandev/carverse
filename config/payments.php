<?php

return ['driver' => env('PAYMENT_DRIVER', 'demo'), 'key' => env('MPESA_CONSUMER_KEY'), 'secret' => env('MPESA_CONSUMER_SECRET'), 'shortcode' => env('MPESA_SHORTCODE'), 'passkey' => env('MPESA_PASSKEY'), 'callback_token' => env('MPESA_CALLBACK_TOKEN')];
