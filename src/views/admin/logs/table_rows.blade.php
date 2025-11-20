@forelse($logs as $log)
    @php
        $badgeClass = match($log['level']) {
            'error', 'critical', 'emergency' => 'danger',
            'warning', 'alert' => 'warning text-dark',
            'info', 'notice' => 'info text-dark',
            default => 'secondary'
        };
    @endphp
    <tr style="cursor: pointer;" onclick="showLogDetail('{{ rawurlencode($log['full']) }}')">
        <td>
            <span class="badge bg-{{ $badgeClass }} text-uppercase w-100">{{ $log['level'] }}</span>
        </td>
        <td class="small text-muted">{{ $log['date'] }}</td>
        <td class="text-truncate" style="max-width: 500px;">
            {{ Str::limit($log['message'], 120) }}
        </td>
    </tr>
@empty
    <tr><td colspan="3" class="text-center text-muted py-4">No logs found matching criteria.</td></tr>
@endforelse