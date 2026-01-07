@if(Session::has('message'))
    <div class="alert alert-danger" role="alert">
        <span class="font-weight-500">{{ Session::get('message') }}</span>
    </div>
@endif
