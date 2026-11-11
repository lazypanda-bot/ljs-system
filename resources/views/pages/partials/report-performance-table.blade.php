<div class="report-table-wrap"><table class="report-staff-table"><thead><tr><th>Professional</th><th>Listings</th><th>Inquiries</th><th>Appointments</th><th>Completed</th><th>Cancelled</th><th>Sold</th><th>Completion</th><th>Sales</th><th>Rating</th><th>Team share</th></tr></thead><tbody>
    @forelse ($perUser as $row)
        <tr data-staff-row="{{ $row['staffUser']->user_id }}">
            <th scope="row">{{ $row['staffUser']->name }}<small>{{ $row['staffUser']->role_name }}</small></th>
            <td>{{ $row['propertiesCount'] }}<small>{{ $row['activeListingsCount'] }} active</small></td>
            <td>{{ $row['inquiriesCount'] }}</td>
            <td>{{ $row['appointmentsCount'] }}</td>
            <td>{{ $row['completedCount'] }}</td>
            <td>{{ $row['cancelledCount'] }}</td>
            <td>{{ $row['soldCount'] }}</td>
            <td>{{ $row['completionRate'] }}%</td>
            <td>{{ $row['salesConversionRate'] }}%</td>
            <td>{{ $row['averageRating'] ?: 'n/a' }}<small>{{ $row['reviewsCount'] }} reviews</small></td>
            <td><small>{{ $row['listingShare'] }}% listings</small><small>{{ $row['salesShare'] }}% sales</small></td>
        </tr>
    @empty
        <tr><td colspan="11" class="report-empty">No performance activity for this selection.</td></tr>
    @endforelse
</tbody></table></div>
{{ $perUser->links('pagination.ljs') }}
