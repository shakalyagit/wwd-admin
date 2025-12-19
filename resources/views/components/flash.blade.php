<div>
    @if (session('success'))
        <div class="alert alert-success border border-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close text-success" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning border border-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close text-warning" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info border border-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close text-info" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border border-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close text-danger" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>
