@props(['icon', 'title', 'subtitle', 'tone' => 'blue'])
<div class="page-header split"><div><span class="eyebrow">Resident portal</span><h1>{{ $title }}</h1><p>{{ $subtitle }}</p></div><span class="header-icon {{ $tone }} material-symbols-rounded filled">{{ $icon }}</span></div>
