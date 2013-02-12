<?php

class Pages
{

	private static $uri_parts = array();
	private static $uri_length;

	public static function render($uri)
	{
		static::read_uri($uri);
		// Special case for homepage at /
		if( count(self::$uri_parts) === 0){
			$page = DB::table('pages')->where_slug("/")->or_where('slug', '=', 'home')->first();
			if( count($page) < 1 ){
				return Response::error('404');
			}
		}

		// Loop pages
		foreach (self::$uri_parts as $key => $value) {
			// First page so just look up slug
			if($key === 0){
				$page = DB::table('pages')->where_slug($value)->where_parent(0)->first();
			}
			// For sub pages check parent
			else {
				$page = DB::table('pages')->where_slug($value)->where_parent($page->id)->first();
			}
			// No page found so return 404
			if( count($page) < 1 ){
				return Response::error('404');
			}
		}
		return View::make( self::find_view() )->with('page', $page);
	}

	public static function read_uri($uri)
	{
		self::$uri_parts = array_filter(explode("/", $uri));
		self::$uri_length = count(self::$uri_parts);
	}

	private static function find_view()
	{
		
		if( View::exists('default.index') ){
			return 'default.index';
		} else {
			return 'pages::default.index';
		}
	}

}