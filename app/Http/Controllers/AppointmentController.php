<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentTimeProposal;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\BlockedDate;
use App\Services\AppointmentSchedulingService;
use App\Services\NotificationService;
use App\Models\ReferralCode;
use App\Models\AppNotification;
use App\Mail\ActivityMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
	public function index(Request $request)
	{
		$user = Auth::user();
		if (! ($user instanceof User)) {
			abort(403);
		}
		$actingRole = $user->actingRole();
		if ($actingRole === 'admin') {
			return $this->adminIndex($request, $user);
		}
		if ($user->normalizedRole() !== 'client') {
			return $this->staffIndex($request, $user);
		}
		$filter = $request->string('status')->toString();

		$appointmentsQuery = Appointment::with(['assignment.property', 'assignment.user', 'blockedDate', 'client', 'proposals.proposedBy']);

		if ($actingRole !== 'admin') {
			if ($user->normalizedRole() === 'client') {
				$appointmentsQuery->where(function ($query) use ($user) {
					$query->where('client_id', $user->user_id)
						->orWhere(function ($query) use ($user) {
							$query->whereNull('client_id')->where('contact_email', $user->email);
						});
				});
			} else {
				$appointmentsQuery->whereHas('assignment', fn ($query) => $query->where('user_id', $user->user_id));
			}
		}

		$appointments = $appointmentsQuery
			->when($filter === 'active', fn ($query) => $query->whereIn('appointment_status', ['Pending', 'Confirmed']))
			->when($filter === 'cancelled', fn ($query) => $query->whereIn('appointment_status', ['Cancelled', 'Rejected']))
			->when($filter === 'done', fn ($query) => $query->where('appointment_status', 'Completed'))
			->orderByRaw('CASE WHEN starts_at_utc IS NULL THEN 1 ELSE 0 END')
			->orderBy('starts_at_utc')
			->orderBy('preferred_date')
			->orderBy('preferred_time')
			->paginate(5)
			->appends($request->except('selected'));

		$selectedId = $request->integer('selected');
		$selectedAppointment = $selectedId
			? $appointments->getCollection()->firstWhere('appointment_id', $selectedId)
			: $appointments->first();
		$pendingProposal = $selectedAppointment?->proposals->firstWhere('status', 'Pending');
		$proposalNotification = $pendingProposal
			? AppNotification::query()
				->where('user_id', $user->user_id)
				->where('appointment_proposal_id', $pendingProposal->proposal_id)
				->first()
			: null;

		$viewerTimezone = $user->timezone ?: config('appointments.default_timezone');
		$isAdmin = $actingRole === 'admin';

		return view('pages.appointments', compact('appointments', 'selectedAppointment', 'pendingProposal', 'proposalNotification', 'filter', 'viewerTimezone', 'isAdmin'));
	}

	public function activeCount(Request $request)
	{
		$user = $request->user();
		abort_unless($user instanceof User, 403);

		return response()->json(['count' => Appointment::activeForClient($user)->count()]);
	}

	private function staffIndex(Request $request, User $user)
	{
		$filter = $request->string('status')->toString();
		$search = trim($request->string('q')->toString());

		$appointments = Appointment::with(['assignment.property', 'assignment.user', 'client', 'proposals.proposedBy'])
			->whereHas('assignment', fn ($query) => $query->where('user_id', $user->user_id))
			->when($filter === 'Reschedule', fn ($query) => $query->whereHas('proposals', fn ($proposal) => $proposal->where('status', 'Pending')))
			->when(in_array($filter, ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'], true), fn ($query) => $query->where('appointment_status', $filter))
			->when($search !== '', function ($query) use ($search) {
				$term = '%' . addcslashes($search, '%_\\') . '%';
				$query->where(fn ($q) => $q->where('contact_name', 'like', $term)
					->orWhere('contact_email', 'like', $term)
					->orWhereHas('assignment.property', fn ($property) => $property->where('property_name', 'like', $term)));
			})
			->orderByRaw('CASE WHEN starts_at_utc IS NULL THEN 1 ELSE 0 END')
			->orderByDesc('starts_at_utc')
			->orderByDesc('appointment_id')
			->paginate(10)
			->withQueryString();

		$tab = 'projects';
		$counts = ['monitoring' => 0, 'projects' => 0];
		$staffMode = true;
		$viewerTimezone = $user->timezone ?: config('appointments.default_timezone');

		return view('pages.appointments-admin', compact('appointments', 'tab', 'filter', 'search', 'counts', 'viewerTimezone', 'staffMode'));
	}

	private function adminIndex(Request $request, User $user)
	{
		$tab = $request->query('tab') === 'projects' ? 'projects' : 'monitoring';
		$filter = $request->string('status')->toString();
		$search = trim($request->string('q')->toString());
		$projectScope = fn ($query) => $query->whereHas('assignment', fn ($assignment) => $assignment
			->where('user_id', $user->user_id)
			->whereHas('property', fn ($property) => $property->where('listing_mode', 'admin')));
		$monitoringScope = fn ($query) => $query->whereHas('assignment', fn ($assignment) => $assignment
			->whereHas('user', fn ($professional) => $professional->whereIn('role_name', ['Agent', 'Broker', 'Lead Broker']))
			->whereHas('property', fn ($property) => $property->where(fn ($mode) => $mode->whereNull('listing_mode')->orWhere('listing_mode', '!=', 'admin'))));

		$appointments = Appointment::with(['assignment.property', 'assignment.user', 'client', 'proposals.proposedBy'])
			->where($tab === 'projects' ? $projectScope : $monitoringScope)
			->when($filter === 'Reschedule', fn ($query) => $query->whereHas('proposals', fn ($proposal) => $proposal->where('status', 'Pending')))
			->when($filter !== '' && in_array($filter, ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'], true), fn ($query) => $query->where('appointment_status', $filter))
			->when($search !== '', function ($query) use ($search) {
				$term = '%' . addcslashes($search, '%_\\') . '%';
				$query->where(function ($query) use ($term) {
					$query->where('contact_name', 'like', $term)
						->orWhere('contact_email', 'like', $term)
						->orWhereHas('assignment.property', fn ($property) => $property->where('property_name', 'like', $term))
						->orWhereHas('assignment.user', fn ($professional) => $professional->where('name', 'like', $term));
				});
			})
			->orderByRaw('CASE WHEN starts_at_utc IS NULL THEN 1 ELSE 0 END')
			->orderByDesc('starts_at_utc')
			->orderByDesc('appointment_id')
			->paginate(10)
			->withQueryString();

		$counts = [
			'monitoring' => Appointment::query()->where($monitoringScope)->count(),
			'projects' => Appointment::query()->where($projectScope)->count(),
		];
		$viewerTimezone = $user->timezone ?: config('appointments.default_timezone');

		return view('pages.appointments-admin', compact('appointments', 'tab', 'filter', 'search', 'counts', 'viewerTimezone'));
	}

	public function create(Request $request, Property $property)
	{
		$user = $request->user();
		abort_unless($user instanceof User && $user->normalizedRole() === 'client', 403);
		abort_unless($property->approval_status === 'Approved', 404);
		if (! $property->acceptsAppointments()) {
			return redirect()->route('properties.show', $property)->with('error', "This property is {$property->property_status}; appointments are closed.");
		}
		if (Appointment::clientHasActiveForProperty($user, $property->property_id)) {
			return redirect()->route('properties.show', $property)->with('popup', [
				'title' => 'Already booked',
				'message' => 'You already have an active appointment for this property.',
			]);
		}
		$referralCode = null;
		$isAdminListing = $property->listing_mode === 'admin';

		// A listing's appointments always go to the person who listed it.
		$assignment = $this->ownerAssignment($property);
		abort_unless($property->user?->account_status === 'Active', 404);
		$assignments = collect([$assignment->load('user')]);
		$selectedAssignmentId = $assignment->assign_id;

		$clientTimezone = $user->timezone ?: '';

		return view('customer.appointment-create', compact('property', 'assignments', 'referralCode', 'isAdminListing', 'selectedAssignmentId', 'clientTimezone'));
	}

	public function availability(Request $request, Property $property, AppointmentSchedulingService $scheduling)
	{
		$user = $request->user();
		abort_unless($user instanceof User && $user->normalizedRole() === 'client', 403);
		abort_unless($property->approval_status === 'Approved' && $property->acceptsAppointments(), 404);

		$validated = $request->validate([
			'assign_id' => ['required', 'integer'],
			'date' => ['required', 'date_format:Y-m-d'],
			'timezone' => ['required', 'timezone'],
			'ref' => ['nullable', 'string', 'max:255'],
			'with_dates' => ['nullable', 'boolean'],
		]);

		$assignment = $this->resolveAvailableAssignment($property, (int) $validated['assign_id']);

		return response()->json([
			'slots' => $scheduling->availableSlots($assignment, $validated['date'], $validated['timezone']),
			'all_slots' => $scheduling->daySlots($assignment, $validated['date'], $validated['timezone']),
			'unavailable_dates' => $request->boolean('with_dates') ? $scheduling->unavailableDates($assignment, $validated['timezone']) : null,
		]);
	}

	public function proposalAvailability(Request $request, Appointment $appointment, AppointmentSchedulingService $scheduling)
	{
		$user = $request->user();
		abort_unless($user instanceof User && $this->canAccessAppointment($appointment, $user), 403);
		abort_unless(in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true), 409);

		$validated = $request->validate([
			'date' => ['required', 'date_format:Y-m-d'],
			'timezone' => ['required', 'timezone'],
			'with_dates' => ['nullable', 'boolean'],
		]);

		$assignment = $appointment->assignment;
		abort_unless($assignment, 422);

		return response()->json([
			'slots' => $scheduling->availableSlots($assignment, $validated['date'], $validated['timezone'], $appointment->appointment_id),
			'all_slots' => $scheduling->daySlots($assignment, $validated['date'], $validated['timezone'], $appointment->appointment_id),
			'unavailable_dates' => $request->boolean('with_dates') ? $scheduling->unavailableDates($assignment, $validated['timezone'], $appointment->appointment_id) : null,
		]);
	}

	public function store(Request $request, Property $property, AppointmentSchedulingService $scheduling)
	{
		$client = $request->user();
		abort_unless($client instanceof User && $client->normalizedRole() === 'client', 403);
		abort_unless($property->approval_status === 'Approved', 404);
		if (! $property->acceptsAppointments()) {
			throw ValidationException::withMessages(['appointment_type' => "This property is {$property->property_status}; appointments are closed."]);
		}
		if (Appointment::clientHasActiveForProperty($client, $property->property_id)) {
			throw ValidationException::withMessages(['appointment_type' => 'You already have an active appointment for this property. Wait for it to finish or cancel it before booking again.']);
		}

		\App\Support\InputRules::normalize($request, ['contact_name'], ['contact_number']);
		$validated = $request->validate([
			'assign_id' => ['nullable', 'integer'],
			'ref' => ['nullable', 'string', 'max:255'],
			'contact_name' => \App\Support\InputRules::name(),
			'contact_number' => \App\Support\InputRules::phone(true),
			'contact_email' => \App\Support\InputRules::email(),
			'appointment_type' => ['required', 'string', 'max:100'],
			'preferred_date' => ['required', 'date_format:Y-m-d'],
			'preferred_time' => ['required', 'date_format:H:i'],
			'slot_start_utc' => ['nullable', 'date'],
			'client_timezone' => ['nullable', 'timezone'],
			'additional_note' => ['nullable', 'string', 'max:1000'],
		], \App\Support\InputRules::messages());

		// A listing's appointments always go to the person who listed it.
		$assignment = $this->ownerAssignment($property)->load('user');
		abort_unless($assignment->user?->account_status === 'Active', 404);

		$clientTimezone = $validated['client_timezone']
			?? ($client->timezone ?: config('appointments.default_timezone'));
		$slotUtc = ! empty($validated['slot_start_utc'])
			? CarbonImmutable::parse($validated['slot_start_utc'])->utc()
			: CarbonImmutable::createFromFormat(
				'!Y-m-d H:i',
				$validated['preferred_date'] . ' ' . $validated['preferred_time'],
				$clientTimezone
			)->utc();
		$clientLocalStart = $slotUtc->setTimezone($clientTimezone);
		if ($clientLocalStart->format('Y-m-d') !== $validated['preferred_date']
			|| $clientLocalStart->format('H:i') !== $validated['preferred_time']) {
			throw ValidationException::withMessages(['preferred_time' => 'The selected time slot is no longer valid. Choose an available slot.']);
		}

		$appointment = DB::transaction(function () use ($assignment, $scheduling, $slotUtc, $clientTimezone, $validated, $client): Appointment {
			User::query()->where('user_id', $assignment->user_id)->lockForUpdate()->firstOrFail();
			$scheduling->assertAvailable($assignment, $slotUtc);

			return Appointment::create([
				'assign_id' => $assignment->assign_id,
				'client_id' => $client->user_id,
				'contact_name' => $validated['contact_name'],
				'contact_number' => $validated['contact_number'],
				'contact_email' => $validated['contact_email'],
				'appointment_type' => $validated['appointment_type'],
				'preferred_date' => $validated['preferred_date'],
				'preferred_time' => $validated['preferred_time'],
				'starts_at_utc' => $slotUtc->format('Y-m-d H:i:s'),
				'client_timezone' => $clientTimezone,
				'additional_note' => $validated['additional_note'] ?? null,
			]);
		});

		$this->notifyAppointmentRecipient($assignment, $property, $appointment, $validated);

		return redirect()->route('properties.show', $property)->with('success', 'Appointment request submitted.')->with('popup', [
			'title' => 'Appointment requested',
			'message' => 'Your appointment request was sent. You will be notified once it is confirmed.',
		]);
	}

	public function updateStatus(Request $request, Appointment $appointment)
	{
		$user = Auth::user();
		if (! ($user instanceof User)) abort(403);

		$isAdmin = $user->actingRole() === 'admin' && $this->canAdminUpdateAppointment($appointment, $user);
		$isProfessional = (int) $appointment->assignment?->user_id === (int) $user->user_id;
		$isClient = $this->isClientForAppointment($appointment, $user);
		abort_unless($isAdmin || $isProfessional || $isClient, 403);

		if ($isClient && ! $isAdmin && ! $isProfessional) {
			abort_unless($appointment->appointment_status === 'Pending', 409, 'This appointment status has already been set.');
			$allowedStatuses = ['Cancelled'];
		} else {
			abort_unless(in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true), 409, 'This appointment status is final.');
			$allowedStatuses = $appointment->appointment_status === 'Confirmed'
				? ['Confirmed', 'Completed', 'Cancelled']
				: ['Confirmed', 'Completed', 'Cancelled', 'Rejected'];
		}
		$validated = $request->validate([
			'appointment_status' => ['required', 'in:' . implode(',', $allowedStatuses)],
			'cancellation_reason' => ['required_if:appointment_status,Cancelled', 'nullable', 'string', 'max:1000'],
		]);
		$appointment->update([
			'appointment_status' => $validated['appointment_status'],
			'completed_at' => $validated['appointment_status'] === 'Completed' ? ($appointment->completed_at ?? now()) : null,
			'cancellation_reason' => $validated['appointment_status'] === 'Cancelled' ? $validated['cancellation_reason'] : null,
		]);
		$this->notifyAppointmentStatus($appointment, $validated['appointment_status'], $user);

		return redirect()->back()->with('success', 'Appointment status updated.');
	}

	public function proposeTime(Request $request, Appointment $appointment, AppointmentSchedulingService $scheduling)
	{
		$user = $request->user();
		abort_unless($user instanceof User && $this->canAccessAppointment($appointment, $user), 403);
		abort_unless(
			(int) $appointment->assignment?->user_id === (int) $user->user_id
				|| $this->isClientForAppointment($appointment, $user),
			403
		);
		abort_unless(in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true), 409);

		$validated = $request->validate([
			'preferred_date' => ['required', 'date_format:Y-m-d'],
			'preferred_time' => ['required', 'date_format:H:i'],
			'slot_start_utc' => ['required', 'date'],
			'timezone' => ['required', 'timezone'],
			'message' => ['nullable', 'string', 'max:500'],
		]);

		$proposedUtc = CarbonImmutable::parse($validated['slot_start_utc'])->utc();
		$localStart = $proposedUtc->setTimezone($validated['timezone']);
		if ($localStart->format('Y-m-d') !== $validated['preferred_date']
			|| $localStart->format('H:i') !== $validated['preferred_time']) {
			throw ValidationException::withMessages(['preferred_time' => 'Choose an available time from the list.']);
		}
		abort_if($appointment->starts_at_utc?->equalTo($proposedUtc), 422, 'That is already the scheduled appointment time.');

		$proposal = DB::transaction(function () use ($appointment, $user, $validated, $proposedUtc, $scheduling): AppointmentTimeProposal {
			$lockedAppointment = Appointment::query()
				->with('assignment')
				->lockForUpdate()
				->findOrFail($appointment->appointment_id);
			$assignment = $lockedAppointment->assignment;
			abort_unless($assignment, 422);
			User::query()->where('user_id', $assignment->user_id)->lockForUpdate()->firstOrFail();

			if ($lockedAppointment->proposals()->where('status', 'Pending')->exists()) {
				throw ValidationException::withMessages(['preferred_time' => 'There is already a pending time proposal for this appointment.']);
			}

			$scheduling->assertAvailable($assignment, $proposedUtc, $lockedAppointment->appointment_id);

			return $lockedAppointment->proposals()->create([
				'proposed_by_user_id' => $user->user_id,
				'starts_at_utc' => $proposedUtc->format('Y-m-d H:i:s'),
				'preferred_date' => $validated['preferred_date'],
				'preferred_time' => $validated['preferred_time'],
				'timezone' => $validated['timezone'],
				'message' => $validated['message'] ?? null,
				'status' => 'Pending',
			]);
		});

		$appointment->loadMissing(['assignment.user', 'client']);
		$recipients = $this->proposalRecipients($appointment, $user);
		$this->notifyProposalParticipants(
			$recipients,
			$appointment,
			$proposal,
			'New appointment time proposal',
			$user->name . ' proposed ' . $localStart->format('M j, Y g:i A') . '.'
		);

		NotificationService::oversight([
			'type' => 'listing',
			'title' => 'New appointment time proposal',
			'body' => $user->name . ' proposed ' . $localStart->format('M j, Y g:i A') . '.',
			'url' => route('notifications.index'),
			'appointment_id' => $appointment->appointment_id,
		], $user, $recipients);

		$notified = $recipients->pluck('name')->filter()->implode(', ');

		return redirect()->back()->with('success', 'Your new appointment time was proposed.')->with('popup', [
			'title' => 'Proposal sent',
			'message' => 'Your new appointment time was proposed' . ($notified ? " and {$notified} was notified." : '.') . ' The schedule stays the same until they accept.',
		]);
	}

	public function respondToProposal(Request $request, AppointmentTimeProposal $proposal, AppointmentSchedulingService $scheduling)
	{
		$user = $request->user();
		if (! ($user instanceof User)) abort(403);

		$proposal->loadMissing(['appointment.assignment.user', 'appointment.client', 'proposedBy']);
		$appointment = $proposal->appointment;
		abort_unless($appointment && $this->canAccessAppointment($appointment, $user), 403);
		abort_if($proposal->status !== 'Pending', 409, 'This proposal has already been answered.');
		abort_unless(in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true), 409);
		$proposer = $proposal->proposedBy;
		$proposedByClient = $proposer instanceof User && $this->isClientForAppointment($appointment, $proposer);
		$canRespond = $proposedByClient
			? (int) $appointment->assignment?->user_id === (int) $user->user_id
			: $this->isClientForAppointment($appointment, $user);
		abort_unless($canRespond && (int) $proposal->proposed_by_user_id !== (int) $user->user_id, 403);

		$validated = $request->validate(['decision' => ['required', 'in:Accepted,Rejected']]);
		$decision = $validated['decision'];

		if ($decision === 'Accepted') {
			DB::transaction(function () use ($proposal, $scheduling): void {
				$lockedProposal = AppointmentTimeProposal::query()
					->with('appointment.assignment')
					->lockForUpdate()
					->findOrFail($proposal->proposal_id);
				$lockedAppointment = $lockedProposal->appointment;
				$assignment = $lockedAppointment?->assignment;
				abort_unless($lockedAppointment && $assignment, 422);
				abort_if($lockedProposal->status !== 'Pending', 409, 'This proposal has already been answered.');
				User::query()->where('user_id', $assignment->user_id)->lockForUpdate()->firstOrFail();
				$scheduling->assertAvailable(
					$assignment,
					CarbonImmutable::parse($lockedProposal->starts_at_utc, 'UTC')->utc(),
					$lockedAppointment->appointment_id
				);

				$appointmentTimezone = $lockedAppointment->client_timezone ?: config('appointments.default_timezone');
				$clientLocalStart = CarbonImmutable::parse($lockedProposal->starts_at_utc, 'UTC')->setTimezone($appointmentTimezone);
				$lockedAppointment->update([
					'starts_at_utc' => $clientLocalStart->utc()->format('Y-m-d H:i:s'),
					'preferred_date' => $clientLocalStart->toDateString(),
					'preferred_time' => $clientLocalStart->format('H:i:s'),
					'appointment_status' => 'Confirmed',
					'completed_at' => null,
				]);
				$lockedProposal->update(['status' => 'Accepted', 'responded_at' => now()]);
				$lockedAppointment->proposals()
					->where('status', 'Pending')
					->where('proposal_id', '!=', $lockedProposal->proposal_id)
					->update(['status' => 'Rejected', 'responded_at' => now()]);
			});
		} else {
			DB::transaction(function () use ($proposal): void {
				$lockedProposal = AppointmentTimeProposal::query()->lockForUpdate()->findOrFail($proposal->proposal_id);
				abort_if($lockedProposal->status !== 'Pending', 409, 'This proposal has already been answered.');
				$lockedProposal->update(['status' => 'Rejected', 'responded_at' => now()]);
			});
		}

		$proposal->refresh()->loadMissing('appointment.assignment.user', 'appointment.client', 'proposedBy');
		$recipients = collect([$proposal->proposedBy])->filter(fn ($recipient) => $recipient instanceof User && (int) $recipient->user_id !== (int) $user->user_id);
		$this->notifyProposalParticipants(
			$recipients,
			$proposal->appointment,
			$proposal,
			'Appointment time proposal ' . strtolower($decision),
			$user->name . ' ' . strtolower($decision) . ' the proposed appointment time.'
		);
		AppNotification::query()
			->where('user_id', $user->user_id)
			->where('appointment_proposal_id', $proposal->proposal_id)
			->whereNull('read_at')
			->update(['read_at' => now()]);

		return redirect()->back()->with('success', 'The appointment time proposal was ' . strtolower($decision) . '.')->with('popup', [
			'title' => 'Proposal ' . strtolower($decision),
			'message' => 'The appointment time proposal was ' . strtolower($decision) . '. The other participant has been notified.',
		]);
	}

	public function blockDate(Request $request)
    {
        $user = Auth::user();
        if (! ($user instanceof User)) abort(403);
        
        // Ensure authorized roles can block dates
        abort_unless(in_array($user->actingRole(), ['admin', 'broker', 'agent', 'lead_broker'], true), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
			'timezone' => ['nullable', 'timezone'],
        ]);

        // 1. Create the blocked date/time record
		$validated['timezone'] = $validated['timezone'] ?: ($user->timezone ?: config('appointments.default_timezone'));
		$validated['acting_role'] = $user->actingRole();
		$user->blockedDates()->create($validated);

        // 2. Format details for the notification body
        $timeInfo = !empty($validated['start_time']) 
            ? ' from ' . $validated['start_time'] . ' to ' . ($validated['end_time'] ?? 'onwards') 
            : ' for the whole day';
            
        $reasonText = !empty($validated['reason']) ? ' Reason: ' . $validated['reason'] : '';

        // 3. Admin oversight only; other staff only receive their own appointments and messages.
        NotificationService::oversight([
            'type' => 'team',
            'title' => 'Schedule Blocked',
            'body' => $user->name . ' blocked off ' . $validated['date'] . $timeInfo . '.' . $reasonText,
        ], $user);

        return redirect()->back()->with('success', 'Date blocked successfully and admins notified.');
    }

	private function ownerAssignment(Property $property): PropertyAssign
	{
		abort_unless($property->user_id, 422);

		$assignment = $property->assignments()->where('user_id', $property->user_id)->first();
		if (! $assignment) {
			return $property->assignments()->create([
				'user_id' => $property->user_id,
				'assigned_date' => now()->toDateString(),
				'status' => 'Approved',
			]);
		}

		if ($assignment->status !== 'Approved') {
			$assignment->update(['status' => 'Approved']);
		}

		return $assignment;
	}

	private function resolveAvailableAssignment(Property $property, int $assignmentId): PropertyAssign
	{
		$assignment = $this->ownerAssignment($property)->load('user');
		abort_unless((int) $assignment->assign_id === $assignmentId, 404);
		abort_unless($assignment->user?->account_status === 'Active', 404);

		return $assignment;
	}

	private function isClientForAppointment(Appointment $appointment, User $user): bool
	{
		return $user->normalizedRole() === 'client'
			&& ((int) $appointment->client_id === (int) $user->user_id
				|| ($appointment->client_id === null && strcasecmp($appointment->contact_email, $user->email) === 0));
	}

	private function canAccessAppointment(Appointment $appointment, User $user): bool
	{
		return ($user->actingRole() === 'admin' && ($appointment->assignment?->property?->isManageableByAdmin($user) ?? true))
			|| (int) $appointment->assignment?->user_id === (int) $user->user_id
			|| $this->isClientForAppointment($appointment, $user);
	}

	private function canAdminUpdateAppointment(Appointment $appointment, User $user): bool
	{
		$property = $appointment->assignment?->property;

		return $property instanceof Property
			&& $property->listing_mode === 'admin'
			&& (int) $property->user_id === (int) $user->user_id
			&& (int) $appointment->assignment?->user_id === (int) $user->user_id;
	}

	private function appointmentClient(Appointment $appointment): ?User
	{
		if ($appointment->client instanceof User) {
			return $appointment->client;
		}

		return User::query()
			->where('email', $appointment->contact_email)
			->where('role_name', 'Client')
			->where('account_status', 'Active')
			->first();
	}

	private function proposalRecipients(Appointment $appointment, User $proposer): \Illuminate\Support\Collection
	{
		$recipients = collect();
		$professional = $appointment->assignment?->user;
		$client = $this->appointmentClient($appointment);

		if ($proposer->actingRole() === 'admin') {
			$recipients->push($professional, $client);
		} elseif ((int) $appointment->assignment?->user_id === (int) $proposer->user_id) {
			$recipients->push($client);
		} else {
			$recipients->push($professional);
		}

		return $recipients
			->filter(fn ($recipient) => $recipient instanceof User && (int) $recipient->user_id !== (int) $proposer->user_id)
			->unique('user_id')
			->values();
	}

	private function notifyProposalParticipants(iterable $recipients, Appointment $appointment, AppointmentTimeProposal $proposal, string $title, string $body): void
	{
		foreach ($recipients as $recipient) {
			if (! $recipient instanceof User) {
				continue;
			}

			AppNotification::create([
				'user_id' => $recipient->user_id,
				'type' => 'listing',
				'title' => $title,
				'body' => $body,
				'url' => route('notifications.index'),
				'appointment_proposal_id' => $proposal->proposal_id,
				'appointment_id' => $appointment->appointment_id,
			]);

			try {
				Mail::to($recipient->email, $recipient->name)->queue(new ActivityMail($title, $body . "\n\nOpen LJS Realty to review the appointment."));
			} catch (\Throwable $exception) {
				Log::warning('Appointment proposal email could not be queued.', [
					'appointment_id' => $appointment->appointment_id,
					'recipient_id' => $recipient->user_id,
					'error' => $exception->getMessage(),
				]);
			}
		}
	}

	private function notifyAppointmentRecipient(PropertyAssign $assignment, Property $property, Appointment $appointmentRecord, array $appointment): void
	{
		$recipient = $assignment->user;
		if (! $recipient instanceof User) {
			return;
		}

		AppNotification::create([
			'user_id' => $recipient->user_id,
			'type' => 'listing',
			'title' => 'New appointment request',
			'body' => $appointment['contact_name'] . ' requested a ' . strtolower($appointment['appointment_type']) . ' for ' . $property->property_name . '.',
			'url' => route('notifications.index'),
			'appointment_id' => $appointmentRecord->appointment_id,
		]);

		NotificationService::oversight([
			'type' => 'listing',
			'title' => 'New appointment request',
			'body' => $appointment['contact_name'] . ' requested a ' . strtolower($appointment['appointment_type']) . ' for ' . $property->property_name . '.',
			'url' => route('notifications.index'),
			'appointment_id' => $appointmentRecord->appointment_id,
		], null, [$recipient]);
		try {
			$when = isset($appointment['slot_start_utc'])
				? CarbonImmutable::parse($appointment['slot_start_utc'], 'UTC')
					->setTimezone($recipient->timezone ?: config('appointments.default_timezone'))
					->format('M j, Y g:i A')
				: $appointment['preferred_date'] . ' ' . $appointment['preferred_time'];
			Mail::to($recipient->email, $recipient->name)->queue(new ActivityMail(
				'New appointment request: ' . $property->property_name,
				"You have a new appointment request for {$property->property_name}.\n\n"
				. "Client: {$appointment['contact_name']}\n"
				. "Type: {$appointment['appointment_type']}\n"
				. "When: {$when}\n\n"
				. 'Open your appointments in LJS Realty to manage the request.'
			));
		} catch (\Throwable $exception) {
			Log::warning('Appointment request email could not be sent.', ['appointment_property_id' => $property->property_id, 'recipient_id' => $recipient->user_id, 'error' => $exception->getMessage()]);
		}
	}

	private function notifyAppointmentStatus(Appointment $appointment, string $status, User $actor): void
	{
		$propertyName = $appointment->assignment?->property?->property_name ?? 'your property appointment';
		$body = "{$actor->name} changed your appointment for {$propertyName} to {$status}.";
		if ($status === 'Cancelled' && filled($appointment->cancellation_reason)) {
			$body .= ' Reason: ' . $appointment->cancellation_reason;
		}
		$recipients = collect([$this->appointmentClient($appointment), $appointment->assignment?->user])
			->filter(fn ($recipient) => $recipient instanceof User && (int) $recipient->user_id !== (int) $actor->user_id)
			->unique('user_id');

		NotificationService::oversight([
			'type' => 'listing',
			'title' => 'Appointment ' . strtolower($status),
			'body' => $body,
			'url' => route('notifications.index'),
			'appointment_id' => $appointment->appointment_id,
		], $actor, $recipients);

		if ($status === 'Completed') {
			NotificationService::reviewPrompt($this->appointmentClient($appointment), $appointment->assignment?->user, 'your appointment');
		}

		foreach ($recipients as $recipient) {
			AppNotification::create([
				'user_id' => $recipient->user_id,
				'type' => 'listing',
				'title' => 'Appointment ' . strtolower($status),
				'body' => $body,
				'url' => route('notifications.index'),
				'appointment_id' => $appointment->appointment_id,
			]);

			try {
				Mail::to($recipient->email, $recipient->name)->queue(new ActivityMail(
					'Appointment update: ' . $status,
					$this->appointmentStatusEmailBody($appointment, $status, $actor, $recipient)
				));
			} catch (\Throwable $exception) {
				Log::warning('Appointment status email could not be sent.', [
					'appointment_id' => $appointment->appointment_id,
					'recipient_id' => $recipient->user_id,
					'error' => $exception->getMessage(),
				]);
			}
		}
	}

 	private function appointmentStatusEmailBody(Appointment $appointment, string $status, User $actor, User $recipient): string
	{
		$propertyName = $appointment->assignment?->property?->property_name ?? 'your property appointment';
		$timezone = $recipient->timezone ?: ($appointment->client_timezone ?: config('appointments.default_timezone'));
		$when = $appointment->startInTimezone($timezone)->format('M j, Y g:i A');
		$professional = $appointment->assignment?->user?->name;

		$lines = [
			"Your appointment for {$propertyName} was updated to {$status}.",
			"",
			"Property: {$propertyName}",
			"Status: {$status}",
			"When: {$when}",
			"Appointment type: {$appointment->appointment_type}",
		];

		if ($professional) {
			$lines[] = "Handled by: {$professional}";
		}

		if ($status === 'Cancelled' && filled($appointment->cancellation_reason)) {
			$lines[] = "Cancellation reason: {$appointment->cancellation_reason}";
		}

		$lines[] = "";
		$lines[] = "Updated by: {$actor->name}";
		$lines[] = "You do not need to log in just to see this update, but you can open LJS Realty if you want to manage the appointment.";

		return implode("\n", $lines);
	}
}
