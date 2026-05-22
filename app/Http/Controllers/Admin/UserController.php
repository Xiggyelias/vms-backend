<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\UserController as BaseUserController;

/**
 * Admin-scoped user management.
 * Inherits all logic from the base UserController.
 * Placed in the Admin namespace so routes read as Admin\UserController
 * instead of the flat UserController, making the intent explicit.
 */
class UserController extends BaseUserController {}
