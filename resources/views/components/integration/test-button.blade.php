@props([
    'token',
])

<x-ui.button tag="a" href="{{ route('forms.show', $token) }}" target="_blank" rel="noreferrer" variant="secondary" size="sm">
    Send Test Enquiry
</x-ui.button>
