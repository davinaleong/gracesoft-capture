<p>A new enquiry was submitted.</p>
<p><strong>Form:</strong> {{ $enquiry->form?->name ?? 'N/A' }}</p>
<p><strong>Name:</strong> {{ $maskedName }}</p>
<p><strong>Email:</strong> {{ $maskedEmail }}</p>
<p><strong>Subject:</strong> {{ $subjectPreview }}</p>
<p><strong>Message Preview:</strong></p>
<p>{{ $messagePreview }}</p>
