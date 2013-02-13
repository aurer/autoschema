@layout('layouts/default')

@section('pagetitle') Form @endsection

@section('main')
<div class="cell">
	<h3><a href="/">&larr; Back</a></h3>

	<ol>
		@foreach( $tables as $table )
			<li><a href="/{{ URI::current() }}/{{ $table }}">{{ $table }}</a></li>
		@endforeach
	</ol>
</div>
@endsection