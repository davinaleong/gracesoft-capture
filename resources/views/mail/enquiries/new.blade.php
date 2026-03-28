<p>A new enquiry was submitted.</p>
<p><strong>Form:</strong> {{ $enquiry->form?->name ?? 'N/A' }}</p>
<p><strong>Name:</strong> {{ $enquiry->name }}</p>
<p><strong>Email:</strong> {{ $enquiry->email }}</p>
<p><strong>Subject:</strong> {{ $enquiry->subject }}</p>
<p><strong>Message:</strong></p>
<p>{{ $enquiry->message }}</p>
