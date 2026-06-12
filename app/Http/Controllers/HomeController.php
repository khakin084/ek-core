<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
    }

    public function index()
    {
        $company_id = $journal_id = 0;
        // $company = Entity::where('type', 'COMPANY')->first();
        $company = null;

        if ($company) {
            $company_id = $company->id;
            // $journal = Journal::where('reference', 'BEG_BAL_JOURNAL_' . $company_id)->first();
            $journal = null;
            $journal_id = $journal ? $journal->id : 0;
        }

        return view('home.index', compact('company_id', 'journal_id'));
    }
}
