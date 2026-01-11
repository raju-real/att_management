@if(Session::has('message'))
    <div class="alert alert-{{ Session::get('type') }}" role="alert">
        <span class="font-weight-500">{{ Session::get('message') }}</span>
    </div>
@endif
