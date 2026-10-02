<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentTimeProposal;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\BlockedDate;
use App\Services\AppointmentSchedulingService;
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

	public function create(Request $request, Property $property)
	{
		abort_unless($property->approval_status === 'Approved', 404);
		$referralCode = $request->filled('ref')
			? ReferralCode::query()->with('user')->where('code', $request->query('ref'))->where('property_id', $property->property_id)->firstOrFail()
			: null;
		$isAdminListing = $property->listing_mode === 'admin';
		$selectedAssignmentId = null;

		if ($isAdminListing) {
			$assignment = $this->ownerAssignment($property);
			$assignments = collect([$assignment->load('user')]);
			$selectedAssignmentId = $assignment->assign_id;
		} elseif ($referralCode) {
			abort_unless($referralCode->user?->account_status === 'Active', 404);

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

		$clientTimezone = $request->user()?->timezone ?: '';

		return view('customer.appointment-create', compact('property', 'assignments', 'referralCode', 'isAdminListing', 'selectedAssignmentId', 'clientTimezone'));
	}

	public function availability(Request $request, Property $property, AppointmentSchedulingService $scheduling)
	{
		abort_unless($property->approval_status === 'Approved', 404);

		$validated = $request->validate([
			'assign_id' => ['required', 'integer'],
			'date' => ['required', 'date_format:Y-m-d'],
			'timezone' => ['required', 'timezone'],
			'ref' => ['nullable', 'string', 'max:255'],
		]);

		$assignment = $this->resolveAvailableAssignment($property, (int) $validated['assign_id'], $validated['ref'] ?? null);

		return response()->json([
			'slots' => $scheduling->availableSlots($assignment, $validated['date'], $validated['timezone']),
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
		]);

		$assignment = $appointment->assignment;
		abort_unless($assignment, 422);

		return response()->json([
			'slots' => $scheduling->availableSlots($assignment, $validated['date'], $validated['timezone'], $appointment->appointment_id),
		]);
	}

	public function store(Request $request, Property $property, AppointmentSchedulingService $scheduling)
	{
		abort_unless($property->approval_status === 'Approved', 404);

		$validated = $request->validate([
			'assign_id' => ['nullable', 'integer'],
			'ref' => ['nullable', 'string', 'max:255'],
			'contact_name' => ['required', 'string', 'max:255'],
			'contact_number' => ['required', 'string', 'max:20'],
			'contact_email' => ['required', 'email', 'max:255'],
			'appointment_type' => ['required', 'string', 'max:100'],
			'preferred_date' => ['required', 'date_format:Y-m-d'],
			'preferred_time' => ['required', 'date_format:H:i'],
			'slot_start_utc' => ['nullable', 'date'],
			'client_timezone' => ['nullable', 'timezone'],
			'additional_note' => ['nullable', 'string', 'max:1000'],
		]);

		if ($property->listing_mode === 'admin') {
			$assignment = $this->ownerAssignment($property);
		} elseif (! empty($validated['ref'])) {
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
			// 1. If an assign_id was submitted explicitly, use it
            if (! empty($validated['assign_id'])) {
                $assignment = $property->assignments()
                    ->where('assign_id', $validated['assign_id'])
                    ->where('status', 'Approved')
                    ->firstOrFail();
            } else {
                // 2. Automatically look for an assignment belonging to the property's direct user/owner first
                $assignment = null;
                if ($property->user_id) {
                    $assignment = $property->assignments()
                        ->where('user_id', $property->user_id)
                        ->where('status', 'Approved')
                        ->first();
                }

                // 3. If the owner has no direct assignment, fallback to the first approved assignment
                if (!$assignment) {
                    $assignment = $property->assignments()
                        ->where('status', 'Approved')
                        ->first();
                }
            }
		}

		abort_unless($assignment, 422);

		$clientTimezone = $validated['client_timezone']
			?? ($request->user()?->timezone ?: config('appointments.default_timezone'));
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

		$client = $request->user();
		$appointment = DB::transaction(function () use ($assignment, $scheduling, $slotUtc, $clientTimezone, $validated, $client): Appointment {
			User::query()->where('user_id', $assignment->user_id)->lockForUpdate()->firstOrFail();
			$scheduling->assertAvailable($assignment, $slotUtc);

			return Appointment::create([
				'assign_id' => $assignment->assign_id,
				'client_id' => $client instanceof User && $client->normalizedRole() === 'client' ? $client->user_id : null,
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

		return redirect()->route('properties.show', $property)->with('success', 'Appointment request submitted.');
	}

	public function updateStatus(Request $request, Appointment $appointment)
	{
		$user = Auth::user();
		if (! ($user instanceof User)) abort(403);

		$isAdmin = $user->actingRole() === 'admin';
		$isProfessional = (int) $appointment->assignment?->user_id === (int) $user->user_id;
		$isClient = $this->isClientForAppointment($appointment, $user);
		abort_unless($isAdmin || $isProfessional || $isClient, 403);
		abort_unless($appointment->appointment_status === 'Pending', 409, 'This appointment status has already been set.');

		$allowedStatuses = $isClient && ! $isAdmin && ! $isProfessional
			? ['Cancelled']
			: ['Confirmed', 'Completed', 'Cancelled', 'Rejected'];
		$validated = $request->validate(['appointment_status' => ['required', 'in:' . implode(',', $allowedStatuses)]]);
		$appointment->update([
			'appointment_status' => $validated['appointment_status'],
			'completed_at' => $validated['appointment_status'] === 'Completed' ? ($appointment->completed_at ?? now()) : null,
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

		return redirect()->back()->with('success', 'Your new appointment time was proposed.');
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
			DB::transaction(function () use ($proposal, $scheduling, $appointment): void {
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

		return redirect()->back()->with('success', 'The appointment time proposal was ' . strtolower($decision) . '.');
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
		$user->blockedDates()->create($validated);

        // 2. Format details for the notification body
        $timeInfo = !empty($validated['start_time']) 
            ? ' from ' . $validated['start_time'] . ' to ' . ($validated['end_time'] ?? 'onwards') 
            : ' for the whole day';
            
        $reasonText = !empty($validated['reason']) ? ' Reason: ' . $validated['reason'] : '';

        // 3. Find other team members to notify
        $recipients = User::where('user_id', '!=', $user->user_id)->get()->filter(function ($recipient) {
            return in_array($recipient->actingRole(), ['admin', 'broker', 'agent', 'lead_broker'], true);
        });

        // 4. Create notifications using your exact AppNotification fillable schema
        foreach ($recipients as $recipient) {
            \App\Models\AppNotification::create([
                'user_id' => $recipient->user_id,
                'type'    => 'team',
                'title'   => 'Schedule Blocked',
                'body'    => $user->name . ' blocked off ' . $validated['date'] . $timeInfo . '.' . $reasonText,
                'url'     => null, // Optional: add a link if your calendar view has a specific URL
                'read_at' => null, // Unread by default
            ]);
        }

        return redirect()->back()->with('success', 'Date blocked successfully and team notified.');
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

	private function resolveAvailableAssignment(Property $property, int $assignmentId, ?string $referralCode): PropertyAssign
	{
		if ($property->listing_mode === 'admin') {
			$assignment = $this->ownerAssignment($property);
			abort_unless((int) $assignment->assign_id === $assignmentId, 404);
		} else {
			$assignmentQuery = $property->assignments()
				->where('assign_id', $assignmentId)
				->where('status', 'Approved')
				->with('user');

			if ($referralCode) {
				$referral = ReferralCode::query()
					->where('code', $referralCode)
					->where('property_id', $property->property_id)
					->firstOrFail();
				$assignmentQuery->where('user_id', $referral->user_id);
			}

			$assignment = $assignmentQuery->firstOrFail();
		}

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
		return $user->actingRole() === 'admin'
			|| (int) $appointment->assignment?->user_id === (int) $user->user_id
			|| $this->isClientForAppointment($appointment, $user);
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
		$recipients = collect([$this->appointmentClient($appointment), $appointment->assignment?->user])
			->filter(fn ($recipient) => $recipient instanceof User && (int) $recipient->user_id !== (int) $actor->user_id)
			->unique('user_id');

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
					$body . "\n\nLog in to LJS Realty for the latest details."
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
}
