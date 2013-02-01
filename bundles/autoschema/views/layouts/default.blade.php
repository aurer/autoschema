<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>AutoSchema:@yield('pagetitle')</title>
	<meta name="viewport" content="width=device-width">
	{{ HTML::style('/autoschema-theme/css/main.css') }}
</head>
<body>
	<div class="page">
		<header>
			<div class="col">
				<div class="cell">
					<h1>AutoSchema</h1>
					<h2>A Laravel Database Management Library</h2>
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
