<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>{{$page->pagetitle}}</title>
</head>
<body>
	<nav>@include('pages::partials.nav')</nav>
	{{$page->content}}
</body>
</html>