<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormManagementController extends Controller
{
    public function index(): View
    {
        return view('forms.index', [
            'forms' => Form::query()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('forms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'account_id' => ['required', 'uuid'],
            'application_id' => ['required', 'uuid'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $form = Form::create([
            'name' => $data['name'],
            'account_id' => $data['account_id'],
            'application_id' => $data['application_id'],
            'is_active' => true,
            'settings' => [
                'notification_email' => $data['notification_email'] ?? null,
            ],
        ]);

        return redirect()
            ->route('manage.forms.edit', $form)
            ->with('status', 'Form created successfully.');
    }

    public function edit(Form $form): View
    {
        return view('forms.edit', [
            'form' => $form,
        ]);
    }

    public function update(Request $request, Form $form): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'account_id' => ['required', 'uuid'],
            'application_id' => ['required', 'uuid'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $settings = $form->settings ?? [];
        $settings['notification_email'] = $data['notification_email'] ?? null;

        $form->update([
            'name' => $data['name'],
            'account_id' => $data['account_id'],
            'application_id' => $data['application_id'],
            'settings' => $settings,
        ]);

        return redirect()
            ->route('manage.forms.edit', $form)
            ->with('status', 'Form updated successfully.');
    }

    public function toggleActive(Form $form): RedirectResponse
    {
        $form->update([
            'is_active' => ! $form->is_active,
        ]);

        return redirect()
            ->route('manage.forms.index')
            ->with('status', $form->is_active ? 'Form activated.' : 'Form deactivated.');
    }
}
