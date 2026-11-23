@props(['eyebrow', 'title', 'description', 'icon'])

<section class="src-module-hero" aria-labelledby="src-module-title">
    <div>
        <span class="src-module-hero-eyebrow">{{ $eyebrow }}</span>
        <h1 id="src-module-title">{{ $title }}</h1>
        <p>{{ $description }}</p>
    </div>
    <div class="src-module-hero-icon" aria-hidden="true">
        <i class="{{ $icon }}"></i>
    </div>
</section>
