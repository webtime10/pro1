<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index()
    {
        $pageTitle = 'Admin Panel';
        return view('admin.index', compact('pageTitle'));
    }
}
