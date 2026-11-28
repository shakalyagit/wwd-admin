{{-- "Found on" (source site) card, shared by Add business and the Excel draft review --}}
@php
    $values = (array) ($values ?? []);
    $val = fn($field) => old($field, $values[$field] ?? null);
@endphp
<div class="card">
    <div class="card-header"><h6 class="mb-0">Found on <span class="text-600 fw-normal small">· admin reference only</span></h6></div>
    <div class="card-body">
        <label class="form-label" for="source_name">Source site</label>
        <input type="text" class="form-control" id="source_name" name="source_name" value="{{ $val('source_name') }}" maxlength="100"
            list="sourceList" placeholder="e.g. Yelp">
        <datalist id="sourceList">
            @foreach ($sources as $source)
            <option value="{{ $source }}">
            @endforeach
        </datalist>
        <label class="form-label mt-3" for="source_url">Page on that site</label>
        <input type="url" class="form-control @error('source_url') is-invalid @enderror" id="source_url" name="source_url" value="{{ $val('source_url') }}" maxlength="500" placeholder="https://…">
        @error('source_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text">Never shown on the public site. Used for the claim rate by source report.</div>
    </div>
</div>
