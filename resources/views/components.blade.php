<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ config('app.name', 'Laravel') }} - Components</title>
	<link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
	<main class="container mx-auto p-4 space-y-4">
		<section class="space-y-4">
			<header class="space-y-2">
				<h1 class="text-4xl font-bold">Components</h1>
				<p>All components used by the application are listed below.</p>
			</header>

			<h2 class="text-2xl font-bold">Card</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
			</div>

			<h2 class="text-2xl font-bold">Alerts</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow space-y-4">
				<div class="bg-green-50 border border-green-300 rounded p-4 shadow space-y-2">
					<h2 class="text-green-600 text-xl font-bold">Lorem Ipsum</h2>
					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
				</div>

				<div class="bg-gs-purple-50 border border-gs-purple-300 rounded p-4 shadow space-y-2">
					<h2 class="text-gs-purple-600 text-xl font-bold">Lorem Ipsum</h2>
					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
				</div>
			</div>

			<h2 class="text-2xl font-bold">Badges</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<div class="flex items-center gap-4">
					<div class="text-sm text-gray-600 bg-gray-50 rounded-full px-2 py-1">Badge</div>
					<div class="text-sm text-blue-600 bg-blue-50 rounded-full px-2 py-1">Badge</div>
					<div class="text-sm text-gs-purple-600 bg-gs-purple-50 rounded-full px-2 py-1">Badge</div>
				</div>
			</div>

			<h2 class="text-2xl font-bold">Form Fields</h2>
			<form class="bg-white border border-gray-300 rounded p-4 shadow">
				<div class="space-y-1">
					<label for="example">Example Label<span class="text-red-500">*</span></label>
					<input type="text" id="example" name="example" class="w-full block bg-gs-black-50 rounded p-2">
				</div>
			</form>

			<h2 class="text-2xl font-bold">Buttons</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<div class="flex items-center gap-2">
					<button type="button" class="flex items-center gap-2 text-white bg-gs-purple-600 border border-gs-purple-600 rounded px-3 py-2">Button</button>
					<button type="button" class="flex items-center gap-2 text-gs-purple-600 bg-white border border-gs-purple-600 rounded px-3 py-2">Button</button>
				</div>
			</div>

			<h2 class="text-2xl font-bold">Table</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<div class="overflow-x-auto">
					<table class="w-full border-collapse bg-white border border-gray-300">
						<caption class="font-bold uppercase">Table Caption</caption>
						<thead class="bg-gray-50 uppercase">
							<tr>
								<th class="p-2">Header 1</th>
								<th class="p-2">Header 2</th>
								<th class="p-2">Header 3</th>
								<th class="p-2">Header 4</th>
								<th class="p-2">Header 5</th>
								<th class="p-2">Header 6</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="p-2">Header 1</td>
								<td class="p-2">Header 2</td>
								<td class="p-2">Header 3</td>
								<td class="p-2">Header 4</td>
								<td class="p-2">Header 5</td>
								<td class="p-2">Header 6</td>
							</tr>
							<tr class="bg-gray-50">
								<td class="p-2">Header 1</td>
								<td class="p-2">Header 2</td>
								<td class="p-2">Header 3</td>
								<td class="p-2">Header 4</td>
								<td class="p-2">Header 5</td>
								<td class="p-2">Header 6</td>
							</tr>
							<tr>
								<td class="p-2">Header 1</td>
								<td class="p-2">Header 2</td>
								<td class="p-2">Header 3</td>
								<td class="p-2">Header 4</td>
								<td class="p-2">Header 5</td>
								<td class="p-2">Header 6</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<td class="p-2">Header 1</td>
								<td class="p-2">Header 2</td>
								<td class="p-2">Header 3</td>
								<td class="p-2">Header 4</td>
								<td class="p-2">Header 5</td>
								<td class="p-2">Header 6</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>

			<h2 class="text-2xl font-bold">Action Buttons</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<div class="flex items-center gap-2">
					<button type="button" class="flex items-center gap-2 text-blue-600 bg-blue-50 border border-blue-50 rounded px-2 py-1">
						@include('components.icons.eye')
					</button>
					<button type="button" class="flex items-center gap-2 text-green-600 bg-green-50 border border-green-50 rounded px-2 py-1">
						@include('components.icons.pencil')
					</button>
					<button type="button" class="flex items-center gap-2 text-red-600 bg-red-50 border border-red-50 rounded px-2 py-1">
						@include('components.icons.trash-2')
					</button>
				</div>
			</div>

			<h2 class="text-2xl font-bold">Filters</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<form class="flex flex-wrap items-end gap-2">
					<div class="space-y-1">
						<label for="example">Example Label<span class="text-red-500">*</span></label>
						<input type="text" id="example" name="example" class="w-full block bg-gs-black-50 rounded p-2">
					</div>

					<div class="space-y-1">
						<label for="example">Example Label<span class="text-red-500">*</span></label>
						<input type="text" id="example" name="example" class="w-full block bg-gs-black-50 rounded p-2">
					</div>

					<div class="space-y-1">
						<label for="example">Example Label<span class="text-red-500">*</span></label>
						<input type="text" id="example" name="example" class="w-full block bg-gs-black-50 rounded p-2">
					</div>

					<div class="flex items-center gap-2">
						<button type="button" class="flex items-center gap-2 text-white bg-gs-purple-600 border border-gs-purple-600 rounded px-3 py-2">Button</button>
						<button type="button" class="flex items-center gap-2 text-gs-purple-600 bg-white border border-gs-purple-600 rounded px-3 py-2">Button</button>
					</div>
				</form>
			</div>

			<h2 class="text-2xl font-bold">Form Layout</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<form class="grid grid-cols-2 gap-4">
					<div class="space-y-1">
						<label for="name">Name<span class="text-red-500">*</span></label>
						<input type="text" id="name" name="name" class="w-full block bg-gs-black-50 rounded p-2">
					</div>

					<div class="space-y-1">
						<label for="email">Email<span class="text-red-500">*</span></label>
						<input type="text" id="email" name="email" class="w-full block bg-gs-black-50 rounded p-2">
					</div>

					<div class="space-y-1 col-span-2">
						<label for="subject">Subject<span class="text-red-500">*</span></label>
						<input type="text" id="subject" name="subject" class="w-full block bg-gs-black-50 rounded p-2">
					</div>

					<div class="space-y-1 col-span-2">
						<label for="message">Message<span class="text-red-500">*</span></label>
						<textarea id="message" name="message" class="w-full block bg-gs-black-50 rounded p-2" rows="4"></textarea>
					</div>

					<div class="flex items-center gap-2 col-span-2">
						<button type="button" class="flex items-center gap-2 text-white bg-gs-purple-600 border border-gs-purple-600 rounded px-3 py-2">Button</button>
						<button type="button" class="flex items-center gap-2 text-gs-purple-600 bg-white border border-gs-purple-600 rounded px-3 py-2">Button</button>
					</div>
				</form>
			</div>
		</section>
	</main>
</body>
</html>
