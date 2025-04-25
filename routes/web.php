<?php


use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cron', function () {
    if (request()->get('token') !== config('admin.CRON_JOB_TOKEN')) {
        abort(401, 'Unauthorized');
    }

    Artisan::call('queue:work --stop-when-empty');

    return response('Queue processed successfully');
});
