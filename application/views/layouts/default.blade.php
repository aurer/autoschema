<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>AutoSchema:@yield('pagetitle')</title>
	<meta name="viewport" content="width=device-width">
	{{ HTML::style('laravel/css/style.css') }}
	{{ HTML::style('css/main.css') }}
</head>
<body>
	<div class="wrapper">
		<header>
			<h1>Laravel AutoSchema</h1>
			<h2>A Database Management Library</h2>
		</header>
		<div role="main" class="main">
			<h2>@yield('pagetitle')</h2>
			@yield('main')
		</div>
	</div>
</body>
</html>
