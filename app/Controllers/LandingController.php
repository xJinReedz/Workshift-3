<?php
/**
 * Landing Page Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;

class LandingController extends Controller
{
    public function index(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('landing.index', [
            'pageTitle' => 'WorkShift — The Freelance Client CRM That Ends Project Stalls',
        ], 'public');
    }
}
