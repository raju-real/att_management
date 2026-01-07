@if(Session::has('message'))
    <div class="alert alert-{{ Session::get('type') }} alert-dismissible fade show" role="alert">
    <span class="font-weight-500">{{ Session::get("message") }}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
