@if (isset($settings['app_logo']))
    <img src="{{ Storage::url($settings['app_logo']) }}" alt="Logo">
@endif
