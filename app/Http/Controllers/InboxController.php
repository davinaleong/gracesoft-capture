<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $enquiries = Enquiry::query()
            ->with('form')
            ->when(in_array($status, ['new', 'contacted', 'closed'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('inbox.index', [
            'enquiries' => $enquiries,
            'selectedStatus' => $status,
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        $enquiry->load('form');

        return view('inbox.show', [
            'enquiry' => $enquiry,
        ]);
    }

    public function updateStatus(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $nextStatus = $data['status'];

        if (! $this->isValidTransition($enquiry->status, $nextStatus)) {
            return back()->withErrors([
                'status' => 'Invalid status transition requested.',
            ]);
        }

        $updates = ['status' => $nextStatus];

        if ($nextStatus === 'contacted' && $enquiry->contacted_at === null) {
            $updates['contacted_at'] = now();
        }

        if ($nextStatus === 'closed' && $enquiry->closed_at === null) {
            $updates['closed_at'] = now();
        }

        $enquiry->update($updates);

        return redirect()
            ->route('inbox.show', $enquiry)
            ->with('status', 'Enquiry status updated.');
    }

    private function isValidTransition(string $current, string $target): bool
    {
        if ($current === $target) {
            return true;
        }

        $allowed = [
            'new' => ['contacted'],
            'contacted' => ['closed'],
            'closed' => [],
        ];

        return in_array($target, $allowed[$current] ?? [], true);
    }
}
