{{-- Logo editor dialog (same as WWD Business): move, zoom, rotate, trim, background and live preview before upload.
     Driven by public/assets/js/wwd-logo-editor.js. Keep it outside the form so its controls are never posted.
     Pair it with <x-logo-field>, which holds the file input the editor fills. --}}
@props(['name' => 'Business name', 'place' => null])
<div class="modal fade logo-editor" id="logoEditorModal" tabindex="-1" aria-labelledby="logoEditorTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="logoEditorTitle">Adjust the logo</h2>
                    <div class="le-src" data-le="src"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="le-body">
                    <div class="le-main">
                        <canvas class="le-stage" data-le="stage" tabindex="0" role="img"
                            aria-label="Logo position. Drag or use the arrow keys to move it; plus and minus zoom; R rotates."></canvas>
                        <p class="le-hint">Drag to move · scroll or pinch to zoom · the bright square is what we save</p>
                        <div class="le-zoom">
                            <button type="button" class="btn btn-outline-secondary btn-icon" data-le="zoom-out" aria-label="Zoom out"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                            <input type="range" class="form-range" data-le="zoom" min="0" max="1000" step="1" aria-label="Zoom">
                            <button type="button" class="btn btn-outline-secondary btn-icon" data-le="zoom-in" aria-label="Zoom in"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                        </div>
                        <div class="le-tools">
                            <div class="le-seg" role="group" aria-label="Size">
                                <button type="button" data-mode="fit" aria-pressed="false">Fit whole logo</button>
                                <button type="button" data-mode="fill" aria-pressed="false">Fill square</button>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-le="rotate"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Rotate</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-le="center">Center</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-le="reset">Reset</button>
                        </div>
                    </div>

                    <div class="le-side">
                        <div class="le-block">
                            <h3>Background</h3>
                            <div class="le-chips" role="radiogroup" aria-label="Background">
                                <label class="le-chip"><input type="radio" name="le_bg" value="transparent"><span class="le-swatch is-checker"></span>Transparent</label>
                                <label class="le-chip"><input type="radio" name="le_bg" value="white"><span class="le-swatch"></span>White</label>
                                <label class="le-chip"><input type="radio" name="le_bg" value="custom"><input type="color" data-le="color" value="#ffffff" aria-label="Choose a background colour">Colour</label>
                            </div>
                        </div>
                        <div class="le-block" data-le="trim-block">
                            <label class="le-check">
                                <input type="checkbox" class="form-check-input" data-le="trim">
                                <span>Trim empty edges<small>We found empty space around the logo.</small></span>
                            </label>
                        </div>
                        <div class="le-block">
                            <h3>Sharpness</h3>
                            <div class="le-quality" data-le="quality"><span class="le-dot"></span><span><b></b><small></small></span></div>
                            <p class="le-tip" data-le="wide-tip" hidden>Wide logos look small in square spaces. An icon-only version stands out more in search results.</p>
                        </div>
                        <div class="le-block">
                            <h3>How it will look</h3>
                            <div class="le-previews">
                                <div class="le-pv"><canvas data-size="72"></canvas><div><span class="le-pv-label">Listing page</span><b>{{ $name }}</b></div></div>
                                <div class="le-pv"><canvas data-size="44"></canvas><div><span class="le-pv-label">Search results</span><b>{{ $name }}</b>@if($place)<small>{{ $place }}</small>@endif</div></div>
                                <div class="le-pv"><canvas data-size="32"></canvas><div><span class="le-pv-label">Owner's dashboard menu</span><b>{{ $name }}</b></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="le-out">Saved as a 500 × 500 px image</span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" data-le="apply">Use this logo</button>
            </div>
        </div>
    </div>
</div>
