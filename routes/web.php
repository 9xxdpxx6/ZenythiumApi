<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Test Forms Routes
Route::get('/test-forms', function () {
    return view('test-forms.index');
});

Route::get('/test-forms/{form}', function ($form) {
    $allowedForms = [
        'auth', 'muscle-groups', 'exercises', 'cycles', 
        'plans', 'workouts', 'workout-sets', 'metrics', 'training-programs', 'statistics'
    ];
    
    if (in_array($form, $allowedForms)) {
        return view("test-forms.{$form}");
    }
    
    abort(404);
});
