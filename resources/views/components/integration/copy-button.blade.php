@props([
    'code',
    'token',
])

@php
    $buttonId = 'copy-embed-' . $token;
@endphp

<x-ui.button
    type="button"
    variant="neutral"
    size="sm"
    id="{{ $buttonId }}"
    data-label="Copy embed code"
    onclick="(async () => {
        const text = @js($code);
        let copied = false;

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
                copied = true;
            }
        } catch (error) {
            copied = false;
        }

        if (!copied) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            }

            document.body.removeChild(textarea);
        }

        const originalLabel = this.dataset.label || 'Copy embed code';
        this.innerText = copied ? 'Copied' : 'Copy failed';
        setTimeout(() => {
            this.innerText = originalLabel;
        }, 1200);
    })();"
>
    Copy embed code
</x-ui.button>
