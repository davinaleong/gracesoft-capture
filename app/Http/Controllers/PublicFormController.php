<?php

namespace App\Http\Controllers;

use App\Jobs\SendEnquiryNotificationJob;
use App\Jobs\SendFormSubmissionWebhookJob;
use App\Jobs\SyncAnalyticsEventToHQJob;
use App\Models\Consent;
use App\Models\Enquiry;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PublicFormController extends Controller
{
    public function show(string $token): Response
    {
        $form = Form::query()
            ->where('public_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $request = request();

        $this->assertDomainAllowed($request, $form);

        $statusMessage = session('status');

        if (! is_string($statusMessage) || trim($statusMessage) === '') {
            $isSubmitted = filter_var($request->query('submitted', false), FILTER_VALIDATE_BOOLEAN);
            $statusMessage = $isSubmitted ? 'Thanks, your message has been sent.' : null;
        }

        return response()->view('form', [
            'form' => $form,
            'customFields' => $this->resolvedCustomFields($form),
            'themeClass' => $this->resolvedThemeClass($form),
            'embedSurface' => $this->resolvedEmbedSurface($request),
            'statusMessage' => $statusMessage,
        ]);
    }

    public function submit(Request $request, string $token): RedirectResponse|Response
    {
        $form = Form::query()
            ->where('public_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $this->assertDomainAllowed($request, $form);

        $requireConsent = (bool) config('capture.features.require_form_consent', false);

        $data = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'max:0'],
            'consent_accepted' => $requireConsent ? ['required', 'accepted'] : ['sometimes', 'accepted'],
            'attachment' => (bool) config('capture.features.enable_form_file_uploads', true)
                ? ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,doc,docx,txt', 'max:5120']
                : ['nullable'],
        ], $this->customFieldValidationRules($form)));

        $metadata = null;

        if ((bool) config('capture.features.store_submission_request_metadata', false)) {
            $metadata = [
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ];
        }

        $customFieldPayload = $this->extractCustomFieldPayload($form, $data);

        if ($customFieldPayload !== []) {
            $metadata = is_array($metadata) ? $metadata : [];
            $metadata['custom_fields'] = $customFieldPayload;
        }

        if ((bool) config('capture.features.enable_form_file_uploads', true) && $request->hasFile('attachment')) {
            $path = $request->file('attachment')?->store('form-uploads');

            if (is_string($path) && $path !== '') {
                $metadata = is_array($metadata) ? $metadata : [];
                $metadata['attachments'] = [
                    ['path' => $path],
                ];
            }
        }

        $enquiry = Enquiry::create([
            'form_id' => $form->id,
            'account_id' => $form->account_id,
            'application_id' => $form->application_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'new',
            'metadata' => $metadata,
        ]);

        if (($data['consent_accepted'] ?? false) === '1' || ($data['consent_accepted'] ?? false) === true || ($data['consent_accepted'] ?? false) === 'on') {
            Consent::query()->create([
                'account_id' => $form->account_id,
                'user_id' => null,
                'policy_type' => 'public_form_submission',
                'policy_version' => (string) config('capture.features.consent_policy_version', 'v1'),
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        $recipient = data_get($form->settings, 'notification_email') ?: config('mail.from.address');

        if (is_string($recipient) && $recipient !== '') {
            SendEnquiryNotificationJob::dispatch($enquiry->id, $recipient);
        }

        SyncAnalyticsEventToHQJob::dispatch([
            'event' => 'enquiry.created',
            'account_id' => $enquiry->account_id,
            'application_id' => $enquiry->application_id,
            'form_uuid' => $form->uuid,
            'enquiry_uuid' => $enquiry->uuid,
            'status' => $enquiry->status,
            'occurred_at' => now()->toIso8601String(),
        ]);

        $webhookUrl = trim((string) data_get($form->settings, 'webhook_url', ''));

        if ((bool) config('capture.features.enable_form_submission_webhooks', true) && $webhookUrl !== '') {
            SendFormSubmissionWebhookJob::dispatch($webhookUrl, [
                'event' => 'form.submission.received',
                'account_id' => $enquiry->account_id,
                'application_id' => $enquiry->application_id,
                'form_uuid' => $form->uuid,
                'enquiry_uuid' => $enquiry->uuid,
                'status' => $enquiry->status,
                'occurred_at' => now()->toIso8601String(),
            ]);
        }

        if ($request->expectsJson()) {
            return response([
                'message' => 'Enquiry submitted successfully.',
            ], 201);
        }

        if ((bool) config('capture.features.enable_form_success_redirect', false)) {
            $redirectUrl = $this->resolveSuccessRedirectUrl($form);

            if ($redirectUrl !== null) {
                return redirect()->away($redirectUrl);
            }
        }

        $surface = $this->resolvedEmbedSurface($request);

        return redirect()
            ->route('forms.show', [
                'token' => $form->public_token,
                'surface' => $surface,
                'submitted' => '1',
            ])
            ->with('status', 'Thanks, your message has been sent.');
    }

    private function assertDomainAllowed(Request $request, Form $form): void
    {
        if (! (bool) config('capture.features.enforce_form_domain_validation', false)) {
            return;
        }

        $allowedDomains = collect((array) data_get($form->settings, 'allowed_domains', []))
            ->map(fn ($domain): string => Str::lower(trim((string) $domain)))
            ->filter(fn (string $domain): bool => $domain !== '')
            ->values();

        if ($allowedDomains->isEmpty()) {
            return;
        }

        $originHost = $this->extractHost((string) $request->headers->get('Origin', ''));
        $refererHost = $this->extractHost((string) $request->headers->get('Referer', ''));
        $requestHost = Str::lower((string) $request->getHost());

        $candidateHosts = collect([$originHost, $refererHost])
            ->filter(fn (?string $host): bool => is_string($host) && $host !== '' && $host !== $requestHost)
            ->values();

        if ($candidateHosts->isEmpty()) {
            abort(403, 'Form access domain cannot be validated.');
        }

        $matches = $candidateHosts->contains(function (string $host) use ($allowedDomains): bool {
            return $allowedDomains->contains(function (string $allowedDomain) use ($host): bool {
                return $host === $allowedDomain || Str::endsWith($host, '.' . $allowedDomain);
            });
        });

        if (! $matches) {
            abort(403, 'Form access is not allowed from this domain.');
        }
    }

    private function extractHost(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return Str::lower($host);
    }

    private function resolveSuccessRedirectUrl(Form $form): ?string
    {
        $redirectUrl = trim((string) data_get($form->settings, 'success_redirect_url', ''));

        if ($redirectUrl === '') {
            return null;
        }

        $scheme = parse_url($redirectUrl, PHP_URL_SCHEME);

        if (! is_string($scheme) || ! in_array(Str::lower($scheme), ['http', 'https'], true)) {
            return null;
        }

        return $redirectUrl;
    }

    /**
     * @return array<int, array{name: string, label: string, type: string, required: bool}>
     */
    private function resolvedCustomFields(Form $form): array
    {
        if (! (bool) config('capture.features.enable_form_custom_fields', true)) {
            return [];
        }

        $fields = data_get($form->settings, 'custom_fields', []);

        if (! is_array($fields)) {
            return [];
        }

        return collect($fields)
            ->filter(fn ($field): bool => is_array($field))
            ->map(function (array $field): array {
                $name = trim((string) data_get($field, 'name', ''));
                $label = trim((string) data_get($field, 'label', $name));
                $type = trim((string) data_get($field, 'type', 'text'));

                return [
                    'name' => $name,
                    'label' => $label !== '' ? $label : $name,
                    'type' => in_array($type, ['text', 'email', 'textarea'], true) ? $type : 'text',
                    'required' => (bool) data_get($field, 'required', false),
                ];
            })
            ->filter(fn (array $field): bool => $field['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function customFieldValidationRules(Form $form): array
    {
        $rules = [];

        foreach ($this->resolvedCustomFields($form) as $field) {
            $key = 'custom_fields.' . $field['name'];
            $fieldRules = [$field['required'] ? 'required' : 'nullable', 'string', 'max:255'];

            if ($field['type'] === 'email') {
                $fieldRules[] = 'email';
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function extractCustomFieldPayload(Form $form, array $data): array
    {
        $submitted = data_get($data, 'custom_fields', []);

        if (! is_array($submitted)) {
            return [];
        }

        $allowedNames = collect($this->resolvedCustomFields($form))
            ->pluck('name')
            ->all();

        return collect($submitted)
            ->filter(fn ($value, $key): bool => in_array((string) $key, $allowedNames, true))
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->all();
    }

    private function resolvedThemeClass(Form $form): string
    {
        if (! (bool) config('capture.features.enable_form_custom_themes', true)) {
            return 'theme-default';
        }

        $theme = trim((string) data_get($form->settings, 'theme', 'default'));

        return match ($theme) {
            'sunrise' => 'theme-sunrise',
            'forest' => 'theme-forest',
            default => 'theme-default',
        };
    }

    private function resolvedEmbedSurface(Request $request): string
    {
        $surface = Str::lower(trim((string) $request->query('surface', 'card')));

        return in_array($surface, ['none', 'transparent'], true) ? 'none' : 'card';
    }
}
