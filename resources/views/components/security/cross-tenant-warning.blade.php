@props([
    'message' => 'Cross-tenant access attempt detected and blocked.',
])

<x-ui.alert variant="error" {{ $attributes }}>
    <div class="space-y-1">
        <p class="font-semibold">Security Boundary Enforced</p>
        <p>{{ $message }}</p>
    </div>
</x-ui.alert>
