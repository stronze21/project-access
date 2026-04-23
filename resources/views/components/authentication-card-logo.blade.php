@if (isset($settings['app_logo']))
    <a href="/">
        <img src="{{ Storage::url($settings['app_logo']) }}" alt="Logo" class="w-auto mr-2 h-36">
    </a>
@endif
