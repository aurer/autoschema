<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>Admin</title>
</head>
<body>
	
	@if( count($pages) )
		@foreach($pages as $page)
	 		<td>{{ $page->pagetitle }}</td>
		@endforeach
	@endif

	{{ Form::open() }}

		{{ FormField::field('pagetitle', 'Page title') }}

		{{ FormField::field('menutitle', 'Menu title') }}

		{{ FormField::field('slug', 'URL Slug') }}

		{{ FormField::field('content', 'Content', 'textarea') }}

		{{ FormField::field('visible', 'Visible', 'checkbox') }}

		{{ FormField::field('save', 'Save', 'button') }}

	{{ Form::close() }}
</body>
</html>