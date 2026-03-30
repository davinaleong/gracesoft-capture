@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4">
        <div class="mb-3">
            <h1 class="text-xl font-semibold text-gs-black-900">Admin Compliance Monitoring</h1>
            <p class="text-sm text-gs-black-700">Audit logs, data access logs, and data subject requests.</p>
        </div>

        <form method="get" action="{{ route('admin.compliance.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,280px)_auto] md:items-end">
            <x-ui.field for="account_id" label="Filter by Account ID">
                <x-ui.input id="account_id" name="account_id" :value="$accountId" placeholder="Optional UUID" />
            </x-ui.field>
            <div>
                <x-ui.button type="submit">Apply</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card class="mb-4">
        <h2 class="mb-3 text-lg font-semibold">Verification Enforcement Telemetry (Today)</h2>
        <div class="mb-3 text-sm text-gs-black-700">
            Total blocked actions: <strong>{{ data_get($verificationBlockedSummary, 'total', 0) }}</strong>
        </div>
        <x-ui.table>
            <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                <tr>
                    <th class="p-2">Scope</th>
                    <th class="p-2">Blocked Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach (data_get($verificationBlockedSummary, 'breakdown', []) as $scope => $count)
                    <tr class="border-b border-gray-200">
                        <td class="p-2">{{ $scope }}</td>
                        <td class="p-2">{{ $count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </x-ui.card>

    <x-ui.card class="mb-4">
        <h2 class="mb-3 text-lg font-semibold">Break-Glass Controls</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <form method="post" action="{{ route('admin.compliance.break-glass.request') }}" class="space-y-3">
                @csrf
                <x-ui.field for="bg_account_id" label="Account ID">
                    <x-ui.input id="bg_account_id" name="account_id" :value="$accountId" placeholder="Target account UUID" />
                </x-ui.field>
                <x-ui.field for="bg_scope" label="Scope">
                    <x-ui.select id="bg_scope" name="scope" required>
                        <option value="dsr_sensitive">dsr_sensitive</option>
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field for="bg_reason" label="Reason">
                    <x-ui.textarea id="bg_reason" name="reason" rows="3" required placeholder="Why elevated access is required" />
                </x-ui.field>
                <x-ui.button type="submit" variant="secondary">Request Break-Glass</x-ui.button>
            </form>

            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gs-black-700">Recent Requests</h3>
                <x-ui.table>
                    <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                        <tr>
                            <th class="p-2">ID</th>
                            <th class="p-2">Scope</th>
                            <th class="p-2">Requested By</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($breakGlassApprovals as $approval)
                            <tr class="border-b border-gray-200">
                                <td class="p-2">{{ $approval->id }}</td>
                                <td class="p-2">{{ $approval->scope }}</td>
                                <td class="p-2">{{ $approval->requested_by_administrator_uuid }}</td>
                                <td class="p-2">{{ $approval->approved_at ? 'approved' : 'pending' }}</td>
                                <td class="p-2">
                                    @if (! $approval->approved_at)
                                        <form method="post" action="{{ route('admin.compliance.break-glass.approve', $approval) }}" class="flex items-center gap-2">
                                            @csrf
                                            <x-ui.input name="expires_minutes" value="30" class="w-20" />
                                            <x-ui.button type="submit" size="sm" variant="danger">Approve</x-ui.button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gs-black-600">{{ $approval->expires_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-gs-black-600">No break-glass requests.</td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>
        </div>
    </x-ui.card>

    <div class="grid gap-4">
        <x-ui.card>
            <h2 class="mb-3 text-lg font-semibold">Audit Logs</h2>
            <x-ui.table>
                <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                    <tr>
                        <th class="p-2">Time</th>
                        <th class="p-2">Actor</th>
                        <th class="p-2">Action</th>
                        <th class="p-2">Target</th>
                        <th class="p-2">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $log)
                        <tr class="border-b border-gray-200">
                            <td class="p-2">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="p-2">{{ $log->actor_type }}</td>
                            <td class="p-2">{{ $log->action }}</td>
                            <td class="p-2">{{ $log->target_type }} {{ $log->target_id }}</td>
                            <td class="p-2">{{ $log->access_reason ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-center text-gs-black-600">No audit logs.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-3 text-lg font-semibold">Data Access Logs</h2>
            <x-ui.table>
                <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                    <tr>
                        <th class="p-2">Time</th>
                        <th class="p-2">Actor</th>
                        <th class="p-2">Target</th>
                        <th class="p-2">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dataAccessLogs as $log)
                        <tr class="border-b border-gray-200">
                            <td class="p-2">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="p-2">{{ $log->actor_type }}</td>
                            <td class="p-2">{{ $log->target_type }} {{ $log->target_id }}</td>
                            <td class="p-2">{{ $log->access_reason ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-4 text-center text-gs-black-600">No data access logs.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-3 text-lg font-semibold">Data Subject Requests</h2>
            <x-ui.table>
                <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                    <tr>
                        <th class="p-2">Requested</th>
                        <th class="p-2">Type</th>
                        <th class="p-2">Subject</th>
                        <th class="p-2">Status</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dsrRequests as $item)
                        <tr class="border-b border-gray-200">
                            <td class="p-2">{{ $item->requested_at?->format('Y-m-d H:i') }}</td>
                            <td class="p-2">{{ $item->request_type }}</td>
                            <td class="p-2">{{ $item->subject_email ?? $item->subject_user_id ?? '-' }}</td>
                            <td class="p-2">{{ $item->status }}</td>
                            <td class="p-2">
                                <form method="post" action="{{ route('admin.compliance.dsr.update', $item) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <x-ui.select name="status" required>
                                        <option value="pending" @selected($item->status === 'pending')>pending</option>
                                        <option value="in_progress" @selected($item->status === 'in_progress')>in_progress</option>
                                        <option value="completed" @selected($item->status === 'completed')>completed</option>
                                        <option value="rejected" @selected($item->status === 'rejected')>rejected</option>
                                    </x-ui.select>
                                    <x-ui.input name="reason" :value="$item->reason" placeholder="Optional reason" />
                                    <x-ui.button type="submit" size="sm">Update</x-ui.button>
                                </form>
                                <form method="post" action="{{ route('admin.compliance.dsr.process', $item) }}" class="mt-2 flex flex-wrap items-center gap-2">
                                    @csrf
                                    <x-ui.input name="reason" placeholder="Processing reason" />
                                    <x-ui.button type="submit" size="sm" variant="success">Process {{ $item->request_type }}</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-center text-gs-black-600">No data subject requests.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
@endsection
