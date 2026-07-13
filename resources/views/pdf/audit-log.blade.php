@php
    /** @var \Illuminate\Support\Collection $activities */
    $formatValue = function ($value): string {
        if (is_null($value)) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return \Illuminate\Support\Str::limit((string) $value, 120);
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('audit.export.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1c1917;
            font-size: 10px;
            margin: 0;
        }
        .header { border-bottom: 2px solid #6d28d9; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 18px; margin: 0 0 2px; color: #6d28d9; }
        .header .app { font-size: 11px; color: #78716c; margin: 0; }
        .meta { margin: 8px 0 0; font-size: 9px; color: #57534e; }
        .meta span { display: inline-block; margin-right: 14px; }
        .scope {
            margin-top: 8px;
            padding: 5px 8px;
            background: #f5f3ff;
            border-radius: 4px;
            font-size: 9px;
            color: #4c1d95;
        }
        .scope strong { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #78716c;
            border-bottom: 1px solid #d6d3d1;
            padding: 6px 8px;
        }
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e7e5e4;
            vertical-align: top;
        }
        tbody tr:nth-child(even) { background: #fafaf9; }
        .action {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            background: #ede9fe;
            color: #6d28d9;
            font-weight: 700;
            font-size: 8px;
            text-transform: capitalize;
        }
        .muted { color: #a8a29e; }
        .changes { margin: 0; padding: 0; list-style: none; }
        .changes li { margin-bottom: 2px; }
        .changes .field { font-weight: 700; }
        .changes .old { color: #b91c1c; }
        .changes .new { color: #15803d; }
        .empty { padding: 24px; text-align: center; color: #78716c; }
        .note { margin-top: 10px; font-size: 9px; color: #b45309; }
        .col-when { width: 13%; }
        .col-user { width: 14%; }
        .col-action { width: 9%; }
        .col-record { width: 16%; }
    </style>
</head>
<body>
    <div class="header">
        <p class="app">{{ config('app.name') }}</p>
        <h1>{{ __('audit.export.title') }}</h1>
        <div class="meta">
            <span>{{ __('audit.export.generated_at', ['datetime' => $generatedAt->format('M j, Y g:i A')]) }}</span>
            <span>{{ __('audit.export.total', ['count' => $activities->count()]) }}</span>
        </div>

        @if ($patient)
            <div class="scope"><strong>{{ __('audit.export.scope_patient', ['name' => $patient['name']]) }}</strong></div>
        @endif

        @if (! empty($filterSummary))
            <div class="scope">{{ __('audit.export.filters_label') }}: {{ implode(' · ', $filterSummary) }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-when">{{ __('audit.export.col_when') }}</th>
                <th class="col-user">{{ __('audit.export.col_user') }}</th>
                <th class="col-action">{{ __('audit.export.col_action') }}</th>
                <th class="col-record">{{ __('audit.export.col_record') }}</th>
                <th>{{ __('audit.export.col_changes') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($activities as $activity)
                <tr>
                    <td>{{ $activity['created_at'] ? \Illuminate\Support\Carbon::parse($activity['created_at'])->format('M j, Y g:i A') : '—' }}</td>
                    <td>{{ $activity['causer_name'] ?? __('audit.export.system_user') }}</td>
                    <td><span class="action">{{ $activity['event'] ? __('audit.actions.'.$activity['event']) : '—' }}</span></td>
                    <td>
                        {{ $activity['subject_key'] ? __('audit.subjects.'.$activity['subject_key']) : ($activity['subject_type'] ?? '—') }}
                        @if ($activity['subject_id'])
                            <span class="muted">#{{ $activity['subject_id'] }}</span>
                        @endif
                    </td>
                    <td>
                        @if (empty($activity['changes']))
                            <span class="muted">—</span>
                        @else
                            <ul class="changes">
                                @foreach ($activity['changes'] as $change)
                                    <li>
                                        <span class="field">{{ $change['field'] }}:</span>
                                        <span class="old">{{ $formatValue($change['old']) }}</span>
                                        &rarr;
                                        <span class="new">{{ $formatValue($change['new']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">{{ __('audit.export.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($truncated)
        <p class="note">{{ __('audit.export.truncated', ['count' => $limit]) }}</p>
    @endif
</body>
</html>
