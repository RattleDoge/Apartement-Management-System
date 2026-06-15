<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckKaryawanDept
{
    public function handle(Request $request, Closure $next, string ...$allowed): Response
    {
        $dept = optional(auth()->user()?->karyawan)->departemen ?? '';

        if (empty($dept) || !in_array($dept, $allowed)) {
            abort(403, 'Akses tidak diizinkan untuk departemen Anda.');
        }

        return $next($request);
    }
}
