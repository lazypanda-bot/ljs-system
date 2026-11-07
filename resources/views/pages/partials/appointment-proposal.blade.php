@php
    $proposal = $appointment->pendingProposal();
    $viewer = auth()->user();
    $proposalExpired = $proposal && $proposal->starts_at_utc->isPast();
    $isClient = fn ($person) => $person && $person->normalizedRole() === 'client'
        && ((int) $appointment->client_id === (int) $person->user_id
            || ($appointment->client_id === null && strcasecmp($appointment->contact_email, $person->email) === 0));
    $canRespond = ($allowResponse ?? true) && $proposal && in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true)
        && (int) $proposal->proposed_by_user_id !== (int) $viewer->user_id
        && ($isClient($proposal->proposedBy)
            ? (int) $appointment->assignment?->user_id === (int) $viewer->user_id
            : $isClient($viewer));
@endphp
@if ($proposal)
    <div class="appointment-proposal">
        <p><strong>Proposed time:</strong> {{ $proposal->starts_at_utc->setTimezone($viewerTimezone)->format('M j, Y · g:i A') }}</p>
        <small>Proposed by {{ $proposal->proposedBy?->name ?? 'Appointment participant' }}</small>
        @if ($proposal->message)<p>{{ $proposal->message }}</p>@endif
        @if ((int) old('response_proposal_id') === (int) $proposal->proposal_id && $errors->any())
            <p class="appointment-proposal-error" role="alert">{{ $errors->first() }}</p>
        @endif
        @if ($canRespond)
            @if ($proposalExpired)
                <p class="appointment-proposal-error" role="alert">This proposed time has passed. Decline it so a new time can be proposed.</p>
            @endif
            <form method="POST" action="{{ route('appointments.proposals.respond', $proposal) }}" class="appointment-proposal-response">
                @csrf @method('PATCH')
                <input type="hidden" name="response_proposal_id" value="{{ $proposal->proposal_id }}">
                <button type="submit" name="decision" value="Accepted" @disabled($proposalExpired)>Accept</button>
                <button type="submit" name="decision" value="Rejected">Decline</button>
            </form>
        @elseif (in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true))
            <small>Waiting for the other participant to respond.</small>
        @endif
    </div>
@endif
