<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Notification Channels
    |--------------------------------------------------------------------------
    |
    | Toggles the delivery channels for App\Notifications\SystemEventNotification
    | — the contribution / advance / recovery / interest / settlement /
    | scheduled-task alerts.
    |
    |   database : the in-app header dropdown + the notifications listing page
    |   mail     : the email alert sent to the recipient
    |
    | Each channel can be switched off independently from .env. With both off,
    | NotificationService skips delivery entirely (no recipient lookup, no jobs).
    |
    */

    'channels' => [
        'database' => env('NOTIFY_APP_ENABLED', true),
        'mail'     => env('NOTIFY_MAIL_ENABLED', true),
    ],

];
