<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $user = $this->user();
        $properties = Property::visibleTo($user)->get();
        $appointments = Appointment::query()->when($user->actingRole() !== 'admin', fn ($query) => $query->whereHas('assignment', fn ($query) => $query->where('user_id', $user->user_id)))->get();

        return view('pages.reports-live', compact('properties', 'appointments'));
    }

    public function export(Request $request)
    {
        $user = $this->user();
        $properties = Property::visibleTo($user)->get(['property_id', 'property_name', 'property_status', 'price']);

        return response()->streamDownload(function () use ($properties) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Property ID', 'Property', 'Status', 'Price']);
            foreach ($properties as $property) fputcsv($handle, [$property->property_id, $property->property_name, $property->property_status, $property->price]);
            fclose($handle);
        }, 'ljs-property-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        return $user;
    }
}