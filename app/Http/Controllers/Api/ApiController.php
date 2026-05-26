<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;

abstract class ApiController extends Controller
{
    use RespondsWithJson;
}
