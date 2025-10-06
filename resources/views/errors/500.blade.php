
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">

             <h1 class="display-3 text-danger">500</h1>
    <h3 class="mb-3">Oops! Something went wrong</h3>
    <p class="text-muted">{{ $message ?? 'Unexpected error occurred.' }}</p>

   
        <div class="card text-start mt-4">
            <div class="card-header bg-warning">
                <strong>Debug Information (Local Only)</strong>
            </div>
            <div class="card-body">
                <p><b>File:</b> {{ $file ?? 'N/A' }}</p>
                <p><b>Line:</b> {{ $line ?? 'N/A' }}</p>
                <pre>{{ $exception ?? '' }}</pre>
            </div>
        </div>

        </div>
    </div>

