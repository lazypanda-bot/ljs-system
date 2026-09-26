<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\BlockedDate;
use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
	public function index(Request $request)
	{
		$user = Auth::user();
		if (! ($user instanceof User)) {
			abort(403);
		}
		$actingRole = $user->actingRole();
		$filter = $request->string('status')->toString();

		$appointments = Appointment::with(['assignment.property', 'assignment.user', 'blockedDate'])
			->when($actingRole !== 'admin', function ($query) use ($user) {
				$query->whereHas('assignment', fn ($query) => $query->where('user_id', $user->user_id));
			})
			->when($filter === 'active', fn ($query) => $query->whereIn('appointment_status', ['Pending', 'Confirmed']))
			->when($filter === 'cancelled', fn ($query) => $query->whereIn('appointment_status', ['Cancelled', 'Rejected']))
			->when($filter === 'done', fn ($query) => $query->where('appointment_status', 'Completed'))
			->latest('preferred_date')
			->latest('preferred_time')
			->get();

		return view('pages.appointments', compact('appointments', 'filter'));
	}

	public function create(Request $request, Property $property)
	{
		abort_unless($property->approval_status === 'Approved', 404);
		$referralCode = $request->filled('ref')
			? ReferralCode::query()->with('user')->where('code', $request->query('ref'))->where('property_id', $property->property_id)->firstOrFail()
			: null;
		$isAdminListing = $property->listing_mode === 'admin';
		$selectedAssignmentId = null;

		if ($referralCode) {
			abort_unless($referralCode->user?->account_status === 'Active', 404);

			if ($isAdminListing) {
				$assignments = $property->assignments()
					->where('status', 'Approved')
					->whereHas('user', fn ($query) => $query->where('account_status', 'Active'))
					->with('user')
					->get();

				if ($assignments->isEmpty()) {
					$assignment = $property->assignments()->where('user_id', $referralCode->user_id)->first();
					if ($assignment) {
						abort_unless($assignment->status === 'Approved', 404);
					} else {
						$assignment = $property->assignments()->create([
							'user_id' => $referralCode->user_id,
							'assigned_date' => now()->toDateString(),
							'status' => 'Approved',
						]);
					}
					$assignments = collect([$assignment->load('user')]);
				}

				$selectedAssignment = $assignments->firstWhere('assign_id', $request->integer('assign_id'))
					?? $assignments->firstWhere('user_id', $referralCode->user_id)
					?? $assignments->first();
				$selectedAssignmentId = $selectedAssignment?->assign_id;
			} else {
				$assignment = $property->assignments()->where('user_id', $referralCode->user_id)->first();

				if (! $assignment) {
					$assignment = $property->assignments()->create([
						'user_id' => $referralCode->user_id,
						'assigned_date' => now()->toDateString(),
						'status' => 'Approved',
					]);
				} elseif ($assignment->status !== 'Approved' && (int) $property->user_id === (int) $referralCode->user_id) {
					$assignment->update(['status' => 'Approved']);
				}

				abort_unless($assignment->status === 'Approved', 404);
				$assignments = collect([$assignment->load('user')]);
				$selectedAssignmentId = $assignment->assign_id;
			}
		} else {
			if ($property->assignments()->where('status', 'Approved')->doesntExist() && in_array($property->user?->role_name, ['Broker', 'Agent', 'Lead Broker'], true)) {
				$property->assignments()->create(['user_id' => $property->user_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
			}
			$assignments = $property->assignments()->where('status', 'Approved')->with('user')->get();

			if ($assignments->isEmpty() && in_array($property->user?->role_name, ['Broker', 'Agent', 'Lead Broker'], true)) {
				$assignments = collect([(object) ['assign_id' => null, 'user' => $property->user]]);
			}
			$selectedAssignmentId = $request->integer('assign_id')
				?: $assignments->first()?->assign_id;
		}

		return view('customer.appointment-create', compact('property', 'assignments', 'referralCode', 'isAdminListing', 'selectedAssignmentId'));
	}

	public function store(Request $request, Property $property)
	{
		abort_unless($property->approval_status === 'Approved', 404);

		$validated = $request->validate([
			'assign_id' => ['nullable', 'integer'],
			'ref' => ['nullable', 'string', 'max:255'],
			'contact_name' => ['required', 'string', 'max:255'],
			'contact_number' => ['required', 'string', 'max:20'],
			'contact_email' => ['required', 'email', 'max:255'],
			'appointment_type' => ['required', 'string', 'max:100'],
			'preferred_date' => ['required', 'date', 'after_or_equal:today'],
			'preferred_time' => ['required', 'date_format:H:i'],
			'additional_note' => ['nullable', 'string', 'max:1000'],
		]);

		if (! empty($validated['ref'])) {
			$referralCode = ReferralCode::query()
				->with('user')
				->where('code', $validated['ref'])
				->where('property_id', $property->property_id)
				->firstOrFail();
			abort_unless($referralCode->user?->account_status === 'Active', 404);

			$assignmentQuery = $property->assignments()
				->where('assign_id', $validated['assign_id'] ?? null)
				->where('status', 'Approved')
				->whereHas('user', fn ($query) => $query->where('account_status', 'Active'));

			if ($property->listing_mode !== 'admin') {
				$assignmentQuery->where('user_id', $referralCode->user_id);
			}

			$assignment = $assignmentQuery->firstOrFail();
		} else {
			$assignment = ! empty($validated['assign_id'])
				? $property->assignments()->where('assign_id', $validated['assign_id'])->where('status', 'Approved')->firstOrFail()
				: $property->assignments()->where('status', 'Approved')->first();
		}

		abort_unless($assignment, 422);

		$blocked = BlockedDate::where('user_id', $assignment->user_id)
			->whereDate('date', $validated['preferred_date'])
			->where(function ($query) use ($validated) {
				$query->whereNull('start_time')->orWhere(function ($query) use ($validated) {
					$query->where('start_time', '<=', $validated['preferred_time'])->where('end_time', '>=', $validated['preferred_time']);
				});
			})->exists();

		abort_if($blocked, 422, 'The selected professional is unavailable at that time.');

		Appointment::create([
			'assign_id' => $assignment->assign_id,
			'contact_name' => $validated['contact_name'],
			'contact_number' => $validated['contact_number'],
			'contact_email' => $validated['contact_email'],
			'appointment_type' => $validated['appointment_type'],
			'preferred_date' => $validated['preferred_date'],
			'preferred_time' => $validated['preferred_time'],
			'additional_note' => $validated['additional_note'] ?? null,
		]);

		return redirect()->route('properties.show', $property)->with('success', 'Appointment request submitted.');
	}

	public function updateStatus(Request $request, Appointment $appointment)
	{
		$user = Auth::user();
		if (! ($user instanceof User)) abort(403);

		abort_unless($user->actingRole() === 'admin' || $appointment->assignment?->user_id === $user->user_id, 403);
		$validated = $request->validate(['appointment_status' => ['required', 'in:Pending,Confirmed,Completed,Cancelled,Rejected']]);
		$appointment->update(['appointment_status' => $validated['appointment_status']]);

		return redirect()->back()->with('success', 'Appointment status updated.');
	}

	public function blockDate(Request $request)
	{
		$user = Auth::user();
		if (! ($user instanceof User)) abort(403);
		abort_unless(in_array($user->actingRole(), ['admin', 'broker', 'agent'], true), 403);

		$validated = $request->validate([
			'date' => ['required', 'date'],
			'start_time' => ['nullable', 'date_format:H:i'],
			'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
			'reason' => ['nullable', 'string', 'max:255'],
		]);

		$user->blockedDates()->create($validated);

		return redirect()->back()->with('success', 'Date blocked successfully.');
	}
}
