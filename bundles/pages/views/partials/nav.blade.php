<?php $level1 = DB::table('pages')->where_active(true)->where_visible(true)->where_depth(1)->get(); ?>
<ul>
	@foreach($level1 as $page1)
		<li>
			<a href="/{{$page1->slug}}">{{$page1->menutitle}}</a>
			@if(URI::segment(1) == $page1->slug)
				<?php $level2 = DB::table('pages')->where_active(true)->where_visible(true)->where_depth(2)->where_parent($page1->id)->get(); ?>
				@if(count($level2) > 0)
					<ul>
					@foreach($level2 as $page2)
						<li><a href="/{{$page1->slug}}/{{$page2->slug}}">{{$page2->menutitle}}</a></li>
					@endforeach
					</ul>
				@endif
			@endif
		</li>
	@endforeach
</ul>