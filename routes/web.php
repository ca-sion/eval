<?php

use App\Livewire\CoachGroupEvaluation;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/groupe/{group:access_token}', CoachGroupEvaluation::class)->name('group.mobile');
