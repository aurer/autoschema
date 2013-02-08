@layout('layouts/default')

@section('pagetitle') Form @endsection

@section('main')
	<h3><a href="/">&larr; Back</a></h3>

	<ol>
	@foreach( $tables as $table )
		<li><a href="/admin/{{ $table }}">{{ $table }}</a></li>
	@endforeach
	</ol>
	
@endsection