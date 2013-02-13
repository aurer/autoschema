@layout('layouts/default')

@section('pagetitle') Form {{ $table }} @endsection

@section('main')
<div class="cell">
	<h2>{{ $table }} <a class="right" href="/admin/{{ $table }}/add">New</a></h2>
	<h3><a href="/admin">&larr; Back</a></h3>

	@if( count($items) < 1 )
		<p>There are no {{ $table }} to display.</p>
	@else
		<table class="display database">
			<thead>
				<tr>
					@foreach( $title_columns as $column)
						<td>{{ $column }}</td>
					@endforeach
				</tr>
			</thead>
			<tbody>
				@foreach( $items as $item )
				<tr>
					@foreach( $title_columns as $key => $column)
						<td>
							@if( $key == 0)
								<a href="/{{ URI::current() }}/{{ $item->id }}/edit">{{ $item->$column }}</a> 
							@else
								{{ $item->$column }}
							@endif
						</td>
					@endforeach
				</tr>
				@endforeach
			</tbody>
		</table>
	@endif
</div>
@endsection