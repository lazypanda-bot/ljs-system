<?php

namespace App\Http\Controllers;

use App\Models\Agent;

class AgentController extends Controller 
{
    public function agents()
    {
        $topAgents = Agent::with('user')
                            ->orderBy('years_experience', 'desc')
                            ->take(5)
                            ->get();

        $allAgents = Agent::with('user')->get();

        return view('customer.agents', compact('topAgents', 'allAgents'));
    }
}