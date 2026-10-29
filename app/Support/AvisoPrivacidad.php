<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AvisoPrivacidad
{
    public static function vigente(Request $request): bool
    {
        return $request->session()->get('privacy_notice.version') === config('privacy.version')
            && is_string($request->session()->get('privacy_notice.accepted_at'));
    }

    public static function registrar(Request $request, Model $cuenta): void
    {
        if (self::vigente($request)) {
            $cuenta->forceFill([
                'privacy_notice_version' => config('privacy.version'),
                'privacy_notice_read_at' => $request->session()->get('privacy_notice.accepted_at'),
            ])->save();
        }
    }
}
