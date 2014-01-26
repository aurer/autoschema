<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>AutoSchema:@yield('pagetitle')</title>
	<meta name="viewport" content="width=device-width">
	{{ HTML::style('packages/aurer/autoschema/css/main.css') }}
</head>
<body>
	<div class="page">
		<header>
			<div class="col">
				<div class="cell">
					<h1>AutoSchema</h1>
					<h2>A Database Management Library for the Laravel framework</h2>
				</div>
			</div>
		</header>
		<div role="main" class="main">
			<div class="col">
				@yield('main')
			</div>
		</div>
		<footer>
			<div class="col">
			</div>
		</footer>
	</div>
</body>
</html>
